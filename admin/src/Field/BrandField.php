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

/**
 * Form field for a list of brands.
 */
class BrandField extends ListField
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
        $params  = clone ComponentHelper::getParams('com_siteareas');
        $options = array();
        $db = Factory::getDBO();
        $query  = 'SELECT id, name FROM #__brands WHERE catid = ' . $params->get('brand_category_id') . ' AND state = 1 ORDER BY name';


        $db->setQuery($query);
        if (!$db->execute($query)) {
            throw new GenericDataException($db->stderr(), 500);
            return false;
        }

        $brands = $db->loadAssocList();

        $i = 0;
        foreach ($brands as $brand) {
            $options[] = HTMLHelper::_('select.option', $brand['id'], $brand['name']);
            $i++;
        }
        if ($i > 0) {
            // Merge any additional options in the XML definition.
            $options = array_merge(parent::getOptions(), $options);
        } else {
            $options = parent::getOptions();
            $options[0]->text = Text::_('COM_SITEAREAS_BRAND_DEFAULT_NO_BRANDS');
        }

        // If an ID is already selected, we don't want the auto-generate option:
        if (!empty($this->value)) {
            unset($options[1]);
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
            $return[] = '    <a href="/administrator/index.php?option=com_brands&task=brand.edit&id=' . $this->value . '" target="_blank" class="btn  btn-primary">' . Text::_('COM_SITEAREAS_BRAND_EDIT_LINK') . ' <span class="icon-out-2" aria-hidden="true"></span></a>';
            $return[] = '</div>';
        }

        return implode("\n", $return);
    }
}