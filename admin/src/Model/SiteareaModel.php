<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2023.
 * @license     MIT License; see LICENSE.md
 */

namespace NPEU\Component\Siteareas\Administrator\Model;

defined('_JEXEC') or die;


#use Joomla\CMS\Form\Form;
#use Joomla\CMS\Helper\TagsHelper;
#use Joomla\CMS\Language\Associations;
#use Joomla\CMS\Language\LanguageHelper;
#use Joomla\CMS\UCM\UCMType;
#use Joomla\CMS\Versioning\VersionableModelTrait;
#use Joomla\Component\Categories\Administrator\Helper\CategoriesHelper;
#use Joomla\Registry\Registry;
#use Joomla\String\StringHelper;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\String\StringHelper;


/**
 * Sitearea Model
 */
class SiteareaModel extends AdminModel
{
    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $type    The table name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  \Joomla\CMS\Table\Table  A \Joomla\CMS\Table\Table object
     */
    /*public function getTable($type = 'Siteareas', $prefix = 'SiteareasTable', $config = [])
    {
        return \Joomla\CMS\Table\Table::getInstance($type, $prefix, $config);
    }*/

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  mixed    A JForm object on success, false on failure
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm(
            'com_siteareas.sitearea',
            'sitearea',
            [
                'control' => 'jform',
                'load_data' => $loadData
            ]
        );

        if (empty($form)) {
            return false;
        }

        // Modify the form based on access controls.
        if (!$this->canEditState((object) $data)) {
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
        $data = Factory::getApplication()->getUserState(
            'com_siteareas.edit.sitearea.data',
            []
        );

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Method to get a single record.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  mixed  Object on success, false on failure.
     */
    /*public function getItem($pk = null)
    {
        if ($item = parent::getItem($pk)) {
            // Convert the metadata field to an array.
            $registry = new Registry;
            $registry->loadString($item->metadata);
            $item->metadata = $registry->to[];

            // Convert the images field to an array.
            $registry = new Registry;
            $registry->loadString($item->images);
            $item->images = $registry->to[];

            if (!empty($item->id)) {
                $item->tags = new JHelperTags;
                $item->tags->getTagIds($item->id, 'com_weblinks.weblink');
                $item->metadata['tags'] = $item->tags;
            }
        }

        return $item;
    }*/



    /**
     * Prepare and sanitise the table data prior to saving.
     *
     * @param   \Joomla\CMS\Table\Table  $table  A reference to a \Joomla\CMS\Table\Table object.
     *
     * @return  void
     */
    protected function prepareTable($table)
    {
        $date = Factory::getDate();
        $user = Factory::getApplication()->getIdentity();

        $table->name = htmlspecialchars_decode($table->name, ENT_QUOTES);
        $table->alias = ApplicationHelper::stringURLSafe($table->alias);

        if (empty($table->alias)) {
            $table->alias = ApplicationHelper::stringURLSafe($table->name);
        }

        $table->modified    = $date->toSql();
        $table->modified_by = $user->id;

        if (empty($table->id)) {
            $table->created    = $date->toSql();
            $table->created_by = $user->id;
        }
    }

    protected function id_from_name($table, $id_key, $name_key, $name_value) {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select($db->quoteName($id_key));
        $query->from($db->quoteName('#__' . ($table)));
        $query->where($db->quoteName($name_key) . ' = ' . $db->quote($name_value));

        $db->setQuery($query);

        return $db->loadResult();
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
        #echo 'data<pre>'; var_dump($data); echo '</pre>'; #exit;
        $is_new = empty($data['id']);
        $app    = Factory::getApplication();
        $input  = $app->getInput();

        $db     = Factory::getDbo();

        // Get parameters:
        $params = ComponentHelper::getParams($this->option);

        // Alter the name for save as copy
        if ($app->input->get('task') == 'save2copy') {
            list($name, $alias) = $this->generateNewTitle(null, $data['alias'], $data['name']);
            $data['name']    = $name;
            $data['alias']    = $alias;
            $data['state']    = 0;
        }

        // Automatic handling of alias for empty fields
        // Taken from com_content/models/article.php
        if (in_array($input->get('task'), ['apply', 'save', 'save2new'])) {
            if (empty($data['alias'])) {
                if (Factory::getConfig()->get('unicodeslugs') == 1) {
                    $data['alias'] = OutputFilter::stringURLUnicodeSlug($data['name']);
                } else {
                    $data['alias'] = OutputFilter::stringURLSafe($data['name']);
                }

                $table = $this->getMVCFactory()->createTable('Sitearea', 'Administrator');

                if ($table->load(['alias' => $data['alias']])) {
                    $msg = Text::_('COM_CONTENT_SAVE_WARNING');
                }

                #list($name, $alias) = $this->generateNewSiteAreasTitle($data['alias'], $data['name']);
                list($name, $alias) = $this->generateNewTitle(null, $data['alias'], $data['name']);
                $data['alias'] = $alias;

                if (isset($msg)) {
                    $app->enqueueMessage($msg, 'warning');
                }
            }
        }

        // Need to generate an Admin Group if one isn't selected:
        if (empty($data['admin_group_id'])) {

            // Check to see if the row we're about to generate doesn't already exist:
            $name = $data['name'] . ' admin';
            $id = $this-> id_from_name('usergroups', 'id', 'title', $name);
            if (!empty($id)) {
                $app->enqueueMessage(Text::sprintf('COM_SITEAREAS_RECORD_ERROR_ROW_EXISTS', 'Admin Group'), 'warning');
                return false;
            }

            #echo '<pre>'; var_dump($id); echo '</pre>';

            $group = [
                'id'       => 0,
                'title'    => $name,
                'parent_id'=> 11
            ];

            $groupModel = $app->bootComponent('com_users')->getMVCFactory()->createModel('Group', 'Administrator', ['ignore_request' => true]);
            if (!$groupModel->save($group)) {
                $app->enqueueMessage($groupModel->getError());
                return false;
            }

            // Getting the new ID from the state doesn't seem to work anymore, so query the DB for
            // the matching name (note this is deliberatly a repeat of the earlier query):
            $id = $this-> id_from_name('usergroups', 'id', 'title', $name);

            $data['admin_group_id'] = $id;
        }

        // Need to add the Owner to the Group:
        $owner = Factory::getUser($data['owner_user_id']);
        $owner->set('groups', array_merge($owner->get('groups'), [$data['admin_group_id']]));
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

            // Generate a new menu:
            $menuType = [
                'menutype'     => $data['alias'],
                'title'        => $data['name'],
                'client_id'    => 0
            ];

            $menuTypesTable = $app->bootComponent('com_menus')->getMVCFactory()->createTable('MenuType', 'Administrator');

            // If the menu already exists, warn the user an go no further.
            if (!$menuTypesTable->save($menuType)) {
                $app->enqueueMessage($menuTypesTable->getError());
                return false;
            }

            // Check to see if the menu item already exists in the menu table via alias:
            $menu_item_id = $this-> id_from_name('menu', 'id', 'alias', $data['alias']);

            // If we already have a menu item prompt the user to move that item (and children) to
            // the new menu. We don't want to risk doing this automatically.
            if ($menu_item_id) {
                $app->enqueueMessage(Text::_('COM_SITEAREAS_RECORD_ERROR_MENU_ITEM_EXISTS'), 'warning');
                return false;
            }

            // Generate a stub for that menu:
            $menuItem = [
                'menutype'     => $menuTypesTable->menutype,
                'title'        => $data['name'],
                'alias'        => $data['alias'],
                'path'         => $data['alias'],
                'access'       => $data['access'],
                'link'         => '',
                'type'         => 'heading',
                'state'        => 0,
                'parent_id'    => 1,
                'level'        => 1,
                'component_id' => 0,
                'language'     => '*'
            ];

            $menuTable = $app->bootComponent('com_menus')->getMVCFactory()->createTable('Menu', 'Administrator');

            $menuTable->setLocation(1, 'last-child');

            if (!$menuTable->save($menuItem)) {
                throw new Exception($menuTable->getError());
                return false;
            }

            $data['root_menu_item_id'] = $menuTable->id;
        }


        // Respond to Category auto-generate:
        if ($data['params']['root_catid'] == 'autogenerate') {
            // TableCategory is autoloaded in J! 3.0, so...
            if (version_compare(JVERSION, '3.0', 'lt')) {
                Table::addIncludePath(JPATH_PLATFORM . 'joomla/database/table');
            }

            // Initialize a new category
            $category = Table::getInstance('Category');
            $category->parent_id = 1;
            $category->extension = 'com_content';
            $category->title = $data['name'];
            $category->alias = $data['alias'];
            //$category->description = 'A category for my extension';
            $category->published = 1;
            $category->access = $data['access'];
            $category->params = '{"category_layout":"","image":"","image_alt":""}';
            $category->metadata = '{"author":"","robots":""}';
            $category->language = '*';

            // Set the location in the tree:
            // First we need to the ID of the row that will come before it:
            $query = $db->getQuery(true);
            $query->select($query->qn('id'))
                  ->from($query->qn('#__categories'))
                  ->where($query->qn('title') .' < ' . $query->q($data['name']))
                  ->andWhere($query->qn('level') .' = 1')
                  ->order('title DESC')
                  ->setLimit('1');
            $db->setQuery($query);
            $ref_id = $db->loadResult();

            $category->setLocation($ref_id, 'after');

            // Check to make sure our data is valid
            if (!$category->check()) {
                throw new GenericDataException($category->getError(), 500);
                return false;
            }

            // Now store the category
            if (!$category->store(true)) {
                throw new GenericDataException($category->getError(), 500);
                return false;
            }

            // Build the path for our category
            $category->rebuildPath($category->id);
            $data['params']['root_catid'] = (string) $category->id;

            // We need to grant all permissions to this category for the admin user group:
            // {"core.create":{"162":1},"core.delete":{"162":1},"core.edit":{"162":1},"core.edit.state":{"162":1},"core.edit.own":{"162":1}}
            $g = $data['admin_group_id'];
            $rules = '{"core.create":{"' . $g . '":1},"core.delete":{"' . $g . '":1},"core.edit":{"' . $g . '":1},"core.edit.state":{"' . $g . '":1},"core.edit.own":{"' . $g . '":1}}';
            $query = $db->getQuery(true);
            $query->update($query->qn('#__assets'))
                  ->set($query->qn('rules') . ' = ' . $query->q($rules))
                  ->where($query->qn('name') . ' = ' . $query->q('com_content.category.' . $data['params']['root_catid']));
            $db->setQuery($query);
            $db->execute();
        }

        // If a category was simply selected, check for a News category, and make one if not
        // found:
        if (isset($data['params']['news_catid']) && $data['params']['news_catid'] == 'autogenerate') {

            // Check if a news category already exists, and select it if it does:
            $query = $db->getQuery(true);
            $query->select($query->qn('id'))
                  ->from($query->qn('#__categories'))
                  ->where($query->qn('parent_id') .' = ' . $query->q($data['params']['root_catid']))
                  ->andWhere($query->qn('alias') .' = "news"');
            $db->setQuery($query);
            $news_cat_id = $db->loadResult();

            if ($news_cat_id) {
                $data['params']['news_catid'] = $news_cat_id;
            } else {
                // Generate a news category.
                // It may not be used, but can always be deleted, which is less of a pain than if it
                // is needed and it's not there.
                $news_category = Table::getInstance('Category');
                $news_category->parent_id = $data['params']['root_catid'];
                $news_category->extension = 'com_content';
                $news_category->title = $data['name'] . ' News';
                $news_category->alias = 'news';
                //$category->description = 'A category for my extension';
                $news_category->published = 1;
                $news_category->access = $data['access'];
                $news_category->params = '{"category_layout":"","image":"","image_alt":""}';
                $news_category->metadata = '{"author":"","robots":""}';
                $news_category->language = '*';

                // Set the location in the tree
                $news_category->setLocation($data['params']['root_catid'], 'last-child');

                // Check to make sure our data is valid
                if (!$news_category->check()) {
                    throw new GenericDataException($category->getError(), 500);
                    return false;
                }

                // Now store the category
                if (!$news_category->store(true)) {
                    throw new GenericDataException($category->getError(), 500);
                    return false;
                }

                // Build the path for our category
                $news_category->rebuildPath($news_category->id);
                $data['params']['news_catid'] = (string) $news_category->id;

            }
        }


        // Respond to Brand auto-generate:
        if ($data['params']['brand_id'] == 'autogenerate') {

            // Check to see if the row we're about to generate doesn't already exist:
            $id = $this-> id_from_name('brands', 'id', 'alias', $data['alias']);

            if (!empty($id)) {
                $app->enqueueMessage(Text::sprintf('COM_SITEAREAS_RECORD_ERROR_ROW_EXISTS', 'Brand'), 'warning');
                return false;
            }
            #echo 'brand<pre>'; var_dump($id); echo '</pre>'; exit;
            $new_brand = [];
            $new_brand['id']    = null;
            $new_brand['name']  = $data['name'];
            $new_brand['alias'] = $data['alias'];
            $new_brand['catid'] = $params->get('brand_category_id');
            $new_brand['state'] = 1;

            $brandModel = $app->bootComponent('com_brands')->getMVCFactory()->createModel('Brand', 'Administrator', ['ignore_request' => true]);

            if (!$brandModel->save($new_brand)) {
                $app->enqueueMessage($brandModel->getError());
                return false;
            }
            // Getting the new ID from the state doesn't seem to work anymore, so query the DB for
            // the matching name (note this is deliberatly a repeat of the earlier query):
            $id = $this-> id_from_name('brands', 'id', 'alias', $data['alias']);

            $data['params']['brand_id'] = (string) $id;
        }


        // Respond to Template Style auto-generate:
        if ($data['params']['template_style_id'] == 'autogenerate') {

            // Get the base template params:
            $template        = explode(',', $params->get('template'));
            $template_name   = $template[0];
            $template_ext_id = $template[1];
            $template_title  = $template[2];
            $template_full_title  = $template_title . ' - ' . $data['name'];

            // Check to see if the row we're about to generate doesn't already exist:
            $id = $this-> id_from_name('template_styles', 'id', 'title', $template_full_title);
            if (!empty($id)) {
                $app->enqueueMessage(Text::sprintf('COM_SITEAREAS_RECORD_ERROR_ROW_EXISTS', 'Template Style'), 'warning');
                return false;
            }

            // This should trigger generation of navigation modules:
            if (!isset($data['params']['navbar_module_id'])) {
                $data['params']['navbar_module_id'] = 'autogenerate';
            }

            if (!isset($data['params']['section_menu_module_id'])) {
                $data['params']['section_menu_module_id'] = 'autogenerate';
            }



            // Build the query.
            $query = $db->getQuery(true);
            $query->select('params')
                ->from('#__extensions')
                ->where('extension_id = ' . (int) $template_ext_id);

            // Set the query and load the data.
            $db->setQuery($query);
            $template_params = json_decode($db->loadResult(), true);

            $template_params['site_title']       = $data['name'];
            $template_params['site_description'] = !!isset($data['params']['description']) ? $data['params']['description'] : $data['name'];
            $template_params['layout_name']      = 'structure--branded';
            $template_params['brand_id']         = $data['params']['brand_id'];
            $template_params['unit']             = $data['params']['unit'];

            $new_style = [];
            $new_style['template']  = $template_name;
            $new_style['client_id'] = '0';
            $new_style['home']      = '0';
            $new_style['title']     = $template_full_title;
            $new_style['params']    = $template_params;

            ####JLoader::import('style', JPATH_ADMINISTRATOR . '/components/com_templates/models');
            ####Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_templates/tables');
            ####$templateStylesModel = JModelLegacy::getInstance('Style', 'TemplatesModel');

            $templateStylesModel = $app->bootComponent('com_templates')->getMVCFactory()->createModel('Style', 'Administrator', ['ignore_request' => true]);

            if (!$templateStylesModel->save($new_style)) {

                $lang = Factory::getLanguage();
                $extension = 'com_templates';
                $base_dir = JPATH_ADMINISTRATOR;
                $language_tag = 'en-GB';
                $reload = true;
                $lang->load($extension, $base_dir, $language_tag, $reload);

                $app->enqueueMessage($templateStylesModel->getError());
                return false;
            } else {
                // Getting the new ID from the state doesn't seem to work anymore, so query the DB for
                // the matching name (note this is deliberatly a repeat of the earlier query):
                $id = $this-> id_from_name('template_styles', 'id', 'title', $template_full_title);
                $data['params']['template_style_id'] = (string) $id;
            }
        }

        // Respond to Search Menu Item auto-generate:
        if ($data['params']['search_menu_item_id'] == 'autogenerate') {

            // Generate a new menu item:
            $menuItem = [
                'menutype'     => $data['alias'],
                'title'        => 'Search',
                'alias'        => 'search',
                'path'         => $data['alias'] . '/search',
                'access'       => 6,
                'link'         => 'index.php?option=com_finder&view=search',
                'type'         => 'component',
                'state'        => 0,
                'parent_id'    => $data['root_menu_item_id'],
                'level'        => 2,
                'component_id' => 27,
                'language'     => '*'
            ];

            $menuTable = $app->bootComponent('com_menus')->getMVCFactory()->createTable('Menu', 'Administrator');
            $menuTable->setLocation($data['root_menu_item_id'], 'first-child');

            if (!$menuTable->save($menuItem)) {
                throw new Exception($menuTable->getError());
                return false;
            }

            $data['params']['search_menu_item_id'] = $menuTable->id;
        }

        // MODULES
        ####JLoader::import('module', JPATH_ADMINISTRATOR . '/components/com_modules/models');
        ####$moduleModel = JModelLegacy::getInstance('Module', 'ModulesModel');
        $moduleModel = $app->bootComponent('com_modules')->getMVCFactory()->createModel('Module', 'Administrator', ['ignore_request' => true]);

        // Autogenerate 'PROJECT navbar' and 'In this section':

        // Respond to Navbar Module auto-generate:
        if ($data['params']['navbar_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = $data['root_menu_item_id'];
            $module['title']      = $data['name'] . ' navbar';
            $module['ordering']   = 1;
            $module['position']   = '2-header-nav-bar';
            $module['published']  = 1;
            $module['module']     = 'mod_menu';
            $module['access']     = $data['access'];
            $module['showtitle']  = 0;
            $module['language']   = '*';
            $module['params']     = json_decode('{"menutype":"' . $data['alias'] . '","base":"","startLevel":2,"endLevel":0,"showAllChildren":1,"tag_id":"","class_sfx":"","window_open":"","layout":"npeu6:Navbar","moduleclass_sfx":"","cache":1,"cache_time":900,"cachemode":"itemid","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0","cta_text":"","cta_url":"","wrapper":"","theme":"","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['navbar_module_id'] = (string) $moduleModel->getState('module.id');
            $moduleModel->setState('module.id', 0);
        }

        // Respond to Section Menu Module auto-generate:
        if ($data['params']['section_menu_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = $data['root_menu_item_id'];
            $module['title']      = 'In this section';
            $module['note']       = $data['name'];
            $module['ordering']   = 1;
            $module['position']   = '4-sidebar-bottom';
            $module['published']  = 1;
            $module['module']     = 'mod_menu';
            $module['access']     = $data['access'];
            $module['showtitle']  = 1;
            $module['language']   = '*';
            $module['params']     = json_decode('{"menutype":"' . $data['alias'] . '","base":"","startLevel":3,"endLevel":0,"showAllChildren":1,"tag_id":"","class_sfx":"","window_open":"","layout":"npeu6:Section-Menu","moduleclass_sfx":"","cache":1,"cache_time":900,"cachemode":"itemid","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0","cta_text":"","cta_url":"","wrapper":"panel","theme":"","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['section_menu_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // Respond to generating these modules:

        // Funder modules (appears on all pages)
        if ($data['params']['funder_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = $data['root_menu_item_id'];
            $module['title']      = $data['name'] . ' funder';
            $module['ordering']   = 1;
            $module['position']   = '6-footer-top';
            $module['published']  = 1;
            $module['module']     = 'mod_funder';
            $module['access']     = $data['access'];
            $module['showtitle']  = 0;
            $module['language']   = '*';
            $module['params']     = json_decode('{"brand_id":"2","brand_url":"https:\/\/www.nihr.ac.uk\/funding-and-support\/funding-for-research-studies\/funding-programmes\/health-technology-assessment\/","statement":"<p>This study is funded by the National Institute for Health and CareResearch (NIHR) <a href=\"http:\/\/www.nihr.ac.uk\/funding-and-support\/funding-for-research-studies\/funding-programmes\/health-technology-assessment\/\" rel=\"external nofollow noreferrer\">Health Technology Assessment (HTA) Programme<\/a> (Reference Number [CHANGE ME]). The views expressed are those of the author(s) and not necessarily those of the NIHR or the Department of Health and Social Care.<\/p>\r\n","image":"assets\/images\/npeu\/baby-asleep-1.jpg","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"Npeu6-magic","cta_text":"","cta_url":"","wrapper":"","theme":"","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['funder_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // Latest update (appears on most pages)
        if ($data['params']['latest_update_module_id'] == 'autogenerate') {

            // In order for this to work there has to be a category, so find that:
            if (!isset($data['params']['root_catid'])) {
                $app->enqueueMessage(Text::_('COM_SITEAREAS_RECORD_ERROR_NO_CATEGORY'), 'warning');
                return false;
            }

            // There also has to be a 'news' child category:
            if (!isset($data['params']['news_catid'])) {
                $app->enqueueMessage(Text::_('COM_SITEAREAS_RECORD_ERROR_NO_NEWS_CATEGORY'), 'warning');
                return false;
            }

            $module = [];
            $module['assignment'] = false;
            $module['title']      = 'Latest update';
            $module['note']       = $data['name'];
            $module['ordering']   = 3;
            $module['position']   = '4-sidebar-bottom';
            $module['published']  = 1;
            $module['module']     = 'mod_articles_latest';
            $module['access']     = $data['access'];
            $module['showtitle']  = 1;
            $module['language']   = '*';
            $module['params']     = json_decode('{"catid":[' . $data['params']['news_catid'] . '],"count":1,"show_featured":"","ordering":"p_dsc","user_id":"0","layout":"_:default","moduleclass_sfx":"","cache":1,"cache_time":900,"cachemode":"static","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0","cta_text":"See all updates","cta_url":"https:\/\/www.npeu.ox.ac.uk\/' . $data['alias'] . '\/whats-new","wrapper":"panel","theme":"","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['latest_update_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // Contact us (appears on most pages)
        if ($data['params']['contact_us_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = false;
            $module['title']      = 'Contact us';
            $module['note']       = $data['name'];
            $module['content']    = '<p>For more information about ' . $data['name'] . ', please view the&nbsp;<a href="https://www.npeu.ox.ac.uk/' . $data['alias'] . '/contact">contact details page</a></p>';
            $module['ordering']   = 4;
            $module['position']   = '4-sidebar-bottom';
            $module['published']  = 1;
            $module['module']     = 'mod_custom';
            $module['access']     = $data['access'];
            $module['showtitle']  = 1;
            $module['language']   = '*';
            $module['params']     = json_decode('{"prepare_content":0,"backgroundimage":"","layout":"_:default","moduleclass_sfx":"","cache":1,"cache_time":900,"cachemode":"static","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0","cta_text":"","cta_url":"","wrapper":"panel","theme":"","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['contact_us_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // Find us (contact page only)
        if ($data['params']['find_us_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = false;
            $module['title']      = 'Find us';
            $module['note']       = $data['name'];
            $module['ordering']   = 2;
            $module['position']   = '4-sidebar-bottom';
            $module['published']  = 1;
            $module['module']     = 'mod_map';
            $module['access']     = $data['access'];
            $module['showtitle']  = 1;
            $module['language']   = '*';
            $module['params']     = json_decode('{"lat":51.751613839,"lng":-1.21574715729,"zoom":13,"access_token":"pk.eyJ1IjoiYW5keWtraXJrIiwiYSI6ImNqbGh3a3FnbzA1aDMza204eDJnMmVhMmMifQ.I7diR0BZvQWzn2okKy6qIQ","height":300,"legend":"","manual_markers":"true,true,blue,\"' . ($data['params']['unit'] == 'npeu_ctu' ? 'NPEU Clinical Trials Unit\r\n' : '') . 'National Perinatal Epidemiology Unit (NPEU)\r\nNuffield Department of Population Health\r\nUniversity of Oxford\r\nOld Road Campus\r\nOxford, OX3 7LF\"","remote_markers_url":"","remote_markers_json_format":"","static_map_alt":"","static_map_no_js":"No javascript available, can\'t display an interactive map.","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0","cta_text":"Find out more","cta_url":"https:\/\/www.npeu.ox.ac.uk\/contact","wrapper":"panel","theme":"","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['find_us_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // PROJECT (intro text - landing page only)
        if ($data['params']['intro_text_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = $data['root_menu_item_id'];
            $module['title']      = $data['name'];
            $module['content']    = '<p>Coming soon.</p>';
            $module['ordering']   = 1;
            $module['position']   = 'bespoke';
            $module['published']  = 1;
            $module['module']     = 'mod_text';
            $module['access']     = $data['access'];
            $module['showtitle']  = 1;
            $module['language']   = '*';
            $module['params']     = json_decode('{"cta_text":"Find out more","cta_url":"https:\/\/www.npeu.ox.ac.uk\/' . $data['alias'] . '\/parents","cta_position":"bottom","module_tag":"div","bootstrap_size":"0","header_tag":"h1","header_class":"","style":"Npeu6-bespoke","wrapper":"panel_longform","theme":"white","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['intro_text_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // PROJECT signpost (landing page only)
        if ($data['params']['signpost_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = $data['root_menu_item_id'];
            $module['title']      = $data['name'] . ' signpost';
            $module['ordering']   = 1;
            $module['position']   = 'bespoke';
            $module['published']  = 1;
            $module['module']     = 'mod_signpost';
            $module['access']     = $data['access'];
            $module['showtitle']  = 0;
            $module['language']   = '*';
            $module['params']     = json_decode('{"signs":{"signs0":{"url":"https:\/\/www.npeu.ox.ac.uk\/' . $data['alias'] . '\/parents","content":"<span class=\"c-sign__text  c-sign__text--large\">Parents<\/span>","padding":1,"colspan":0,"signclass_sfx":"","svg":"<svg xmlns=\"http:\/\/www.w3.org\/2000\/svg\" viewBox=\"0 0 100 100\" preserveAspectRatio=\"xMidYMax meet\" fill=\"#fff\">\r\n    <g>\r\n        <path d=\"M66.4,27.1C73.9,27.1,80,21,80,13.5C80,6.1,73.9,0,66.4,0C59,0,52.9,6.1,52.9,13.5C52.9,21,59,27.1,66.4,27.1z\"\/>\r\n        <path d=\"M95.6,45c0,0,0.8-15.1-15.2-15.1H52.8c-2.6,0-4.7,0.4-6.5,1c10.5,0.5,15.4,6.9,16.5,10.3c0.3,0.9,7.1,21.3,5.7,27.5\r\n            c-0.9,4-5.6,7.4-9.2,9.5l6.5,21.2L66,100h18.4l0.9-12c0,2.3,2.2,4.9,5.1,4.9c2.8,0,5.1-2.7,5.3-5L95.6,45z\"\/>\r\n        <path d=\"M36.4,30.9c7.2,0,13-6,13-13.4C49.4,10,43.6,4,36.4,4s-13,6-13,13.5C23.4,24.9,29.2,30.9,36.4,30.9z\"\/>\r\n        <path d=\"M59.5,42.3c-0.6-2-4.3-7.9-14.3-8l-19.6,0.1c-9.7-0.5-13.6,5.2-14.2,7.7c0,0-6.6,21-5.4,25.8c0.7,3,5.9,6.7,11.5,9.4\r\n            L9.9,100h52.3L55,76.6c5-2.6,9.3-5.9,10-8.7C66.1,63,59.5,42.3,59.5,42.3z M27.6,65.4c-0.9,0.4-1.9,0.6-3,0.6\r\n            c-4.1,0-7.4-3.4-7.4-7.5s3.3-7.5,7.4-7.5c4,0,7.3,3.2,7.4,7.3c1.3-0.9,2.6-1.3,3.6-1.5l0,0c1.5-0.3,2.6,0,2.6,0\r\n            c4.9,1.1,15.8,5.9,15.8,5.9S48.2,70,37.6,70c-0.3,0-0.5,0-0.8,0l0,0c-1,0-1.9-0.1-2.6-0.2C29.1,69.2,27.7,67.3,27.6,65.4z\"\/>\r\n    <\/g>\r\n<\/svg>","data_src":"","data_src_err":"Data could not be fetched from the data source.","data_decode_err":"Data could not be decoded as JSON."},"signs1":{"url":"https:\/\/www.npeu.ox.ac.uk\/' . $data['alias'] . '\/clinicians","content":"<span class=\"c-sign__text  c-sign__text--large\">Clinicians<\/span>","padding":1,"colspan":0,"signclass_sfx":"","svg":"<svg xmlns=\"http:\/\/www.w3.org\/2000\/svg\" viewBox=\"0 0 100 100\" preserveAspectRatio=\"xMidYMax meet\" fill=\"#fff\">\r\n    <g>\r\n        <circle cx=\"50\" cy=\"16.9\" r=\"16.9\"\/>\r\n        <path d=\"M50,52.3c7.3,0,13.2-6,13.2-13.2H36.8C36.8,46.3,42.7,52.3,50,52.3z\"\/>\r\n        <path d=\"M69.7,39.3C69.6,49,62.5,57,53.3,58.6v15.9c0,8.2-6.2,15.4-14.4,15.8c-8.5,0.5-15.6-6-16.1-14.3c-2.2-1.3-3.6-3.9-3.1-6.8\r\n            c0.5-2.6,2.6-4.7,5.2-5.1c4.1-0.7,7.6,2.4,7.6,6.3c0,2.3-1.3,4.4-3.1,5.5c0.5,4.6,4.6,8.2,9.4,7.8c4.6-0.4,7.9-4.5,7.9-9.1v-16\r\n            C37.5,57,30.4,49,30.3,39.3c-9.1,1.2-16.1,9-16.1,18.4v34.1c0,4.5,3.7,8.2,8.2,8.2h55.4c4.5,0,8.2-3.7,8.2-8.2V57.7\r\n            C85.9,48.3,78.8,40.5,69.7,39.3z\"\/>\r\n    <\/g>\r\n<\/svg>","data_src":"","data_src_err":"Data could not be fetched from the data source.","data_decode_err":"Data could not be decoded as JSON."},"signs2":{"url":"https:\/\/www.npeu.ox.ac.uk\/' . $data['alias'] . '\/recruitment","content":"{% if data.override_msg is empty %}\r\n{% set meter_threshold = 5 %}\r\n<span class=\"l-layout  l-row  l-gutter--s  l-flush-edge-gutter\">\r\n    <span class=\"l-layout__inner\">\r\n        <span class=\"l-box  ff-width-100--30--50\">\r\n            <span class=\"c-sign__text  c-sign__text--large\">Recruitment total: {{ data.total }}<\/span><br>\r\n            <span class=\"c-sign__text  c-sign__text--small\">(Target: {{ data.target }})<\/span>\r\n            {% if (data.total \/ data.target * 100) > meter_threshold %}\r\n            <div class=\"c-meter\">\r\n                <meter min=\"0\" max=\"{{ data.target }}\" value=\"{{ data.total }}\">\r\n                    <span style=\"width: calc({{ data.total }} \/ {{ data.target }} * 100%);\">{{ data.total }} out of {{ data.target }} recruited.<\/span>\r\n                <\/meter>\r\n                <div aria-hidden=\"true\" class=\"c-meter__marker\" style=\"width: calc({{ data.total }} \/ {{ data.target }} * 100%);\" data-value=\"{{ data.total }}\"><\/div>\r\n            <\/div>\r\n            {% endif %}\r\n        <\/span>\r\n        <span class=\"l-box  ff-width-100--30--50  l-center\">\r\n            <span class=\"c-sign__text  c-sign__text\">{{ data.latest_msg|md|blockless|raw }}<\/span>\r\n        <\/span>\r\n    <\/span>\r\n<\/span>\r\n{% else %}\r\n  {{ data.override_msg|md|raw }}\r\n{% endif %}","padding":0,"colspan":1,"signclass_sfx":"--alt","svg":"","data_src":"\/datastore\/centre-news\/' . $data['alias'] . '.json","data_src_err":"Data could not be fetched from the data source.","data_decode_err":"Data could not be decoded as JSON."}},"module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0","cta_text":"","cta_url":"","wrapper":"","theme":"","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['signpost_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // PROJECT intro video (landing page only)
        if ($data['params']['intro_video_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = $data['root_menu_item_id'];
            $module['title']      = $data['name'] . ' intro video';
            $module['ordering']   = 1;
            $module['position']   = 'bespoke';
            $module['published']  = 1;
            $module['module']     = 'mod_video';
            $module['access']     = $data['access'];
            $module['showtitle']  = 0;
            $module['language']   = '*';
            $module['content']    = '';
            $module['language']   = '*';
            $module['params']     = json_decode('{"youtube_id":"","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0","cta_text":"","cta_url":"","wrapper":"","theme":"","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['intro_video_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // PROJECT updates (landing page only)
        if ($data['params']['updates_module_id'] == 'autogenerate') {

            // In order for this to work there has to be a category, so find that:
            if (!isset($data['params']['root_catid'])) {
                $app->enqueueMessage(Text::_('COM_SITEAREAS_RECORD_ERROR_NO_CATEGORY'), 'warning');
                return false;
            }

            // There also has to be a 'news' child category:
            if (!isset($data['params']['news_catid'])) {
                $app->enqueueMessage(Text::_('COM_SITEAREAS_RECORD_ERROR_NO_NEWS_CATEGORY'), 'warning');
                return false;
            }

            $module = [];
            $module['assignment'] = $data['root_menu_item_id'];
            $module['title']      = $data['name'] . ' updates';
            $module['ordering']   = 1;
            $module['position']   = 'bespoke';
            $module['published']  = 1;
            $module['module']     = 'mod_articles_latest';
            $module['access']     = $data['access'];
            $module['showtitle']  = 0;
            $module['language']   = '*';
            $module['params']     = json_decode('{"catid":[' . $data['params']['news_catid'] . '],"count":3,"show_featured":"","ordering":"p_dsc","user_id":"0","layout":"_:default","moduleclass_sfx":"","cache":1,"cache_time":900,"cachemode":"static","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"Npeu6-bespoke","cta_text":"See all updates","cta_url":"https:\/\/www.npeu.ox.ac.uk\/' . $data['alias'] . '\/whats-new","wrapper":"panel","theme":"dark","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['updates_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // PROJECT trial info (landing page only)
        if ($data['params']['trial_info_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = $data['root_menu_item_id'];
            $module['title']      = $data['name'] . ' trial info';
            $module['ordering']   = 1;
            $module['position']   = '6-footer-mid-left';
            $module['published']  = 1;
            $module['module']     = 'mod_dataview';
            $module['access']     = $data['access'];
            $module['showtitle']  = 0;
            $module['language']   = '*';
            $module['params']     = json_decode('{"data_src":"\/data\/trials?alias=' . $data['alias'] . '","data_tpl":"<div class=\"c-panel\">\r\n    <section class=\"c-panel__module\">\r\n        <h2>Trial information<\/h2>\r\n        <dl class=\"dl-2-col\">\r\n            {% if data[0].eudract is not empty and not  data[0].eudract == \"N\/A\" %}\r\n            <dt>EudraCT No.:<\/dt>\r\n            <dd>{{ data[0].eudract }}<\/dd>\r\n            {% endif %}\r\n            {% if data[0].rec_ref is not empty %}\r\n            <dt>REC Reference:<\/dt>\r\n            <dd>{{ data[0].rec_ref }}<\/dd>\r\n            {% endif %}\r\n            {% if data[0].isrctn is not empty %}\r\n            <dt>ISRCTN:<\/dt>\r\n            <dd>{{ data[0].isrctn }}<\/dd>\r\n            {% endif %}\r\n            {% if data[0].ctu is not empty %}\r\n            <dt>Clinical Trials Unit:<\/dt>\r\n            <dd>{{ data[0].ctu }}<\/dd>\r\n            {% endif %}\r\n            {% if data[0].sponser is not empty %}\r\n            <dt>Sponsor:<\/dt>\r\n            <dd>{{ data[0].sponser }}<\/dd>\r\n            {% endif %}\r\n            {% if data[0].funder is not empty %}\r\n            <dt>Funder:<\/dt>\r\n            <dd>{{ data[0].funder }}<\/dd>\r\n            {% endif %}\r\n            {% if data[0].rec_target is not empty %}\r\n            <dt>Recruitment Target:<\/dt>\r\n            <dd>{{ data[0].rec_target }}<\/dd>\r\n            {% endif %}\r\n            {% if data[0].duration is not empty %}\r\n            <dt>Duration of Study:<\/dt>\r\n            <dd>{{ data[0].duration }}<\/dd>\r\n            {% endif %}\r\n        <\/dl>\r\n    <\/section>\r\n<\/div>","data_src_err":"Data could not be fetched from the data source.","data_decode_err":"Data could not be decoded as JSON.","highcharts":"0","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0","cta_text":"","cta_url":"","wrapper":"","theme":"","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['trial_info_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // PROJECT support and connect (all pages)
        /*if ($data['params']['support_connect_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = false;
            $module['title']      = $data['name'] . ' support and connect';
            $module['content']    = <<<EOD
<div class="l-col-to-row-wrap">
<div class="l-col-to-row">
<div>
<div class="c-panel t-white u-fill-height">
<section class="c-panel__module">
<h2>Connect with us</h2>

<div class="l-start">
<div class="u-padding--s"><a class="c-badge  c-badge  x" href="https://x.com/npeu_oxford" rel="external noopener noreferrer" target="_blank"><img alt="YouTube" height="60" onerror="this.src='/assets/images/brand-logos/social/x.png'; this.onerror=null;" src="/assets/images/brand-logos/social/x.svg"> </a></div>

<div class="u-padding--s"><a class="c-badge  c-badge  youtube" href="https://www.youtube.com/user/NPEUOxford" rel="external noopener noreferrer" target="_blank"><img alt="x" height="60" onerror="this.src='/assets/images/brand-logos/social/youtube.png'; this.onerror=null;" src="/assets/images/brand-logos/social/youtube.svg"> </a></div>
</div>
</section>
</div>
</div>

<div>
<div class="c-panel t-white u-fill-height">
<section class="c-panel__module">
<h2>Support</h2>

<div class="l-start">

<div class="l-center u-padding--s"><a class="c-badge" href="http://www.CHANGE-ME" rel="external noopener noreferrer" target="_blank"><img alt="Logo: CHANGE-ME" height="80" onerror="this.src='/assets/images/brand-logos/affiliate/CHANGE-ME-logo.png'; this.onerror=null;" src="/assets/images/brand-logos/affiliate/CHANGE-ME-logo.svg"> </a></div>

</div>
</section>
</div>
</div>
</div>
</div>
EOD;
            $module['ordering']   = 1;
            $module['position']   = '6-footer-mid-right';
            $module['published']  = 1;
            $module['module']     = 'mod_custom';
            $module['access']     = $data['access'];
            $module['showtitle']  = 0;
            $module['params']     = json_decode('{"prepare_content":0,"backgroundimage":"","layout":"_:default","moduleclass_sfx":"","cache":1,"cache_time":900,"cachemode":"static","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0","cta_text":"","cta_url":"","wrapper":"","theme":"","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['support_connect_module_id'] = (string) $moduleModel->getState('module.id');
        }*/

        // PROJECT support (all pages)
        if ($data['params']['support_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = false;
            $module['title']      = 'Support';
            $module['note']       = $data['name'];

            $module['ordering']   = 1;
            $module['position']   = '6-footer-mid-right';
            $module['published']  = 1;
            $module['module']     = 'mod_badgelist';
            $module['access']     = $data['access'];
            $module['showtitle']  = 1;
            $module['language']   = '*';
            //{"badges":{"badges0":{"brand_id":"91","override_url":"","limit_height":"8"}},"list_layout":"","list_gutter":"m","flush_gutter":"1","list_basis":"","layout":"_:default","module_tag":"div","bootstrap_size":"0","header_tag":"h2","header_class":"u-space--below","style":"0","cta_text":"","cta_url":"","cta_position":"header","wrapper":"panel","theme":"white","color":"neutral","headline_image":""}
            $module['params']     = json_decode('{"list_layout":"","list_gutter":"m","flush_gutter":"1","list_basis":"","layout":"_:default","module_tag":"div","bootstrap_size":"0","header_tag":"h2","header_class":"u-space--below","style":"0","cta_text":"","cta_url":"","cta_position":"header","wrapper":"panel","theme":"white","color":"neutral","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['support_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // PROJECT connect (all pages)
        if ($data['params']['connect_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = false;
            $module['title']      = 'Connect with us';
            $module['note']       = $data['name'];

            $module['ordering']   = 1;
            $module['position']   = '6-footer-mid-right';
            $module['published']  = 1;
            $module['module']     = 'mod_social';
            $module['access']     = $data['access'];
            $module['showtitle']  = 1;
            $module['language']   = '*';
            //{"enable_x":"1","x":"npeu_ctu","enable_youtube":"1","youtube":"NPEUOxford","layout":"_:default","module_tag":"div","bootstrap_size":"0","header_tag":"h2","header_class":"u-space--below","style":"0","cta_text":"","cta_url":"","cta_position":"header","wrapper":"panel","theme":"white","color":"neutral","headline_image":""}
            $module['params']     = json_decode('{"enable_x":"1","x":"npeu_oxford","enable_youtube":"1","youtube":"NPEUOxford","layout":"_:default","module_tag":"div","bootstrap_size":"0","header_tag":"h2","header_class":"u-space--below","style":"0","cta_text":"","cta_url":"","cta_position":"header","wrapper":"panel","theme":"white","color":"neutral","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['connect_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // PROJECT sites map
        if ($data['params']['sites_map_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = false;
            $module['title']      = $data['name'] . ' sites map';
            $module['ordering']   = 1;
            $module['position']   = 'bespoke';
            $module['published']  = 1;
            $module['module']     = 'mod_map';
            $module['access']     = $data['access'];
            $module['showtitle']  = 0;
            $module['language']   = '*';
            $module['params']     = json_decode('{"lat":53.5,"lng":-2,"zoom":6,"access_token":"pk.eyJ1IjoiYW5keWtraXJrIiwiYSI6ImNqbGh3a3FnbzA1aDMza204eDJnMmVhMmMifQ.I7diR0BZvQWzn2okKy6qIQ","height":500,"legend":"<p class=\"l-layout l-row l-row--center l-gutter l-flush-edge-gutter\"><span class=\"l-layout__inner\"><span class=\"l-box\"><img alt=\"Red marker\" class=\"icon--marker\" height=\"32\" src=\"/assets/images/icons/red-marker.svg\" width=\"21\" />- Recruiting site</span> </span></p>\r\n","manual_markers":"","remote_markers_url":"https:\/\/www.npeu.ox.ac.uk\/datastore\/json\/' . $data['alias'] . '\/' . $data['alias'] . '-centres.json","remote_markers_json_format":"[{% set i = 0 %}\r\n{% for id, row in data %}\r\n    {% if\r\n        row.site is defined and row.site is not empty and\r\n        row.lat is defined and row.lat is not empty and\r\n        row.long is defined and row.long is not empty and\r\n        row.type is defined and row.type is not empty and\r\n        row.permission is defined and row.permission == \"Yes\"\r\n    %}\r\n    {% if i > 0 %},{% endif %} {\r\n        \"lat\":   \"{{ attribute(row, \"lat\")|raw }}\",\r\n        \"lng\":   \"{{ attribute(row, \"long\")|raw }}\",\r\n        \"color\": \"red\",\r\n        \"popup\": \"<p><b>{{ attribute(row, \"site\")|raw }}<\/b><\/p><p><a href=\\\\\"#{{ id }}\\\\\">More details<\/a><\/p>\"\r\n    }\r\n    {% set i = i + 1 %}\r\n    {% endif %}\r\n{% endfor %}\r\n]","static_map_alt":"","static_map_no_js":"No javascript available, can\'t display an interactive map.","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0","cta_text":"","cta_url":"","wrapper":"","theme":"","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['sites_map_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // PROJECT site details
        if ($data['params']['site_details_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = false;
            $module['title']      = $data['name'] . ' site details';
            $module['ordering']   = 2;
            $module['position']   = 'bespoke';
            $module['published']  = 1;
            $module['module']     = 'mod_dataview';
            $module['access']     = $data['access'];
            $module['showtitle']  = 0;
            $module['language']   = '*';
            $module['params']     = json_decode('{"data_src":"\/datastore\/json\/' . $data['alias'] . '\/' . $data['alias'] . '-centres.json","data_tpl":"{% for key, centre in data if centre.permission == \'Yes\' %}\r\n<article class=\"c-card  d-background\" id=\"{{ key }}\">\r\n    <div data-fs-block=\"border\">\r\n        <header class=\"c-card__header\" id=\"{{ key }}--org\" itemprop=\"worksFor\" itemref=\"{{ key }}--address\" itemscope=\"itemscope\" itemtype=\"http://schema.org/Organization\">\r\n            <h3 class=\"c-card__title\"><span itemprop=\"name\">{{ centre.site }}</span></h3>\r\n            <p>{{ centre.trust }}</p>\r\n        </header>\r\n\r\n        <div class=\"c-card__body\">\r\n            <details class=\"c-panel  d-border  d-background--dark\">\r\n                <summary>Show details</summary>\r\n                <div class=\"l-layout  l-row  l-gutter--s  l-flush-edge-gutter\">\r\n                    <div class=\"l-layout__inner\">\r\n                        <div class=\"l-box  ff-width-100--30--50\">\r\n                            <dl class=\"c-contact-list\" itemref=\"{{ key }}--org\" itemscope=\"\" itemtype=\"http://schema.org/Person\">\r\n                                <dt>Name</dt>\r\n                                <dd>\r\n                                    <svg aria-hidden=\"true\" class=\"icon\" display=\"none\"><use xlink:href=\"#icon-person\"></use></svg>\r\n                                    <b itemprop=\"name\">{{ centre.pi_name }}</b>\r\n                                </dd>\r\n                                <dt>Role</dt>\r\n                                <dd itemprop=\"jobTitle\">{{ centre.pi_position }}</dd>\r\n                            </dl>\r\n                        </div>\r\n                        <div class=\"l-box  ff-width-100--30--50\">\r\n                            {% if centre.address_parts is not empty %}\r\n                            <dl class=\"c-contact-list\">\r\n                                <dt>Address</dt>\r\n                                <dd id=\"{{ key }}--address\" itemprop=\"address\" itemscope=\"\" itemtype=\"http://schema.org/PostalAddress\">\r\n                                    <svg aria-hidden=\"true\" class=\"icon\" display=\"none\"><use xlink:href=\"#icon-building\"></use></svg>\r\n                                    {% if centre.address_parts.address is not empty %}\r\n                                    <span itemprop=\"streetAddress\">\r\n                                        {{ centre.address_parts.address|join(\'<br>\')|raw }\r\n                                    </span><br>\r\n                                    {% endif %}\r\n                                    <span itemprop=\"addressLocality\">{{ centre.address_parts.locality }}</span> <span itemprop=\"postalCode\">{{ centre.address_parts.postcode }}</span>\r\n                                </dd>\r\n                            </dl>\r\n                            {% endif %}\r\n                        </div>\r\n                    </div>\r\n                </div>\r\n            </details>\r\n        </div>\r\n\r\n    </div>\r\n</article>\r\n{% endfor %}","data_src_err":"Data could not be fetched from the data source.","data_decode_err":"Data could not be decoded as JSON.","highcharts":"0","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0","cta_text":"","cta_url":"","wrapper":"","theme":"","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['site_details_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // PROJECT recruitment summary
        if ($data['params']['recruitment_summary_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = false;
            $module['title']      = $data['name'] . ' recruitment summary';
            $module['ordering']   = 2;
            $module['position']   = 'bespoke';
            $module['published']  = 1;
            $module['module']     = 'mod_dataview';
            $module['access']     = $data['access'];
            $module['showtitle']  = 0;
            $module['language']   = '*';
            $module['params']     = json_decode('{"data_src":"\/datastore\/recruitment-summary\/' . $data['alias'] . '.json","data_tpl":"{% if data.footer.total > 0 %}\r\n<table border=\"1\" data-contains=\"numbers\">\r\n    <caption>Recruitment Summary<\/caption>\r\n    <thead>\r\n        <tr>\r\n            <th>{{ data.header.centre }}<\/th>\r\n            <th>{{ data.header.total }}<\/th>\r\n            {% for item in data.header.month %}\r\n            <th>{{ item }}<\/th>\r\n            {% endfor %}\r\n        <\/tr>\r\n    <\/thead>\r\n    <tfoot>\r\n        <tr>\r\n            <td>&nbsp;<\/td>\r\n            <td>{{ data.footer.total }}<\/td>\r\n            {% for item in data.footer.month %}\r\n            <td>{{ item }}<\/td>\r\n            {% endfor %}\r\n        <\/tr>\r\n    <\/tfoot>\r\n    <tbody>\r\n    {% for item in data.centre %}\r\n        <tr>\r\n            <th>{{ item.name }}<\/th>\r\n            <td>{{ item.total }}<\/td>\r\n            {% for m in item.month %}\r\n            <td>{{ m }}<\/td>\r\n            {% endfor %}\r\n        <\/tr>\r\n        {% endfor %}\r\n    <\/tbody>\r\n<\/table>\r\n{% endif %}","data_src_err":"Data could not be fetched from the data source.","data_decode_err":"Data could not be decoded as JSON.","highcharts":"0","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0","cta_text":"","cta_url":"","wrapper":"","theme":"","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['recruitment_summary_module_id'] = (string) $moduleModel->getState('module.id');
        }

        // PROJECT recruitment chart
        if ($data['params']['recruitment_chart_module_id'] == 'autogenerate') {

            $module = [];
            $module['assignment'] = false;
            $module['title']      = $data['name'] . ' recruitment chart';
            $module['ordering']   = 1;
            $module['position']   = 'bespoke';
            $module['published']  = 1;
            $module['module']     = 'mod_dataview';
            $module['access']     = $data['access'];
            $module['showtitle']  = 0;
            $module['language']   = '*';
            $module['params']     = json_decode('{"data_src":"\/datastore\/centre-rates\/' . $data['alias'] . '.json","data_tpl":"{% if data.categories|length > 0 %}\r\n<div id=\"recruitment-chart\" style=\"min-width: 310px; max-width: 100%; height: 500px; margin: 0 auto\"><\/div>\r\n<script>\r\nHighcharts.chart(\"recruitment-chart\", {\r\n    \"chart\": {\r\n        \"type\": \"bar\",\r\n        \"style\": {\r\n            \"fontFamily\": \"Lato,sans-serif\",\r\n            \"fontWeight\": \"700\",\r\n            \"fontSize\": \"1.6rem\"\r\n        }\r\n    },\r\n    \"title\": {\r\n        \"text\": \"Total Recruitment {{ data.series[1].data | sum }}\"\r\n    },\r\n    \"colors\": [\r\n        \"#009590\",\r\n        \"#B51A82\"\r\n    ],\r\n    \"xAxis\": {\r\n        \"categories\": [\r\n        {% for site in data.categories %}{% if loop.first == false %},{% endif %} \"{{ site|raw }}\"\r\n        {% endfor %}],\r\n        \"title\": {\r\n            \"text\": null\r\n        }\r\n    },\r\n    \"yAxis\": {\r\n        \"visible\": false\r\n    },\r\n    \"plotOptions\": {\r\n        \"bar\": {\r\n            \"dataLabels\": {\r\n                \"enabled\": true\r\n            }\r\n        }\r\n    },\r\n    \"legend\": {\r\n        \"verticalAlign\": \'top\',\r\n        \"backgroundColor\": ((Highcharts.theme && Highcharts.theme.legendBackgroundColor) || \'#FFFFFF\')\r\n    },\r\n    \"credits\" :{\r\n        \"enabled\": false\r\n    },\r\n    \"series\": [\r\n        {\r\n            \"name\":\"{{ data.series[0].name }}\",\r\n            \"data\":[\r\n                {% for n in data.series[0].data %}\r\n                {% if loop.first == false %},{% endif %} {{ n|number_format(2) }}\r\n                {% endfor %}\r\n            ]\r\n        },\r\n        {\r\n            \"name\": \"{{ data.series[1].name }}\",\r\n            \"data\": [\r\n                {% for n in data.series[1].data %}\r\n                {% if loop.first == false %},{% endif %} {{ n|number_format(2) }}\r\n                {% endfor %}\r\n            ]\r\n        }\r\n    ],\r\n    exporting: {\r\n        \"chartOptions\": {\r\n            \"chart\": {\r\n                \"width\": 1500\r\n            }\r\n        }\r\n    }\r\n});\r\n\r\n<\/script>\r\n{% else %}\r\n<p>Recruitment figures will be shown here once the first recruit has been randomised.<p>\r\n{% endif %}","data_src_err":"Data could not be fetched from the data source.","data_decode_err":"Data could not be decoded as JSON.","highcharts":"1","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0","cta_text":"","cta_url":"","wrapper":"","theme":"","headline_image":""}', true);

            $moduleModel->setState('module.id', 0);
            $t_pk = $app->input->getInt('id');
            $app->input->set('id', 0);
            if (!$moduleModel->save($module)) {

                $app->enqueueMessage($moduleModel->getError());
                return false;
            }
            $app->input->set('id', $t_pk);
            $data['params']['recruitment_chart_module_id'] = (string) $moduleModel->getState('module.id');
        }

        #echo 'P<pre>'; var_dump($data); echo '</pre>'; exit;

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
        // Alter the title & alias
        $table = $this->getTable();

        while ($table->load(['alias' => $alias])) {
            if ($name == $table->name) {
                $name = StringHelper::increment($name);
            }

            $alias = StringHelper::increment($alias, 'dash');
        }

        return [$name, $alias];
    }

}
