<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('list');

/**
 * Form field for a list of categories with link to edit category.
 */
class JFormFieldRootCategory extends JFormFieldCategory
{
    /**
     * The form field type.
     *
     * @var    string
     */
    public $type = 'RootCategory';

    /**
     * Exclude these groups.
     *
     * @var    array
     */
    protected $excludeCategories = array();

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
            case 'excludeCategories':
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
            case 'excludeCategories':
                $this->excludeCategories = (array) $value;
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
            $this->excludeCategories = explode(',', str_replace(', ', ',', $this->element['exclude_categories']));
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
            $return[] = '    <a href="' . JRoute::_('index.php?option=com_categories&task=category.edit&id=' . $this->value . '&extension=com_content') .'" target="_blank" class="btn  btn-primary">' . JText::_('COM_SITEAREAS_CATEGORY_EDIT_LINK') . ' <span class="icon-out-2" aria-hidden="true"></span></a>';
            $return[] = '</div>';
        }

        return implode("\n", $return);
    }

    /**
     * Method to get the field options for category
     * Use the extension attribute in a form to specify the.specific extension for
     * which categories should be displayed.
     * Use the show_root attribute to specify whether to show the global category root in the list.
     *
     * @return  array    The field option objects.
     */
    protected function getOptions()
    {
        $options = parent::getOptions();

        // If an ID is already selected, we don't want the auto-generate option:
        if (!empty($this->value)) {
            unset($options[1]);
        }

        foreach ($options as $i => $option) {
            // Remove any options that aren't top-level:
            // Note this could be adapted to allow a level to be set on the form element, but I don't
            // need that right now.
            if (strpos($option->text, '- ') === 0) {
                unset($options[$i]);
            }

            // Remove any groups specified in the exclude list:
            if (in_array($option->text, $this->excludeCategories)) {
                unset($options[$i]);
            }
        }

        return $options;
    }
}
