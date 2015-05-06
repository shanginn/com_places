<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_places
 *
 * @copyright   Copyright (C) 2015 Saity74 LLC, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

JLoader::register('PlacesHelper', JPATH_ADMINISTRATOR . '/components/com_places/helpers/places.php');

/**
 * Item Model for an Point.
 *
 * @since  1.6
 */
class PlacesModelPoint extends JModelAdmin
{
  /**
   * @var     string    The prefix to use with controller messages.
   * @since   1.6
   */
  protected $text_prefix = 'COM_PLACES';

  /**
   * The type alias for this content type (for example, 'com_places.point').
   *
   * @var      string
   * @since    3.2
   */
  public $typeAlias = 'com_places.point';

  /**
   * Batch copy items to a new category or current.
   *
   * @param   integer  $value     The new category.
   * @param   array    $pks       An array of row IDs.
   * @param   array    $contexts  An array of item contexts.
   *
   * @return  mixed  An array of new IDs on success, boolean false on failure.
   *
   * @since   11.1
   */
  protected function batchCopy($value, $pks, $contexts)
  {
    $regionId = (int) $value;

    $newIds = array();

    if (!parent::checkregionId($regionId))
    {
      return false;
    }

    // Parent exists so we let's proceed
    while (!empty($pks))
    {
      // Pop the first ID off the stack
      $pk = array_shift($pks);

      $this->table->reset();

      // Check that the row actually exists
      if (!$this->table->load($pk))
      {
        if ($error = $this->table->getError())
        {
          // Fatal error
          $this->setError($error);

          return false;
        }
        else
        {
          // Not fatal error
          $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $pk));
          continue;
        }
      }

      // Alter the title & alias
      $data = $this->generateNewTitle($regionId, $this->table->alias, $this->table->title);
      $this->table->title = $data['0'];
      $this->table->alias = $data['1'];

      // Reset the ID because we are making a copy
      $this->table->id = 0;

      // Reset hits because we are making a copy
      $this->table->hits = 0;

      // Unpublish because we are making a copy
      $this->table->state = 0;

      // New region ID
      $this->table->regionid = $regionId;

      // TODO: Deal with ordering?
      // $table->ordering = 1;

      // Get the featured state
      //$featured = $this->table->featured;

      // Check the row.
      if (!$this->table->check())
      {
        $this->setError($this->table->getError());
        return false;
      }

      parent::createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);

      // Store the row.
      if (!$this->table->store())
      {
        $this->setError($this->table->getError());
        return false;
      }

      // Get the new item ID
      $newId = $this->table->get('id');

      // Add the new ID to the array
      $newIds[$pk] = $newId;

      // // Check if the point was featured and update the #__content_frontpage table
      // if ($featured == 1)
      // {
      //  $db = $this->getDbo();
      //  $query = $db->getQuery(true)
      //    ->insert($db->quoteName('#__content_frontpage'))
      //    ->values($newId . ', 0');
      //  $db->setQuery($query);
      //  $db->execute();
      // }
    }

    // Clean the cache
    $this->cleanCache();

    return $newIds;
  }

  /**
   * Method to test whether a record can be deleted.
   *
   * @param   object  $record  A record object.
   *
   * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
   *
   * @since   1.6
   */
  protected function canDelete($record)
  {
    if (!empty($record->id))
    {
      if ($record->state != -2)
      {
        return false;
      }
      $user = JFactory::getUser();

      return $user->authorise('core.delete', 'com_places.point.' . (int) $record->id);
    }

    return false;
  }

  /**
   * Method to test whether a record can have its state edited.
   *
   * @param   object  $record  A record object.
   *
   * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
   *
   * @since   1.6
   */
  protected function canEditState($record)
  {
    $user = JFactory::getUser();

    // Check for existing point.
    if (!empty($record->id))
    {
      return $user->authorise('core.edit.state', 'com_places.point.' . (int) $record->id);
    }
    // New point, so check against the category.
    elseif (!empty($record->townid))
    {
     return $user->authorise('core.edit.state', 'com_places.town.' . (int) $record->townid);
    }
    //Default to component settings if neither point nor category known.
    else
    {
      return parent::canEditState('com_places');
    }
  }

  /**
   * Prepare and sanitise the table data prior to saving.
   *
   * @param   JTable  $table  A JTable object.
   *
   * @return  void
   *
   * @since   1.6
   */
  protected function prepareTable($table)
  {
    // Set the publish date to now
    $db = $this->getDbo();

    if ($table->state == 1 && (int) $table->publish_up == 0)
    {
      $table->publish_up = JFactory::getDate()->toSql();
    }

    if ($table->state == 1 && intval($table->publish_down) == 0)
    {
      $table->publish_down = $db->getNullDate();
    }

    // Increment the content version number.
    $table->version++;

    // Reorder the points within the town so the new point is first
    if (empty($table->id))
    {
     $table->reorder('townid = ' . (int) $table->townid . ' AND state >= 0');
    }
  }

  /**
   * Returns a Table object, always creating it.
   *
   * @param   string  $type    The table type to instantiate
   * @param   string  $prefix  A prefix for the table class name. Optional.
   * @param   array   $config  Configuration array for model. Optional.
   *
   * @return  JTable    A database object
   */
  public function getTable($type = 'Points', $prefix = 'PlacesTable', $config = array())
  {
    return JTable::getInstance($type, $prefix, $config);
  }

  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  mixed  Object on success, false on failure.
   */
  public function getItem($pk = null)
  {
    if ($item = parent::getItem($pk))
    {
      // Convert the params field to an array.
      $registry = new Registry;
      $registry->loadString($item->attribs);
      $item->attribs = $registry->toArray();

      // Convert the metadata field to an array.
      $registry = new Registry;
      $registry->loadString($item->metadata);
      $item->metadata = $registry->toArray();

      // Convert the images field to an array.
      $registry = new Registry;
      $registry->loadString($item->images);
      $item->images = $registry->toArray();

      // Convert the urls field to an array.
      $registry = new Registry;
      $registry->loadString($item->urls);
      $item->urls = $registry->toArray();

      if (!empty($item->id))
      {
        $item->tags = new JHelperTags;
        $item->tags->getTagIds($item->id, 'com_places.point');
      }
    }

    // Load associated content items
    $app = JFactory::getApplication();
    $assoc = JLanguageAssociations::isEnabled();

    if ($assoc)
    {
      $item->associations = array();

      if ($item->id != null)
      {
        $associations = JLanguageAssociations::getAssociations('com_places', '#__places_point', 'com_places.item', $item->id);

        foreach ($associations as $tag => $association)
        {
          $item->associations[$tag] = $association->id;
        }
      }
    }

    return $item;
  }

  /**
   * Method to get the record form.
   *
   * @param   array    $data      Data for the form.
   * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
   *
   * @return  mixed  A JForm object on success, false on failure
   *
   * @since   1.6
   */
  public function getForm($data = array(), $loadData = true)
  {
    // Get the form.
    $form = $this->loadForm('com_places.point', 'point', array('control' => 'jform', 'load_data' => $loadData));
    if (empty($form))
    {
      return false;
    }
    $jinput = JFactory::getApplication()->input;

    // The front end calls this model and uses a_id to avoid id clashes so we need to check for that first.
    if ($jinput->get('a_id'))
    {
      $id = $jinput->get('a_id', 0);
    }
    // The back end uses id so we use that the rest of the time and set it to 0 by default.
    else
    {
      $id = $jinput->get('id', 0);
    }
    // Determine correct permissions to check.
    if ($this->getState('point.id'))
    {
     $id = $this->getState('point.id');

     // Existing record. Can only edit in selected categories.
     $form->setFieldAttribute('townid', 'action', 'core.edit');

     // Existing record. Can only edit own points in selected categories.
     $form->setFieldAttribute('townid', 'action', 'core.edit.own');
    }
    else
    {
     // New record. Can only create in selected categories.
     $form->setFieldAttribute('townid', 'action', 'core.create');
    }

    $user = JFactory::getUser();

    // Check for existing point.
    // Modify the form based on Edit State access controls.
    if ($id != 0 && (!$user->authorise('core.edit.state', 'com_places.point.' . (int) $id))
      || ($id == 0 && !$user->authorise('core.edit.state', 'com_places')))
    {
      //TODO:
      // Disable fields for display.
      //$form->setFieldAttribute('featured', 'disabled', 'true');
      $form->setFieldAttribute('ordering', 'disabled', 'true');
      $form->setFieldAttribute('publish_up', 'disabled', 'true');
      $form->setFieldAttribute('publish_down', 'disabled', 'true');
      $form->setFieldAttribute('state', 'disabled', 'true');

      // Disable fields while saving.
      // The controller has already verified this is an point you can edit.
      //$form->setFieldAttribute('featured', 'filter', 'unset');
      $form->setFieldAttribute('ordering', 'filter', 'unset');
      $form->setFieldAttribute('publish_up', 'filter', 'unset');
      $form->setFieldAttribute('publish_down', 'filter', 'unset');
      $form->setFieldAttribute('state', 'filter', 'unset');
    }

    // Prevent messing with point language and category when editing existing point with associations
    $app = JFactory::getApplication();
    $assoc = JLanguageAssociations::isEnabled();

    // Check if point is associated
    if ($this->getState('point.id') && $app->isSite() && $assoc)
    {
      $associations = JLanguageAssociations::getAssociations('com_places', '#__places_point', 'com_places.item', $id);

      // Make fields read only
      if ($associations)
      {
        $form->setFieldAttribute('language', 'readonly', 'true');
        $form->setFieldAttribute('townid', 'readonly', 'true');
        $form->setFieldAttribute('language', 'filter', 'unset');
        $form->setFieldAttribute('townid', 'filter', 'unset');
      }
    }

    return $form;
  }

  /**
   * Method to get the data that should be injected in the form.
   *
   * @return  mixed  The data for the form.
   *
   * @since   1.6
   */
  protected function loadFormData()
  {
    // Check the session for previously entered form data.
    $app = JFactory::getApplication();
    $data = $app->getUserState('com_places.edit.point.data', array());

    if (empty($data))
    {
      $data = $this->getItem();

      // Prime some default values.
      if ($this->getState('point.id') == 0)
      {
       $filters = (array) $app->getUserState('com_places.points.filter');
       $filterregionid = isset($filters['region_id']) ? $filters['region_id'] : null;

       $data->set('regionid', $app->input->getInt('regionid', $filterregionid));
      }
    }

    $this->preprocessData('com_places.point', $data);

    return $data;
  }

  /**
   * Method to save the form data.
   *
   * @param   array  $data  The form data.
   *
   * @return  boolean  True on success.
   *
   * @since   1.6
   */
  public function save($data)
  {
    $input = JFactory::getApplication()->input;
    $filter  = JFilterInput::getInstance();

    if (isset($data['metadata']) && isset($data['metadata']['author']))
    {
      $data['metadata']['author'] = $filter->clean($data['metadata']['author'], 'TRIM');
    }

    if (isset($data['created_by_alias']))
    {
      $data['created_by_alias'] = $filter->clean($data['created_by_alias'], 'TRIM');
    }

    if (isset($data['images']) && is_array($data['images']))
    {
      $registry = new Registry;
      $registry->loadArray($data['images']);
      $data['images'] = (string) $registry;
    }

    if (isset($data['urls']) && is_array($data['urls']))
    {
      foreach ($data['urls'] as $i => $url)
      {
        if ($url != false && ($i == 'urla' || $i == 'urlb' || $i == 'urlc'))
        {
          $data['urls'][$i] = JStringPunycode::urlToPunycode($url);
        }
      }

      $registry = new Registry;
      $registry->loadArray($data['urls']);
      $data['urls'] = (string) $registry;
    }

    // Alter the title for save as copy
    if ($input->get('task') == 'save2copy')
    {
      $origTable = clone $this->getTable();
      $origTable->load($input->getInt('id'));

      if ($data['title'] == $origTable->title)
      {
       list($title, $alias) = $this->generateNewTitle($data['townid'], $data['alias'], $data['title']);
       $data['title'] = $title;
       $data['alias'] = $alias;
      }
      else
      {
        if ($data['alias'] == $origTable->alias)
        {
          $data['alias'] = '';
        }
      }

      $data['state'] = 0;
    }

    // Automatic handling of alias for empty fields
    if (in_array($input->get('task'), array('apply', 'save', 'save2new')) && (int) $input->get('id') == 0)
    {
      if ($data['alias'] == null)
      {
        if (JFactory::getConfig()->get('unicodeslugs') == 1)
        {
          $data['alias'] = JFilterOutput::stringURLUnicodeSlug($data['title']);
        }
        else
        {
          $data['alias'] = JFilterOutput::stringURLSafe($data['title']);
        }

        $table = JTable::getInstance('Points', 'PlacesTable');

        if ($table->load(array('alias' => $data['alias'], 'townid' => $data['townid'])))
        {
          $msg = JText::_('COM_PLACES_SAVE_WARNING');
        }

        //list($title, $alias) = $this->generateNewTitle($data['regionid'], $data['alias'], $data['title']);
        $data['alias'] = $alias;

        if (isset($msg))
        {
          JFactory::getApplication()->enqueueMessage($msg, 'warning');
        }
      }
    }

    if (parent::save($data))
    {
      //TODO:
      // if (isset($data['featured']))
      // {
      //  $this->featured($this->getState($this->getName() . '.id'), $data['featured']);
      // }

      $assoc = JLanguageAssociations::isEnabled();
      if ($assoc)
      {
        $id = (int) $this->getState($this->getName() . '.id');
        $item = $this->getItem($id);

        // Adding self to the association
        $associations = $data['associations'];

        foreach ($associations as $tag => $id)
        {
          if (empty($id))
          {
            unset($associations[$tag]);
          }
        }

        // Detecting all item menus
        $all_language = $item->language == '*';

        if ($all_language && !empty($associations))
        {
          JError::raiseNotice(403, JText::_('COM_CONTENT_ERROR_ALL_LANGUAGE_ASSOCIATED'));
        }

        $associations[$item->language] = $item->id;

        // Deleting old association for these items
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
          ->delete('#__associations')
          ->where('context=' . $db->quote('com_places.item'))
          ->where('id IN (' . implode(',', $associations) . ')');
        $db->setQuery($query);
        $db->execute();

        if ($error = $db->getErrorMsg())
        {
          $this->setError($error);

          return false;
        }

        if (!$all_language && count($associations))
        {
          // Adding new association for these items
          $key = md5(json_encode($associations));
          $query->clear()
            ->insert('#__associations');

          foreach ($associations as $id)
          {
            $query->values($id . ',' . $db->quote('com_places.item') . ',' . $db->quote($key));
          }

          $db->setQuery($query);
          $db->execute();

          if ($error = $db->getErrorMsg())
          {
            $this->setError($error);
            return false;
          }
        }
      }

      return true;
    }

    return false;
  }

  /**
   * Method to toggle the featured setting of points.
   *
   * @param   array    $pks    The ids of the items to toggle.
   * @param   integer  $value  The value to toggle to.
   *
   * @return  boolean  True on success.
   */
  // public function featured($pks, $value = 0)
  // {
  //  die('TODO: com_places/model/point.php @featured');
  //  return false;
  //  // Sanitize the ids.
  //  $pks = (array) $pks;
  //  JArrayHelper::toInteger($pks);

  //  if (empty($pks))
  //  {
  //    $this->setError(JText::_('COM_CONTENT_NO_ITEM_SELECTED'));

  //    return false;
  //  }

  //  $table = $this->getTable('Featured', 'ContentTable');

  //  try
  //  {
  //    $db = $this->getDbo();
  //    $query = $db->getQuery(true)
  //          ->update($db->quoteName('#__places_point'))
  //          ->set('featured = ' . (int) $value)
  //          ->where('id IN (' . implode(',', $pks) . ')');
  //    $db->setQuery($query);
  //    $db->execute();

  //    if ((int) $value == 0)
  //    {
  //      // Adjust the mapping table.
  //      // Clear the existing features settings.
  //      $query = $db->getQuery(true)
  //            ->delete($db->quoteName('#__content_frontpage'))
  //            ->where('content_id IN (' . implode(',', $pks) . ')');
  //      $db->setQuery($query);
  //      $db->execute();
  //    }
  //    else
  //    {
  //      // First, we find out which of our new featured points are already featured.
  //      $query = $db->getQuery(true)
  //        ->select('f.content_id')
  //        ->from('#__content_frontpage AS f')
  //        ->where('content_id IN (' . implode(',', $pks) . ')');
  //      $db->setQuery($query);

  //      $old_featured = $db->loadColumn();

  //      // We diff the arrays to get a list of the points that are newly featured
  //      $new_featured = array_diff($pks, $old_featured);

  //      // Featuring.
  //      $tuples = array();

  //      foreach ($new_featured as $pk)
  //      {
  //        $tuples[] = $pk . ', 0';
  //      }

  //      if (count($tuples))
  //      {
  //        $db = $this->getDbo();
  //        $columns = array('content_id', 'ordering');
  //        $query = $db->getQuery(true)
  //          ->insert($db->quoteName('#__content_frontpage'))
  //          ->columns($db->quoteName($columns))
  //          ->values($tuples);
  //        $db->setQuery($query);
  //        $db->execute();
  //      }
  //    }
  //  }
  //  catch (Exception $e)
  //  {
  //    $this->setError($e->getMessage());
  //    return false;
  //  }

  //  $table->reorder();

  //  $this->cleanCache();

  //  return true;
  // }

  /**
   * A protected method to get a set of ordering conditions.
   *
   * @param   object  $table  A record object.
   *
   * @return  array  An array of conditions to add to add to ordering queries.
   *
   * @since   1.6
   */
  // protected function getReorderConditions($table)
  // {
  //  $condition = array();
  //  $condition[] = 'regionid = ' . (int) $table->regionid;

  //  return $condition;
  // }

  /**
   * Auto-populate the model state.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @param   JForm   $form   The form object
   * @param   array   $data   The data to be merged into the form object
   * @param   string  $group  The plugin group to be executed
   *
   * @return  void
   *
   * @since    3.0
   */
  protected function preprocessForm(JForm $form, $data, $group = 'points')
  {
    // Association places items
    $app = JFactory::getApplication();
    $assoc = JLanguageAssociations::isEnabled();

    if ($assoc)
    {
      $languages = JLanguageHelper::getLanguages('lang_code');
      $addform = new SimpleXMLElement('<form />');
      $fields = $addform->addChild('fields');
      $fields->addAttribute('name', 'associations');
      $fieldset = $fields->addChild('fieldset');
      $fieldset->addAttribute('name', 'item_associations');
      $fieldset->addAttribute('description', 'COM_CONTENT_ITEM_ASSOCIATIONS_FIELDSET_DESC');
      $add = false;

      foreach ($languages as $tag => $language)
      {
        if (empty($data->language) || $tag != $data->language)
        {
          $add = true;
          $field = $fieldset->addChild('field');
          $field->addAttribute('name', $tag);
          $field->addAttribute('type', 'modal_point');
          $field->addAttribute('language', $tag);
          $field->addAttribute('label', $language->title);
          $field->addAttribute('translate_label', 'false');
          $field->addAttribute('edit', 'true');
          $field->addAttribute('clear', 'true');
        }
      }
      if ($add)
      {
        $form->load($addform, false);
      }
    }

    parent::preprocessForm($form, $data, $group);
  }

  /**
   * Custom clean the cache of com_places and places modules
   *
   * @param   string   $group      The cache group
   * @param   integer  $client_id  The ID of the client
   *
   * @return  void
   *
   * @since   1.6
   */
  protected function cleanCache($group = null, $client_id = 0)
  {
    parent::cleanCache('com_places');
    parent::cleanCache('mod_towns_archive');
  }

}
