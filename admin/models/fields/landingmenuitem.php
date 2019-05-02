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
class JFormFieldLandingMenuItem extends JFormFieldMenuitem
{
    /**
     * The form field type.
     *
     * @var     string
     */
    public $type = 'LandingMenuItem';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
       
        if (empty($this->value)) {
            
            //$return .= '<div class="control-label" style="width:auto;"><i>Reserved when first saved</i>';
            return parent::getInput();
            
        } else {
            
            $return = array();
            $return[] = '<div>';
            $return[] = '    <a href="/administrator/index.php?option=com_menus&view=item&client_id=0&layout=edit&id=' . $this->value . '" target="_blank" class="btn  btn-primary">Edit Menu Item <span class="icon-out-2" aria-hidden="true"></span></a>';
            $return[] = '</div>';
            $return[] = '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '">';
            return implode("\n", $return);
        }
        
        // @TODO when possible, add a method to the related plugin to listen for menu item saves
        // that checks if the menu item is assigned to a project and, if so, update the projects
        // table with the type and/or link so that HERE we can check for if the menu item has been
        // properly set (and not just a heading) and then change the wording accordingly.

	}
    
    /**
	 * Method to get the field option groups.
	 *
	 * @return  array  The field option objects as a nested array in groups.
	 *
	 * @since   1.6
	 */
	protected function getGroups()
	{
        $groups = parent::getGroups();

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