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
 * Places Component Point Model
 *
 * @since  1.5
 */
class PlacesModelPoint extends JModelItem
{
  /**
   * Model context string.
   *
   * @var        string
   */
  protected $_context = 'com_places.point';

  /**
   * Method to auto-populate the model state.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @since   1.6
   *
   * @return void
   */
  protected function populateState()
  {
    $app = JFactory::getApplication('site');

    // Load state from the request.
    $pk = $app->input->getInt('id');
    $this->setState('point.id', $pk);

    $offset = $app->input->getUInt('limitstart');
    $this->setState('list.offset', $offset);

    // Load the parameters.
    $params = $app->getParams();
    $this->setState('params', $params);

    // TODO: Tune these values based on other permissions.
    $user = JFactory::getUser();

    if ((!$user->authorise('core.edit.state', 'com_places')) && (!$user->authorise('core.edit', 'com_places')))
    {
      $this->setState('filter.published', 1);
      $this->setState('filter.archived', 2);
    }

    $this->setState('filter.language', JLanguageMultilang::isEnabled());
  }

  /**
   * Method to get point data.
   *
   * @param   integer  $pk  The id of the point.
   *
   * @return  mixed  Menu item data object on success, false on failure.
   */
  public function getItem($pk = null)
  {
    $user = JFactory::getUser();

    $pk = (!empty($pk)) ? $pk : (int) $this->getState('point.id');

    if ($this->_item === null)
    {
      $this->_item = array();
    }

    if (!isset($this->_item[$pk]))
    {
      try
      {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
          ->select(
            $this->getState(
              'item.select', 'p.id, p.asset_id, p.title, p.alias, p.introtext, p.fulltext, ' .
              // If badcats is not null, this means that the point is inside an unpublished category
              // In this case, the state is set to 0 to indicate Unpublished (even if the point state is Published)
              'CASE WHEN badcats.id is null THEN p.state ELSE 0 END AS state, ' .
              'p.townid, p.created, p.created_by, p.created_by_alias, ' .
              // Use created if modified is 0
              'CASE WHEN p.modified = ' . $db->quote($db->getNullDate()) . ' THEN p.created ELSE p.modified END as modified, ' .
              'p.modified_by, p.checked_out, p.checked_out_time, p.publish_up, p.publish_down, ' .
              'p.images, p.urls, p.attribs, p.version, p.ordering, ' .
              'p.metakey, p.metadesc, p.access, p.hits, p.metadata, p.featured, p.language, p.xreference'
            )
          );
        $query->from('#__places_point AS p');

        // Join on town table.
        $query->select('t.title AS town_title, t.alias AS town_alias, t.access AS town_access')
          ->join('LEFT', '#__places_town AS c on t.id = p.townid');

        // Join on user table.
        $query->select('u.name AS author')
          ->join('LEFT', '#__users AS u on u.id = p.created_by');

        // Filter by language
        if ($this->getState('filter.language'))
        {
          $query->where('p.language in (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
        }

        // Join over the categories to get parent category titles
        // $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
        //   ->join('LEFT', '#__categories as parent ON parent.id = c.parent_id');

        // Join on voting table
        // $query->select('ROUND(v.rating_sum / v.rating_count, 0) AS rating, v.rating_count as rating_count')
        //   ->join('LEFT', '#__content_rating AS v ON p.id = v.content_id')

          ->where('p.id = ' . (int) $pk);

        if ((!$user->authorise('core.edit.state', 'com_places')) && (!$user->authorise('core.edit', 'com_places')))
        {
          // Filter by start and end dates.
          $nullDate = $db->quote($db->getNullDate());
          $date = JFactory::getDate();

          $nowDate = $db->quote($date->toSql());

          $query->where('(p.publish_up = ' . $nullDate . ' OR p.publish_up <= ' . $nowDate . ')')
            ->where('(p.publish_down = ' . $nullDate . ' OR p.publish_down >= ' . $nowDate . ')');
        }

        // Join to check for category published state in parent categories up the tree
        // If all categories are published, badcats.id will be null, and we just use the point state
        // $subquery = ' (SELECT cat.id as id FROM #__categories AS cat JOIN #__categories AS parent ';
        // $subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
        // $subquery .= 'WHERE parent.extension = ' . $db->quote('com_places');
        // $subquery .= ' AND parent.published <= 0 GROUP BY cat.id)';
        // $query->join('LEFT OUTER', $subquery . ' AS badcats ON badcats.id = c.id');

        // Filter by published state.
        $published = $this->getState('filter.published');
        $archived = $this->getState('filter.archived');

        if (is_numeric($published))
        {
          $query->where('(p.state = ' . (int) $published . ' OR p.state =' . (int) $archived . ')');
        }

        $db->setQuery($query);

        $data = $db->loadObject();

        if (empty($data))
        {
          return JError::raiseError(404, JText::_('COM_CONTENT_ERROR_ARTICLE_NOT_FOUND'));
        }

        // Check for published state if filter set.
        if (((is_numeric($published)) || (is_numeric($archived))) && (($data->state != $published) && ($data->state != $archived)))
        {
          return JError::raiseError(404, JText::_('COM_CONTENT_ERROR_ARTICLE_NOT_FOUND'));
        }

        // Convert parameter fields to objects.
        $registry = new Registry;
        $registry->loadString($data->attribs);

        $data->params = clone $this->getState('params');
        $data->params->merge($registry);

        $registry = new Registry;
        $registry->loadString($data->metadata);
        $data->metadata = $registry;

        // Technically guest could edit an point, but lets not check that to improve performance a little.
        if (!$user->get('guest'))
        {
          $userId = $user->get('id');
          $asset = 'com_places.point.' . $data->id;

          // Check general edit permission first.
          if ($user->authorise('core.edit', $asset))
          {
            $data->params->set('access-edit', true);
          }

          // Now check if edit.own is available.
          elseif (!empty($userId) && $user->authorise('core.edit.own', $asset))
          {
            // Check for a valid user and that they are the owner.
            if ($userId == $data->created_by)
            {
              $data->params->set('access-edit', true);
            }
          }
        }

        // Compute view access permissions.
        if ($access = $this->getState('filter.access'))
        {
          // If the access filter has been set, we already know this user can view.
          $data->params->set('access-view', true);
        }
        else
        {
          // If no access filter is set, the layout takes some responsibility for display of limited information.
          $user = JFactory::getUser();
          $groups = $user->getAuthorisedViewLevels();

          if ($data->townid == 0 || $data->town_access === null)
          {
            $data->params->set('access-view', in_array($data->access, $groups));
          }
          else
          {
            $data->params->set('access-view', in_array($data->access, $groups) && in_array($data->town_access, $groups));
          }
        }

        $this->_item[$pk] = $data;
      }
      catch (Exception $e)
      {
        if ($e->getCode() == 404)
        {
          // Need to go thru the error handler to allow Redirect to work.
          JError::raiseError(404, $e->getMessage());
        }
        else
        {
          $this->setError($e);
          $this->_item[$pk] = false;
        }
      }
    }

    return $this->_item[$pk];
  }

  /**
   * Increment the hit counter for the point.
   *
   * @param   integer  $pk  Optional primary key of the point to increment.
   *
   * @return  boolean  True if successful; false otherwise and internal error set.
   */
  public function hit($pk = 0)
  {
    $input = JFactory::getApplication()->input;
    $hitcount = $input->getInt('hitcount', 1);

    if ($hitcount)
    {
      $pk = (!empty($pk)) ? $pk : (int) $this->getState('point.id');

      $table = JTable::getInstance('Content', 'JTable');
      $table->load($pk);
      $table->hit($pk);
    }

    return true;
  }

  /**
   * Save user vote on point
   *
   * @param   integer  $pk    Joomla Article Id
   * @param   integer  $rate  Voting rate
   *
   * @return  boolean          Return true on success
   */
  // public function storeVote($pk = 0, $rate = 0)
  // {
  //   if ($rate >= 1 && $rate <= 5 && $pk > 0)
  //   {
  //     $userIP = $_SERVER['REMOTE_ADDR'];

  //     // Initialize variables.
  //     $db    = JFactory::getDbo();
  //     $query = $db->getQuery(true);

  //     // Create the base select statement.
  //     $query->select('*')
  //       ->from($db->quoteName('#__content_rating'))
  //       ->where($db->quoteName('content_id') . ' = ' . (int) $pk);

  //     // Set the query and load the result.
  //     $db->setQuery($query);

  //     // Check for a database error.
  //     try
  //     {
  //       $rating = $db->loadObject();
  //     }
  //     catch (RuntimeException $e)
  //     {
  //       JError::raiseWarning(500, $e->getMessage());

  //       return false;
  //     }

  //     // There are no ratings yet, so lets insert our rating
  //     if (!$rating)
  //     {
  //       $query = $db->getQuery(true);

  //       // Create the base insert statement.
  //       $query->insert($db->quoteName('#__content_rating'))
  //         ->columns(array($db->quoteName('content_id'), $db->quoteName('lastip'), $db->quoteName('rating_sum'), $db->quoteName('rating_count')))
  //         ->values((int) $pk . ', ' . $db->quote($userIP) . ',' . (int) $rate . ', 1');

  //       // Set the query and execute the insert.
  //       $db->setQuery($query);

  //       try
  //       {
  //         $db->execute();
  //       }
  //       catch (RuntimeException $e)
  //       {
  //         JError::raiseWarning(500, $e->getMessage());

  //         return false;
  //       }
  //     }
  //     else
  //     {
  //       if ($userIP != ($rating->lastip))
  //       {
  //         $query = $db->getQuery(true);

  //         // Create the base update statement.
  //         $query->update($db->quoteName('#__content_rating'))
  //           ->set($db->quoteName('rating_count') . ' = rating_count + 1')
  //           ->set($db->quoteName('rating_sum') . ' = rating_sum + ' . (int) $rate)
  //           ->set($db->quoteName('lastip') . ' = ' . $db->quote($userIP))
  //           ->where($db->quoteName('content_id') . ' = ' . (int) $pk);

  //         // Set the query and execute the update.
  //         $db->setQuery($query);

  //         try
  //         {
  //           $db->execute();
  //         }
  //         catch (RuntimeException $e)
  //         {
  //           JError::raiseWarning(500, $e->getMessage());

  //           return false;
  //         }
  //       }
  //       else
  //       {
  //         return false;
  //       }
  //     }

  //     return true;
  //   }

  //   JError::raiseWarning('SOME_ERROR_CODE', JText::sprintf('COM_CONTENT_INVALID_RATING', $rate), "JModelArticle::storeVote($rate)");

  //   return false;
  // }
}
