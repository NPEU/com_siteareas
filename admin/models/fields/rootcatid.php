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
class JFormFieldRootCatId extends JFormFieldCategory
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.6
	 */
	public $type = 'RootCatId';

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

        
        if (!empty($this->value)) {
            $return[] = '<div style="margin: 1em 0 0 0;">';
            $return[] = '    <a href="' . JRoute::_('index.php?option=com_categories&view=category&layout=edit&id=' . $this->value . '&extension=com_content') .'" target="_blank" class="btn  btn-primary">' . JText::_('COM_SITEAREAS_CATEGORY_EDIT_LINK') . ' <span class="icon-out-2" aria-hidden="true"></span></a>';
            $return[] = '</div>';
        }
        */

        return implode("\n", $return);
	}
}
