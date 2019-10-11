<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('menuitem');

/**
 * Form field for a list of active staff members.
 */
class JFormFieldRootMenuItem extends JFormFieldMenuitem
{
    /**
     * The form field type.
     *
     * @var     string
     */
    public $type = 'RootMenuItem';

    /**
     * Exclude these groups.
     *
     * @var    array
     */
    protected $excludeMenus;

    /**
     * Method to get certain otherwise inaccessible properties from the form field object.
     *
     * @param   string  $name  The property name for which to get the value.
     *
     * @return  mixed  The property value or null.
     */
    public function __get($name)
    {
        switch ($name)
        {
            case 'excludeMenus':
                return $this->$name;
        }

        return parent::__get($name);
    }


    /**
     * Method to set certain otherwise inaccessible properties of the form field object.
     *
     * @param   string  $name   The property name for which to set the value.
     * @param   mixed   $value  The value of the property.
     *
     * @return  void
     */
    public function __set($name, $value)
    {
        switch ($name)
        {
            case 'excludeMenus':
                $this->excludeMenus = (array) $value;
                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Method to attach a JForm object to the field.
     *
     * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param   mixed              $value    The form field value to validate.
     * @param   string             $group    The field name group control value. This acts as an array container for the field.
     *                                       For example if the field has name="foo" and the group value is set to "bar" then the
     *                                       full field name would end up being "bar[foo]".
     *
     * @return  boolean  True on success.
     *
     * @see     FormField::setup()
     */
    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        $result = parent::setup($element, $value, $group);

        if ($result === true)
        {
            $this->excludeMenus = explode(',', str_replace(', ', ',', $this->element['exclude_menus']));
        }

        return $result;
    }

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
            $return[] = '    <a href="/administrator/index.php?option=com_menus&task=item.edit&id=' . $this->value . '" target="_blank" class="btn  btn-primary">' . JText::_('COM_SITEAREAS_ROOT_MENU_ITEM_EDIT_LINK') . ' <span class="icon-out-2" aria-hidden="true"></span></a>';
            $return[] = '</div>';

            // Check this is a valid component menu item type:

            // Note this doesn't seem to allow for retrieval of unpublished items, but keep for
            // reference.
            //$menu     = JMenu::getInstance('site');
            //$menuitem = $menu->getItem($this->value);

            $db = JFactory::getDBO();
            $query = "SELECT type, published FROM #__menu WHERE id = " . $this->value;
            $db->setQuery($query);
            $menuitem = $db->loadObject();

            if ($menuitem->type != 'component') {

                $return[] = '<div class="alert alert-warning" style="margin: 1em 0 0 0; display: inline-block;">';
                $return[] = '    ' . JText::_('COM_SITEAREAS_ROOT_MENU_ITEM_STUB_MSG');
                $return[] = '</div>';
            }

            if ($menuitem->published != '1') {
                if ($menuitem->type != 'component') {
                    $return[] = '<br>';
                }
                $return[] = '<div class="alert  alert-info" style="margin: 1em 0 0 0; display: inline-block;">';
                $return[] = '    ' . JText::_('COM_SITEAREAS_ROOT_MENU_ITEM_PUB_MSG');
                $return[] = '</div>';
            }
        }

        return implode("\n", $return);
    }

    /**
     * Method to get the field option groups.
     *
     * @return  array  The field option objects as a nested array in groups.
     */
    protected function getGroups()
    {
        $groups = parent::getGroups();

        // If an ID is already selected, we don't want the auto-generate option:
        if (!empty($this->value)) {
            unset($groups[0]);
        }

        // Remove any groups specified in the exclude list:
        if (!empty($this->excludeMenus)) {
            foreach ($this->excludeMenus as $group) {
                if (array_key_exists($group, $groups)) {
                    unset($groups[$group]);
                }
            }
        }

        // Remove any options that aren't top-level:
        // Note this could be adapted to allow a level to be set on the form element, but I don't
        // need that right now.
        foreach ($groups as $i => $group) {
            foreach ($group as $j => $item) {
                if (strpos($item->text, '- ') === 0) {
                    unset($groups[$i][$j]);
                }
            }
        }

        return $groups;
    }
}