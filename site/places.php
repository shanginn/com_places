<?php

defined('_JEXEC') or die;

define('DS', DIRECTORY_SEPARATOR);

$controller = JControllerLegacy::getInstance('Places');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
