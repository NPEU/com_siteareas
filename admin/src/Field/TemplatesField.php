<?php
namespace NPEU\Component\Siteareas\Administrator\Field;

use Joomla\CMS\Application\ApplicationHelper;
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
 * Form field for a list of templates.
 */
class TemplatesField extends ListField
{
    /**
     * The form field type.
     *
     * @var     string
     */
    protected $type = 'Templates';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     */
    protected function getOptions()
    {
        $lang = Factory::getLanguage();
        $client = ApplicationHelper::getClientInfo('site', true);

        $options = [];
        $db = Factory::getDBO();

        $query = $db->getQuery(true);

        // Build the query.
        $query->select('s.id, s.title, e.name as name, e.extension_id as ext_id, s.template')
            ->from('#__template_styles as s')
            ->where('s.client_id = ' . (int) $client->id)
            ->order('template')
            ->order('title');

        $query->join('LEFT', '#__extensions as e on e.element=s.template')
            ->where('e.enabled = 1')
            ->where($db->quoteName('e.type') . ' = ' . $db->quote('template'));

        // Set the query and load the styles.
        $db->setQuery($query);

        if (!$db->execute($query)) {
            throw new GenericDataException($db->stderr(), 500);
            return false;
        }

        $styles = $db->loadObjectList();

        // Build the options array.
        $options = [];
        $templates = [];


        if ($styles) {
            foreach ($styles as $style) {
                $template = $style->template;

                $file = 'tpl_' . $template . '.sys';
                $path_1 = $client->path;
                $path_2 = $client->path . '/templates/' . $template;
                #echo '<pre>'; var_dump($path_1); echo '</pre>'; #exit;
                #$lang->load($file, $path_1, null, false, true);

                $lang->load($file, $path_1, null, false, true) || $lang->load($file, $path_2, null, false, true);

                $name = Text::_(strtoupper($style->name));

                if (in_array($name, $templates)) {
                    continue;
                }

                $options[] = HTMLHelper::_('select.option', $template . ',' . $style->ext_id . ',' . $name, $name);
                $templates[] = $name;

            }
        }
        return $options;
    }
}