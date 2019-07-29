<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * SiteAreasHelper component helper.
 */
class SiteAreasHelper extends JHelperContent
{
    /**
     * Add style
     */
    public static function addStyle()
    {
        // Set some global property
        $document = JFactory::getDocument();
        // Update this with icon of choice from:
        // /administrator/templates/isis/css/template.css
        $document->addStyleDeclaration('.icon-sitearea:before {content: "\e244";}');
    }

    /**
     * Configure the Submenu. Delete if component has only one view.
     *
     * @param   string  The name of the active view.
     */
    public static function addSubmenu($vName = 'siteareas')
    {
        JHtmlSidebar::addEntry(
            JText::_('COM_SITEAREAS_MANAGER_SUBMENU_RECORDS'),
            'index.php?option=com_siteareas&view=siteareas',
            $vName == 'siteareas'
        );

        JHtmlSidebar::addEntry(
            JText::_('COM_SITEAREAS_MANAGER_SUBMENU_CATEGORIES'),
            'index.php?option=com_categories&view=categories&extension=com_siteareas',
            $vName == 'categories'
        );
    }
}
