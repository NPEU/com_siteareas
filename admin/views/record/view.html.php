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
 * SiteAreas Record View
 */
class SiteAreasViewRecord extends JViewLegacy
{
    /**
     * View form
     *
     * @var         form
     */
    protected $form = null;

    /**
     * Display the SiteAreas view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        // Get the Data
        $this->form   = $this->get('Form');
        $this->item   = $this->get('Item');
        $this->script = $this->get('Script');
        $this->canDo  = SiteAreasHelper::getActions($this->item->id, $this->getModel());

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode('<br />', $errors));

            return false;
        }

        // Set the toolbar
        $this->addToolBar();

        // Display the template
        parent::display($tpl);

        // Set the document
        $this->setDocument();
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     */
    protected function addToolBar()
    {
        // Hide Joomla Administrator Main menu:
        JFactory::getApplication()->input->set('hidemainmenu', true);

        $user       = JFactory::getUser();
        $userId     = $user->id;


        $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
        $isNew = ($this->item->id == 0);

        // Build the actions for new and existing records.
        $canDo = $this->canDo;

        /*if ($isNew) {
            $title = JText::_('COM_SITEAREAS_MANAGER_RECORD_NEW');
        } else {
            $title = JText::_('COM_SITEAREAS_MANAGER_RECORD_EDIT');
        }

        JToolBarHelper::title($title, 'record');*/

        JToolbarHelper::title(
            JText::_('COM_SITEAREAS_MANAGER_' . ($checkedOut ? 'RECORD_VIEW' : ($isNew ? 'RECORD_ADD' : 'RECORD_EDIT'))),
            'palette'
        );

        // For new records, check the create permission.
        if ($isNew && (count($user->getAuthorisedCategories('com_siteareas', 'core.create')) > 0)) {
            JToolbarHelper::apply('record.apply');
            JToolbarHelper::save('record.save');
            JToolbarHelper::save2new('record.save2new');
            JToolbarHelper::cancel('record.cancel');
        } else {
            // Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
            $itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);

            // Can't save the record if it's checked out and editable
            if (!$checkedOut && $itemEditable) {
                JToolbarHelper::apply('record.apply');
                JToolbarHelper::save('record.save');

                // We can save this record, but check the create permission to see if we can return to make a new one.
                if ($canDo->get('core.create')) {
                    JToolbarHelper::save2new('record.save2new');
                }
            }

            // Leaving this out. See note in models/record.php above comment:
            // "Alter the name for save as copy" for explanation.
            // If checked out, we can still save
            /*if ($canDo->get('core.create')) {
                JToolbarHelper::save2copy('record.save2copy');
            }*/

            JToolbarHelper::cancel('record.cancel', 'JTOOLBAR_CLOSE');
        }
    }

    /**
     * Method to set up the document properties
     *
     * @return void
     */
    protected function setDocument()
    {
        $isNew = ($this->item->id < 1);
        $document = JFactory::getDocument();
        $document->setTitle($isNew ? JText::_('COM_SITEAREAS_RECORD_CREATING') :
                JText::_('COM_SITEAREAS_RECORD_EDITING'));
        $document->addScript(JURI::root() . $this->script);
        $document->addScript(JURI::root() . "/administrator/components/com_siteareas"
                                          . "/views/record/submitbutton.js");
        JText::script('COM_SITEAREAS_RECORD_ERROR_UNACCEPTABLE');
    }
}
