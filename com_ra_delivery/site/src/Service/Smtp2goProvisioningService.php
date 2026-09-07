<?php

namespace Ramblers\Component\Ra_delivery\Site\Service;

defined('_JEXEC') or die;

class Smtp2goProvisioningService
{
    private Smtp2goClient $client;

    public function __construct(Smtp2goClient $client)
    {
        $this->client = $client;
    }

    public function findSubaccount(string $name): ?array
    {
        $name = trim($name);

        if ($name === '') {
            throw new Smtp2goException('The SMTP2GO sub-account name is empty');
        }

        $response = $this->client->post('/v3/subaccounts/search', [
            'fuzzy_search' => false,
            'search_terms' => [$name],
            'states' => 'all',
            'page_size' => 100,
        ]);
        $subaccounts = $response['data']['subaccounts'] ?? null;

        if (!is_array($subaccounts)) {
            throw new Smtp2goException('SMTP2GO did not return a sub-account list');
        }

        $matches = array_values(array_filter(
            $subaccounts,
            static fn ($subaccount): bool => is_array($subaccount)
                && (string) ($subaccount['name'] ?? '') === $name
        ));

        if ($matches !== []) {
            return $matches[0];
        }

        if ($subaccounts !== []) {
            throw new Smtp2goException('SMTP2GO returned an ambiguous sub-account search result');
        }

        return null;
    }

    public function createSubaccount(string $name, int $limit): array
    {
        $name = trim($name);

        if ($name === '') {
            throw new Smtp2goException('The SMTP2GO sub-account name is empty');
        }

        if ($limit < 1) {
            throw new Smtp2goException('The SMTP2GO sub-account email limit is invalid');
        }

        $response = $this->client->post('/v3/subaccount/add', [
            'fullname' => $name,
            'limit' => $limit,
            'dedicated_ip' => false,
            'archiving' => false,
            'enforce_2fa' => false,
            'enable_sms' => false,
        ]);

        $subaccount = $response['data'] ?? null;

        if (!is_array($subaccount) || trim((string) ($subaccount['id'] ?? '')) === '') {
            throw new Smtp2goException('SMTP2GO did not return the new sub-account ID');
        }

        return $subaccount;
    }

    public function createApiKey(string $subaccountId, string $description): array
    {
        $subaccountId = trim($subaccountId);

        if ($subaccountId === '') {
            throw new Smtp2goException('The SMTP2GO sub-account ID is empty');
        }

        $response = $this->client->post('/v3/api_keys/add', [
            'description' => trim($description),
            'status' => 'allowed',
            'endpoints' => ['/email/send', '/activity/search'],
            'subaccount_id' => $subaccountId,
        ]);

        $data = $response['data'] ?? null;
        $key = is_array($data) && array_is_list($data) ? ($data[0] ?? null) : $data;
        $apiKey = is_array($key) ? trim((string) ($key['api_key'] ?? '')) : '';

        if ($apiKey === '' || str_contains($apiKey, '*')) {
            throw new Smtp2goException('SMTP2GO did not return an unmasked API key');
        }

        return $key;
    }

    public function registerSenderDomain(string $subaccountId, string $hostname): array
    {
        $subaccountId = trim($subaccountId);
        $hostname = strtolower(rtrim(trim($hostname), '.'));

        if ($subaccountId === '') {
            throw new Smtp2goException('The SMTP2GO sub-account ID is empty');
        }

        if (filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
            || filter_var($hostname, FILTER_VALIDATE_IP) !== false
        ) {
            throw new Smtp2goException(
                'The SMTP2GO sender-domain hostname "' . $hostname . '" is not a valid DNS hostname'
            );
        }

        $response = $this->client->post('/v3/domain/add', [
            'domain' => $hostname,
            'subaccount_id' => $subaccountId,
            'auto_verify' => false,
        ]);

        $domains = $response['data']['domains'] ?? null;

        if (!is_array($domains) || $domains === []) {
            throw new Smtp2goException('SMTP2GO did not return sender-domain setup details');
        }

        return $domains;
    }

    public function registerSingleSenderEmail(string $subaccountId, string $emailAddress): array
    {
        $subaccountId = trim($subaccountId);
        $emailAddress = trim($emailAddress);

        if ($subaccountId === '') {
            throw new Smtp2goException('The SMTP2GO sub-account ID is empty');
        }

        if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL) === false) {
            throw new Smtp2goException(
                'The SMTP2GO single sender email "' . $emailAddress . '" is invalid'
            );
        }

        $response = $this->client->post('/v3/single_sender_emails/add', [
            'email_address' => $emailAddress,
            'subaccount_id' => $subaccountId,
        ]);

        return [
            'email_address' => $emailAddress,
            'request_id' => trim((string) ($response['request_id'] ?? ($response['data']['request_id'] ?? ''))),
        ];
    }

    public function removeSingleSenderEmail(string $subaccountId, string $emailAddress): void
    {
        $this->client->post('/v3/single_sender_emails/remove', [
            'email_address' => trim($emailAddress),
            'subaccount_id' => $this->requireSubaccountId($subaccountId),
        ]);
    }

    public function removeSenderDomain(string $subaccountId, string $hostname): void
    {
        $this->client->post('/v3/domain/remove', [
            'domain' => strtolower(rtrim(trim($hostname), '.')),
            'subaccount_id' => $this->requireSubaccountId($subaccountId),
        ]);
    }

    public function removeApiKey(string $subaccountId, string $apiKey): void
    {
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            throw new Smtp2goException('The SMTP2GO API key to remove is empty');
        }

        $this->client->post('/v3/api_keys/remove', [
            'id' => $apiKey,
            'subaccount_id' => $this->requireSubaccountId($subaccountId),
        ]);
    }

    public function closeSubaccount(string $subaccountId): void
    {
        $this->client->post('/v3/subaccount/close', [
            'id' => $this->requireSubaccountId($subaccountId),
        ]);
    }

    private function requireSubaccountId(string $subaccountId): string
    {
        $subaccountId = trim($subaccountId);

        if ($subaccountId === '') {
            throw new Smtp2goException('The SMTP2GO sub-account ID is empty');
        }

        return $subaccountId;
    }
}
