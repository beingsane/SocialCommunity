<?php
/**
 * @package      Socialcommunity
 * @subpackage   Components
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2016 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      GNU General Public License version 3 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

class SocialcommunityViewProfiles extends JViewLegacy
{
    /**
     * @var JDocumentHtml
     */
    public $document;

    /**
     * @var Joomla\Registry\Registry
     */
    protected $state;

    protected $items;
    protected $pagination;

    protected $filesystemHelper;

    protected $option;
    protected $listOrder;
    protected $listDirn;
    protected $saveOrder;
    protected $saveOrderingUrl;

    protected $sortFields;

    protected $sidebar;
    
    public function display($tpl = null)
    {
        $this->option     = JFactory::getApplication()->input->get('option');

        // Create profiles if orphans exist.
        $createProfilesCommand = new \Socialcommunity\Profile\Command\CreateProfiles();
        $createProfilesCommand->setGateway(new \Socialcommunity\Profile\Command\Gateway\Joomla\CreateProfiles(JFactory::getDbo()));
        $createProfilesCommand->handle();

        $this->state      = $this->get('State');
        $this->items      = $this->get('Items');
        $this->pagination = $this->get('Pagination');

        // Prepare social profiles.
        $helperBus = new Prism\Helper\HelperBus($this->items);
        $helperBus->addCommand(new Socialcommunity\Helper\Joomla\PrepareSocialProfilesHelper());
        $helperBus->handle();

        // Load the component parameters.
        $params           = $this->state->get('params');

        $this->filesystemHelper  = new Prism\Filesystem\Helper($params);

        // Prepare sorting data
        $this->prepareSorting();

        // Prepare actions
        $this->addToolbar();
        $this->addSidebar();
        $this->setDocument();

        parent::display($tpl);
    }

    /**
     * Prepare sortable fields, sort values and filters.
     */
    protected function prepareSorting()
    {
        // Prepare filters
        $this->listOrder = $this->escape($this->state->get('list.ordering'));
        $this->listDirn  = $this->escape($this->state->get('list.direction'));
        $this->saveOrder = (strcmp($this->listOrder, 'a.ordering') === 0);

        if ($this->saveOrder) {
            $this->saveOrderingUrl = 'index.php?option=' . $this->option . '&task=' . $this->getName() . '.saveOrderAjax&format=raw';
            JHtml::_('sortablelist.sortable', $this->getName() . 'List', 'adminForm', strtolower($this->listDirn), $this->saveOrderingUrl);
        }

        $this->sortFields = array(
            'a.name'         => JText::_('COM_SOCIALCOMMUNITY_NAME'),
            'b.registerDate' => JText::_('COM_SOCIALCOMMUNITY_REGISTERED'),
            'a.id'           => JText::_('JGRID_HEADING_ID')
        );
    }

    /**
     * Add a menu on the sidebar of page
     */
    protected function addSidebar()
    {
        // Add submenu
        SocialcommunityHelper::addSubmenu($this->getName());
        
        JHtmlSidebar::setAction('index.php?option=' . $this->option . '&view=' . $this->getName());

        $states = array(
            JHtml::_('select.option', '0', JText::_('COM_SOCIALCOMMUNITY_DOES_NOT_EXISTS')),
            JHtml::_('select.option', '1', JText::_('COM_SOCIALCOMMUNITY_EXISTS'))
        );

        JHtmlSidebar::addFilter(
            JText::_('COM_SOCIALCOMMUNITY_SELECT_PROFILE_STATE'),
            'filter_profile',
            JHtml::_('select.options', $states, 'value', 'text', $this->state->get('filter.profile'), true)
        );

        $this->sidebar = JHtmlSidebar::render();
    }

    /**
     * Add the page title and toolbar.
     *
     * @since   1.6
     */
    protected function addToolbar()
    {
        // Set toolbar items for the page
        JToolbarHelper::title(JText::_('COM_SOCIALCOMMUNITY_PROFILES_MANAGER'));
        JToolbarHelper::editList('profile.edit');
        JToolbarHelper::divider();
        JToolbarHelper::deleteList(JText::_('COM_SOCIALCOMMUNITY_DELETE_ITEMS_QUESTION'), 'profiles.delete');
        JToolbarHelper::divider();
        JToolbarHelper::custom('profiles.backToDashboard', 'dashboard', '', JText::_('COM_SOCIALCOMMUNITY_DASHBOARD'), false);
    }

    /**
     * Method to set up the document properties
     *
     * @return void
     */
    protected function setDocument()
    {
        $this->document->setTitle(JText::_('COM_SOCIALCOMMUNITY_PROFILES_MANAGER'));

        // Scripts
        JHtml::_('behavior.multiselect');
        JHtml::_('formbehavior.chosen', 'select');
        JHtml::_('bootstrap.tooltip');

        JHtml::_('Prism.ui.joomlaList');
    }
}
