<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Validates Google ID tokens issued to the mobile app (Firebase web client ID).
 */
class GoogleIdTokenVerifier
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $googleWebClientId,
    ) {
    }

    public function verifyAndGetEmail(string $idToken): string
    {
        $idToken = trim($idToken);
        if ($idToken === '') {
            throw new \InvalidArgumentException('Google ID token is required.');
        }

        $response = $this->httpClient->request(
            'GET',
            'https://oauth2.googleapis.com/tokeninfo',
            ['query' => ['id_token' => $idToken]],
        );

        if ($response->getStatusCode() !== 200) {
            throw new \InvalidArgumentException('Invalid or expired Google token.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->toArray(false);

        $audience = (string) ($payload['aud'] ?? '');
        if ($audience !== $this->googleWebClientId) {
            throw new \InvalidArgumentException('Google token was not issued for this application.');
        }

        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        if ($email === '') {
            throw new \InvalidArgumentException('Google account email is required.');
        }

        return $email;
    }
}
