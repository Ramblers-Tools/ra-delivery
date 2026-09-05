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

        if (count($matches) > 1) {
            throw new Smtp2goException('SMTP2GO returned multiple exact sub-account matches');
        }

        return $matches[0] ?? null;
    }

    public function createSubaccount(string $name, string $email, int $limit): array
    {
        $name = trim($name);
        $email = trim($email);

        if ($name === '') {
            throw new Smtp2goException('The SMTP2GO sub-account name is empty');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new Smtp2goException('The SMTP2GO sub-account email is invalid');
        }

        if ($limit < 1) {
            throw new Smtp2goException('The SMTP2GO sub-account email limit is invalid');
        }

        return $this->client->post('/v3/subaccount/add', [
            'fullname' => $name,
            'subaccount_email' => $email,
            'limit' => $limit,
            'dedicated_ip' => false,
            'archiving' => false,
            'enforce_2fa' => false,
            'enable_sms' => false,
        ]);
    }

    public function createApiKey(string $subaccountId, string $description): array
    {
        $subaccountId = trim($subaccountId);

        if ($subaccountId === '') {
            throw new Smtp2goException('The SMTP2GO sub-account ID is empty');
        }

        return $this->client->post('/v3/api_keys/add', [
            'description' => trim($description),
            'status' => 'allowed',
            'endpoints' => ['/email/send', '/activity/search'],
            'subaccount_id' => $subaccountId,
        ]);
    }

    public function registerSenderDomain(string $subaccountId, string $hostname): array
    {
        $subaccountId = trim($subaccountId);
        $hostname = strtolower(rtrim(trim($hostname), '.'));

        if ($subaccountId === '') {
            throw new Smtp2goException('The SMTP2GO sub-account ID is empty');
        }

        if (filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw new Smtp2goException('The SMTP2GO sender-domain hostname is invalid');
        }

        return $this->client->post('/v3/domain/add', [
            'domain' => $hostname,
            'subaccount_id' => $subaccountId,
            'auto_verify' => false,
        ]);
    }
}
