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

class ItpTransifexViewPackage extends JViewLegacy
{
    /**
     * @var JDocumentHtml
     */
    public $document;

    /**
     * @var Joomla\Registry\Registry
     */
    protected $state;
    protected $item;
    protected $form;

    protected $items;

    protected $documentTitle;
    protected $option;

    public function display($tpl = null)
    {
        $this->option = JFactory::getApplication()->input->get('option');
        
        $this->item  = $this->get('Item');
        $this->form  = $this->get('Form');
        $this->state = $this->get('State');

        $this->items = new Transifex\Resource\Resources(JFactory::getDbo());
        $options = array(
            'package_id' => $this->item->id
        );
        $this->items->load($options);

        // Prepare actions, behaviors, scripts and document
        $this->addToolbar();
        $this->setDocument();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @since   1.6
     */
    protected function addToolbar()
    {
        JFactory::getApplication()->input->set('hidemainmenu', true);
        $isNew = ((int)$this->item->id === 0);

        $this->documentTitle = JText::_('COM_ITPTRANSIFEX_EDIT_PACKAGE');

        JToolbarHelper::title($this->documentTitle);

        JToolbarHelper::apply('package.apply');
        JToolbarHelper::save('package.save');

        if (!$isNew) {
            JToolbarHelper::cancel('package.cancel', 'JTOOLBAR_CANCEL');
        } else {
            JToolbarHelper::cancel('package.cancel', 'JTOOLBAR_CLOSE');
        }
    }

    /**
     * Method to set up the document properties.
     *
     * @return void
     */
    protected function setDocument()
    {
        $this->document->setTitle($this->documentTitle);

        // Load language string in JavaScript
        JText::script('COM_ITPTRANSIFEX_DELETE_ITEM_QUESTION');
        JText::script('COM_ITPTRANSIFEX_ERROR_CANNOT_ADD_RESOURCE');
        JText::script('COM_ITPTRANSIFEX_FAIL');

        // Add scripts
        JHtml::_('behavior.formvalidation');
        JHtml::_('bootstrap.tooltip');

        JHtml::_('Prism.ui.pnotify');
        JHtml::_('Prism.ui.joomlaHelper');
        JHtml::_('Prism.ui.bootstrap2Typeahead');

        $this->document->addScript('../media/' . $this->option . '/js/admin/' . strtolower($this->getName()) . '.js');
    }
}
