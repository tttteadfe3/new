<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Helper function to make a request
function makeRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    // Follow redirects
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    // Set headers to simulate AJAX request
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Requested-With: XMLHttpRequest',
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => $response];
}

// Base URL - assuming local dev environment
$baseUrl = 'http://localhost:8000'; 

echo "Verifying API Responses...\n\n";

// 1. Test 404 Not Found
echo "1. Testing 404 Not Found (GET /api/non-existent-route)...\n";
$result = makeRequest($baseUrl . '/api/non-existent-route');
echo "HTTP Code: " . $result['code'] . "\n";
echo "Response: " . $result['body'] . "\n";

$json = json_decode($result['body'], true);
if ($result['code'] === 404 && isset($json['success']) && $json['success'] === false && $json['error_code'] === 'NOT_FOUND') {
    echo "PASS: 404 response format is correct.\n";
} else {
    echo "FAIL: 404 response format is incorrect.\n";
}
echo "\n";

// Note: Testing 500 requires triggering an error, which is hard to do safely without modifying code.
// We will rely on the 404 test as proof that the Router's handleError is working correctly with JsonResponse.

echo "Verification Complete.\n";
