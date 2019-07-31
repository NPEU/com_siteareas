<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\String\StringHelper;

/**
 * SiteAreas SiteArea Model
 */
class SiteAreasModelSiteArea extends JModelAdmin
{
    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $type    The table name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  JTable  A JTable object
     */
    public function getTable($type = 'SiteAreas', $prefix = 'SiteAreasTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  mixed    A JForm object on success, false on failure
     */
    public function getForm($data = array(), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm(
            'com_siteareas.sitearea',
            'sitearea',
            array(
                'control' => 'jform',
                'load_data' => $loadData
            )
        );

        if (empty($form))
        {
            return false;
        }

        // Determine correct permissions to check.
        /*if ($this->getState('sitearea.id'))
        {
            // Existing record. Can only edit in selected categories.
            $form->setFieldAttribute('catid', 'action', 'core.edit');
        }
        else
        {
            // New record. Can only create in selected categories.
            $form->setFieldAttribute('catid', 'action', 'core.create');
        }*/

        // Modify the form based on access controls.
        if (!$this->canEditState((object) $data))
        {
            // Disable fields for display.
            $form->setFieldAttribute('state', 'disabled', 'true');
            $form->setFieldAttribute('publish_up', 'disabled', 'true');
            $form->setFieldAttribute('publish_down', 'disabled', 'true');

            // Disable fields while saving.
            // The controller has already verified this is a record you can edit.
            $form->setFieldAttribute('state', 'filter', 'unset');
            $form->setFieldAttribute('publish_up', 'filter', 'unset');
            $form->setFieldAttribute('publish_down', 'filter', 'unset');
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = JFactory::getApplication()->getUserState(
            'com_siteareas.edit.siteareas.data',
            array()
        );

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Prepare and sanitise the table data prior to saving.
     *
     * @param   JTable  $table  A reference to a JTable object.
     *
     * @return  void
     */
    protected function prepareTable($table)
    {
        $date = JFactory::getDate();
        $user = JFactory::getUser();

        $table->name = htmlspecialchars_decode($table->name, ENT_QUOTES);
        $table->alias = JApplicationHelper::stringURLSafe($table->alias);

        if (empty($table->alias))
        {
            $table->alias = JApplicationHelper::stringURLSafe($table->name);
        }

        if (empty($table->id))
        {
            // Set the values
            $table->modified    = $date->toSql();
            $table->modified_by = $user->id;
        }
    }

    /**
     * Method to prepare the saved data.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success, False on error.
     */
    public function save($data)
    {
        $is_new = empty($data['id']);
        $input  = JFactory::getApplication()->input;
        $app    = JFactory::getApplication();

        // Get parameters:
        $params = JComponentHelper::getParams(JRequest::getVar('option'));


        // Alter the name for save as copy
        if ($app->input->get('task') == 'save2copy')
        {
            list($name, $alias) = $this->generateNewTitle(null, $data['alias'], $data['name']);
            $data['name']    = $name;
            $data['alias']    = $alias;
            $data['state']    = 0;
        }

        // Automatic handling of alias for empty fields
        // Taken from com_content/models/article.php
        if (in_array($input->get('task'), array('apply', 'save', 'save2new'))) {
            if (empty($data['alias'])) {
                if (JFactory::getConfig()->get('unicodeslugs') == 1) {
                    $data['alias'] = JFilterOutput::stringURLUnicodeSlug($data['name']);
                } else {
                    $data['alias'] = JFilterOutput::stringURLSafe($data['name']);
                }

                $table = JTable::getInstance('SiteAreas', 'SiteAreasTable');

                if ($table->load(array('alias' => $data['alias']))) {
                    $msg = JText::_('COM_CONTENT_SAVE_WARNING');
                }

                #list($name, $alias) = $this->generateNewSiteAreasTitle($data['alias'], $data['name']);
                list($name, $alias) = $this->generateNewTitle($data['alias'], $data['name']);
                $data['alias'] = $alias;

                if (isset($msg)) {
                    JFactory::getApplication()->enqueueMessage($msg, 'warning');
                }
            }
        }

        // Need to generate an Admin Group if one isn't selected:
        if (empty($data['admin_group_id'])) {

            $group = array(
                'id'       => 0,
                'title'    => $data['name'] . ' admin',
                'parent_id'=> 11
            );

            JLoader::import('group', JPATH_ADMINISTRATOR . '/components/com_users/models');
            $groupModel = JModelLegacy::getInstance('Group', 'UsersModel');

            if (!$groupModel->save($group)) {
               JFactory::getApplication()->enqueueMessage($groupModel->getError());
               return false;
            }

            $data['admin_group_id'] = $groupModel->getState('group.id');
        }

        // Need to add the Owner to the Group:
        $owner = JFactory::getUser($data['owner_user_id']);
        $owner->set('groups', array_merge($owner->get('groups'), array($data['admin_group_id'])));
        $owner->save();



        // Need to create a menu item and add the new ID to the data if one doesn't exist:
        // https://stackoverflow.com/questions/12651075/programmatically-create-menu-item-in-joomla

        // Need to act upon the selected menu type in order to generate the correct link.
        // This would seem impossible to do to try and support every menu type so may have to
        // abandon the option to choose the link type here - I think it's too complicated.
        // Just creating an heading, and providing a link to the menu item so it can manually set.


        //index.php?option=com_bespoke&view=bespoke
        //index.php?option=com_content&view=article&id=1668

        //type = 'heading' (link can be empty)
        //id  menutype    title   alias   note    path    link    type    state   parent_id   level   component_id    checked_out checked_out_time    browserNav  access  img template_style_id   params  lft rgt home    language    client_id
        //962 mainmenu    Test Placeholder    test-placeholder    ""  test-placeholder    ""  heading 0   1   1   0   0   29/12/1899  0   1   ""  0   "{""menu-anchor_title"":"""",""menu-anchor_css"":"""",""menu_image"":"""",""menu_image_css"":"""",""menu_text"":1,""menu_show"":1}" 1359    1360    0   *   0
        //$link = 'index.php?option=com_content&view=article&id='.$resultID,
        if (empty($data['root_menu_item_id'])) {
           $menuItem = array(
                'menutype'     => 'mainmenu',
                'title'        => $data['name'],
                'alias'        => $data['alias'],
                'path'         => $data['alias'],
                'link'         => '',
                'type'         => 'heading',
                'state'        => 0,
                'parent_id'    => 1,
                'level'        => 1,
                'component_id' => 0,
                'language'     => '*'
            );

            $menuTable = JTable::getInstance('Menu', 'JTable', array());

            $menuTable->setLocation(1, 'last-child');

            if (!$menuTable->save($menuItem)) {
                throw new Exception($menuTable->getError());
                return false;
            }

            $data['root_menu_item_id'] = $menuTable->id;
        }


        // Respond to Category autogenerate:
        if ($data['params']['root_catid'] == 'autogenerate') {
            // JTableCategory is autoloaded in J! 3.0, so...
            if (version_compare(JVERSION, '3.0', 'lt')) {
                JTable::addIncludePath(JPATH_PLATFORM . 'joomla/database/table');
            }

            // Initialize a new category
            $category = JTable::getInstance('Category');
            $category->extension = 'com_content';
            $category->title = $data['name'];
            $category->alias = $data['alias'];
            //$category->description = 'A category for my extension';
            $category->published = 1;
            $category->access = 1;
            $category->params = '{"category_layout":"","image":"","image_alt":""}';
            $category->metadata = '{"author":"","robots":""}';
            $category->language = '*';

            // Set the location in the tree
            $category->setLocation(1, 'last-child');

            // Check to make sure our data is valid
            if (!$category->check()) {
                JError::raiseNotice(500, $category->getError());
                return false;
            }

            // Now store the category
            if (!$category->store(true)) {
                JError::raiseNotice(500, $category->getError());
                return false;
            }

            // Build the path for our category
            $category->rebuildPath($category->id);
            $data['params']['root_catid'] = (string) $category->id;
        }


        // Respond to Brand autogenerate:
        if ($data['params']['brand_id'] == 'autogenerate') {
            $new_brand = array();
            $new_brand['id'] = '';
            $new_brand['name'] = $data['name'];
            $new_brand['alias'] = $data['alias'];
            $new_brand['catid'] = '151';

            JLoader::import('brand', JPATH_ADMINISTRATOR . '/components/com_brands/models');
            JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_brands/tables');
            $brandModel = JModelLegacy::getInstance('Brand', 'BrandsModel');

            if (!$brandModel->save($new_brand)) {
               JFactory::getApplication()->enqueueMessage($brandModel->getError());
               return false;
            }
            $data['params']['brand_id'] = (string) $brandModel->getState('brand.id');
        }


        // Respond to Template Style autogenerate:
        if ($data['params']['template_style_id'] == 'autogenerate') {
            // Get the base template params:
            $template        = explode(',', $params->get('template'));
            $template_name   = $template[0];
            $template_ext_id = $template[1];
            $template_title  = $template[2];

            // Build the query.
            $query = $db->getQuery(true);
            $query->select('params')
                ->from('#__extensions')
                ->where('extension_id = ' . (int) $template_ext_id);

            // Set the query and load the data.
            $db->setQuery($query);
            $default_params = $db->loadResult();

            $new_style = array();
            $new_style['template']  = $template_name;
            $new_style['client_id'] = '0';
            $new_style['home']      = '0';
            $new_style['title']     = $template_title . ' - ' . $data['name'];
            $new_style['params']    = $default_params;

            JLoader::import('style', JPATH_ADMINISTRATOR . '/components/com_templates/models');
            JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_templates/tables');
            $templateStylesModel = JModelLegacy::getInstance('Style', 'TemplatesModel');

            if (!$templateStylesModel->save($new_style)) {
               JFactory::getApplication()->enqueueMessage($templateStylesModel->getError());
               return false;
            }

            $data['params']['template_style_id'] = (string) $templateStylesModel->getState('style.id');
        }


        return parent::save($data);
    }

    /**
     * Method to change the title & alias.
     *
     * @param   integer  $category_id  The id of the parent.
     * @param   string   $alias        The alias.
     * @param   string   $name         The title.
     *
     * @return  array  Contains the modified title and alias.
     */
    protected function generateNewTitle($category_id, $alias, $name)
    {
        // Alter the name & alias
        $table = $this->getTable();

        while ($table->load(array('alias' => $alias)))
        {
            if ($name == $table->name)
            {
                $name = JString::increment($name);
            }

            $alias = JString::increment($alias, 'dash');
        }

        return array($name, $alias);
    }
}
