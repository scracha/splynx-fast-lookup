<?php

/**
 * Splynx Background Data Exporter (CLI Script)
 *
 * This script runs daily via cron/timer, fetches all active services from Splynx,
 * and compiles them into a single JSON file indexed by IPv4 address for fast lookup.
 */

require_once 'config.php';

// Silence all output during background operation
$isSilent = true; 

/**
 * Splynx API Client Class (Retained from previous working version)
 */
class SplynxApiClient
{
    private $apiUrl;
    private $apiKey;
    private $apiSecret;

    public function __construct($apiUrl, $apiKey, $apiSecret)
    {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public function get($endpoint, $params = [])
    {
        global $isSilent;
        
        $url = $this->apiUrl . '/' . ltrim($endpoint, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $headers = [
            'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
            'Accept: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        } else {
            if (!$isSilent) {
                error_log("API Request Error: Endpoint: {$endpoint}, HTTP Code: {$httpCode}, cURL Error: {$curlError}");
            }
            return null;
        }
    }
}

// --- Main Execution Logic ---

$splynxApi = new SplynxApiClient($splynxBaseUrl, $apiKey, $apiSecret);
$activeServicesData = []; // Array to store IP-indexed data

// Define search parameters for active customers
$customerSearchParams = [
    'main_attributes' => [
        'status' => 'active'
    ]
];

// 1. Retrieve ONLY ACTIVE customers
$customers = $splynxApi->get('admin/customers/customer', $customerSearchParams);

if ($customers) {
    foreach ($customers as $customer) {
        if (isset($customer['id'])) {
            // 2. Retrieve internet services for the customer
            $services = $splynxApi->get('admin/customers/customer/' . $customer['id'] . '/internet-services');

            if (is_array($services) && !empty($services)) {
                foreach ($services as $service) {
                    
                    // 3. Filter for active services with an IPv4 address
                    if (isset($service['id']) && isset($service['status']) && $service['status'] === 'active' && !empty($service['ipv4'])) {
                        
                        // --- Extract Geo-Location Details ---
                        $serviceAddress = $service['geo']['address'] ?? 'N/A';
                        $serviceLatitude = 'N/A';
                        $serviceLongitude = 'N/A';
                        
                        // Parse marker string (e.g., "51.5074, -0.1278")
                        if (isset($service['geo']['marker']) && is_string($service['geo']['marker'])) {
                            $coords = array_map('trim', explode(',', $service['geo']['marker']));
                            if (count($coords) === 2) {
                                $serviceLatitude = $coords[0];
                                $serviceLongitude = $coords[1];
                            }
                        }
                        
                        // 4. Store data, using IPv4 as the primary key for O(1) lookup speed
                        $ipv4 = $service['ipv4'];
                        
                        // Clean data structure for the API response
                        $activeServicesData[$ipv4] = [
                            'customer_name'   => $customer['name'] ?? 'N/A',
                            'customer_status' => $customer['status'] ?? '',
                            'service_status'  => $service['status'] ?? '',
                            'service_id'      => $service['id'] ?? '',
                            'service_ipv4'    => $ipv4,
                            'service_address' => $serviceAddress, 
                            'service_latitude'=> $serviceLatitude,  
                            'service_longitude'=> $serviceLongitude, 
                        ];
                    }
                }
            } 
        } 
    }
} else {
    error_log("CRON ERROR: Failed to retrieve active customer data from Splynx.");
}

// 5. Save the compiled IP data as JSON to shared memory
if (!empty($activeServicesData)) {
    $json = json_encode($activeServicesData, JSON_PRETTY_PRINT);
    if (file_put_contents(DATA_STORE_PATH, $json) === false) {
        error_log("CRON ERROR: Could not write data to " . DATA_STORE_PATH);
    } else {
        error_log("CRON SUCCESS: Splynx data updated at " . DATA_STORE_PATH . " with " . count($activeServicesData) . " services.");
    }
} else {
    error_log("CRON WARNING: Splynx returned zero active services with IPv4. Data store was not updated.");
}

?>
