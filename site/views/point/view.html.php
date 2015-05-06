<?php

defined('_JEXEC') or die;

class PlacesViewPlace extends JViewLegacy
{
	protected $items;
	protected $state;


	public function display($tpl = null)
	{
		
		$this->items = $this->get('Items');
		$this->towns = $this->get('Towns');
		$this->state = $this->get('State');

		$this->assignRef('items', $this->items);
		$this->assignRef('towns', $this->towns);

		parent::display($tpl);
	}

	
}
