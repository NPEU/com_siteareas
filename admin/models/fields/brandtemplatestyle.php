<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('TemplatestyleField');

/**
 * Form field for a list of template style with link to edit it.
 */
class JFormFieldBrandTemplateStyle extends JFormFieldTemplatestyle
{
    /**
     * The form field type.
     *
     * @var    string
     */
    public $type = 'BrandTemplateStyle';

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
        // A brand has to have been created in order to create a branded template:
        $brand_id = $this->form->getValue('brand_id', 'params');
        if (empty($brand_id)) {
            $element->addAttribute('disabled', 'true');
        }

        $result = parent::setup($element, $value, $group);

        if ($result === true)
        {
            // Get the template from the config params:
            $params = JComponentHelper::getParams('com_siteareas');
            $this->template = explode(',', $params->get('template'))[0];
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
            $return[] = '    <a href="/administrator/index.php?option=com_templates&task=style.edit&id=' . $this->value . '" target="_blank" class="btn  btn-primary">' . JText::_('COM_SITEAREAS_TEMPLATE_STYLE_EDIT_LINK') . ' <span class="icon-out-2" aria-hidden="true"></span></a>';
            $return[] = '</div>';
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
            unset($groups[0][1]);
        }

        return $groups;
    }
}
