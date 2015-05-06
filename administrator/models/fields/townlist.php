<?php

defined('JPATH_BASE') or die;

JFormHelper::loadFieldClass('list');

require_once __DIR__ . '/../../helpers/places.php';


class JFormFieldTownList extends JFormFieldList
{
	
	protected $type = 'TownsList';

	
	public function getOptions()
	{
		$options = array();

    $db   = JFactory::getDbo();
    $query  = $db->getQuery(true);

    $query->select('id As value, title As text');
    $query->from('#__places_town AS p');
    $query->order('p.ordering');

    // Get the options.
    $db->setQuery($query);

    try
    {
      $options = $db->loadObjectList();
    }
    catch (RuntimeException $e)
    {
      JError::raiseWarning(500, $e->getMessage());
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
	}
}
