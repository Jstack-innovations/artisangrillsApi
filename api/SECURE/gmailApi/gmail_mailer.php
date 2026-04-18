<?php
require_once __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Gmail;

function sendEmail($to, $subject, $body) {

    $client = new Client();
    $client->setAuthConfig(__DIR__ . '/credentials.json');
    $client->addScope(Gmail::GMAIL_SEND);
    $client->setAccessType('offline'); // important for refresh token

$tokenPath = __DIR__ . '/token.json';

if (file_exists($tokenPath)) {

    $token = json_decode(file_get_contents($tokenPath), true);

    // ✅ strict validation (VERY IMPORTANT)
    if (!is_array($token) || empty($token['access_token'])) {
        throw new Exception("Invalid or corrupted token.json");
    }

    $client->setAccessToken($token);
}

// ✅ refresh safely only if token exists
if ($client->getAccessToken() && $client->isAccessTokenExpired()) {

    if ($client->getRefreshToken()) {

        $newToken = $client->fetchAccessTokenWithRefreshToken(
            $client->getRefreshToken()
        );

        if (!isset($newToken['access_token'])) {
            throw new Exception("Refresh failed: " . json_encode($newToken));
        }

        $client->setAccessToken($newToken);

        file_put_contents($tokenPath, json_encode($client->getAccessToken()));

    } else {
        throw new Exception("Refresh token missing — re-auth required");
    }
}

    $service = new Gmail($client);

    $rawMessage = "To: $to\r\n";
    $rawMessage .= "Subject: $subject\r\n";
    $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
    $rawMessage .= $body;

    $message = new Gmail\Message();
    $message->setRaw(rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '='));

    return $service->users_messages->send("me", $message);
}
