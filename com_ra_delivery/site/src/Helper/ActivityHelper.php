<?php

namespace Ramblers\Component\Ra_delivery\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Ramblers\Component\Ra_delivery\Site\Service\Smtp2goActivityService;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

class ActivityHelper {

    private const WATERMARK_RECORD_TYPE = 2;
    private const LOG_SUB_SYSTEM = 'RA Delivery';
    private const LOG_RECORD_TYPE = '1';
    private const INSERTED = 'inserted';
    private const DUPLICATE = 'duplicate';
    private const FAILED = 'failed';

    private $db;
    private $messages = array();
    private $service;
    private $toolsHelper;

    public function __construct() {
        $this->db = Factory::getDbo();
        $this->toolsHelper = new ToolsHelper();
        $this->service = new Smtp2goActivityService();
    }

    private function calculateStartDate($lookbackMinutes) {
        $watermark = $this->loadWatermark();

        if ($watermark === '') {
            return gmdate('Y-m-d\TH:i:s\Z', strtotime('-1 day'));
        }

        $timestamp = strtotime($watermark);

        if ($timestamp === false) {
            return gmdate('Y-m-d\TH:i:s\Z', strtotime('-1 day'));
        }

        return gmdate('Y-m-d\TH:i:s\Z', $timestamp - ($lookbackMinutes * 60));
    }

    private function filterEventsBySubdomain(array $events, $subdomainFilter) {
        if ($subdomainFilter === '') {
            return $events;
        }

        return array_values(array_filter($events, function ($event) use ($subdomainFilter) {
                    return $this->matchesSubdomainFilter($event, $subdomainFilter);
                }));
    }

    public function getMessages() {
        return $this->messages;
    }

    private function loadWatermark() {
        $query = $this->db->getQuery(true)
                ->select($this->db->quoteName('key_value'))
                ->from($this->db->quoteName('#__ra_control'))
                ->where($this->db->quoteName('record_type') . ' = ' . self::WATERMARK_RECORD_TYPE);

        $this->db->setQuery($query);

        return trim((string) $this->db->loadResult());
    }

    private function logMessage($message, $ref = '0') {
        $this->toolsHelper->createLog(self::LOG_SUB_SYSTEM, self::LOG_RECORD_TYPE, $ref, $message);
    }

    private function matchesSubdomainFilter(array $event, $subdomainFilter) {
        if ($subdomainFilter === '') {
            return true;
        }

        $sender = strtolower(trim((string) ($event['sender'] ?? '')));

        return $sender !== '' && strpos($sender, $subdomainFilter) !== false;
    }

    private function normaliseConfiguredEventTypes($value) {
        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        $value = trim((string) $value);

        if ($value === '') {
            return array('soft-bounced', 'hard-bounced', 'rejected', 'spam');
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    private function normaliseEventDate($value) {
        $timestamp = strtotime((string) $value);

        if ($timestamp === false) {
            return gmdate('Y-m-d H:i:s');
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    private function normaliseSubdomainFilter($value) {
        return strtolower(trim((string) $value));
    }

    public function pollConfiguredEvents() {
        $params = ComponentHelper::getParams('com_ra_delivery');

        if ((int) $params->get('polling_enabled', 1) !== 1) {
            $this->messages[] = 'Polling is disabled in com_ra_delivery configuration';
            return array('inserted' => 0, 'duplicates' => 0, 'pages' => 0, 'events' => 0);
        }

        $apiSiteId = (int) $params->get('smtp2go_api_site_id', 0);

        if ($apiSiteId <= 0) {
            $this->messages[] = 'SMTP2GO API site id is not configured';
            return false;
        }

        $eventTypes = $this->normaliseConfiguredEventTypes($params->get('event_types'));
        $lookbackMinutes = max(0, (int) $params->get('lookback_minutes', 10));
        $pageLimit = min(1000, max(1, (int) $params->get('page_limit', 250)));
        $subdomainFilter = $this->normaliseSubdomainFilter($params->get('subdomain'));
        $tidyUpDays = max(0, (int) $params->get('tidy_up_days', 30));

        $startDate = $this->calculateStartDate($lookbackMinutes);
        $endDate = gmdate('Y-m-d\TH:i:s\Z');
        $continueToken = '';
        $stats = array('inserted' => 0, 'duplicates' => 0, 'failed' => 0, 'filtered' => 0, 'deleted' => 0, 'pages' => 0, 'events' => 0);

        $this->messages[] = 'Polling SMTP2GO activity from ' . $startDate . ' to ' . $endDate;
        $this->logMessage('Starting activity poll from ' . $startDate . ' to ' . $endDate . ' for api site ' . $apiSiteId, (string) $apiSiteId);

        if ($subdomainFilter !== '') {
            $this->messages[] = 'Applying sender subdomain filter: ' . $subdomainFilter;
        }

        do {
            $result = $this->service->searchActivity($apiSiteId, $startDate, $endDate, $eventTypes, $pageLimit, $continueToken);

            if ($result === false) {
                $this->messages[] = 'Polling failed: ' . $this->service->getLastError();
                $this->logMessage('Polling failed: ' . $this->service->getLastError(), (string) $apiSiteId);
                return false;
            }

            $stats['pages']++;
            $events = $result['events'];
            $stats['events'] += count($events);
            $this->messages[] = 'Fetched page ' . $stats['pages'] . ' with ' . count($events) . ' events'
                    . ($result['request_id'] !== '' ? ' (request ' . $result['request_id'] . ')' : '');
            $this->logMessage(
                    'Fetched page ' . $stats['pages'] . ' with ' . count($events) . ' events'
                    . ($result['request_id'] !== '' ? ' (request ' . $result['request_id'] . ')' : ''),
                    (string) $apiSiteId
            );

            foreach ($events as $event) {
                if (!$this->matchesSubdomainFilter($event, $subdomainFilter)) {
                    $stats['filtered']++;
                    continue;
                }

                $storeResult = $this->storeEvent($apiSiteId, $event);

                if ($storeResult === self::INSERTED) {
                    $stats['inserted']++;
                } elseif ($storeResult === self::DUPLICATE) {
                    $stats['duplicates']++;
                } else {
                    $stats['failed']++;
                }
            }

            $continueToken = $result['continue_token'];
        } while ($continueToken !== '');

        if ($stats['failed'] > 0) {
            $this->messages[] = 'Polling completed with ' . $stats['failed'] . ' storage failures; watermark not advanced';
            $this->logMessage('Polling completed with ' . $stats['failed'] . ' storage failures; watermark not advanced', (string) $apiSiteId);
            return false;
        }

        $this->storeWatermark($endDate);
        if ($tidyUpDays > 0) {
            $stats['deleted'] = $this->tidyUpOldEvents($tidyUpDays);
        }

        $this->messages[] = 'Polling complete: ' . $stats['inserted'] . ' inserted, ' . $stats['duplicates'] . ' duplicates'
                . ($stats['filtered'] > 0 ? ', ' . $stats['filtered'] . ' filtered out' : '')
                . ($stats['deleted'] > 0 ? ', ' . $stats['deleted'] . ' tidied up' : '');
        $this->messages[] = 'Watermark updated to ' . $endDate;
        $this->logMessage(
                'Polling complete: ' . $stats['inserted'] . ' inserted, ' . $stats['duplicates'] . ' duplicates'
                . ($stats['filtered'] > 0 ? ', ' . $stats['filtered'] . ' filtered out' : '')
                . ($stats['deleted'] > 0 ? ', ' . $stats['deleted'] . ' tidied up' : '')
                . ', watermark updated to ' . $endDate,
                (string) $apiSiteId
        );

        return $stats;
    }

    private function storeEvent($apiSiteId, array $event) {
        $query = $this->db->getQuery(true)
                ->insert($this->db->quoteName('#__ra_delivery_events'))
                ->columns(array(
                    $this->db->quoteName('provider_name'),
                    $this->db->quoteName('api_site_id'),
                    $this->db->quoteName('email_id'),
                    $this->db->quoteName('event_type'),
                    $this->db->quoteName('event_date_utc'),
                    $this->db->quoteName('recipient'),
                    $this->db->quoteName('sender'),
                    $this->db->quoteName('subject'),
                    $this->db->quoteName('smtp_response'),
                    $this->db->quoteName('username'),
                    $this->db->quoteName('subaccount_name'),
                    $this->db->quoteName('host'),
                    $this->db->quoteName('outbound_ip'),
                    $this->db->quoteName('byte_size'),
                    $this->db->quoteName('raw_payload')
                ))
                ->values(implode(',', array(
            $this->db->quote('smtp2go'),
            (int) $apiSiteId,
            $this->db->quote((string) ($event['email_id'] ?? '')),
            $this->db->quote((string) ($event['event'] ?? '')),
            $this->db->quote($this->normaliseEventDate($event['date'] ?? '')),
            $this->db->quote((string) ($event['recipient'] ?? '')),
            $this->db->quote((string) ($event['sender'] ?? '')),
            $this->db->quote((string) ($event['subject'] ?? '')),
            $this->db->quote((string) ($event['smtp_response'] ?? '')),
            $this->db->quote((string) ($event['username'] ?? '')),
            $this->db->quote((string) ($event['subaccount_name'] ?? '')),
            $this->db->quote((string) ($event['host'] ?? '')),
            $this->db->quote((string) ($event['outbound_ip'] ?? '')),
            (int) ($event['byte_size'] ?? 0),
            $this->db->quote(json_encode($event))
        )));

        try {
            $this->db->setQuery($query);
            $this->db->execute();
            return self::INSERTED;
        } catch (\RuntimeException $exception) {
            if ((int) $exception->getCode() === 1062 || strpos($exception->getMessage(), 'Duplicate entry') !== false) {
                return self::DUPLICATE;
            }

            $this->messages[] = 'Failed to store event for email id '
                    . (string) ($event['email_id'] ?? '')
                    . ': ' . $exception->getMessage();
            $this->logMessage(
                    'Failed to store event for email id ' . (string) ($event['email_id'] ?? '') . ': ' . $exception->getMessage(),
                    (string) $apiSiteId
            );

            return self::FAILED;
        }
    }

    private function storeWatermark($value) {
        $query = $this->db->getQuery(true)
                ->insert($this->db->quoteName('#__ra_control'))
                ->columns(array($this->db->quoteName('record_type'), $this->db->quoteName('key_value')))
                ->values((int) self::WATERMARK_RECORD_TYPE . ',' . $this->db->quote($value));

        $sql = (string) $query . ' ON DUPLICATE KEY UPDATE ' . $this->db->quoteName('key_value') . ' = VALUES(' . $this->db->quoteName('key_value') . ')';
        $this->db->setQuery($sql);
        $this->db->execute();
    }

    public function testApiSite($apiSiteId) {
        $apiSiteId = (int) $apiSiteId;
        $params = ComponentHelper::getParams('com_ra_delivery');
        $eventTypes = $this->normaliseConfiguredEventTypes($params->get('event_types'));
        $lookbackMinutes = max(0, (int) $params->get('lookback_minutes', 10));
        $pageLimit = min(1000, max(1, (int) $params->get('page_limit', 250)));
        $subdomainFilter = $this->normaliseSubdomainFilter($params->get('subdomain'));

        if ($apiSiteId <= 0) {
            $this->messages[] = 'API site id is missing';
            return false;
        }

        $startDate = $this->calculateStartDate($lookbackMinutes);
        $endDate = gmdate('Y-m-d\TH:i:s\Z');
        $this->messages[] = 'Testing SMTP2GO activity for site ' . $apiSiteId . ' from ' . $startDate . ' to ' . $endDate;

        $result = $this->service->searchActivity($apiSiteId, $startDate, $endDate, $eventTypes, $pageLimit);

        if ($result === false) {
            $this->messages[] = 'Test failed: ' . $this->service->getLastError();
            return false;
        }

        $events = $this->filterEventsBySubdomain($result['events'], $subdomainFilter);
        $filteredOut = count($result['events']) - count($events);
        $this->messages[] = 'Request ' . ($result['request_id'] !== '' ? $result['request_id'] : 'n/a')
                . ' returned ' . count($events) . ' events';

        if ($subdomainFilter !== '') {
            $this->messages[] = 'Applied sender subdomain filter: ' . $subdomainFilter;
        }

        if ($filteredOut > 0) {
            $this->messages[] = $filteredOut . ' events excluded by sender subdomain filter';
        }

        return array(
            'start_date' => $startDate,
            'end_date' => $endDate,
            'request_id' => (string) $result['request_id'],
            'event_count' => count($events),
            'events' => $events,
        );
    }

    private function tidyUpOldEvents($tidyUpDays) {
        $cutoff = gmdate('Y-m-d H:i:s', strtotime('-' . (int) $tidyUpDays . ' days'));
        $query = $this->db->getQuery(true)
                ->delete($this->db->quoteName('#__ra_delivery_events'))
                ->where($this->db->quoteName('event_date_utc') . ' < ' . $this->db->quote($cutoff));

        try {
            $this->db->setQuery($query);
            $this->db->execute();
            $deleted = (int) $this->db->getAffectedRows();
            $this->messages[] = 'Tidy-up removed ' . $deleted . ' rows older than ' . $tidyUpDays . ' days';
            return $deleted;
        } catch (\RuntimeException $exception) {
            $this->messages[] = 'Tidy-up failed: ' . $exception->getMessage();
            $this->logMessage('Tidy-up failed: ' . $exception->getMessage());
            return 0;
        }
    }

}
