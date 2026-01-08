<?php
// SplynxApiClient.php

/**
 * Splynx API Client Class for interacting with the Splynx API.
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

    /**
     * Handles cURL response, closes connection, and logs errors.
     *
     * @param resource $ch cURL handle.
     * @param string|bool $response cURL response.
     * @param string $endpoint The API endpoint called.
     * @param string $method HTTP method used.
     * @return array|bool Decoded JSON response array on success, false on failure.
     */
    private function handleResponse($ch, $response, $endpoint, $method = 'GET')
    {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // This is a global used in CLI scripts to suppress output
        global $isSilent;
        $shouldEcho = !isset($isSilent) || !$isSilent;

        if ($response === false) {
            $errorMsg = "cURL Error for {$method} {$endpoint}: {$curlError}";
            error_log($errorMsg);
            if ($shouldEcho) { echo "ERROR: {$errorMsg}\n"; }
            return false;
        }

        $responseData = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return $responseData;
        }

        // Log API Errors
        $errorMsg = "API Request Error ({$method}): Endpoint: {$endpoint}, HTTP Code: {$httpCode}, cURL Error: {$curlError}. Response: " . print_r($responseData, true);
        error_log($errorMsg);
        if ($shouldEcho) { echo "ERROR: API Request failed with HTTP {$httpCode}. See error log.\n"; }

        // Return the error response array for caller to inspect (especially 422 validation errors)
        return $responseData;
    }

    /**
     * Performs a GET request to the Splynx API.
     *
     * @param string $endpoint The API endpoint (e.g., 'customers/customer-service/service-list').
     * @param array $params Query parameters.
     * @return array|bool Decoded JSON response array on success, false on failure.
     */
    public function get($endpoint, $params = [])
    {
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
        return $this->handleResponse($ch, $response, $endpoint, 'GET');
    }
    
    /**
     * Performs a POST request to the Splynx API.
     *
     * @param string $endpoint The API endpoint.
     * @param array $data Data to be sent in the request body.
     * @return array|bool Decoded JSON response array on success, false on failure.
     */
    public function post($endpoint, $data)
    {
        $url = $this->apiUrl . '/' . ltrim($endpoint, '/');

        $headers = [
            'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        return $this->handleResponse($ch, $response, $endpoint, 'POST');
    }
    
    /**
     * Performs a PUT request to the Splynx API.
     *
     * @param string $endpoint The API endpoint.
     * @param array $data Data to be sent in the request body.
     * @return array|bool Decoded JSON response array on success, false on failure.
     */
    public function put($endpoint, $data)
    {
        $url = $this->apiUrl . '/' . ltrim($endpoint, '/');

        $headers = [
            'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        
        // Treat 200, 202, 204 as success for PUT operations
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (in_array($httpCode, [200, 202, 204])) {
            curl_close($ch);
            return true; // Simple success indication for PUT
        }
        
        // Otherwise, rely on handleResponse for errors
        return $this->handleResponse($ch, $response, $endpoint, 'PUT');
    }
}

?>
