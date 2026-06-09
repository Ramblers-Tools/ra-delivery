<?php
/**
 * @version    1.0.0
 * @package    com_ra_delivery
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_delivery\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Versioning\VersionableControllerTrait;

/**
 * Deliveryevent controller class.
 *
 * @since  1.0.0
 */
class DeliveryeventController extends FormController
{
	use VersionableControllerTrait;

	protected $view_list = 'deliveryevents';
}
