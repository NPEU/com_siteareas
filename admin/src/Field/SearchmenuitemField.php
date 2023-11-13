<?php
namespace NPEU\Component\Siteareas\Administrator\Field;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\MenuitemField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die;

#JFormHelper::loadFieldClass('list');

/**
 * Form field for a list of template styles with link to edit it.
 */
class SearchMenuItemField extends MenuitemField
{
    /**
     * The form field type.
     *
     * @var     string
     *
     * Note this should probably be 'protected' as with most other Fields,
     * but TemplatestyleField declares this as 'public' for some reason(bug?)
     * so must be public here too.
     */
    public $type = 'SearchMenuItem';


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
        // A root menu item has to have been created in order to create a search item:
        $root_menu_item_id = $this->form->getValue('root_menu_item_id');
        if (empty($root_menu_item_id)) {
            $element['disabled'] = "true";
        }

        // Force the menu type:
        $element['menu_type'] = $this->form->getValue('alias');

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

        if (!empty($this->value)) {
            $return[] = '<div style="margin: 1em 0 0 0;">';
            $return[] = '    <a href="/administrator/index.php?option=com_menus&task=item.edit&id=' . $this->value . '" target="_blank" class="btn  btn-primary">' . Text::_('COM_SITEAREAS_SEARCH_MENU_ITEM_EDIT_LINK') . ' <span class="icon-out-2" aria-hidden="true"></span></a>';
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

        // Remove any options we don't want:
        foreach ($groups as $i => $group) {
            foreach ($group as $j => $item) {
                if ($item->text == 'Menu_Item_Root') {
                    unset($groups[$i][$j]);
                }
            }
        }

        return $groups;
    }
}