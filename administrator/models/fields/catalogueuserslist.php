<?php

defined('JPATH_BASE') or die;

JFormHelper::loadFieldClass('list');

require_once __DIR__ . '/../../../com_places/helpers/places.php';


class JFormFieldPlacesusersList extends JFormFieldList
{
	
	protected $type = 'PlacesusersList';

	
	public function getOptions()
	{
		return PlacesHelper::getUsersOptions();
	}
}
