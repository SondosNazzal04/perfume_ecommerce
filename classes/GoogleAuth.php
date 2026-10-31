<?php

class GoogleAuth
{
    private Google\Client $client;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/google.php';

        $this->client = new Google\Client();

        $this->client->setClientId($config['client_id']);
        $this->client->setClientSecret($config['client_secret']);
        $this->client->setRedirectUri($config['redirect_uri']);

        $this->client->addScope('openid');
        $this->client->addScope('email');
        $this->client->addScope('profile');
    }

    public function getLoginUrl(): string
    {
        $state = bin2hex(random_bytes(32));

        $_SESSION['google_oauth_state'] = $state;

        $this->client->setState($state);

        return $this->client->createAuthUrl();
    }
    public function getGoogleUser(string $code): ?array
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            return null;
        }

        $this->client->setAccessToken($token);

        $payload = $this->client->verifyIdToken();

        if (!$payload || empty($payload['email_verified'])) {
            return null;
        }

        return [
            'google_id' => $payload['sub'],
            'name' => $payload['name'] ?? '',
            'email' => $payload['email']
        ];
    }
}
