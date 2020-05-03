<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.view');

/**
 * HTML Itemversions View class for the Collector component
 *
 * @package	Collector
 */
class CollectorViewImport extends JViewLegacy
{
	protected $items;
	protected $state;

	/**
	 * Display the view
	 *
	 * @return	void
	 */
	public function display($tpl = null)
	{
		$this->collections   = $this->get('Collections');
		$this->state         = $this->get('State');
		$this->files         = $this->get('Files');
		$this->fields        = $this->get('Fields');
		
		// What Access Permissions does this user have? What can (s)he do?
		$this->canDo	= CollectorHelper::getActions($this->state->get('filter.collection'));
		
		CollectorHelper::addSubmenu('items');
		
		if (jimport('phpspreadsheet.phpspreadsheet')) {
			// Check for errors.
			if (count($errors = $this->get('Errors'))) {
				JFactory::getApplication()->enqueueMessage(implode('<br />', $errors),'error');
				return false;
			}
			
			// We don't need toolbar in the modal window.
			if ($this->getLayout() !== 'modal') {
				$this->addToolbar();
				$this->sidebar = JHtmlSidebar::render();
			}
			
			parent::display($tpl);
		} else {
			$msg = JText::_( 'COM_COLLECTOR_INSTALL_PHPSPREADSHEET' ) . ' <a href="index.php?option=com_collector#about" >' . JText::_( 'COM_COLLECTOR_DASHBORD_MENU' ) . '</a>';
			$type = 'error';
			$app = JFactory::getApplication();
			$app->enqueueMessage($msg,$type);
		}
	}
	
	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		$canDo	= CollectorHelper::getActions();
		
		JToolBarHelper::title(JText::_('COM_COLLECTOR_IMPORT_FROM_EXCEL'), 'preview');
		
		JToolBarHelper::custom('import.back','arrow-left-2','','JTOOLBAR_BACK',false);
	}
}
?>
