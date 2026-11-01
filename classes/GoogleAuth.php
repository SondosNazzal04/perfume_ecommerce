<?php

class GoogleAuth
{
    private array $config;

    public function __construct()
    {
        $configPath = __DIR__ . '/../config/google.php';
        if (!is_file($configPath)) {
            throw new RuntimeException('Google OAuth credentials are not configured.');
        }
        $config = require $configPath;
        foreach (['client_id', 'client_secret', 'redirect_uri'] as $key) {
            if (!isset($config[$key]) || !is_string($config[$key]) || trim($config[$key]) === '') {
                throw new RuntimeException('Google OAuth configuration is incomplete.');
            }
        }
        $this->config = $config;
    }

    public function getLoginUrl(): string
    {
        $state = bin2hex(random_bytes(32));

        $_SESSION['google_oauth_state'] = $state;

        $query = http_build_query([
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'include_granted_scopes' => 'true'
        ], '', '&', PHP_QUERY_RFC3986);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $query;
    }
    public function getGoogleUser(string $code): ?array
    {
        $token = $this->request(
            'POST',
            'https://oauth2.googleapis.com/token',
            [
                'code' => $code,
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'redirect_uri' => $this->config['redirect_uri'],
                'grant_type' => 'authorization_code'
            ]
        );

        if (!is_array($token) || empty($token['access_token'])) {
            return null;
        }

        $profile = $this->request(
            'GET',
            'https://openidconnect.googleapis.com/v1/userinfo',
            [],
            (string) $token['access_token']
        );

        if (
            !is_array($profile) ||
            empty($profile['sub']) ||
            empty($profile['email']) ||
            !filter_var($profile['email'], FILTER_VALIDATE_EMAIL) ||
            !in_array($profile['email_verified'] ?? false, [true, 'true', 1, '1'], true)
        ) {
            return null;
        }

        return [
            'google_id' => (string) $profile['sub'],
            'name' => (string) ($profile['name'] ?? ''),
            'email' => (string) $profile['email']
        ];
    }

    private function request(string $method, string $url, array $fields = [], ?string $accessToken = null): ?array
    {
        $headers = ['Accept: application/json'];
        $options = [
            'method' => $method,
            'timeout' => 15,
            'ignore_errors' => true
        ];

        if ($accessToken !== null) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $options['content'] = http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
        } elseif ($fields !== []) {
            $url .= '?' . http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
        }

        $options['header'] = implode("\r\n", $headers);
        $context = stream_context_create(['http' => $options]);
        $response = @file_get_contents($url, false, $context);
        if (!is_string($response)) {
            return null;
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }
}
