<?php

/**
 * Installation script for com_ra_delivery.
 * 07/09/26 CB Enqueue messages, rather than echo them
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

class Com_Ra_deliveryInstallerScript
{
    private $minimumJoomlaVersion = '4.0';
    private $minimumPHPVersion = '7.4.0';
    private $minimumToolsVersion = '4.0.9';

    private function fail(string $message): bool
    {
        Factory::getApplication()->enqueueMessage($message, 'error');
        Log::add($message, Log::ERROR, 'jerror');

        return false;
    }

    private function buildButton(string $url, string $text, string $colour = 'sunrise'): string
    {
        return '<a class="link-button ' . htmlspecialchars($colour, ENT_QUOTES, 'UTF-8')
            . '" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_self">'
            . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</a>';
    }

    /**
     * Returns the installed component and database schema versions.
     *
     * @return object|false Object containing component and db_version, or false if not found.
     */
    public function getVersions(string $component = 'com_ra_delivery')
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('e.manifest_cache'),
                $db->quoteName('s.version_id', 'db_version'),
            ])
            ->from($db->quoteName('#__extensions', 'e'))
            ->join(
                'LEFT',
                $db->quoteName('#__schemas', 's')
                    . ' ON ' . $db->quoteName('s.extension_id') . ' = ' . $db->quoteName('e.extension_id')
            )
            ->where($db->quoteName('e.element') . ' = ' . $db->quote($component))
            ->where($db->quoteName('e.type') . ' = ' . $db->quote('component'));
        $item = $db->setQuery($query)->loadObject();

        if (!$item) {
            return false;
        }

        $manifest = json_decode((string) $item->manifest_cache);

        if (!$manifest || !isset($manifest->version)) {
            return false;
        }

        $versions = new \stdClass();
        $versions->component = (string) $manifest->version;
        $versions->db_version = $item->db_version;

        return $versions;
    }

    public function install($parent): bool
    {
        Factory::getApplication()->enqueueMessage( 'Installing RA Delivery (com_ra_delivery)', 'info');

        return true;
    }

    public function update($parent): bool
    {
        Factory::getApplication()->enqueueMessage( 'Updating RA Delivery (com_ra_delivery)', 'info');

        return true;
    }

    public function uninstall($parent): bool
    {
        Factory::getApplication()->enqueueMessage( 'Uninstalling RA Delivery (com_ra_delivery)', 'info');
        $versions = $this->getVersions();

        if ($versions !== false) {
            $this->reportVersions('RA Delivery', $versions);
        }

        return true;
    }

    public function preflight($type, $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }
        Factory::getApplication()->enqueueMessage( 'RA Delivery: preflight checks', 'info');
        if (version_compare(PHP_VERSION, $this->minimumPHPVersion, '<')) {
            return $this->fail(Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', $this->minimumPHPVersion));
        }

        if (version_compare(JVERSION, $this->minimumJoomlaVersion, '<')) {
            return $this->fail(Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomlaVersion));
        }

        if (!ComponentHelper::isEnabled('com_ra_tools')) {
            return $this->fail('RA Delivery requires RA Tools (com_ra_tools) to be installed and enabled.');
        }

        $toolsVersions = $this->getVersions('com_ra_tools');

        if ($toolsVersions === false) {
            return $this->fail('Unable to determine the installed version of RA Tools.');
        }

        $this->reportVersions('RA Tools', $toolsVersions);

        if (version_compare($toolsVersions->component, $this->minimumToolsVersion, '<')) {
            return $this->fail(
                'RA Delivery requires RA Tools version ' . $this->minimumToolsVersion
                    . ' or later; found ' . $toolsVersions->component . '.'
            );
        }

        if ($type === 'update') {
            $versions = $this->getVersions();

            if ($versions !== false) {
                $this->reportVersions('Current RA Delivery', $versions);
            }
        }

        return true;
    }

    public function postflight($type, $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }
        Factory::getApplication()->enqueueMessage( 'RA Delivery: postflight checks', 'info');
        $versions = $this->getVersions();

        if ($versions === false) {
            Factory::getApplication()->enqueueMessage(
                'RA Delivery was installed, but its installed version could not be determined.',
                'warning'
            );
        } else {
            $this->reportVersions('RA Delivery', $versions);
        }

        echo '<p><strong>Useful links</strong></p>';
        echo '<p>' . $this->buildButton(
            'index.php?option=com_ra_delivery&view=deliveryevents',
            'Open RA Delivery'
        ) . '</p>';
        echo '<p>' . $this->buildButton(
            'index.php?option=com_ra_tools&view=dashboard',
            'Dashboard', false,
            'granite'
        ) . '</p>';
        echo '<p>' . $this->buildButton(
            'index.php?option=com_config&view=component&component=com_ra_delivery',
            'Configure RA Delivery'
        ) . '</p>';

        return true;
    }

    private function reportVersions(string $label, object $versions): void
    {
        $databaseVersion = $versions->db_version ?: 'not recorded';
        $message = htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . ' version ' . htmlspecialchars((string) $versions->component, ENT_QUOTES, 'UTF-8')
            . ', database version ' . htmlspecialchars((string) $databaseVersion, ENT_QUOTES, 'UTF-8'); 
        Factory::getApplication()->enqueueMessage($message, 'info');
    }
}
