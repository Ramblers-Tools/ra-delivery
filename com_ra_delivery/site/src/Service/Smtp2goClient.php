<?php

namespace Ramblers\Component\Ra_delivery\Site\Service;

defined('_JEXEC') or die;

class Smtp2goClient
{
    private string $apiKey;
    private string $baseUrl;
    private int $connectTimeout;
    private int $requestTimeout;
    private Smtp2goTransportInterface $transport;

    public function __construct(
        string $baseUrl,
        string $apiKey,
        ?Smtp2goTransportInterface $transport = null,
        int $connectTimeout = 30,
        int $requestTimeout = 300
    ) {
        $baseUrl = rtrim(trim($baseUrl), '/');
        $apiKey = trim($apiKey);

        if ($baseUrl === '' || stripos($baseUrl, 'https://') !== 0) {
            throw new Smtp2goException('The SMTP2GO API endpoint must use HTTPS');
        }

        if ($apiKey === '') {
            throw new Smtp2goException('The SMTP2GO API key is empty');
        }

        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
        $this->transport = $transport ?? new Smtp2goCurlTransport();
        $this->connectTimeout = max(1, $connectTimeout);
        $this->requestTimeout = max($this->connectTimeout, $requestTimeout);
    }

    public function post(string $path, array $payload): array
    {
        if ($path === '' || $path[0] !== '/' || str_contains($path, '://')) {
            throw new Smtp2goException('Invalid SMTP2GO API path');
        }

        try {
            $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\JsonException $exception) {
            throw new Smtp2goException('Unable to encode the SMTP2GO request');
        }

        try {
            $response = $this->transport->post(
                $this->baseUrl . $path,
                $body,
                [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Smtp2go-Api-Key' => $this->apiKey,
                ],
                $this->connectTimeout,
                $this->requestTimeout
            );
        } catch (\Throwable $exception) {
            throw new Smtp2goException('The SMTP2GO request could not be completed');
        }

        $status = (int) ($response['status'] ?? 0);
        $responseBody = (string) ($response['body'] ?? '');
        $transportError = trim((string) ($response['error'] ?? ''));
        $decoded = json_decode($responseBody, true);
        $requestId = is_array($decoded) ? trim((string) ($decoded['request_id'] ?? '')) : '';
        $providerCode = is_array($decoded) ? trim((string) ($decoded['data']['error_code'] ?? '')) : '';
        $providerMessage = is_array($decoded) ? trim((string) ($decoded['data']['error'] ?? '')) : '';

        if ($status < 200 || $status >= 300) {
            $message = 'SMTP2GO returned HTTP ' . $status;

            if ($providerCode !== '') {
                $message .= ' (' . $providerCode . ')';
            }

            if ($providerMessage !== '') {
                $message .= ': ' . $providerMessage;
            } elseif ($transportError !== '') {
                $message .= ': ' . $transportError;
            }

            if ($requestId !== '') {
                $message .= ' [request ' . $requestId . ']';
            }

            throw new Smtp2goException($message, $status, $providerCode, $requestId);
        }

        if (!is_array($decoded)) {
            throw new Smtp2goException('SMTP2GO returned an invalid JSON response', $status);
        }

        if ($providerMessage !== '') {
            throw new Smtp2goException(
                'SMTP2GO returned an error: ' . $providerMessage
                    . ($requestId !== '' ? ' [request ' . $requestId . ']' : ''),
                $status,
                $providerCode,
                $requestId
            );
        }

        return $decoded;
    }
}
