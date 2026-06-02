<?php
/**
 * @version    1.0.0
 * @package    com_ra_delivery
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_delivery\Administrator\View\Deliveryevents;
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
/**
 * View class for a list of Deliveryevents.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode("\n", $errors));
		}

		$this->addToolbar();

		$this->sidebar = Sidebar::render();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function addToolbar()
	{
		$canDo = ToolsHelper::getActions('com_ra_delivery');

		ToolbarHelper::title(Text::_('COM_RA_DELIVERY_TITLE_DELIVERYEVENTS'), "generic");

		$toolbar = Toolbar::getInstance('toolbar');

		if ($canDo->get('core.admin'))
		{
			$toolbar->preferences('com_ra_delivery');
		}

		// Set sidebar action
		Sidebar::setAction('index.php?option=com_ra_delivery&view=deliveryevents');
	}
	
	/**
	 * Method to order fields 
	 *
	 * @return void 
	 */
	protected function getSortFields()
	{
		return array(
			'a.`id`' => Text::_('JGRID_HEADING_ID'),
			'a.`event_date_utc`' => Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_EVENT_DATE_UTC'),
			'a.`event_type`' => Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_EVENT_TYPE'),
			'a.`recipient`' => Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_RECIPIENT'),
			'a.`sender`' => Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_SENDER'),
			'a.`subject`' => Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_SUBJECT'),
			'a.`email_id`' => Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_EMAIL_ID'),
			'a.`api_site_id`' => Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_API_SITE_ID'),
			'a.`provider_name`' => Text::_('COM_RA_DELIVERY_DELIVERYEVENTS_PROVIDER_NAME'),
		);
	}

	/**
	 * Check if state is set
	 *
	 * @param   mixed  $state  State
	 *
	 * @return bool
	 */
	public function getState($state)
	{
		return isset($this->state->{$state}) ? $this->state->{$state} : false;
	}
}
