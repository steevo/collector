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

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

$lang = JFactory::getLanguage();
$extension = 'com_media';
$base_dir = JPATH_SITE;
$lang->load($extension, $base_dir);

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
				JFactory::getApplication()->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_CREATE_NOT_PERMITTED'),'warning');

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
					JFactory::getApplication()->enqueueMessage(JText::plural('COM_COLLECTOR_ERROR_BEFORE_SAVE', count($errors = $object_file->getErrors()), implode('<br />', $errors)),'warning');

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
			JFactory::getApplication()->enqueueMessage(JText::_('COM_COLLECTOR_ERROR_UNABLE_TO_CREATE_FOLDER_WARNDIRNAME'),'warning');

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
		// Check for request forgeries
		$this->checkToken('request');

		$params = JComponentHelper::getParams('com_media');

		// Get some data from the request
		$files		  = $this->input->files->get('Filedata', array(), 'array');
		$this->folder = $this->input->get('folder', '', 'path');
		$return		  = 'index.php?option=com_collector&view=filemanager&folder='.str_replace('/','\\',$this->folder);

		// Instantiate the media helper
		$mediaHelper = new JHelperMedia;

		// First check against unfiltered input.
		if (!$this->input->files->get('Filedata', null, 'RAW'))
		{
			// Total length of post back data in bytes.
			$contentLength = $this->input->server->get('CONTENT_LENGTH', 0, 'INT');

			// Maximum allowed size of post back data in MB.
			$postMaxSize = $mediaHelper->toBytes(ini_get('post_max_size'));

			// Maximum allowed size of script execution in MB.
			$memoryLimit = $mediaHelper->toBytes(ini_get('memory_limit'));

			// Check for the total size of post back data.
			if (($postMaxSize > 0 && $contentLength > $postMaxSize)
				|| ($memoryLimit != -1 && $contentLength > $memoryLimit))
			{
				// Files are too large.
				JFactory::getApplication()->enqueueMessage(JText::_('COM_MEDIA_ERROR_WARNUPLOADTOOLARGE'),'warning');

				$this->setRedirect( $return );
			}

			// No files were provided.
			$this->setMessage(JText::_('COM_MEDIA_ERROR_UPLOAD_INPUT'), 'warning');

			$this->setRedirect( $return );
		}

		if (!$files)
		{
			// Files were provided but are unsafe to upload.
			$this->setMessage(JText::_('COM_MEDIA_ERROR_WARNFILENOTSAFE'), 'error');

			$this->setRedirect( $return );
		}

		// Authorize the user
		if (!JFactory::getUser()->authorise('core.create', 'com_collector'))
		{
			// User is not authorised
			JFactory::getApplication()->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_CREATE_NOT_PERMITTED'),'warning');

			$this->setRedirect( $return );
		}

		$uploadMaxSize = $params->get('upload_maxsize', 0) * 1024 * 1024;
		$uploadMaxFileSize = $mediaHelper->toBytes(ini_get('upload_max_filesize'));

		// Perform basic checks on file info before attempting anything
		foreach ($files as &$file)
		{
			// Make the filename safe
			$file['name'] = JFile::makeSafe($file['name']);

			// We need a url safe name
			$fileparts = pathinfo(COM_COLLECTOR_BASE . '/' . $this->folder . '/' . $file['name']);

			if (strpos(realpath($fileparts['dirname']), JPath::clean(realpath(COM_COLLECTOR_BASE))) !== 0)
			{
				JFactory::getApplication()->enqueueMessage(JText::_('COM_MEDIA_ERROR_WARNINVALID_FOLDER'),'warning');

				$this->setRedirect( $return );
			}

			// Transform filename to punycode, check extension and transform it to lowercase
			$fileparts['filename'] = JStringPunycode::toPunycode($fileparts['filename']);
			$tempExt = !empty($fileparts['extension']) ? strtolower($fileparts['extension']) : '';

			// Neglect other than non-alphanumeric characters, hyphens & underscores.
			$safeFileName = preg_replace(array("/[\\s]/", '/[^a-zA-Z0-9_\-]/'), array('_', ''), $fileparts['filename']) . '.' . $tempExt;

			$file['name'] = $safeFileName;

			$file['filepath'] = JPath::clean(implode(DIRECTORY_SEPARATOR, array(COM_COLLECTOR_BASE, $this->folder, $file['name'])));

			if (($file['error'] == 1)
				|| ($uploadMaxSize > 0 && $file['size'] > $uploadMaxSize)
				|| ($uploadMaxFileSize > 0 && $file['size'] > $uploadMaxFileSize))
			{
				// File size exceed either 'upload_max_filesize' or 'upload_maxsize'.
				JFactory::getApplication()->enqueueMessage(JText::_('COM_MEDIA_ERROR_WARNFILETOOLARGE'),'warning');

				$this->setRedirect( $return );
			}

			if (JFile::exists($file['filepath']))
			{
				// A file with this name already exists
				JFactory::getApplication()->enqueueMessage(JText::_('COM_MEDIA_ERROR_FILE_EXISTS'),'warning');

				$this->setRedirect( $return );
			}

			if (!isset($file['name']))
			{
				// No filename (after the name was cleaned by JFile::makeSafe)
				$this->setRedirect('index.php', JText::_('COM_MEDIA_INVALID_REQUEST'), 'error');

				$this->setRedirect( $return );
			}
		}

		// Set FTP credentials, if given
		JClientHelper::setCredentialsFromRequest('ftp');
		JPluginHelper::importPlugin('content');
		$dispatcher = JEventDispatcher::getInstance();

		foreach ($files as &$file)
		{
			// The request is valid
			$err = null;

			if (!$mediaHelper->canUpload($file, 'com_media'))
			{
				// The file can't be uploaded
				$this->setRedirect( $return );
			}

			// Trigger the onContentBeforeSave event.
			$object_file = new JObject($file);
			$result = $dispatcher->trigger('onContentBeforeSave', array('com_media.file', &$object_file, true));

			if (in_array(false, $result, true))
			{
				// There are some errors in the plugins
				JFactory::getApplication()->enqueueMessage(JText::plural('COM_MEDIA_ERROR_BEFORE_SAVE', count($errors = $object_file->getErrors()), implode('<br />', $errors)),'warning');

				$this->setRedirect( $return );
			}

			if (!JFile::upload($object_file->tmp_name, $object_file->filepath))
			{
				// Error in upload
				JFactory::getApplication()->enqueueMessage(JText::_('COM_MEDIA_ERROR_UNABLE_TO_UPLOAD_FILE'),'warning');

				$this->setRedirect( $return );
			}

			// Trigger the onContentAfterSave event.
			$dispatcher->trigger('onContentAfterSave', array('com_media.file', &$object_file, true));
			$this->setMessage(JText::sprintf('COM_MEDIA_UPLOAD_COMPLETE', substr($object_file->filepath, strlen(COM_COLLECTOR_BASE))));
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