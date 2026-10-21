<?php

namespace Ramblers\Component\Ra_delivery\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class Smtp2goClientFactory
{
    private Smtp2goApiSiteRepository $apiSites;
    private ?Smtp2goTransportInterface $transport;

    public function __construct(
        ?DatabaseInterface $db = null,
        ?Smtp2goTransportInterface $transport = null,
        ?Smtp2goApiSiteRepository $apiSites = null
    ) {
        if ($apiSites === null) {
            $db = $db ?? Factory::getContainer()->get(DatabaseInterface::class);
            $apiSites = new Smtp2goApiSiteRepository($db);
        }

        $this->apiSites = $apiSites;
        $this->transport = $transport;
    }

    public function createForApiSite(int $apiSiteId): Smtp2goClient
    {
        $site = $this->apiSites->loadEnabled($apiSiteId);

        return new Smtp2goClient(
            (string) $site->url,
            (string) $site->token,
            $this->transport
        );
    }

    public function replaceApiSiteCredential(
        int $apiSiteId,
        string $subaccountName,
        string $subaccountId,
        string $apiKey
    ): void {
        // The caller must invoke this only after every remote provisioning call
        // has succeeded, because this replaces the locally held master key.
        $this->apiSites->replaceCredential(
            $apiSiteId,
            $subaccountName,
            $subaccountId,
            $apiKey
        );
    }
}
