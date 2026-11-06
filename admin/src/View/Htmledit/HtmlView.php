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

namespace Steevo\Component\Collector\Administrator\View\Htmledit;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * HTML Htmledit View class for the Collector component
 *
 * @package	Collector
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * Display function
	 */
	function display($tpl = null)
	{
		$app = Factory::getApplication();
		
		$cid = $app->input->get( 'cid', array(0), '', 'array' );
			
		JArrayHelper::toInteger($cid, array(0));
		$id = $app->input->get('id', $cid[0], '', 'int' );
		
		// Create and load the content table row
		JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_collector/tables');
		$row = & JTable::getInstance('collector_templates','Table');
		
		if (!empty($id))
		{
			$row->load($id);
			
			if ( $row->alias == 'default' )
			{
				$msg = Text::_( 'COM_COLLECTOR_NO_EDIT_DEFAULT_TPL' );
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
			$msg = Text::sprintf('COM_COLLECTOR_OPERATION_FAILED_COULD_NOT_OPEN', $file);
			$app->redirect('index.php?option=com_collector&view=templates', $msg);
		}
		
		$title = Text::_( 'COM_COLLECTOR_TEMPLATES_MANAGER' ) . ': <small><small>[ '. Text::_( 'COM_COLLECTOR_EDIT' ) .' ]</small></small>' ;
		ToolBarHelper::title( $title , 'thememanager' );
		
		ToolBarHelper::save( 'save_source' );
		ToolBarHelper::cancel( 'cancel' , 'Close' );
		
		HTMLHelper::_('behavior.tooltip');
		
		$this->row = $row;
		$this->file = $file;
		$this->content = $content;
		
		parent::display($tpl);
	}
}
?>