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

// Access check: is this user allowed to access the backend of this component?
if (!JFactory::getUser()->authorise('core.manage', 'com_collector'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Jcomments integration
if (JPluginHelper::isEnabled('system', 'jcomments')) {
	$destpath		= JPATH_SITE.DS.'components'.DS.'com_jcomments'.DS.'plugins';
	$dest 			= $destpath.DS.'com_collector.plugin.php';
	$source 		= JPATH_SITE.DS.'components'.DS.'com_collector'.DS.'librairies'.DS.'jcomments'.DS.'com_collector.plugin.php';
	
	jimport('joomla.filesystem.file');
	jimport('joomla.filesystem.folder');
	if (!JFile::exists($dest)) {
		if (!JFolder::exists($destpath)) { 
			if (!JFolder::create($destpath)) { 
				JError::raiseWarning(100, JText::_('Unable to create jComments plugin folder'));
			}
		}
		if (!JFile::copy($source, $dest)) {
			JError::raiseWarning(100, JText::_('Unable to copy jComments plugin'));
		} else {
			JFactory::getApplication()->enqueueMessage(JText::_('Copied Collector jComments plugin'));
		}
	}
}

$params = JComponentHelper::getParams('com_collector');
define('COM_COLLECTOR_BASE',    JPATH_ROOT . '/' . $params->get('file_path', 'images/collector'));
define('COM_COLLECTOR_BASEURL', JUri::root() . $params->get('file_path', 'images/collector'));

// require helper file
JLoader::register('CollectorHelper', dirname(__FILE__) . '/helpers/collector.php');

// Get an instance of the controller prefixed by Collector
$controller = JControllerLegacy::getInstance('Collector');

// Get the task
$jinput = JFactory::getApplication()->input;
$task = $jinput->get('task', "", 'STR' );

// Perform the Request task
$controller->execute($task);
 
// Redirect if set by the controller
$controller->redirect();