<?php
// flutterwave-key.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Use environment variables for security
$publicKey = getenv('FLUTTERWAVE_PUBLIC_KEY') ?: 'FLWPUBK_TEST-02a9fcd2b494145c0ae3921c89e834d0-X';
$secretKey = getenv('FLUTTERWAVE_SECRET_KEY') ?: 'FLWSECK_TEST-367db2e5aab9bdeeab6d00b51642ea34-X';

// Only return the secret key in trusted backend calls (optional: you could restrict by IP)
echo json_encode([
    'publicKey' => $publicKey,
    'secretKey' => $secretKey
]);
