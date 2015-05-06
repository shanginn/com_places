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
 * Methods supporting a list of town records.
 *
 * @since  1.6
 */
class PlacesModelTowns extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.6
	 * @see     JController
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 't.id',
				'title', 't.title',
				'alias', 't.alias',
				'checked_out', 't.checked_out',
				'checked_out_time', 't.checked_out_time',
				'regionid', 't.regionid', 'region_title',
				'state', 't.state',
				'access', 't.access', 'access_level',
				'created', 't.created',
				'created_by', 't.created_by',
				'created_by_alias', 't.created_by_alias',
				'ordering', 't.ordering',
				//'featured', 't.featured',
				'language', 't.language',
				'hits', 't.hits',
				'publish_up', 't.publish_up',
				'publish_down', 't.publish_down',
				'published', 't.published',
				'author_id',
				'region_id',
				'level',
				'tag'
			);

			if (JLanguageAssociations::isEnabled())
			{
				$config['filter_fields'][] = 'association';
			}
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication();

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
		$this->setState('filter.access', $access);

		$authorId = $app->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id');
		$this->setState('filter.author_id', $authorId);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		$regionId = $this->getUserStateFromRequest($this->context . '.filter.region_id', 'filter_region_id');
		$this->setState('filter.region_id', $regionId);

		$level = $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level');
		$this->setState('filter.level', $level);

		$language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

		$tag = $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag', '');
		$this->setState('filter.tag', $tag);

		parent::populateState('t.title', 'asc');

		// Force a language
		$forcedLanguage = $app->input->get('forcedLanguage');

		if (!empty($forcedLanguage))
		{
			$this->setState('filter.language', $forcedLanguage);
			$this->setState('filter.forcedLanguage', $forcedLanguage);
		}
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.access');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.region_id');
		$id .= ':' . $this->getState('filter.author_id');
		$id .= ':' . $this->getState('filter.language');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		$db 		= $this->getDbo();
		$query 	= $db->getQuery(true);
		$user 	= JFactory::getUser();
		$app 		= JFactory::getApplication();

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 't.*'
			)
		);

		$query->from('#__places_town AS t');

		// Join over the language
		$query->select('l.title AS language_title')
			->join('LEFT', $db->quoteName('#__languages') . ' AS l ON l.lang_code = t.language');

		// Join over the users for the checked out user.
		$query->select('uc.name AS editor')
			->join('LEFT', '#__users AS uc ON uc.id = t.checked_out');

		// Join over the asset groups.
		$query->select('ag.title AS access_level')
			->join('LEFT', '#__viewlevels AS ag ON ag.id = t.access');

		// Join over the categories.
		// $query->select('c.title AS region_title')
		// 	->join('LEFT', '#__places_region AS c ON c.id = t.regionid');

		// Join over the users for the author.
		$query->select('ua.name AS author_name')
			->join('LEFT', '#__users AS ua ON ua.id = t.created_by');

		// Join over the associations.
		if (JLanguageAssociations::isEnabled())
		{
			$query->select('COUNT(asso2.id)>1 as association')
				->join('LEFT', '#__associations AS asso ON asso.id = t.id AND asso.context=' . $db->quote('com_places.town'))
				->join('LEFT', '#__associations AS asso2 ON asso2.key = asso.key')
				->group('t.id, l.title, uc.name, ag.title, c.title, ua.name');
		}

		// Filter by access level.
		if ($access = $this->getState('filter.access'))
		{
			$query->where('t.access = ' . (int) $access);
		}

		// Implement View Level Access
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('t.access IN (' . $groups . ')');
		}

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where('t.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(t.state = 0 OR t.state = 1)');
		}

		//TODO: Filter by region
		// if (is_numeric($regionId))
		// {
		// 	$query->where('t.regionid = '. (int) $regionId);
		// }
		// elseif (is_array($regionId))
		// {
		// 	JArrayHelper::toInteger($regionId);
		// 	$regionId = implode(',', $regionId);
		// 	$query->where('t.regionid IN (' . $regionId . ')');
		// }

		// Filter by author
		$authorId = $this->getState('filter.author_id');

		if (is_numeric($authorId))
		{
			$type = $this->getState('filter.author_id.include', true) ? '= ' : '<>';
			$query->where('t.created_by ' . $type . (int) $authorId);
		}

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('t.id = ' . (int) substr($search, 3));
			}
			elseif (stripos($search, 'author:') === 0)
			{
				$search = $db->quote('%' . $db->escape(substr($search, 7), true) . '%');
				$query->where('(ua.name LIKE ' . $search . ' OR ua.username LIKE ' . $search . ')');
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('(
					t.title LIKE '.$search.' OR
					t.alias LIKE '.$search.' OR
					t.phone LIKE '.$search.' OR
					t.email LIKE '.$search.'
				)');
			}
		}

		// Filter on the language.
		if ($language = $this->getState('filter.language'))
		{
			$query->where('t.language = ' . $db->quote($language));
		}

		// TODO: Filter by a single tag.
		// $tagId = $this->getState('filter.tag');

		// if (is_numeric($tagId))
		// {
		// 	$query->where($db->quoteName('tagmap.tag_id') . ' = ' . (int) $tagId)
		// 		->join(
		// 			'LEFT', $db->quoteName('#__contentitem_tag_map', 'tagmap')
		// 			. ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('t.id')
		// 			. ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_content.article')
		// 		);
		// }

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', 't.id');
		$orderDirn = $this->state->get('list.direction', 'desc');

		if ($orderCol == 't.ordering' || $orderCol == 'region_title')
		{
			//TODO:
			//$orderCol = 't.title ' . $orderDirn . ', t.ordering';
		}

		// SQL server change
		if ($orderCol == 'language')
		{
			$orderCol = 'l.title';
		}

		if ($orderCol == 'access_level')
		{
			$orderCol = 'ag.title';
		}

		$query->order($db->escape($orderCol . ' ' . $orderDirn));

		return $query;
	}

	/**
	 * Build a list of authors
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.6
	 */
	public function getAuthors()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Construct the query
		$query->select('u.id AS value, u.name AS text')
			->from('#__users AS u')
			->join('INNER', '#__places_town AS t ON t.created_by = u.id')
			->group('u.id, u.name')
			->order('u.name');

		// Setup the query
		$db->setQuery($query);

		// Return the result
		return $db->loadObjectList();
	}

	/**
	 * Method to get a list of towns.
	 * Overridden to add a check for access levels.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.6.1
	 */
	public function getItems()
	{
		$items = parent::getItems();

		if (JFactory::getApplication()->isSite())
		{
			$user = JFactory::getUser();
			$groups = $user->getAuthorisedViewLevels();

			for ($x = 0, $count = count($items); $x < $count; $x++)
			{
				// Check the access level. Remove towns the user shouldn't see
				if (!in_array($items[$x]->access, $groups))
				{
					unset($items[$x]);
				}
			}
		}

		return $items;
	}

}
