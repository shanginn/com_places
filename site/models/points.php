<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_places
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * This models supports retrieving lists of points.
 *
 * @since  1.6
 */
class PlacesModelPoints extends JModelList
{
  /**
   * Constructor.
   *
   * @param   array  $config  An optional associative array of configuration settings.
   *
   * @see     JController
   * @since   1.6
   */
  public function __construct($config = array())
  {
    if (empty($config['filter_fields']))
    {
      $config['filter_fields'] = array(
        'id', 'p.id',
        'title', 'p.title',
        'alias', 'p.alias',
        'checked_out', 'p.checked_out',
        'checked_out_time', 'p.checked_out_time',
        'townid', 'p.townid', 'town_title',
        'address', 'p.address',
        'state', 'p.state',
        'access', 'p.access', 'access_level',
        'created', 'p.created',
        'created_by', 'p.created_by',
        'ordering', 'p.ordering',
        'featured', 'p.featured',
        'language', 'p.language',
        'hits', 'p.hits',
        'publish_up', 'p.publish_up',
        'publish_down', 'p.publish_down',
        'images', 'p.images',
        'urls', 'p.urls',
      );
    }

    parent::__construct($config);
  }

  /**
   * Method to auto-populate the model state.
   *
   * This method should only be called once per instantiation and is designed
   * to be called on the first call to the getState() method unless the model
   * configuration flag to ignore the request is set.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @param   string  $ordering   An optional ordering field.
   * @param   string  $direction  An optional direction (asc|desc).
   *
   * @return  void
   *
   * @since   12.2
   */
  protected function populateState($ordering = 'ordering', $direction = 'ASC')
  {
    $app = JFactory::getApplication();

    // List state information
    $value = $app->input->get('limit', $app->get('list_limit', 0), 'uint');
    $this->setState('list.limit', $value);

    $value = $app->input->get('limitstart', 0, 'uint');
    $this->setState('list.start', $value);

    $orderCol = $app->input->get('filter_order', 'p.ordering');

    if (!in_array($orderCol, $this->filter_fields))
    {
      $orderCol = 'p.ordering';
    }

    $this->setState('list.ordering', $orderCol);

    $listOrder = $app->input->get('filter_order_Dir', 'ASC');

    if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
    {
      $listOrder = 'ASC';
    }

    $this->setState('list.direction', $listOrder);

    $params = $app->getParams();
    $this->setState('params', $params);
    $user = JFactory::getUser();

    if ((!$user->authorise('core.edit.state', 'com_places')) && (!$user->authorise('core.edit', 'com_places')))
    {
      // Filter on published for those who do not have edit or edit.state rights.
      $this->setState('filter.published', 1);
    }

    $this->setState('filter.language', JLanguageMultilang::isEnabled());

    // Process show_noauth parameter
    if (!$params->get('show_noauth'))
    {
      $this->setState('filter.access', true);
    }
    else
    {
      $this->setState('filter.access', false);
    }

    $this->setState('layout', $app->input->getString('layout'));
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
    $id .= ':' . serialize($this->getState('filter.published'));
    $id .= ':' . $this->getState('filter.access');
    $id .= ':' . $this->getState('filter.featured');
    $id .= ':' . serialize($this->getState('filter.point_id'));
    $id .= ':' . $this->getState('filter.point_id.include');
    $id .= ':' . serialize($this->getState('filter.town_id'));
    $id .= ':' . $this->getState('filter.town_id.include');
    $id .= ':' . serialize($this->getState('filter.author_id'));
    $id .= ':' . $this->getState('filter.author_id.include');
    $id .= ':' . serialize($this->getState('filter.author_alias'));
    $id .= ':' . $this->getState('filter.author_alias.include');
    $id .= ':' . $this->getState('filter.date_filtering');
    $id .= ':' . $this->getState('filter.date_field');
    $id .= ':' . $this->getState('filter.start_date_range');
    $id .= ':' . $this->getState('filter.end_date_range');
    $id .= ':' . $this->getState('filter.relative_date');

    return parent::getStoreId($id);
  }

  /**
   * Get the master query for retrieving a list of points subject to the model state.
   *
   * @return  JDatabaseQuery
   *
   * @since   1.6
   */
  protected function getListQuery()
  {
    // Get the current user for authorisation checks
    $user = JFactory::getUser();

    // Create a new query object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select(
      $this->getState(
        'list.select',
        'p.id, p.title, p.alias, p.introtext, p.fulltext, ' .
        'p.checked_out, p.checked_out_time, ' .
        'p.org_kpp, p.org_inn, p.org_ogrn, p.org_phone, p.org_fax as fax, p.org_email, ' .
        'p.org_yr_addr, p.org_fiz_addr, p.org_pocht_addr,' .
        'p.pay_bik, p.pay_bank_name, p.pay_bank_address, ' .
        'p.pay_corr_bill, p.pay_bill, p.email, p.address, p.office, p.site, p.phone, p.lat, p.lng,' .
        'p.townid, p.created, p.created_by, p.created_by_alias, ' .
        // Use created if modified is 0
        'CASE WHEN p.modified = ' . $db->quote($db->getNullDate()) . ' THEN p.created ELSE p.modified END as modified, ' .
        'p.modified_by, uam.name as modified_by_name,' .
        // Use created if publish_up is 0
        'CASE WHEN p.publish_up = ' . $db->quote($db->getNullDate()) . ' THEN p.created ELSE p.publish_up END as publish_up,' .
        'p.publish_down, p.images, p.urls, p.attribs, p.metadata, p.metakey, p.metadesc, p.access, ' .
        'p.hits, p.xreference, p.featured, p.language, ' . ' ' . $query->length('p.fulltext') . ' AS readmore'
      )
    );
    
    // Process an Archived Article layout
    if ($this->getState('filter.published') == 2)
    {
      // If badcats is not null, this means that the point is inside an archived category
      // In this case, the state is set to 2 to indicate Archived (even if the point state is Published)
      //$query->select($this->getState('list.select', 'CASE WHEN badcats.id is null THEN p.state ELSE 2 END AS state'));
      $query->select($this->getState('list.select', 'p.state AS state'));
    }
    else
    {
      /*
      Process non-archived layout
      If badcats is not null, this means that the point is inside an unpublished category
      In this case, the state is set to 0 to indicate Unpublished (even if the point state is Published)
      */
      //$query->select($this->getState('list.select', 'CASE WHEN badcats.id is not null THEN 0 ELSE p.state END AS state'));
      $query->select($this->getState('list.select', 'p.state AS state'));
    }

    $query->from('#__places_point AS p');

    // Join over the frontpage points.
    // if ($this->context != 'com_places.featured')
    // {
    //   $query->join('LEFT', '#__content_frontpage AS fp ON fp.content_id = p.id');
    // }

    // Join over the categories.
    //$query->select('c.title AS town_title, c.path AS category_route, c.access AS category_access, c.alias AS category_alias')
    $query->select('t.title AS town_title, t.access AS town_access, t.alias AS town_alias')
      ->join('LEFT', '#__places_town AS t ON t.id = p.townid');

    // Join over the users for the author and modified_by names.
    $query->select("CASE WHEN p.created_by_alias > ' ' THEN p.created_by_alias ELSE ua.name END AS author")
      ->select("ua.email AS author_email")

      ->join('LEFT', '#__users AS ua ON ua.id = p.created_by')
      ->join('LEFT', '#__users AS uam ON uam.id = p.modified_by');

    // Join over the categories to get parent category titles
    // $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
    //   ->join('LEFT', '#__categories as parent ON parent.id = c.parent_id');

    // Join on voting table
    // $query->select('ROUND(v.rating_sum / v.rating_count, 0) AS rating, v.rating_count as rating_count')
    //   ->join('LEFT', '#__content_rating AS v ON p.id = v.content_id');

    // Join to check for category published state in parent categories up the tree
    // $query->select('c.published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');
    // $subquery = 'SELECT cat.id as id FROM #__categories AS cat JOIN #__categories AS parent ';
    // $subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
    // $subquery .= 'WHERE parent.extension = ' . $db->quote('com_places');

    if ($this->getState('filter.published') == 2)
    {
      // Find any up-path categories that are archived
      // If any up-path categories are archived, include all children in archived layout
      //$subquery .= ' AND parent.published = 2 GROUP BY cat.id ';

      // Set effective state to archived if up-path category is archived
      //$publishedWhere = 'CASE WHEN badcats.id is null THEN p.state ELSE 2 END';
      $publishedWhere = 'p.state';
    }
    else
    {
      // Find any up-path categories that are not published
      // If all categories are published, badcats.id will be null, and we just use the point state
      //$subquery .= ' AND parent.published != 1 GROUP BY cat.id ';

      // Select state to unpublished if up-path category is unpublished
      //$publishedWhere = 'CASE WHEN badcats.id is null THEN p.state ELSE 0 END';
      $publishedWhere = 'p.state';
    }

    //$query->join('LEFT OUTER', '(' . $subquery . ') AS badcats ON badcats.id = t.id');

    // Filter by access level.
    if ($access = $this->getState('filter.access'))
    {
      $groups = implode(',', $user->getAuthorisedViewLevels());
      $query->where('p.access IN (' . $groups . ')')
        ->where('t.access IN (' . $groups . ')');
    }

    // Filter by published state
    $published = $this->getState('filter.published');

    if (is_numeric($published))
    {
      // Use point state if badcats.id is null, otherwise, force 0 for unpublished
      $query->where($publishedWhere . ' = ' . (int) $published);
    }
    elseif (is_array($published))
    {
      JArrayHelper::toInteger($published);
      $published = implode(',', $published);

      // Use point state if badcats.id is null, otherwise, force 0 for unpublished
      $query->where($publishedWhere . ' IN (' . $published . ')');
    }

    // Filter by featured state
    $featured = $this->getState('filter.featured');

    switch ($featured)
    {
      case 'hide':
        $query->where('p.featured = 0');
        break;

      case 'only':
        $query->where('p.featured = 1');
        break;

      case 'show':
      default:
        // Normally we do not discriminate
        // between featured/unfeatured items.
        break;
    }

    // Filter by a single or group of points.
    $pointId = $this->getState('filter.point_id');

    if (is_numeric($pointId))
    {
      $type = $this->getState('filter.point_id.include', true) ? '= ' : '<> ';
      $query->where('p.id ' . $type . (int) $pointId);
    }
    elseif (is_array($pointId))
    {
      JArrayHelper::toInteger($pointId);
      $pointId = implode(',', $pointId);
      $type = $this->getState('filter.point_id.include', true) ? 'IN' : 'NOT IN';
      $query->where('p.id ' . $type . ' (' . $pointId . ')');
    }

    // Filter by a single or group of categories
    $townId = $this->getState('filter.town_id');

    if (is_numeric($townId))
    {
      $type = $this->getState('filter.town_id.include', true) ? '= ' : '<> ';

      // Add subcategory check
      //$includeSubcategories = $this->getState('filter.subcategories', false);
      $townEquals = 'p.townid ' . $type . (int) $townId;

      // if ($includeSubcategories)
      // {
      //   $levels = (int) $this->getState('filter.max_category_levels', '1');

      //   // Create a subquery for the subcategory list
      //   $subQuery = $db->getQuery(true)
      //     ->select('sub.id')
      //     ->from('#__categories as sub')
      //     ->join('INNER', '#__categories as this ON sub.lft > this.lft AND sub.rgt < this.rgt')
      //     ->where('this.id = ' . (int) $categoryId);

      //   if ($levels >= 0)
      //   {
      //     $subQuery->where('sub.level <= this.level + ' . $levels);
      //   }

      //   // Add the subquery to the main query
      //   $query->where('(' . $categoryEquals . ' OR p.townid IN (' . $subQuery->__toString() . '))');
      // }
      // else
      {
        $query->where($townEquals);
      }
    }
    elseif (is_array($townId) && (count($townId) > 0))
    {
      JArrayHelper::toInteger($townId);
      $townId = implode(',', $townId);

      if (!empty($townId))
      {
        $type = $this->getState('filter.town_id.include', true) ? 'IN' : 'NOT IN';
        $query->where('p.townid ' . $type . ' (' . $townId . ')');
      }
    }

    // Filter by author
    $authorId = $this->getState('filter.author_id');
    $authorWhere = '';

    if (is_numeric($authorId))
    {
      $type = $this->getState('filter.author_id.include', true) ? '= ' : '<> ';
      $authorWhere = 'p.created_by ' . $type . (int) $authorId;
    }
    elseif (is_array($authorId))
    {
      JArrayHelper::toInteger($authorId);
      $authorId = implode(',', $authorId);

      if ($authorId)
      {
        $type = $this->getState('filter.author_id.include', true) ? 'IN' : 'NOT IN';
        $authorWhere = 'p.created_by ' . $type . ' (' . $authorId . ')';
      }
    }

    // Filter by author alias
    $authorAlias = $this->getState('filter.author_alias');
    $authorAliasWhere = '';

    if (is_string($authorAlias))
    {
      $type = $this->getState('filter.author_alias.include', true) ? '= ' : '<> ';
      $authorAliasWhere = 'p.created_by_alias ' . $type . $db->quote($authorAlias);
    }
    elseif (is_array($authorAlias))
    {
      $first = current($authorAlias);

      if (!empty($first))
      {
        JArrayHelper::toString($authorAlias);

        foreach ($authorAlias as $key => $alias)
        {
          $authorAlias[$key] = $db->quote($alias);
        }

        $authorAlias = implode(',', $authorAlias);

        if ($authorAlias)
        {
          $type = $this->getState('filter.author_alias.include', true) ? 'IN' : 'NOT IN';
          $authorAliasWhere = 'p.created_by_alias ' . $type . ' (' . $authorAlias .
            ')';
        }
      }
    }

    if (!empty($authorWhere) && !empty($authorAliasWhere))
    {
      $query->where('(' . $authorWhere . ' OR ' . $authorAliasWhere . ')');
    }
    elseif (empty($authorWhere) && empty($authorAliasWhere))
    {
      // If both are empty we don't want to add to the query
    }
    else
    {
      // One of these is empty, the other is not so we just add both
      $query->where($authorWhere . $authorAliasWhere);
    }

    // Define null and now dates
    $nullDate = $db->quote($db->getNullDate());
    $nowDate  = $db->quote(JFactory::getDate()->toSql());

    // Filter by start and end dates.
    if ((!$user->authorise('core.edit.state', 'com_places')) && (!$user->authorise('core.edit', 'com_places')))
    {
      $query->where('(p.publish_up = ' . $nullDate . ' OR p.publish_up <= ' . $nowDate . ')')
        ->where('(p.publish_down = ' . $nullDate . ' OR p.publish_down >= ' . $nowDate . ')');
    }

    // Filter by Date Range or Relative Date
    $dateFiltering = $this->getState('filter.date_filtering', 'off');
    $dateField = $this->getState('filter.date_field', 'p.created');

    switch ($dateFiltering)
    {
      case 'range':
        $startDateRange = $db->quote($this->getState('filter.start_date_range', $nullDate));
        $endDateRange = $db->quote($this->getState('filter.end_date_range', $nullDate));
        $query->where(
          '(' . $dateField . ' >= ' . $startDateRange . ' AND ' . $dateField .
            ' <= ' . $endDateRange . ')'
        );
        break;

      case 'relative':
        $relativeDate = (int) $this->getState('filter.relative_date', 0);
        $query->where(
          $dateField . ' >= DATE_SUB(' . $nowDate . ', INTERVAL ' .
            $relativeDate . ' DAY)'
        );
        break;

      case 'off':
      default:
        break;
    }

    // Process the filter for list views with user-entered filters
    $params = $this->getState('params');

    if ((is_object($params)) && ($params->get('filter_field') != 'hide') && ($filter = $this->getState('list.filter')))
    {
      // Clean filter variable
      $filter = JString::strtolower($filter);
      $hitsFilter = (int) $filter;
      $filter = $db->quote('%' . $db->escape($filter, true) . '%', false);

      switch ($params->get('filter_field'))
      {
        case 'author':
          $query->where(
            'LOWER( CASE WHEN p.created_by_alias > ' . $db->quote(' ') .
              ' THEN p.created_by_alias ELSE ua.name END ) LIKE ' . $filter . ' '
          );
          break;

        case 'hits':
          $query->where('p.hits >= ' . $hitsFilter . ' ');
          break;

        case 'title':
        default:
          // Default to 'title' if parameter is not valid
          $query->where('LOWER( p.title ) LIKE ' . $filter);
          break;
      }
    }

    // Filter by language
    if ($this->getState('filter.language'))
    {
      $query->where('p.language in (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
    }

    // Add the list ordering clause.
    $query->order($this->getState('list.ordering', 'p.ordering') . ' ' . $this->getState('list.direction', 'ASC'));

    return $query;
  }

  /**
   * Method to get a list of points.
   *
   * Overriden to inject convert the attribs field into a JParameter object.
   *
   * @return  mixed  An array of objects on success, false on failure.
   *
   * @since   1.6
   */
  public function getItems()
  {
    $items = parent::getItems();
    
    $user = JFactory::getUser();
    $userId = $user->get('id');
    $guest = $user->get('guest');
    $groups = $user->getAuthorisedViewLevels();
    $input = JFactory::getApplication()->input;

    // Get the global params
    $globalParams = JComponentHelper::getParams('com_places', true);

    // Convert the parameter fields into objects.
    foreach ($items as &$item)
    {
      $pointParams = new Registry;
      $pointParams->loadString($item->attribs);

      // Unpack readmore and layout params
      $item->alternative_readmore = $pointParams->get('alternative_readmore');
      $item->layout = $pointParams->get('layout');

      $item->params = clone $this->getState('params');

      /*For blogs, point params override menu item params only if menu param = 'use_point'
      Otherwise, menu item params control the layout
      If menu item is 'use_point' and there is no point param, use global*/
      if (($input->getString('layout') == 'blog') || ($input->getString('view') == 'featured')
        || ($this->getState('params')->get('layout_type') == 'blog'))
      {
        // Create an array of just the params set to 'use_point'
        $menuParamsArray = $this->getState('params')->toArray();
        $pointArray = array();

        foreach ($menuParamsArray as $key => $value)
        {
          if ($value === 'use_point')
          {
            // If the point has a value, use it
            if ($pointParams->get($key) != '')
            {
              // Get the value from the point
              $pointArray[$key] = $pointParams->get($key);
            }
            else
            {
              // Otherwise, use the global value
              $pointArray[$key] = $globalParams->get($key);
            }
          }
        }

        // Merge the selected point params
        if (count($pointArray) > 0)
        {
          $pointParams = new Registry;
          $pointParams->loadArray($pointArray);
          $item->params->merge($pointParams);
        }
      }
      else
      {
        // For non-blog layouts, merge all of the point params
        $item->params->merge($pointParams);
      }

      // Get display date
      switch ($item->params->get('list_show_date'))
      {
        case 'modified':
          $item->displayDate = $item->modified;
          break;

        case 'published':
          $item->displayDate = ($item->publish_up == 0) ? $item->created : $item->publish_up;
          break;

        default:
        case 'created':
          $item->displayDate = $item->created;
          break;
      }

      // Compute the asset access permissions.
      // Technically guest could edit an point, but lets not check that to improve performance a little.
      if (!$guest)
      {
        $asset = 'com_places.point.' . $item->id;

        // Check general edit permission first.
        if ($user->authorise('core.edit', $asset))
        {
          $item->params->set('access-edit', true);
        }

        // Now check if edit.own is available.
        elseif (!empty($userId) && $user->authorise('core.edit.own', $asset))
        {
          // Check for a valid user and that they are the owner.
          if ($userId == $item->created_by)
          {
            $item->params->set('access-edit', true);
          }
        }
      }

      $access = $this->getState('filter.access');

      if ($access)
      {
        // If the access filter has been set, we already have only the points this user can view.
        $item->params->set('access-view', true);
      }
      else
      {
        // If no access filter is set, the layout takes some responsibility for display of limited information.
        if ($item->townid == 0 || $item->town_access === null)
        {
          $item->params->set('access-view', in_array($item->access, $groups));
        }
        else
        {
          $item->params->set('access-view', in_array($item->access, $groups) && in_array($item->town_access, $groups));
        }
      }

      // Get the tags
      $item->tags = new JHelperTags;
      $item->tags->getItemTags('com_places.point', $item->id);
    }

    return $items;
  }

  /**
   * Method to get the starting number of items for the data set.
   *
   * @return  integer  The starting number of items available in the data set.
   *
   * @since   12.2
   */
  public function getStart()
  {
    return $this->getState('list.start');
  }
}
