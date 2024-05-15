<?php
namespace NPEU\Component\Siteareas\Administrator\Field;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\Database\DatabaseInterface;

defined('_JEXEC') or die;

#JFormHelper::loadFieldClass('list');

/**
 * Form field for a list of active staff members.
 */
class StaffField extends ListField
{
    /**
     * The form field type.
     *
     * @var     string
     */
    protected $type = 'Staff';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     */
    protected function getOptions()
    {
        $options = array();
        $db = Factory::getDBO();
        $query  = 'SELECT u.id, u.name, up1.profile_value AS first_name, up2.profile_value AS last_name FROM `#__users` u ';
        $query .= 'JOIN `#__user_usergroup_map` ugm ON u.id = ugm.user_id ';
        $query .= 'JOIN `#__usergroups` ug ON ugm.group_id = ug.id ';
        $query .= 'JOIN `#__user_profiles` up1 ON u.id = up1.user_id AND up1.profile_key = "firstlastnames.firstname"';
        $query .= 'JOIN `#__user_profiles` up2 ON u.id = up2.user_id AND up2.profile_key = "firstlastnames.lastname"';
        $query .= 'WHERE ug.title = "Staff" ';
        $query .= 'AND u.block = 0 ';
        $query .= 'ORDER BY last_name, first_name;';

        $db->setQuery($query);
        if (!$db->execute($query)) {
            throw new GenericDataException($db->stderr(), 500);
            return false;
        }

        $staff_members = $db->loadAssocList();

        $i = 0;
        foreach ($staff_members as $staff_member) {
            $options[] = HTMLHelper::_('select.option', $staff_member['id'], $staff_member['name']);
            $i++;
        }
        if ($i > 0) {
            // Merge any additional options in the XML definition.
            $options = array_merge(parent::getOptions(), $options);
        } else {
            $options = parent::getOptions();
            $options[0]->text = Text::_('COM_SITEAREAS_CONTACT_DEFAULT_NO_STAFF');
        }
        return $options;
    }
}