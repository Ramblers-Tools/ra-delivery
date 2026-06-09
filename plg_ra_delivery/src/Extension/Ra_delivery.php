<?php

/**
 * @version    1.0.0
 * @package    plg_ra_delivery
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Plugin\Console\Ra_delivery\Extension;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Ramblers\Plugin\Console\Ra_delivery\Command\PollactivityCommand;

class Ra_delivery extends CMSPlugin {

    protected $app;

    public function __construct(&$subject, $config = array()) {
        parent::__construct($subject, $config);

        if (!$this->app->isClient('cli')) {
            return;
        }

        $this->registerCLICommands();
    }

    public function registerCLICommands() {
        $this->app->addCommand(new PollactivityCommand());
    }

}
