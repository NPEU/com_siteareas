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
 * Form field for a list of active staff members.
 */
class JFormFieldBrand extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var     string
     */
    protected $type = 'Brand';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     */
    protected function getOptions()
    {
        $params  = clone JComponentHelper::getParams('com_siteareas');
        $options = array();
        $db = JFactory::getDBO();
        $q  = 'SELECT id, name FROM #__brands WHERE catid = ' . $params->get('brand_category_id') . ' ORDER BY name';


        $db->setQuery($q);
        if (!$db->execute($q)) {
            JError::raiseError( 500, $db->stderr() );
            return false;
        }

        $brands = $db->loadAssocList();

        $i = 0;
        foreach ($brands as $brand) {
            $options[] = JHtml::_('select.option', $brand['id'], $brand['name']);
            $i++;
        }
        if ($i > 0) {
            // Merge any additional options in the XML definition.
            $options = array_merge(parent::getOptions(), $options);
        } else {
            $options = parent::getOptions();
            $options[0]->text = JText::_('COM_SITEAREAS_BRAND_DEFAULT_NO_BRANDS');
        }
        return $options;
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
            $return[] = '    <a href="/administrator/index.php?option=com_brands&task=brand.edit&id=' . $this->value . '" target="_blank" class="btn  btn-primary">' . JText::_('COM_SITEAREAS_BRAND_EDIT_LINK') . ' <span class="icon-out-2" aria-hidden="true"></span></a>';
            $return[] = '</div>';
        }

        return implode("\n", $return);
    }
}