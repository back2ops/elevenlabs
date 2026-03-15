<?php

namespace Back2ops\ElevenLabs\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Back2ops\ElevenLabs\Exceptions\ElevenLabsApiException;

/**
 * Thin wrapper around Guzzle that handles authentication, base URL,
 * and maps HTTP errors into our own exception type.
 */
class ElevenLabsClient
{
    private Client $client;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://api.elevenlabs.io/v1',
        private readonly int    $timeout = 60,
    ) {
        $this->client = new Client([
            'base_uri' => rtrim($this->baseUrl, '/') . '/',
            'timeout'  => $this->timeout,
            'headers'  => [
                'xi-api-key'   => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
        ]);
    }

    /**
     * POST JSON, expecting a JSON response body.
     */
    public function postJson(string $endpoint, array $body = [], array $query = []): array
    {
        try {
            $response = $this->client->post($endpoint, [
                'json'  => $body,
                'query' => $query,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
    }

    /**
     * POST JSON, expecting raw binary audio in the response body.
     * Returns the raw bytes as a string.
     */
    public function postJsonForAudio(string $endpoint, array $body = [], array $query = []): string
    {
        try {
            $response = $this->client->post($endpoint, [
                'json'    => $body,
                'query'   => $query,
                'headers' => [
                    'xi-api-key'   => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'audio/*',          // Accept any audio format back
                ],
            ]);

            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
    }

    /**
     * POST multipart/form-data (used for uploading audio files for STT).
     */
    public function postMultipart(string $endpoint, array $multipart, array $query = []): array
    {
        try {
            $response = $this->client->post($endpoint, [
                'multipart' => $multipart,
                'query'     => $query,
                'headers'   => [
                    'xi-api-key' => $this->apiKey,
                    // Note: Do NOT set Content-Type here — Guzzle sets it with the boundary automatically
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
    }

    /**
     * GET, expecting JSON.
     */
    public function getJson(string $endpoint, array $query = []): array
    {
        try {
            $response = $this->client->get($endpoint, [
                'query' => $query,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
    }

    private function wrapException(RequestException $e): ElevenLabsApiException
    {
        $statusCode = $e->getResponse()?->getStatusCode() ?? 0;
        $body       = $e->getResponse()?->getBody()->getContents() ?? '';
        $decoded    = json_decode($body, true);
        $message    = $decoded['detail']['message'] ?? $decoded['detail'] ?? $e->getMessage();

        return new ElevenLabsApiException(
            message: "ElevenLabs API error [{$statusCode}]: {$message}",
            code: $statusCode,
            previous: $e,
        );
    }
}
