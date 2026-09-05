<?php

namespace Ramblers\Component\Ra_delivery\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class Smtp2goClientFactory
{
    private DatabaseInterface $db;
    private ?Smtp2goTransportInterface $transport;

    public function __construct(
        ?DatabaseInterface $db = null,
        ?Smtp2goTransportInterface $transport = null
    ) {
        $this->db = $db ?? Factory::getContainer()->get(DatabaseInterface::class);
        $this->transport = $transport;
    }

    public function createForApiSite(int $apiSiteId): Smtp2goClient
    {
        if ($apiSiteId < 1) {
            throw new Smtp2goException('SMTP2GO API site id is missing');
        }

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('url'),
                $this->db->quoteName('token'),
            ])
            ->from($this->db->quoteName('#__ra_api_sites'))
            ->where($this->db->quoteName('id') . ' = ' . $apiSiteId);

        $site = $this->db->setQuery($query)->loadObject();

        if ($site === null) {
            throw new Smtp2goException('API site ' . $apiSiteId . ' not found');
        }

        return new Smtp2goClient(
            (string) $site->url,
            (string) $site->token,
            $this->transport
        );
    }
}
