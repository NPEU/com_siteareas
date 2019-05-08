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
 * SiteAreas Records List Model
 */
class SiteAreasModelRecords extends JModelList
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @see     JController
     */
    public function __construct($config = array())
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id',
                'users_name',
                'message',
                'state'
            );
        }

        parent::__construct($config);
    }

    /**
     * Method to build an SQL query to load the list data.
     *
     * @return      string  An SQL query
     */
    protected function getListQuery()
    {
        // Initialize variables.
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        // Create the base select statement.
        $query->select('sa.*')
              ->from($db->quoteName('#__siteareas') . ' AS sa');
              
        // Join the categories table again for the project group:
        /*$query->select('pc.title AS project_group')
            ->join('LEFT', '#__categories AS pc ON pc.id = sa.pr_catid');**/
              
              
        // Join over the users for the checked out user.
        $query->select('uc.name AS editor')
            ->join('LEFT', '#__users AS uc ON uc.id=sa.checked_out');

        // Join the categories table:
        /*$query->select('c.title AS category_title')
            ->join('LEFT', '#__categories AS c ON c.id = p.catid');    */
            
        // Filter: like / search
        $search = $this->getState('filter.search');

        if (!empty($search))
        {
            $like = $db->quote('%' . $search . '%');
            $query->where('sa.name LIKE ' . $like);
            $query->where('sa.alias LIKE ' . $like);
        }

        // Filter by state state
        $state = $this->getState('filter.published');

        if (is_numeric($state))
        {
            $query->where('sa.state = ' . (int) $state);
        }
        elseif ($state === '')
        {
            $query->where('(sa.state IN (0, 1))');
        }

        // Add the list ordering clause.
        $orderCol   = $this->state->get('list.ordering', 'sa.name');
        $orderDirn  = $this->state->get('list.direction', 'asc');

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
