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

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

/**
 * Filemanager Controller
 *
 * @package  	Collector
 */
class CollectorControllerFilemanager extends JControllerAdmin
{
	/**
	 * Method to create a new folder
	 *
	 * @access	public
	 */
	function create()
	{
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$user  = JFactory::getUser();
		
		$view = $this->input->get('view', '');
		$tmpl = $this->input->get('tmpl', '');
		
		$folder      = $this->input->get('foldername', '');
		$folderCheck = (string) $this->input->get('foldername', null, 'raw');
		$parent      = $this->input->get('folderbase', '', 'path');
		
		if ( $tmpl == 'component' )
		{
			$this->setRedirect( 'index.php?option=com_collector&view='.$view.'&tmpl=component&folder='.$parent );
		}
		else
		{
			$this->setRedirect( 'index.php?option=com_collector&view='.$view.'&folder='.$parent );
		}
		
		if (strlen($folder) > 0)
		{
			if (!$user->authorise('core.create', 'com_collector.files'))
			{
				// User is not authorised to create
				JError::raiseWarning(403, JText::_('JLIB_APPLICATION_ERROR_CREATE_NOT_PERMITTED'));

				return false;
			}
			
			// Set FTP credentials, if given
			JClientHelper::setCredentialsFromRequest('ftp');

			$this->input->set('folder', $parent);

			if (($folderCheck !== null) && ($folder !== $folderCheck))
			{
				$this->setMessage(JText::_('COM_COLLECTOR_ERROR_UNABLE_TO_CREATE_FOLDER_WARNDIRNAME'));

				return false;
			}
			
			$path = JPath::clean(COM_COLLECTOR_BASE . '/' . $parent . '/' . $folder);
			
			if (!is_dir($path) && !is_file($path))
			{
				// Trigger the onContentBeforeSave event.
				$object_file = new JObject(array('filepath' => $path));
				JPluginHelper::importPlugin('content');
				$dispatcher	= JEventDispatcher::getInstance();
				$result = $dispatcher->trigger('onContentBeforeSave', array('com_collector.folder', &$object_file, true));

				if (in_array(false, $result, true))
				{
					// There are some errors in the plugins
					JError::raiseWarning(100, JText::plural('COM_COLLECTOR_ERROR_BEFORE_SAVE', count($errors = $object_file->getErrors()), implode('<br />', $errors)));

					return false;
				}

				if (JFolder::create($object_file->filepath))
				{
					$data = "<html>\n<body bgcolor=\"#FFFFFF\">\n</body>\n</html>";
					JFile::write($object_file->filepath . "/index.html", $data);

					// Trigger the onContentAfterSave event.
					$dispatcher->trigger('onContentAfterSave', array('com_collector.folder', &$object_file, true));
					$this->setMessage(JText::sprintf('COM_COLLECTOR_CREATE_COMPLETE', substr($object_file->filepath, strlen(COM_COLLECTOR_BASE))));
				}
			}
			
			$this->input->set('folder', ($parent) ? $parent . '/' . $folder : $folder);
		}
		else
		{
			// File name is of zero length (null).
			JError::raiseWarning(100, JText::_('COM_MEDIA_ERROR_UNABLE_TO_CREATE_FOLDER_WARNDIRNAME'));

			return false;
		}

		return true;
	}
	
	/**
	 * Method to delete a file or a folder
	 *
	 * @access	public
	 */
	function remove()
	{
		$app = JFactory::getApplication();

		$option = $app->input->getCmd( 'option' );
		$view = $app->input->getCmd( 'view' );
		
		$model = $this->getModel('filemanager');
		$folder = $model->getFolder();
		
		$msg = $model->remove();
		
		$this->setRedirect( 'index.php?option='.$option.'&view='.$view.'&folder='.str_replace('/','\\',$folder), $msg[0], $msg[1] );
	}
	
	/**
	 * Method to rename a file or a folder
	 *
	 * @access	public
	 */
	function rename()
	{
		$app = JFactory::getApplication();

		$option = $app->input->getCmd( 'option' );
		$view = $app->input->getCmd( 'view' );
		
		$model = $this->getModel('filemanager');
		$folder = $model->getFolder();
		
		$msg = $model->rename();
		
		$this->setRedirect( 'index.php?option='.$option.'&view='.$view.'&folder='.str_replace('/','\\',$folder) );
	}
	
	/**
	 * Method to copy a file or a folder
	 *
	 * @access	public
	 */
	function copy()
	{
		$app = JFactory::getApplication();

		$option = $app->input->getCmd( 'option' );
		$view = $app->input->getCmd( 'view' );
		
		$model = $this->getModel('filemanager');
		$folder = $model->getFolder();
		
		$msg = $model->copy();
		
		$this->setRedirect( 'index.php?option='.$option.'&view='.$view.'&folder='.str_replace('/','\\',$folder) );
	}
	
	/**
	 * Method to move a file or a folder
	 *
	 * @access	public
	 */
	function move()
	{
		$app = JFactory::getApplication();

		$option = $app->input->getCmd( 'option' );
		$view = $app->input->getCmd( 'view' );
		
		$model = $this->getModel('filemanager');
		$folder = $model->getFolder();
		
		$msg = $model->move();
		
		$this->setRedirect( 'index.php?option='.$option.'&view='.$view.'&folder='.str_replace('/','\\',$folder) );
	}
	
	/**
	 * Method to upload a file
	 *
	 * @access	public
	 */
	function upload()
	{
		$app = JFactory::getApplication();

		$view = $app->input->getCmd( 'view' );
		$files		= $this->input->files->get('Filedata', '', 'array');
		$file 		= $app->input->getVar( 'fileupload', '', 'files', 'array' );
		$folder		= $app->input->getVar( 'folder', '', '', 'path' );
		$tmpl		= $app->input->getVar( 'tmpl', '', '', 'cmd');
		$format		= $app->input->getVar( 'format', 'html', '', 'cmd');
		$err		= null;
		$return		= 'index.php?option=com_collector&view='.$view.'&folder='.str_replace('/','\\',$folder).( $tmpl != '' ? '&tmpl='.$tmpl : '' );
		
		// Set FTP credentials, if given
		jimport('joomla.client.helper');
		JClientHelper::setCredentialsFromRequest('ftp');

		// Make the filename safe
		jimport('joomla.filesystem.file');
		$file['name']	= JFile::makeSafe($file['name']);

		if (isset($file['name'])) {
			$filepath = JPath::clean(JPATH_ROOT.'/'.$folder.'/'.strtolower($file['name']));

			if (JFile::exists($filepath)) {
				if ($format == 'json') {
					jimport('joomla.error.log');
					$log = &JLog::getInstance('upload.error.php');
					$log->addEntry(array('comment' => 'File already exists: '.$filepath));
					header('HTTP/1.0 409 Conflict');
					jexit('Error. File already exists');
				} else {
					JError::raiseNotice(100, JText::_('COM_COLLECTOR_ERROR_FILE_ALREADY_EXISTS'));
					// REDIRECT
					$app->redirect($return);
					return;
				}
			}

			if (!JFile::upload($file['tmp_name'], $filepath)) {
				if ($format == 'json') {
					jimport('joomla.error.log');
					$log = &JLog::getInstance('upload.error.php');
					$log->addEntry(array('comment' => 'Cannot upload: '.$filepath));
					header('HTTP/1.0 400 Bad Request');
					jexit('Error. Unable to upload file');
				} else {
					JError::raiseWarning(100, JText::_('COM_COLLECTOR_ERROR_UNABLE_TO_UPLOAD_FILE'));
					// REDIRECT
					$app->redirect($return);
					return;
				}
			} else {
				if ($format == 'json') {
					jimport('joomla.error.log');
					$log = &JLog::getInstance();
					$log->addEntry(array('comment' => $folder));
					jexit('Upload complete');
				} else {
					if ( $tmpl == '' )
					{
						$app->enqueueMessage(JText::_('COM_COLLECTOR_UPLOAD_COMPLETE'));
					}
					// REDIRECT
					$app->redirect($return);
					return;
				}
			}
		} else {
			$app->redirect('index.php', 'Invalid Request', 'error');
		}
		$this->setRedirect( $return );
	}
	
	/**
	 * Method to remove an extension
	 *
	 * @access	public
	 */
	function remove_ext()
	{
		$app = JFactory::getApplication();

		$option = $app->input->getCmd( 'option' );
		
		$model = $this->getModel('filesconfig');
		
		$msg = $model->remove();
		
		$this->setRedirect( 'index.php?option='.$option.'&tmpl=component&view=filesconfig' );
	}
	
	/**
	 * Method to add an extension
	 *
	 * @access	public
	 */
	function save_ext()
	{
		$app = JFactory::getApplication();

		$option = $app->input->getCmd( 'option' );
		
		$model = $this->getModel('filesconfig');
		
		$msg = $model->save();
		
		$this->setRedirect( 'index.php?option='.$option.'&tmpl=component&view=filesconfig' );
	}
	
	/**
	 * Method to display filemanager configuration
	 *
	 * @access	public
	 */
	function config()
	{
		$app = JFactory::getApplication();

		$app->input->set('view', 'filesconfig');
		
		parent::display();
	}
	
	/**
	 * Method to enable an extension
	 *
	 * @access	public
	 */
	function enable()
	{
		$app = JFactory::getApplication();

		$option = $app->input->getCmd( 'option' );
		
		$model = $this->getModel('filesconfig');
		$msg = $model->state(1);
		
		$this->setRedirect( 'index.php?option='.$option.'&tmpl=component&view=filesconfig' );
	}
	
	/**
	 * Method to disable an extension
	 *
	 * @access	public
	 */
	function disable()
	{
		$app = JFactory::getApplication();

		$option = $app->input->getCmd( 'option' );
		
		$model = $this->getModel('filesconfig');
		$msg = $model->state(0);
		
		$this->setRedirect( 'index.php?option='.$option.'&tmpl=component&view=filesconfig' );
	}
	
	/**
	 * Method to display a view
	 *
	 * @access	public
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$app = JFactory::getApplication();

		$view = $app->input->getCmd( 'view' );
		$view = $this->getView( $view , 'html' );
		$model = $this->getModel( 'filemanager' );
		
		$view->setModel( $model , false );
		$view->display();
		//parent::display($cachable, $urlparams);
	}
}