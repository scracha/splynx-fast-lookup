<?php
// --- Splynx API Configuration ---

// Replace with your Splynx API base URL
$splynxBaseUrl = 'https://YOUR_SPLYNX_URL/api/2.0';

// Replace with your Splynx API Key
$apiKey = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

// Replace with your Splynx API Secret
$apiSecret = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

// --- Daemon/Service Settings ---
// The hour (0-23) when the cron job should run. Default is 1 AM.
// If you set this via crontab, you can ignore this variable.
const UPDATE_TIME_HOUR = 1;

// Path to the shared memory file (fast, in-memory access)
const DATA_STORE_PATH = '/dev/shm/splynx_active_services.json';

?>
