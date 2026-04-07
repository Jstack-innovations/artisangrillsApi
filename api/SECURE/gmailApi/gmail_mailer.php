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

    // Load existing token
    if (file_exists($tokenPath)) {
        $client->setAccessToken(json_decode(file_get_contents($tokenPath), true));
    }

    // ✅ Auto-refresh access token if expired
    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $client->setAccessToken($newToken);
            // Save refreshed token
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        } else {
            // In production, we should never hit this
            throw new Exception("Refresh token missing, reauthorize app manually.");
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
