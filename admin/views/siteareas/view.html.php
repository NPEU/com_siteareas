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
 * SiteAreas SiteAreas View
 */
class SiteAreasViewSiteAreas extends JViewLegacy
{
    protected $items;

    protected $pagination;

    protected $state;

    /**
     * Display the SiteAreas view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     */
    function display($tpl = null)
    {
        $this->state         = $this->get('State');
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors.
        if (count($errors = $this->get('Errors')))
        {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     */
    protected function addToolBar()
    {
        //$canDo = SiteAreasHelper::getActions();
        $canDo = JHelperContent::getActions('com_siteareas');
        $user  = JFactory::getUser();

        $title = JText::_('COM_SITEAREAS_MANAGER_RECORDS');

        if ($this->pagination->total) {
            $title .= "<span style='font-size: 0.5em; vertical-align: middle;'> (" . $this->pagination->total . ")</span>";
        }

        JToolBarHelper::title($title, 'tree-2');
        /*
        JToolBarHelper::addNew('sitearea.add');
        if (!empty($this->items)) {
            JToolBarHelper::editList('sitearea.edit');
            JToolBarHelper::deleteList('', 'siteareas.delete');
        }
        */
        if ($canDo->get('core.create') || count($user->getAuthorisedCategories('com_siteareas', 'core.create')) > 0) {
            JToolbarHelper::addNew('sitearea.add');
        }

        if ($canDo->get('core.edit') || $canDo->get('core.edit.own'))
        {
            JToolbarHelper::editList('sitearea.edit');
        }

        if ($canDo->get('core.edit.state'))
        {
            JToolbarHelper::publish('siteareas.publish', 'JTOOLBAR_PUBLISH', true);
            JToolbarHelper::unpublish('siteareas.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            //JToolbarHelper::custom('sitearea.featured', 'featured.png', 'featured_f2.png', 'JFEATURE', true);
            //JToolbarHelper::custom('sitearea.unfeatured', 'unfeatured.png', 'featured_f2.png', 'JUNFEATURE', true);
            //JToolbarHelper::archiveList('sitearea.archive');
            //JToolbarHelper::checkin('sitearea.checkin');
        }


        if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
        {
            JToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'siteareas.delete', 'JTOOLBAR_EMPTY_TRASH');
        }
        elseif ($canDo->get('core.edit.state'))
        {
            JToolbarHelper::trash('siteareas.trash');
        }

        if ($user->authorise('core.admin', 'com_siteareas') || $user->authorise('core.options', 'com_siteareas'))
        {
            JToolbarHelper::preferences('com_siteareas');
        }
    }

    /**
     * Method to set up the document properties
     *
     * @return void
     */
    protected function setDocument()
    {
        $document = JFactory::getDocument();
        $document->setTitle(JText::_('COM_SITEAREAS_ADMINISTRATION'));
    }

    /**
     * Returns an array of fields the table can be sorted by
     *
     * @return  array  Array containing the field name to sort by as the key and display text as value
     */
    protected function getSortFields()
    {
        return array(
            'a.name' => JText::_('COM_SITEAREAS_RECORDS_NAME'),
            'a.owner_user_id' => JText::_('COM_SITEAREAS_RECORDS_OWNER'),
            'a.state' => JText::_('COM_SITEAREAS_PUBLISHED'),
            'a.id'    => JText::_('COM_SITEAREAS_ID')
        );
    }
}
