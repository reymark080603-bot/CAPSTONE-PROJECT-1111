<?php

/**
 * Knowly Standalone Load and Stress Test Runner
 * Uses php-curl to perform concurrent requests and measure performance.
 */

// Parse .env to get default APP_URL
$appUrl = 'http://localhost';
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed) || str_starts_with($trimmed, '#')) {
            continue;
        }
        $parts = explode('=', $trimmed, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1], " \t\n\r\0\x0B\"'");
            if ($key === 'APP_URL') {
                $appUrl = rtrim($val, '/');
                break;
            }
        }
    }
}

// Help message
if (in_array('--help', $argv) || in_array('-h', $argv)) {
    echo "Knowly Load Test Runner\n";
    echo "Usage: php tests/run_load_test.php [options]\n\n";
    echo "Options:\n";
    echo "  -u, --url <url>          Base URL to test (default: $appUrl)\n";
    echo "  -c, --concurrency <n>    Number of concurrent connections (default: 10)\n";
    echo "  -r, --requests <n>       Total number of requests to run (default: 100)\n";
    echo "  -p, --path <path>        Path to test, or preset: 'home', 'search', 'mix' (default: 'home')\n";
    echo "                           Presets:\n";
    echo "                             'home':   /\n";
    echo "                             'search': /api/books\n";
    echo "                             'mix':    randomly visits /, /login, /api/books\n";
    exit(0);
}

// Parse custom arguments
$options = [
    'url' => $appUrl,
    'concurrency' => 10,
    'requests' => 100,
    'path' => 'home'
];

for ($i = 1; $i < count($argv); $i++) {
    switch ($argv[$i]) {
        case '-u':
        case '--url':
            $options['url'] = rtrim($argv[++$i], '/');
            break;
        case '-c':
        case '--concurrency':
            $options['concurrency'] = (int)$argv[++$i];
            break;
        case '-r':
        case '--requests':
            $options['requests'] = (int)$argv[++$i];
            break;
        case '-p':
        case '--path':
            $options['path'] = $argv[++$i];
            break;
    }
}

$baseUrl = $options['url'];
$concurrency = $options['concurrency'];
$totalRequests = $options['requests'];
$pathOption = $options['path'];

// Define paths based on option
$getUrls = function() use ($baseUrl, $pathOption) {
    switch ($pathOption) {
        case 'home':
            return [$baseUrl . '/'];
        case 'search':
            return [$baseUrl . '/api/books'];
        case 'mix':
            return [
                $baseUrl . '/',
                $baseUrl . '/login',
                $baseUrl . '/api/books'
            ];
        default:
            return [$baseUrl . '/' . ltrim($pathOption, '/')];
    }
};

$urls = $getUrls();

echo "============================================================\n";
echo "         KNOWLY SYSTEM LOAD TEST RUNNER\n";
echo "============================================================\n";
echo "Target URL:       $baseUrl\n";
echo "Path Preset:      $pathOption (running queries against: " . implode(', ', array_map(function($u) use ($baseUrl){ return substr($u, strlen($baseUrl)); }, $urls)) . ")\n";
echo "Concurrency:      $concurrency concurrent users\n";
echo "Total Requests:   $totalRequests requests\n";
echo "============================================================\n\n";

if (!function_exists('curl_multi_init')) {
    echo "Error: PHP cURL extension is not enabled. Please enable extension=curl in your php.ini.\n";
    exit(1);
}

$mh = curl_multi_init();
$handles = [];
$stats = [];
$completed = 0;
$started = 0;
$startTime = microtime(true);

// Function to add a single curl request
$addRequest = function() use ($mh, &$handles, &$started, $urls, $totalRequests, $completed) {
    if ($started >= $totalRequests) {
        return false;
    }

    $url = $urls[array_rand($urls)];
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'KnowlyLoadTester/1.0',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        // Disable SSL checks for local testing ease
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $id = (int)$ch;
    curl_multi_add_handle($mh, $ch);
    
    $handles[$id] = [
        'handle' => $ch,
        'url' => $url,
        'start_time' => microtime(true)
    ];

    $started++;
    return true;
};

// Seed initial concurrency batch
for ($i = 0; $i < min($concurrency, $totalRequests); $i++) {
    $addRequest();
}

$active = null;

// Progress bar helper
$drawProgressBar = function($done, $total) {
    $width = 30;
    $percent = round(($done / $total) * 100);
    $barCount = round(($done / $total) * $width);
    $bar = str_repeat('=', $barCount) . str_repeat(' ', $width - $barCount);
    printf("\rProgress: [%s] %d%% (%d/%d)", $bar, $percent, $done, $total);
};

// Loop execution
do {
    $status = curl_multi_exec($mh, $active);
    
    if ($active) {
        curl_multi_select($mh, 0.1);
    }
    
    // Process any completed requests
    while ($info = curl_multi_info_read($mh)) {
        $ch = $info['handle'];
        $id = (int)$ch;
        
        if (isset($handles[$id])) {
            $requestData = $handles[$id];
            $latency = microtime(true) - $requestData['start_time'];
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            $stats[] = [
                'url' => $requestData['url'],
                'latency' => $latency * 1000, // in ms
                'code' => $httpCode
            ];
            
            $completed++;
            $drawProgressBar($completed, $totalRequests);
            
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            unset($handles[$id]);
            
            // Add next request to maintain queue size
            $addRequest();
        }
    }
} while ($active || count($handles) > 0);

$totalTime = microtime(true) - $startTime;
curl_multi_close($mh);

echo "\n\n";
echo "============================================================\n";
echo "                     TEST RESULTS\n";
echo "============================================================\n";

if (empty($stats)) {
    echo "No requests completed successfully.\n";
    exit(1);
}

// Calculate analytics
$totalLatency = 0;
$minLatency = 9999999;
$maxLatency = 0;
$latencies = [];
$statusCodes = [];
$failedCount = 0;

foreach ($stats as $s) {
    $latencies[] = $s['latency'];
    $totalLatency += $s['latency'];
    if ($s['latency'] < $minLatency) $minLatency = $s['latency'];
    if ($s['latency'] > $maxLatency) $maxLatency = $s['latency'];
    
    $code = $s['code'];
    if (!isset($statusCodes[$code])) {
        $statusCodes[$code] = 0;
    }
    $statusCodes[$code]++;
    
    // Any status other than 2xx and 3xx is a failure
    if ($code < 200 || $code >= 400) {
        $failedCount++;
    }
}

sort($latencies);
$count = count($latencies);
$avgLatency = $totalLatency / $count;
$medianLatency = $latencies[(int)($count * 0.50)];
$p90Latency = $latencies[(int)($count * 0.90)];
$p95Latency = $latencies[(int)($count * 0.95)];
$requestsPerSec = $count / $totalTime;

printf("Total Time Taken:     %.3f seconds\n", $totalTime);
printf("Completed Requests:   %d\n", $count);
printf("Failed Requests:      %d (%.2f%%)\n", $failedCount, ($failedCount / $count) * 100);
printf("Throughput (RPS):     %.2f req/sec\n", $requestsPerSec);
echo "------------------------------------------------------------\n";
echo "Latency Statistics:\n";
printf("  Average Latency:    %.1f ms\n", $avgLatency);
printf("  Min Latency:        %.1f ms\n", $minLatency);
printf("  Median (P50):       %.1f ms\n", $medianLatency);
printf("  90th Percentile:    %.1f ms\n", $p90Latency);
printf("  95th Percentile:    %.1f ms\n", $p95Latency);
printf("  Max Latency:        %.1f ms\n", $maxLatency);
echo "------------------------------------------------------------\n";
echo "HTTP Status Code Distribution:\n";
foreach ($statusCodes as $code => $c) {
    $statusText = 'Unknown';
    if ($code === 200) $statusText = 'OK';
    elseif ($code === 302) $statusText = 'Found/Redirect';
    elseif ($code === 404) $statusText = 'Not Found';
    elseif ($code === 500) $statusText = 'Internal Server Error';
    elseif ($code === 0) $statusText = 'Connection Failed';
    
    printf("  HTTP %d (%s): %d (%.1f%%)\n", $code, $statusText, $c, ($c / $count) * 100);
}
echo "============================================================\n";
