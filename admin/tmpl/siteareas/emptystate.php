<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2023.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

$displayData = [
    'textPrefix' => 'COM_SITEAREAS',
    'formURL'    => 'index.php?option=com_siteareas',
];

/*
$displayData = [
    'textPrefix' => 'COM_SITEAREAS',
    'formURL'    => 'index.php?option=com_siteareas',
    'helpURL'    => '',
    'icon'       => 'icon-globe siteareas',
];
*/

$user = Factory::getApplication()->getIdentity();

if ($user->authorise('core.create', 'com_siteareas') || count($user->getAuthorisedCategories('com_siteareas', 'core.create')) > 0) {
    $displayData['createURL'] = 'index.php?option=com_siteareas&task=sitearea.add';
}

echo LayoutHelper::render('joomla.content.emptystate', $displayData);