<?php

/**
 * Splynx IP Lookup API Endpoint
 *
 * This script serves as a fast, low-latency API endpoint. It reads the pre-generated
 * data from the shared memory file and returns the service details for a given IP.
 * It is designed to be extremely fast as it avoids Splynx API calls entirely.
 */

require_once 'config.php';

header('Content-Type: application/json');

// --- 1. Validate Input ---
$targetIp = $_GET['ipv4'] ?? null;

if (empty($targetIp) || !filter_var($targetIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing IPv4 parameter.']);
    exit;
}

// --- 2. Load Data from Shared Memory ---
if (!file_exists(DATA_STORE_PATH)) {
    http_response_code(503);
    echo json_encode(['error' => 'Service data not available. Exporter job may not have run yet.']);
    exit;
}

$jsonData = file_get_contents(DATA_STORE_PATH);
$servicesIndex = json_decode($jsonData, true);

if ($servicesIndex === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to parse service data file.']);
    exit;
}

// --- 3. Lookup and Respond ---
if (isset($servicesIndex[$targetIp])) {
    http_response_code(200);
    echo json_encode($servicesIndex[$targetIp]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'No active service found for this IPv4 address.']);
}

?>
