<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_places
 *
 * @copyright   Copyright (C) 2015 Saity74 LLC, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Places component helper.
 */

class PlacesHelper extends JHelperContent
{
	public static $extension = 'com_places';

	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  The name of the active view.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */

	public static function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(
			JText::_('COM_PLACES_SUBMENU_POINTS'),
			'index.php?option=com_places&view=points',
			$vName == 'points'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_PLACES_SUBMENU_TOWNS'),
			'index.php?option=com_places&view=towns',
			$vName == 'towns'
		);

	}

}
