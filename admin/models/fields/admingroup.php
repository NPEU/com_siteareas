<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('usergrouplist');

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Helper\UserGroupsHelper;

/**
 * Form field for a list of Area Admin Groups.
 */
class JFormFieldAdminGroup extends JFormFieldUsergrouplist
{
    /**
     * The form field type.
     *
     * @var     string
     */
    public $type = 'AdminGroup';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     */
    protected function getInput()
    {

        $return   = array();
        $return[] = parent::getInput();

        if (!empty($this->value)) {
            $return[] = '<div style="margin: 1em 0 0 0;">';
            $return[] = '    <a href="/administrator/index.php?option=com_users&task=group.edit&id=' . $this->value . '" target="_blank" class="btn  btn-primary">' . JText::_('COM_SITEAREAS_ADMIN_GROUP_EDIT_LINK') . ' <span class="icon-out-2" aria-hidden="true"></span></a>';
            $return[] = '</div>';

            // Check this is a valid component menu item type:

            // Note this doesn't seem to allow for retrieval of unpublished items, but keep for
            // reference.
            //$menu     = JMenu::getInstance('site');
            //$menuitem = $menu->getItem($this->value);

            /*$db = JFactory::getDBO();
            $query = "SELECT type, published FROM #__menu WHERE id = " . $this->value;
            $db->setQuery($query);
            $menuitem = $db->loadObject();

            if ($menuitem->type != 'component') {

                $return[] = '<div class="alert alert-warning" style="margin: 1em 0 0 0; display: inline-block;">';
                $return[] = '    ' . JText::_('COM_SITEAREAS_ROOT_MENU_ITEM_STUB_MSG');
                $return[] = '</div>';
            }

            if ($menuitem->published != '1') {

                $return[] = '<div class="alert  alert-info" style="margin: 1em 0 0 0; display: inline-block;">';
                $return[] = '    ' . JText::_('COM_SITEAREAS_ROOT_MENU_ITEM_PUB_MSG');
                $return[] = '</div>';
            }*/

        }

        return implode("\n", $return);
    }


    /**
     * This method is copied from the parent class:
     * UsergrouplistField (/libraries/src/Form/Field/UsergrouplistField.php)
     * This is because I'm not realy sure how modifying the static options returned from that methof
     * affects things, so I thought it safest just to copy it.
     * Changes made are indicated.
     *
     * Method to get the options to populate list
     *
     * @return  array  The field option objects.
     *
     * @since   3.2
     */
    protected function getOptions()
    {
        // Hash for caching
        $hash = md5($this->element);

        if (!isset(static::$options[$hash]))
        {
            // This was static::$options[$hash] = parent::getOptions();
            //                                    ^----^
            // but we don't want parent now, we want what it was originally referencing
            //                        v------------v
            static::$options[$hash] = JFormFieldList::getOptions();

            $groups         = UserGroupsHelper::getInstance()->getAll();
            $checkSuperUser = (int) $this->getAttribute('checksuperusergroup', 0);
            $isSuperUser    = Factory::getUser()->authorise('core.admin');
            $options        = array();

            $top_group_id    = 11;
            $top_group_level = false;
            $capture_options = false;

            foreach ($groups as $group)
            {
                // Don't show super user groups to non super users.
                if ($checkSuperUser && !$isSuperUser && Access::checkGroup($group->id, 'core.admin'))
                {
                    continue;
                }

                // This is new code added to show only the groups we're interested in:
                // If the top-group is reached, we can start processing NEXT iteration:
                if ($group->id == $top_group_id) {
                    $capture_options = true;
                    $top_group_level = $group->level;

                    // We don't want to actually add the top-level group:
                    continue;
                }

                // If we haven't started capturing yet, skip it:
                if (!$capture_options) {
                    continue;
                }

                // Ok, so we're capturing now, so the level should be 1 deeper than the top-group.

                // Check for when the level goes back up, and stop capturing.
                if ($group->level == $top_group_level) {
                    $capture_options = false;
                    continue;
                }

                // Check for when the level goes deeper and skip:
                if ($group->level != $top_group_level + 1) {
                    continue;
                }

                $options[] = (object) array(
                    'text'  => $group->title,
                    'value' => $group->id,
                    'level' => $group->level
                );

                // Done adding new stuff.
                // The following was the original code:
                /*
                $options[] = (object) array(
                    'text'  => str_repeat('- ', $group->level) . $group->title,
                    'value' => $group->id,
                    'level' => $group->level
                );
                */
            }

            static::$options[$hash] = array_merge(static::$options[$hash], $options);
        }

        return static::$options[$hash];
    }
}