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
class ItpTransifexModelPackages extends JModelList
{
    /**
     * Constructor.
     *
     * @param   array $config  An optional associative array of configuration settings.
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
                'type', 'a.type',
                'project_id', 'a.project_id',
                'project', 'b.name',
                'language_name', 'c.name',
            );
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = null, $direction = null)
    {
        // Load the component parameters.
        $params = JComponentHelper::getParams($this->option);
        $this->setState('params', $params);

        // Load the filter state.
        $value = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $value);

        $value = $this->getUserStateFromRequest($this->context . '.filter.project', 'filter_project');
        $this->setState('filter.project', $value);

        $value = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language');
        $this->setState('filter.language', $value);

        $value = $this->getUserStateFromRequest($this->context . '.filter.language2', 'filter_language2');
        $this->setState('filter.language2', $value);

        $value = $this->getUserStateFromRequest($this->context . '.filter.type', 'filter_type');
        $this->setState('filter.type', $value);

        // List state information.
        parent::populateState('a.id', 'asc');
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
        $id .= ':' . $this->getState('filter.project');
        $id .= ':' . $this->getState('filter.language');
        $id .= ':' . $this->getState('filter.language2');
        $id .= ':' . $this->getState('filter.type');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @throws RuntimeException
     * @return  JDatabaseQuery
     * @since   1.6
     */
    protected function getListQuery()
    {
        $db = $this->getDbo();
        /** @var $db JDatabaseDriver*/

        // Create a new query object.
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'a.id, a.name, a.alias, a.filename, a.language, a.version, a.project_id, a.type, ' .
                'b.name AS title, ' .
                'c.name AS language_name'
            )
        );

        $query->from($db->quoteName('#__itptfx_packages', 'a'));
        $query->leftJoin($db->quoteName('#__itptfx_projects', 'b') . ' ON a.project_id = b.id');
        $query->leftJoin($db->quoteName('#__itptfx_languages', 'c') . ' ON a.language = c.locale');

        // Filter by project
        $projectId = (int)$this->getState('filter.project');
        if ($projectId > 0) {
            $query->where('a.project_id = ' . (int)$projectId);
        }

        // Filter by language
        $languageCode = (string)$this->getState('filter.language');
        if ($languageCode !== '') {
            $query->where('a.language = ' . $db->quote($languageCode));
        }

        // Filter by type
        $type = (string)$this->getState('filter.type');
        if ($type !== '') {
            $query->where('a.type = ' . $db->quote($type));
        }

        // Filter by second language
        $language2 = (string)$this->getState('filter.language2');
        if ($languageCode !== '' and $language2 !== '') {
            $ids = $this->getPackagesWithoutLanguage($projectId, $languageCode, $language2);

            if (count($ids) === 0) {
                $ids = array(0);
            }

            $query->where('a.id IN ( ' . implode(',', $ids) . ')');

            $app = JFactory::getApplication();
            $app->enqueueMessage(JText::sprintf('COM_ITPTRANSIFEX_INFO_FILTER_SECOND_LANGUAGE_S', $language2), 'notice');
        }

        // Filter by search in title
        $search = (string)$this->getState('filter.search');
        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int)substr($search, 3));
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


    protected function getPackagesWithoutLanguage($projectId, $language1, $language2)
    {
        $db = $this->getDbo();

        $subQuery = $db->getQuery(true);
        $subQuery
            ->select('b.filename')
            ->from($db->quoteName('#__itptfx_packages', 'b'))
            ->where('b.project_id =' .(int)$projectId)
            ->where('b.language = ' .$db->quote($language2));

        $query = $db->getQuery(true);
        $query
            ->select('a.id')
            ->from($db->quoteName('#__itptfx_packages', 'a'))
            ->where('a.project_id =' .(int)$projectId)
            ->where('a.language = ' .$db->quote($language1))
            ->where('a.language != ' .$db->quote($language2))
            ->where('a.filename NOT IN ( ' .$subQuery .')');

        $db->setQuery($query);

        return (array)$db->loadColumn();
    }
}
