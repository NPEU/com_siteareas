

<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('sql');

/**
 * Form field for a list of categories with link to edit category.
 */
class JFormFieldModule extends JFormFieldSQL
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.6
     */
    public $type = 'Module';

    /**
     * The type of module we want to list.
     *
     * @var    string
     */
    protected $moduletype = '';

    /**
     * The query template.
     *
     * @var    string
     */
    protected $query_template = "SELECT id, CONCAT(title, IF(LENGTH(`note`), CONCAT(' (', `note`, ')'), '')) AS title FROM #__modules WHERE module = '%s' ORDER BY title";

    /**
     * Method to get certain otherwise inaccessible properties from the form field object.
     *
     * @param   string  $name  The property name for which to get the value.
     *
     * @return  mixed  The property value or null.
     *
     * @since   3.2
     */
    public function __get($name)
    {
        switch ($name)
        {
            case 'moduletype':
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
     *
     * @since   3.2
     */
    public function __set($name, $value)
    {
        switch ($name)
        {
            case 'moduletype':
                $this->moduletype = (array) $value;
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
     * @since   3.2
     */
    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        $element['query'] = sprintf($this->query_template, $element['moduletype']);
        $element['key_field'] = 'id';
        $element['value_field'] = 'title';
        
        $result = parent::setup($element, $value, $group);

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
        The Edit Cateogy link shows an error "You are not permitted to use that link to directly access that page (#139)."
        and won't open the category unless it's already recently opened.
        Not sure how to fix this so leaving off for now.
        Note I've looked at how the com_categories is set up and I can't see how this is being achieved.
        */

        if (!empty($this->value)) {
            $return[] = '<div style="margin: 1em 0 0 0;">';
            $return[] = '    <a href="' . JRoute::_('https://dev.npeu.ox.ac.uk/administrator/index.php?option=com_modules&task=module.edit&id=' . $this->value) .'" target="_blank" class="btn  btn-primary">' . JText::_('COM_SITEAREAS_MODULE_EDIT_LINK') . ' <span class="icon-out-2" aria-hidden="true"></span></a>';
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
     *
     * @since   1.6
     */
    protected function getOptions()
    {
        $options = parent::getOptions();

        // If an ID is already selected, we don't want the auto-generate option:
        if (!empty($this->value)) {
            unset($options[1]);
        }

        /*
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
        */

        return $options;
    }
}
