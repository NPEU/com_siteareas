<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
/*

*/


JFormHelper::loadFieldClass('list');

/**
 * Form field for a list of active staff members.
 */
class JFormFieldTemplates extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var     string
     */
    protected $type = 'Templates';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     */
    protected function getOptions()
    {

        $lang = Factory::getLanguage();

        // Get the client and client_id.
        $client = ApplicationHelper::getClientInfo('site', true);


        // Get the database object and a new query object.
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        // Build the query.
        $query->select('s.id, s.title, e.name as name, e.extension_id as ext_id, s.template')
            ->from('#__template_styles as s')
            ->where('s.client_id = ' . (int) $client->id)
            ->order('template')
            ->order('title');

        $query->join('LEFT', '#__extensions as e on e.element=s.template')
            ->where('e.enabled = 1')
            ->where($db->quoteName('e.type') . ' = ' . $db->quote('template'));

        // Set the query and load the styles.
        $db->setQuery($query);
        $styles = $db->loadObjectList();

        // Build the options array.
        $options = array();
        $templates = array();
        if ($styles)
        {
            foreach ($styles as $style)
            {
                $template = $style->template;
                $lang->load('tpl_' . $template . '.sys', $client->path, null, false, true)
                    || $lang->load('tpl_' . $template . '.sys', $client->path . '/templates/' . $template, null, false, true);
                $name = \JText::_($style->name);

                if (in_array($name, $templates)) {
                    continue;
                }

                $options[] = \JHtml::_('select.option', $template . ',' . $style->ext_id . ',' . $name, $name);
                $templates[] = $name;
            }
        }
        return $options;
        /*
        $com_templates_path = JPATH_ADMINISTRATOR . '/components/com_templates/';
        JLoader::register('TemplatesHelper', $com_templates_path . '/helpers/templates.php');
        jimport('joomla.application.component.model');
        JModelLegacy::addIncludePath($com_templates_path  . 'models');
        $templates_model = JModelLegacy::getInstance('Templates', 'TemplatesModel');

        $templates = $templates_model->getItems();
        echo '<pre>'; var_dump($templates); echo '</pre>'; exit;*/

        /*
        $options = array();
        $db = JFactory::getDBO();
        $q  = 'SELECT u.id, u.name, up1.profile_value AS first_name, up2.profile_value AS last_name FROM `#__users` u ';
        $q .= 'JOIN `#__user_usergroup_map` ugm ON u.id = ugm.user_id ';
        $q .= 'JOIN `#__usergroups` ug ON ugm.group_id = ug.id ';
        $q .= 'JOIN `#__user_profiles` up1 ON u.id = up1.user_id AND up1.profile_key = "firstlastnames.firstname"';
        $q .= 'JOIN `#__user_profiles` up2 ON u.id = up2.user_id AND up2.profile_key = "firstlastnames.lastname"';
        $q .= 'WHERE ug.title = "Staff" ';
        $q .= 'AND u.block = 0 ';
        $q .= 'ORDER BY last_name, first_name;';

        $db->setQuery($q);
        if (!$db->execute($q)) {
            JError::raiseError( 500, $db->stderr() );
            return false;
        }

        $staff_members = $db->loadAssocList();

        $i = 0;
        foreach ($staff_members as $staff_member) {
            $options[] = JHtml::_('select.option', $staff_member['id'], $staff_member['name']);
            $i++;
        }
        if ($i > 0) {
            // Merge any additional options in the XML definition.
            $options = array_merge(parent::getOptions(), $options);
        } else {
            $options = parent::getOptions();
            $options[0]->text = JText::_('COM_SITEAREAS_CONTACT_DEFAULT_NO_STAFF');
        }
        return $options;*/
    }
}