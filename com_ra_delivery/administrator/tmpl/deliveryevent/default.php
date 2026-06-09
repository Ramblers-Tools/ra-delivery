<?php
/**
 * @version    1.0.0
 * @package    Com_Anand
 * @author     Super User <dev@component-creator.com>
 * @copyright  2023 Super User
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$toolsHelper = new ToolsHelper;

?>

<div class="item_fields">

	<table class="table">
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_PROVIDER_NAME'); ?></th><td><?php echo $this->escape($this->item->provider_name); ?></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_API_SITE_ID'); ?></th><td><?php echo (int) $this->item->api_site_id; ?></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_EMAIL_ID'); ?></th><td><?php echo $this->escape($this->item->email_id); ?></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_EVENT_TYPE'); ?></th><td><?php echo $this->escape($this->item->event_type); ?></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_EVENT_DATE_UTC'); ?></th><td><?php echo HTMLHelper::_('date', $this->item->event_date_utc, 'd M y H:i:s'); ?></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_RECIPIENT'); ?></th><td><?php echo $this->escape($this->item->recipient); ?></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_SENDER'); ?></th><td><?php echo $this->escape($this->item->sender); ?></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_SUBJECT'); ?></th><td><?php echo $this->escape($this->item->subject); ?></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_FORM_LBL_DELIVERYEVENT_SMTP_RESPONSE'); ?></th><td><pre><?php echo $this->escape((string) $this->item->smtp_response); ?></pre></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_FORM_LBL_DELIVERYEVENT_USERNAME'); ?></th><td><?php echo $this->escape($this->item->username); ?></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_FORM_LBL_DELIVERYEVENT_SUBACCOUNT_NAME'); ?></th><td><?php echo $this->escape($this->item->subaccount_name); ?></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_FORM_LBL_DELIVERYEVENT_HOST'); ?></th><td><?php echo $this->escape($this->item->host); ?></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_FORM_LBL_DELIVERYEVENT_OUTBOUND_IP'); ?></th><td><?php echo $this->escape($this->item->outbound_ip); ?></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_FORM_LBL_DELIVERYEVENT_BYTE_SIZE'); ?></th><td><?php echo (int) $this->item->byte_size; ?></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_FORM_LBL_DELIVERYEVENT_FETCHED_AT'); ?></th><td><?php echo HTMLHelper::_('date', $this->item->fetched_at, 'd M y H:i:s'); ?></td></tr>
		<tr><th><?php echo Text::_('COM_RA_DELIVERY_FORM_LBL_DELIVERYEVENT_RAW_PAYLOAD'); ?></th><td><pre><?php echo $this->escape((string) $this->item->raw_payload); ?></pre></td></tr>

	</table>

	<p>
	<?php echo $toolsHelper->backButton('administrator/index.php?option=com_ra_delivery&view=deliveryevents'); ?></p>

</div>

