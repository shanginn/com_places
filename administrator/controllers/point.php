<?php

defined('_JEXEC') or die;

class PlacesControllerPoint extends JControllerForm
{
  
	/**
   * Class constructor.
   *
   * @param   array  $config  A named array of configuration variables.
   *
   * @since   1.6
   */
  public function __construct($config = array())
  {
    parent::__construct($config);

    // A point edit form can come from the towns or featured view.
    // Adjust the redirect view on the value of 'return' in the request.
    if ($this->input->get('return') == 'featured')
    {
      $this->view_list = 'featured';
      $this->view_item = 'point&return=featured';
    }
  }
	
  /**
   * Method override to check if you can add a new record.
   *
   * @param   array  $data  An array of input data.
   *
   * @return  boolean
   *
   * @since   1.6
   */
  protected function allowAdd($data = array())
  {
    $user = JFactory::getUser();
    $regionId = JArrayHelper::getValue($data, 'townid', $this->input->getInt('filter_town_id'), 'int');
    $allow = null;

    if ($regionId)
    {
      // If the category has been passed in the data or URL check it.
      $allow = $user->authorise('core.create', 'com_places.town.' . $regionId);
    }

    if ($allow === null)
    {
      // In the absense of better information, revert to the component permissions.
      return parent::allowAdd();
    }
    else
    {
      return $allow;
    }
  }

  /**
   * Method override to check if you can edit an existing record.
   *
   * @param   array   $data  An array of input data.
   * @param   string  $key   The name of the key for the primary key.
   *
   * @return  boolean
   *
   * @since   1.6
   */
  protected function allowEdit($data = array(), $key = 'id')
  {
    $recordId = (int) isset($data[$key]) ? $data[$key] : 0;
    $user = JFactory::getUser();
    $userId = $user->get('id');

    // Check general edit permission first.
    if ($user->authorise('core.edit', 'com_places.point.' . $recordId))
    {
      return true;
    }

    // Fallback on edit.own.
    // First test if the permission is available.
    if ($user->authorise('core.edit.own', 'com_places.point.' . $recordId))
    {
      // Now test the owner is the user.
      $ownerId = (int) isset($data['created_by']) ? $data['created_by'] : 0;
      if (empty($ownerId) && $recordId)
      {
        // Need to do a lookup from the model.
        $record = $this->getModel()->getItem($recordId);

        if (empty($record))
        {
          return false;
        }

        $ownerId = $record->created_by;
      }

      // If the owner matches 'me' then do the test.
      if ($ownerId == $userId)
      {
        return true;
      }
    }

    // Since there is no asset tracking, revert to the component permissions.
    return parent::allowEdit($data, $key);
  }

  /**
   * Method to run batch operations.
   *
   * @param   object  $model  The model.
   *
   * @return  boolean   True if successful, false otherwise and internal error is set.
   *
   * @since   1.6
   */
  public function batch($model = null)
  {
    JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

    // Set the model
    $model = $this->getModel('Point', '', array());

    // Preset the redirect
    $this->setRedirect(JRoute::_('index.php?option=com_places&view=points' . $this->getRedirectToListAppend(), false));

    return parent::batch($model);
  }

  /**
   * Function that allows child controller access to model data after the data has been saved.
   *
   * @param   JModelLegacy  $model      The data model object.
   * @param   array         $validData  The validated data.
   *
   * @return  void
   *
   * @since 3.1
   */
  protected function postSaveHook(JModelLegacy $model, $validData = array())
  {

    return;
  }
}
