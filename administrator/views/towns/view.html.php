<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_places
 *
 * @copyright   Copyright (C) 2015 Saity74 LLC. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * View class for a list of towns.
 *
 * @since  1.6
 */
class PlacesViewTowns extends JViewLegacy
{
  protected $items;

  protected $pagination;

  protected $state;

  /**
   * Display the view
   *
   * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
   *
   * @return  void
   */
  public function display($tpl = null)
  {

    JFactory::getLanguage()->load('com_places', JPATH_ADMINISTRATOR);

    if ($this->getLayout() !== 'modal')
    {
      PlacesHelper::addSubmenu('towns');
    }

    $this->items          = $this->get('Items');
    $this->pagination     = $this->get('Pagination');
    $this->state          = $this->get('State');
    $this->authors        = $this->get('Authors');    
    $this->filterForm     = $this->get('FilterForm');
    $this->activeFilters  = $this->get('ActiveFilters');
    
    // Check for errors.
    if (count($errors = $this->get('Errors')))
    {
      JError::raiseError(500, implode("\n", $errors));

      return false;
    }

    // We don't need toolbar in the modal window.
    if ($this->getLayout() !== 'modal')
    {
      $this->addToolbar();
      $this->sidebar = JHtmlSidebar::render();
    }

    parent::display($tpl);
  }

  protected function addToolbar()
  {
    $canDo = JHelperContent::getActions('com_places', 'town', $this->state->get('filter.town_id'));
    $user  = JFactory::getUser();
    
    // Get the toolbar object instance
    $bar = JToolBar::getInstance('toolbar');

    JToolbarHelper::title(JText::_('COM_PLACES_TOWNS_MANAGER'), 'flag');

    if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_places', 'core.create'))) > 0 )
    {
      JToolbarHelper::addNew('town.add');
    }

    if (($canDo->get('core.edit')) || ($canDo->get('core.edit.own')))
    {
      JToolbarHelper::editList('town.edit');
    }

    if ($canDo->get('core.edit.state'))
    {
      JToolbarHelper::publish('towns.publish', 'JTOOLBAR_PUBLISH', true);
      JToolbarHelper::unpublish('towns.unpublish', 'JTOOLBAR_UNPUBLISH', true);
      // JToolbarHelper::custom('towns.featured', 'featured.png', 'featured_f2.png', 'JFEATURE', true);
      // JToolbarHelper::custom('towns.unfeatured', 'unfeatured.png', 'featured_f2.png', 'JUNFEATURE', true);
      JToolbarHelper::archiveList('towns.archive');
      JToolbarHelper::checkin('towns.checkin');
    }

    // Add a batch button
    if ($user->authorise('core.create', 'com_content') && $user->authorise('core.edit', 'com_content') && $user->authorise('core.edit.state', 'com_content'))
    {
      JHtml::_('bootstrap.modal', 'collapseModal');
      $title = JText::_('JTOOLBAR_BATCH');

      // Instantiate a new JLayoutFile instance and render the batch button
      $layout = new JLayoutFile('joomla.toolbar.batch');

      $dhtml = $layout->render(array('title' => $title));
      $bar->appendButton('Custom', $dhtml, 'batch');
    }

    if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
    {
      JToolbarHelper::deleteList('', 'towns.delete', 'JTOOLBAR_EMPTY_TRASH');
    }
    elseif ($canDo->get('core.edit.state'))
    {
      JToolbarHelper::trash('towns.trash');
    }

    if ($user->authorise('core.admin', 'com_places') || $user->authorise('core.options', 'com_places'))
    {
      JToolbarHelper::preferences('com_places');
    }

  }
  /**
   * Returns an array of fields the table can be sorted by
   *
   * @return  array  Array containing the field name to sort by as the key and display text as value
   *
   * @since   3.0
   */
  protected function getSortFields()
  {
    return array(
      't.ordering'     => JText::_('JGRID_HEADING_ORDERING'),
      't.state'        => JText::_('JSTATUS'),
      't.title'        => JText::_('JGLOBAL_TITLE'),
      //'region_title'   => JText::_('JCATEGORY'),
      'access_level'   => JText::_('JGRID_HEADING_ACCESS'),
      't.created_by'   => JText::_('JAUTHOR'),
      'language'       => JText::_('JGRID_HEADING_LANGUAGE'),
      't.created'      => JText::_('JDATE'),
      't.id'           => JText::_('JGRID_HEADING_ID'),
      't.featured'     => JText::_('JFEATURED')
    );
  }
}
