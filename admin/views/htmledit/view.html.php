<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Philippe Ousset. All rights reserved.
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
 * HTML Htmledit View class for the Collector component
 *
 * @package	Collector
 */
class CollectorsViewHtmledit extends JViewLegacy
{
	/**
	 * Display function
	 */
	function display($tpl = null)
	{
		$app = JFactory::getApplication();
		
		$cid = $app->input->getVar( 'cid', array(0), '', 'array' );
			
		JArrayHelper::toInteger($cid, array(0));
		$id = $app->input->getVar('id', $cid[0], '', 'int' );
		
		// Create and load the content table row
		JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_collector/tables');
		$row = & JTable::getInstance('collector_templates','Table');
		
		if (!empty($id))
		{
			$row->load($id);
			
			if ( $row->alias == 'default' )
			{
				$msg = JText::_( 'COM_COLLECTOR_NO_EDIT_DEFAULT_TPL' );
				$app->redirect('index.php?option=com_collector&view=templates', $msg );
			}
			
			if ($row->client == 1)
			{
				$client = 'collection';
			}
			else
			{
				$client = 'item';
			}
			
			$file = JPATH_SITE.'/components/com_collector/views/'.$client.'/tmpl/default_'.$row->alias.'.php';
		}
		
		// Read the source file
		jimport('joomla.filesystem.file');
		$content = file_get_contents($file);

		if ($content !== false)
		{
			$content = htmlspecialchars($content, ENT_COMPAT, 'UTF-8');
		} else {
			$msg = JText::sprintf('COM_COLLECTOR_OPERATION_FAILED_COULD_NOT_OPEN', $file);
			$app->redirect('index.php?option=com_collector&view=templates', $msg);
		}
		
		$title = JText::_( 'COM_COLLECTOR_TEMPLATES_MANAGER' ) . ': <small><small>[ '. JText::_( 'COM_COLLECTOR_EDIT' ) .' ]</small></small>' ;
		JToolBarHelper::title( $title , 'thememanager' );
		
		JToolBarHelper::save( 'save_source' );
		JToolBarHelper::cancel( 'cancel' , 'Close' );
		
		JHTML::_('behavior.tooltip');
		
		$this->row = $row;
		$this->file = $file;
		$this->content = $content;
		
		parent::display($tpl);
	}
}
?>