<?php

namespace Ramblers\Component\Ra_delivery\Site\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

class Smtp2goApiSiteRepository
{
    private const SUPPORTED_HOSTS = [
        'api.smtp2go.com',
        'us-api.smtp2go.com',
        'eu-api.smtp2go.com',
        'au-api.smtp2go.com',
    ];

    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function loadEnabled(int $apiSiteId): object
    {
        if ($apiSiteId < 1) {
            throw new Smtp2goException('SMTP2GO API site id is missing');
        }

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('id'),
                $this->db->quoteName('url'),
                $this->db->quoteName('token'),
                $this->db->quoteName('state'),
            ])
            ->from($this->db->quoteName('#__ra_api_sites'))
            ->where($this->db->quoteName('id') . ' = ' . $apiSiteId);

        $site = $this->db->setQuery($query)->loadObject();

        if ($site === null) {
            throw new Smtp2goException('The configured SMTP2GO API site was not found');
        }

        if ((int) $site->state !== 1) {
            throw new Smtp2goException('The configured SMTP2GO API site is disabled');
        }

        if (trim((string) $site->token) === '') {
            throw new Smtp2goException('The configured SMTP2GO API site has no API key');
        }

        $site->url = $this->validateEndpoint((string) $site->url);

        return $site;
    }

    public function replaceCredential(
        int $apiSiteId,
        string $subaccountName,
        string $subaccountId,
        string $apiKey
    ): void {
        $site = $this->loadEnabled($apiSiteId);
        $subaccountName = trim($subaccountName);
        $subaccountId = trim($subaccountId);
        $apiKey = trim($apiKey);

        if ($subaccountName === '') {
            throw new Smtp2goException('The SMTP2GO sub-account name is empty');
        }

        if ($subaccountId === '') {
            throw new Smtp2goException('The SMTP2GO sub-account ID is empty');
        }

        if ($apiKey === '') {
            throw new Smtp2goException('The generated SMTP2GO API key is empty');
        }

        $title = 'Sub account name = ' . $subaccountName
            . '; sub account id = ' . $subaccountId;

        if (strlen($title) > 100) {
            throw new Smtp2goException('The SMTP2GO API-site title is too long');
        }

        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__ra_api_sites'))
            ->set($this->db->quoteName('title') . ' = ' . $this->db->quote($title))
            ->set($this->db->quoteName('token') . ' = ' . $this->db->quote($apiKey))
            ->where($this->db->quoteName('id') . ' = ' . (int) $site->id)
            ->where($this->db->quoteName('state') . ' = 1');

        $this->db->setQuery($query)->execute();

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('title'),
                $this->db->quoteName('token'),
            ])
            ->from($this->db->quoteName('#__ra_api_sites'))
            ->where($this->db->quoteName('id') . ' = ' . (int) $site->id)
            ->where($this->db->quoteName('state') . ' = 1');

        $updated = $this->db->setQuery($query)->loadObject();

        if ($updated === null
            || !hash_equals($title, (string) $updated->title)
            || !hash_equals($apiKey, (string) $updated->token)
        ) {
            throw new Smtp2goException('The SMTP2GO API-site credential could not be updated');
        }
    }

    private function validateEndpoint(string $endpoint): string
    {
        $endpoint = rtrim(trim($endpoint), '/');
        $parts = parse_url($endpoint);

        if (!is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || !in_array(strtolower((string) ($parts['host'] ?? '')), self::SUPPORTED_HOSTS, true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || (isset($parts['port']) && (int) $parts['port'] !== 443)
            || (($parts['path'] ?? '') !== '')
        ) {
            throw new Smtp2goException('The configured SMTP2GO API endpoint is not supported');
        }

        return $endpoint;
    }
}
