<?php
/**
 * @version     1.0.6
 * @package     com_ra_delivery
 * @copyright   Copyright (C) 2020. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Charlie <webmaster@bigley.me.uk> - https://www.stokeandnewcastleramblers.org.uk
 * 06/07/26 CB Support sending of emails with Smtp2go API, using the new com_ra_delivery component
 */
namespace Ramblers\Component\Ra_delivery\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;

class Smtp2goActivityService {

    private $clientFactory;
    private $lastError = '';

    public function __construct(?Smtp2goClientFactory $clientFactory = null) {
        $this->clientFactory = $clientFactory ?? new Smtp2goClientFactory();
    }

    public function getLastError() {
        return $this->lastError;
    }

    /*
     * Returns the decoded json data, or false
     * Invoked on-line for testing, or from the batch process
     */

    public function searchActivity($apiSiteId, $startDate, $endDate, array $eventTypes, $limit, $continueToken = '', $subaccounts = []) {
        $payload = array(
            'start_date' => $startDate,
            'end_date' => $endDate,
            'event_types' => array_values($eventTypes),
            'limit' => (int) $limit,
            'only_latest' => false,
        );

        if ($continueToken !== '') {
            $payload['continue_token'] = $continueToken;
        }
        if (!empty($subaccounts)) {
            $payload['subaccounts'] = $subaccounts;
        }

        try {
            $client = $this->clientFactory->createForApiSite((int) $apiSiteId);
            $response = $client->post('/v3/activity/search', $payload);
        } catch (Smtp2goException $exception) {
            $this->lastError = $exception->getMessage();
            return false;
        }

        return array(
            'events' => $response['data']['events'] ?? array(),
            'continue_token' => (string) ($response['data']['continue_token'] ?? ''),
            'request_id' => (string) ($response['request_id'] ?? ''),
        );
    }

    public function send($apiSiteId, $payload) {
        $sender = trim((string) ComponentHelper::getParams('com_ra_delivery')->get('sender_email', ''));

        if ($sender === '') {
            $this->lastError = 'RA Delivery sender_email is not configured';

            return false;
        }

        $payload['sender'] = $sender;

        try {
            $client = $this->clientFactory->createForApiSite((int) $apiSiteId);
            $response = $client->post('/v3/email/send', $payload);
        } catch (Smtp2goException $exception) {
            $this->lastError = $exception->getMessage();
            return false;
        }

        return $response;
    }

}
