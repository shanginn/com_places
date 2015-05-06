<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_places
 *
 * @copyright   Copyright (C) 2015 Saity74 LLC, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::register('PlacesHelper', JPATH_ADMINISTRATOR . '/components/com_places/helpers/places.php');

/**
 * Content HTML helper
 *
 * @since  3.0
 */
abstract class JHtmlPlacesAdministrator
{
	/**
	 * Render the list of associated items
	 *
	 * @param   int  $townid  The town item id
	 *
	 * @return  string  The language HTML
	 */
	public static function association($townid)
	{
		// Defaults
		$html = '';

		// Get the associations
		if ($associations = JLanguageAssociations::getAssociations('com_places', '#__places_town', 'com_places.town', $townid))
		{
			foreach ($associations as $tag => $associated)
			{
				$associations[$tag] = (int) $associated->id;
			}

			// Get the associated menu items
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select('t.*')
				->select('l.sef as lang_sef')
				->from('#__places_town as t')
				//->select('reg.title as region_title')
				//->join('LEFT', '#__places_pregion as reg ON reg.id=t.regionid')
				->where('t.id IN (' . implode(',', array_values($associations)) . ')')
				->join('LEFT', '#__languages as l ON t.language=l.lang_code')
				->select('l.image')
				->select('l.title as language_title');
			$db->setQuery($query);

			try
			{
				$items = $db->loadObjectList('id');
			}
			catch (RuntimeException $e)
			{
				throw new Exception($e->getMessage(), 500);
			}

			if ($items)
			{
				foreach ($items as &$item)
				{
					$text = strtoupper($item->lang_sef);
					$url = JRoute::_('index.php?option=com_places&task=town.edit&id=' . (int) $item->id);
					$tooltipParts = array(
						JHtml::_('image', 'mod_languages/' . $item->image . '.gif',
							$item->language_title,
							array('title' => $item->language_title),
							true
						),
						$item->title,
						//'(' . $item->region_title . ')'
					);
					$item->link = JHtml::_('tooltip', implode(' ', $tooltipParts), null, null, $text, $url, null, 'hasTooltip label label-association label-' . $item->lang_sef);
				}
			}

			$html = JLayoutHelper::render('joomla.content.associations', $items);
		}

		return $html;
	}

	/**
	 * Show the feature/unfeature links
	 *
	 * @param   int      $value      The state value
	 * @param   int      $i          Row number
	 * @param   boolean  $canChange  Is user allowed to change?
	 *
	 * @return  string       HTML code
	 */
	public static function featured($value = 0, $i, $canChange = true)
	{
		JHtml::_('bootstrap.tooltip');

		// Array of image, task, title, action
		$states	= array(
			0	=> array('unfeatured',	'towns.featured',	'COM_CONTENT_UNFEATURED',	'JGLOBAL_TOGGLE_FEATURED'),
			1	=> array('featured',	'towns.unfeatured',	'COM_CONTENT_FEATURED',		'JGLOBAL_TOGGLE_FEATURED'),
		);
		$state	= JArrayHelper::getValue($states, (int) $value, $states[1]);
		$icon	= $state[0];

		if ($canChange)
		{
			$html	= '<a href="#" onclick="return listItemTask(\'cb' . $i . '\',\'' . $state[1] . '\')" class="btn btn-micro hasTooltip' . ($value == 1 ? ' active' : '') . '" title="' . JHtml::tooltipText($state[3]) . '"><i class="icon-'
					. $icon . '"></i></a>';
		}
		else
		{
			$html	= '<a class="btn btn-micro hasTooltip disabled' . ($value == 1 ? ' active' : '') . '" title="' . JHtml::tooltipText($state[2]) . '"><i class="icon-'
					. $icon . '"></i></a>';
		}

		return $html;
	}
}
