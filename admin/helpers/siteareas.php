<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * SiteAreasHelper component helper.
 */
class SiteAreasHelper
{
    /**
     * Add style
     */
    public static function addStyle()
    {
        // Set some global property
        $document = JFactory::getDocument();

        $document->addStyleDeclaration('.icon-record:before {content: "\e244";}');
    }


    /**
     * Get the actions
     */
    public static function getActions($itemId = 0, $model = null)
    {
        jimport('joomla.access.access');
        $user   = JFactory::getUser();
        $result = new JObject;

        if (empty($itemId)) {
            $assetName = 'com_siteareas';
        }
        else {
            $assetName = 'com_siteareas.record.'.(int) $itemId;
        }

        $actions = JAccess::getActions('com_siteareas', 'component');

        foreach ($actions as $action) {
            $result->set($action->name, $user->authorise($action->name, $assetName));
        }

        // Check if user belongs to assigned category and permit edit if so:
        if ($model) {
            $item  = $model->getItem($itemId);

            if (!!($user->authorise('core.edit', 'com_siteareas')
            || $user->authorise('core.edit', 'com_content.category.' . $item->catid))) {
                $result->set('core.edit', true);
            }
        }

        return $result;
    }
}