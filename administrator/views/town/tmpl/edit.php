<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_banners
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

$this->hiddenFieldsets = array();
$this->hiddenFieldsets[0] = 'basic-limited';
$this->configFieldsets = array();
$this->configFieldsets[0] = 'editorConfig';

// Create shortcut to parameters.
$params = $this->state->get('params');

$app = JFactory::getApplication();
$input = $app->input;
$assoc = JLanguageAssociations::isEnabled();

$basePath = JURI::root(true) . '/administrator/components/com_places/';
$doc = JFactory::getDocument();
$doc->addScript('//api-maps.yandex.ru/2.1/?lang=ru_RU')
    ->addScript($basePath . 'assets/js/places_functions_adm.js')
    ->addScript($basePath . 'assets/js/places_town_adm.js')
    ->addCustomTag('<link rel="stylesheet" href="' . $basePath .'assets/css/places_adm.css" type="text/css">');


// This checks if the config options have ever been saved. If they haven't they will fall back to the original settings.
$params = json_decode($params);
$editoroptions = isset($params->show_publishing_options);

if (!$editoroptions)
{
  $params->show_publishing_options = '1';
  $params->show_town_options = '1';
  $params->show_urls_images_backend = '0';
  $params->show_urls_images_frontend = '0';
}

// Check if the town uses configuration settings besides global. If so, use them.
if (isset($this->item->attribs['show_publishing_options']) && $this->item->attribs['show_publishing_options'] != '')
{
  $params->show_publishing_options = $this->item->attribs['show_publishing_options'];
}

if (isset($this->item->attribs['show_town_options']) && $this->item->attribs['show_town_options'] != '')
{
  $params->show_town_options = $this->item->attribs['show_town_options'];
}

if (isset($this->item->attribs['show_urls_images_frontend']) && $this->item->attribs['show_urls_images_frontend'] != '')
{
  $params->show_urls_images_frontend = $this->item->attribs['show_urls_images_frontend'];
}

if (isset($this->item->attribs['show_urls_images_backend']) && $this->item->attribs['show_urls_images_backend'] != '')
{
  $params->show_urls_images_backend = $this->item->attribs['show_urls_images_backend'];
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_places&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

  <div class="control-group">
    <div class="control-label">
      <?php echo $this->form->getLabel('title'); ?>
    </div>
    <div class="controls">
      <div class="input-append">
        <?php echo $this->form->getInput('title'); ?>
        <button id="addressSearchBtn" class="btn" type="button"><i class="icon icon-search"> </i></button>
      </div>
    </div>
  </div>

  <div class="form-horizontal">
    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'general', JText::_('COM_PLACES_TOWN_INFO', true)); ?>
    <div class="row-fluid">
      <div class="span5">
        <div class="control-group">
          <div class="control-label">
            <?php echo $this->form->getLabel('lat') ?> / <?php echo $this->form->getLabel('lng'); ?>
          </div>
          <div class="controls">
            <div class="input-append">
              <?php echo $this->form->getInput('lat'); ?>
              <?php echo $this->form->getInput('lng'); ?>
              <button id="coordsSearchBtn" class="btn" type="button"><i class="icon icon-search"> </i></button>
            </div>
          </div>
        </div>
        <?php echo $this->form->getField('alias')->getControlGroup(); ?>
        <?php echo $this->form->getField('phone')->getControlGroup(); ?>
        <?php echo $this->form->getField('email')->getControlGroup(); ?>
        <?php echo $this->form->getField('state')->getControlGroup(); ?>
      </div>
      <div class="span7">
        <div id="ymap" style="height: 400px; width: 100%">
          
        </div>
      </div>
    </div>
    <?php echo JHtml::_('bootstrap.endTab'); ?>
    
    <?php // Do not show the publishing options if the edit form is configured not to. ?>
    <?php if ($params->show_publishing_options == 1) : ?>
      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('COM_CONTENT_FIELDSET_PUBLISHING', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
        <div class="span6">
          <?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
        </div>
        <div class="span6">
          <?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
        </div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>
    <?php endif; ?>

    <?php // Do not show the images and links options if the edit form is configured not to. ?>
    <?php if ($params->show_urls_images_backend == 1) : ?>
      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'images', JText::_('COM_CONTENT_FIELDSET_URLS_AND_IMAGES', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
        <div class="span6">
          <?php echo $this->form->getControlGroup('images'); ?>
          <?php foreach ($this->form->getGroup('images') as $field) : ?>
            <?php echo $field->getControlGroup(); ?>
          <?php endforeach; ?>
        </div>
        <div class="span6">
          <?php foreach ($this->form->getGroup('urls') as $field) : ?>
            <?php echo $field->getControlGroup(); ?>
          <?php endforeach; ?>
        </div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>
    <?php endif; ?>

    <?php if ($assoc) : ?>
      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'associations', JText::_('JGLOBAL_FIELDSET_ASSOCIATIONS', true)); ?>
        <?php echo $this->loadTemplate('associations'); ?>
      <?php echo JHtml::_('bootstrap.endTab'); ?>
    <?php endif; ?>

    <?php $this->show_options = $params->show_town_options; ?>
    <?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>

    <?php if ($this->canDo->get('core.admin')) : ?>
      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'editor', JText::_('COM_CONTENT_SLIDER_EDITOR_CONFIG', true)); ?>
      <?php echo $this->form->renderFieldset('editorConfig'); ?>
      <?php echo JHtml::_('bootstrap.endTab'); ?>
    <?php endif; ?>

    <?php if ($this->canDo->get('core.admin')) : ?>
      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('COM_CONTENT_FIELDSET_RULES', true)); ?>
        <?php echo $this->form->getInput('rules'); ?>
      <?php echo JHtml::_('bootstrap.endTab'); ?>
    <?php endif; ?>

    <?php echo JHtml::_('bootstrap.endTabSet'); ?>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="return" value="<?php echo $input->getCmd('return'); ?>" />
    <?php echo JHtml::_('form.token'); ?>

    </div>
</form>
