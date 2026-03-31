<?php
require_once __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Gmail;

function sendEmail($to, $subject, $body) {

    $client = new Client();
    $client->setAuthConfig(__DIR__ . '/credentials.json');
    $client->addScope(Gmail::GMAIL_SEND);

    $tokenPath = __DIR__ . '/token.json';

    if (file_exists($tokenPath)) {
        $client->setAccessToken(json_decode(file_get_contents($tokenPath), true));
    }

    // ✅ HANDLE TOKEN EXPIRY
    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        } else {
            throw new Exception("Gmail token expired. Re-authentication required.");
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