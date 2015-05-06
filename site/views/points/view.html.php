<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_places
 *
 * @copyright   Copyright (C) 2015 Saity74 LLC, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * HTML View class for the Content component
 *
 * @since  1.5
 */
class PlacesViewPoints extends JViewLegacy
{

	/**
	 * @var    integer  Number of columns in a multi column display
	 * @since  3.2
	 */
	protected $columns = 3;

	/**
	 * @var    string  The name of the extension for the category
	 * @since  3.2
	 */
	protected $extension = 'com_places';

	/**
	 * @var    string  Default title to use for page title
	 * @since  3.2
	 */
	protected $defaultPageTitle = 'COM_PLACES_HEADING_POINTS';

	/**
	 * @var    string  The name of the view to link individual items to
	 * @since  3.2
	 */
	protected $viewName = 'point';

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */

	public function display($tpl = null)
	{

		$this->items = $this->get('Items');
		$this->places_by_towns = [];
		
		foreach($this->items as $item) {
			$this->places_by_towns[$item->town_title][] = $item;

			$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
		}
		//$this->towns  = $this->get('Towns');
		$this->state  = $this->get('State');

		$this->base_path = str_replace(JPATH_BASE, '', JPATH_COMPONENT);

		JFactory::getDocument()
		  ->addScript('//api-maps.yandex.ru/2.1/?lang=ru_RU')
		  ->addScript($this->base_path.DS.'assets/js/places.js')
		  ->addStyleSheet($this->base_path.DS.'assets/css/places.css');


		//$places = PlacesHelper::getAllPlaces();

		parent::display($tpl);
	}

	
}
