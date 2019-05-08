<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;
//ini_set('display_errors', 'On');
// Force-load the Admin language file to avoid repeating form language strings:
// (this model is used in the front-end too, and the Admin lang isn't auto-loaded there.)
$lang = JFactory::getLanguage();
$extension = 'com_siteareas';
$base_dir = JPATH_COMPONENT_ADMINISTRATOR;
$language_tag = 'en-GB';
$reload = true;
$lang->load($extension, $base_dir, $language_tag, $reload);

// Set some global property
#$document = JFactory::getDocument();
#$document->addStyleDeclaration('.icon-helloworld {background-image: url(../media/com_helloworld/images/tux-16x16.png);}');

// Require helper file
#JLoader::register('HelloWorldHelper', JPATH_COMPONENT . '/helpers/helloworld.php');

// Get an instance of the controller prefixed by SiteAreas
$controller = JControllerLegacy::getInstance('SiteAreas');

// Perform the Request task
$input = JFactory::getApplication()->input;
$controller->execute($input->getCmd('task'));

// Redirect if set by the controller
$controller->redirect();
