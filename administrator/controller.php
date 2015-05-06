<?php


defined('_JEXEC') or die;


class PlacesController extends JControllerLegacy
{
	
	protected $default_view = 'points';
	
	public function display($cachable = false, $urlparams = false)
	{
		
		require_once JPATH_COMPONENT.'/helpers/places.php';


		$view   = $this->input->get('view', 'points');
		$layout = $this->input->get('layout', 'default');
		$id     = $this->input->getInt('id');
		// Check for edit form.
		if ($view == 'point' && $layout == 'edit' && !$this->checkEditId('com_places.edit.point', $id))
		{
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_places&view=points', false));

			return false;
		}
		
		if ($view == 'town' && $layout == 'edit' && !$this->checkEditId('com_places.edit.town', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_places&view=towns', false));

			return false;
		}
		
		parent::display();

		return $this;
	}
}
