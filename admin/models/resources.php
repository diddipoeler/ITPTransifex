<?php
/**
 * @package      ITPTransifex
 * @subpackage   Components
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2015 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      GNU General Public License version 3 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

/**
 * Get a list of items
 *
 * @package      ITPTransifex
 * @subpackage   Components
 */
class ItpTransifexModelResources extends JModelList
{
    /**
     * Constructor.
     *
     * @param   array  $config An optional associative array of configuration settings.
     *
     * @see     JController
     * @since   1.6
     */
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'name', 'a.name',
            );
        }

        parent::__construct($config);
    }
    
    protected function populateState($ordering = null, $direction = null)
    {
        // List state information.
        parent::populateState('a.id', 'asc');

        // Load the component parameters.
        $params = JComponentHelper::getParams($this->option);
        $this->setState('params', $params);

        // Filter search.
        $value = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $value);

        // Get project ID
        $value = $this->getUserStateFromRequest($this->context . '.project_id', 'id');
        $this->setState('project_id', $value);

        // Filter type
        $value = $this->getUserStateFromRequest($this->context . '.filter.category', 'filter_category');
        $this->setState('filter.category', $value);

        // Filter state
        $value = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state');
        $this->setState('filter.state', $value);

        // Filter assigned
        $value = $this->getUserStateFromRequest($this->context . '.filter.assigned', 'filter_assigned');
        $this->setState('filter.assigned', $value);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string $id A prefix for the store id.
     *
     * @return  string      A store id.
     * @since   1.6
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.category');
        $id .= ':' . $this->getState('filter.state');
        $id .= ':' . $this->getState('filter.assigned');
        $id .= ':' . $this->getState('project_id');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @throws \RuntimeException
     * @return  JDatabaseQuery
     * @since   1.6
     */
    protected function getListQuery()
    {
        $projectId = (int)$this->getState('project_id');
        
        $db = $this->getDbo();
        /** @var $db JDatabaseDriver */

        // Create a new query object.
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'a.id, a.name, a.alias, a.filename, a.category, a.source, a.path, ' .
                'a.published, a.source_language_code'
            )
        )
        ->from($db->quoteName('#__itptfx_resources', 'a'))
        ->where('a.project_id = ' . $projectId);

        // Filter by category
        $category = (string)$this->getState('filter.category');
        if ($category !== '') {
            $query->where('a.category = ' . $db->quote($category));
        }

        // Filter by state
        $published = $this->getState('filter.state');
        if (is_numeric($published)) {
            $query->where('a.published = ' . (int)$published);
        } elseif ($published === null or $published === '') {
            $query->where('(a.published IN (0, 1))');
        }

        // Filter by assigned
        $assigned = $this->getState('filter.assigned');
        if ($assigned !== null and $assigned !== '') {
            if ((int)$assigned === 1) { // Select assigned resources.
                $resourcesIds = $this->getAssignedResources($projectId);
            } else {
                $resourcesIds = $this->getNotAssignedResources($projectId);
            }

            if (count($resourcesIds) === 0) {
                $resourcesIds[] = 0;
            }

            $query->where('a.id IN (' . implode(',', $resourcesIds) . ')');
        }

        // Filter by search in title
        $search = (string)$this->getState('filter.search');
        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int)substr($search, 3));

            } elseif (stripos($search, 'pid:') === 0) { // Filter by package ID.
                $query->leftJoin($db->quoteName('#__itptfx_packages_map', 'b') . ' ON a.id = b.resource_id');
                $query->where('b.package_id = ' . (int)substr($search, 4));
            } else {
                $escaped = $db->escape($search, true);
                $quoted  = $db->quote('%' . $escaped . '%', false);
                $query->where('a.name LIKE ' . $quoted);
            }
        }

        // Add the list ordering clause.
        $orderString = $this->getOrderString();
        $query->order($db->escape($orderString));

        return $query;
    }

    protected function getOrderString()
    {
        $orderCol  = $this->getState('list.ordering');
        $orderDirn = $this->getState('list.direction');

        return $orderCol . ' ' . $orderDirn;
    }

    public function getLanguages()
    {
        $db = $this->getDbo();

        // Prepare project folder
        $query = $db->getQuery(true);

        $query
            ->select('a.id, a.name, a.code, a.short_code')
            ->from($db->quoteName('#__itptfx_languages', 'a'));

        $db->setQuery($query);

        return (array)$db->loadObjectList();
    }

    protected function getAssignedResources($projectId)
    {
        $db = $this->getDbo();

        $subQuery = $db->getQuery(true);
        $subQuery
            ->select('a.id')
            ->from($db->quoteName('#__itptfx_packages', 'a'))
            ->where('a.project_id =' .(int)$projectId);

        $query = $db->getQuery(true);
        $query
            ->select('DISTINCT b.resource_id')
            ->from($db->quoteName('#__itptfx_packages_map', 'b'))
            ->where('b.package_id IN ( '. $subQuery .')');

        $db->setQuery($query);

        return (array)$db->loadColumn();
    }

    protected function getNotAssignedResources($projectId)
    {
        $resources = $this->getAssignedResources($projectId);

        $db = $this->getDbo();

        $query = $db->getQuery(true);
        $query
            ->select('a.id')
            ->from($db->quoteName('#__itptfx_resources', 'a'))
            ->where('a.project_id =' .(int)$projectId);

        if (count($resources) > 0) {
            $query->where('a.id NOT IN (' . implode(',', $resources) . ')');
        }

        $db->setQuery($query);

        return (array)$db->loadColumn();
    }
}
