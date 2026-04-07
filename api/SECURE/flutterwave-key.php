<?php
// flutterwave-key.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Use environment variables for security
$publicKey = getenv('FLUTTERWAVE_PUBLIC_KEY') ?: 'FLWPUBK_TEST-xxxxxxxxxxxxxxxx';
$secretKey = getenv('FLUTTERWAVE_SECRET_KEY') ?: 'FLWSECK_TEST-xxxxxxxxxxxxxxxx';

// Only return the secret key in trusted backend calls (optional: you could restrict by IP)
echo json_encode([
    'publicKey' => $publicKey,
    'secretKey' => $secretKey
]);
