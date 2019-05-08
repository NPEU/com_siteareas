<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;
ini_set('display_errors', 'On');
/**
 * SiteAreas Component Controller
 */
class SiteAreasController extends JControllerLegacy
{
    /**
     * The default view for the display method.
     *
     * @var string
     */
    protected $default_view = 'records';
    
    /**
     * display task
     *
     * @return void
     */
    function display($cachable = false, $urlparams = false)
    {
        // Set default view if not set
        //JFactory::getApplication()->input->set('view', JFactory::getApplication()->input->get('view', 'records'));

        //$session = JFactory::getSession();
        //$registry = $session->get('registry');

        // call parent behavior
        parent::display($cachable, $urlparams);

        // Add style
        SiteAreasHelper::addStyle();
    }
}
