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
class JFormFieldChildCategory extends JFormFieldCategory
{
    /**
     * The form field type.
     *
     * @var    string
     */
    public $type = 'ChildCategory';

    /**
     * Parent category field name/id.
     *
     * @var    array
     */
    protected $parentCategoryFieldname;


    /**
     * Parent category field text.
     *
     * @var    array
     */
    protected $parentCategoryText;


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
            case 'parentCategoryText':
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
            case 'parentCategoryText':
                $this->parentCategoryText = (array) $value;
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
        $parent_category_fieldname = (string) $element->attributes()->parent_category;
        $this->parentCategoryFieldname = $parent_category_fieldname;

        // A root category has to have been created in order to create a news category:
        $parent_category_id = $this->form->getValue($parent_category_fieldname, 'params');
        $parent_category_field = $this->form->getField($parent_category_fieldname, 'params');
        $parent_category_field_text = false;

        // We need to loop through the options for find on that matches the correct value:
        foreach ($parent_category_field->getOptions() as $option) {
            if ($option->value === $parent_category_id) {
                $parent_category_field_text = $option->text;
                break;
            }
        }

        if (!$parent_category_field_text) {
            $element->addAttribute('disabled', 'true');
        }

        $result = parent::setup($element, $value, $group);

        if ($result === true)
        {
            $this->parentCategoryText = $parent_category_field_text;
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

        /*
        The Edit Category link shows an error "You are not permitted to use that link to directly access that page (#139)."
        and won't open the category unless it's already recently opened.
        Not sure how to fix this so leaving off for now.
        Note I've looked at how the com_categories is set up and I can't see how this is being achieved.
        */

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

        // Only keep options that are in our specified parent category:
        $capturing = false;
        foreach ($options as $i => $option) {
            // Matches parent:
            if ($option->text == $this->parentCategoryText) {
                // Delete the option:
                unset($options[$i]);
                // Start capturing:
                $capturing = true;
                // Next!
                continue;
            }

            // Not capturing:
            if (!$capturing) {
                // Delete the option:
                unset($options[$i]);
                // Next!
                continue;
            }

            // Currently capturing, are we back to top-level categories?
            if ($capturing && strpos($option->text, '- ') === false) {
                // Delete the option:
                unset($options[$i]);
                // Stop capturing:
                $capturing = false;
                // Next!
                continue;
            }

            // If we got this far we should be 'inside' the correct parent category, so we want to
            // keep those options:
            continue;
        }

        return $options;
    }
}
