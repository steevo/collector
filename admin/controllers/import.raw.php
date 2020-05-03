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
// require_once(JPATH_ROOT.'/administrator/components/com_media/helpers/media.php');
// require_once(JPATH_COMPONENT_ADMINISTRATOR . '/classes/PHPExcel/IOFactory.php');

jimport('phpspreadsheet.phpspreadsheet');
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Itemversions Controller
 *
 * @package  	Collector
 */
class CollectorControllerImport extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'Import', $prefix = 'CollectorModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}
	
	/**
	 * Upload one or more files
	 *
	 * @return  boolean
	 *
	 * @since   1.5
	 */
	public function upload()
	{
		// Check for request forgeries
		JSession::checkToken('request') or jexit(JText::_('JINVALID_TOKEN'));
		$params = JComponentHelper::getParams('com_media');

		// Get some data from the request
		$file        = $this->input->files->get('upload', '', '');
		
		// $this->folder = $this->input->get('folder', '', 'path');
		$this->folder = JPath::clean(COM_COLLECTOR_BASE.DIRECTORY_SEPARATOR);

		// Authorize the user
		if (!$this->authoriseUser('create'))
		{
			$message = JText::_('COM_COLLECTOR_UNAUTHORISED');
		}

		// Total length of post back data in bytes.
		$contentLength = (int) $_SERVER['CONTENT_LENGTH'];

		// Instantiate the media helper
		$mediaHelper = new JHelperMedia;

		// Maximum allowed size of post back data in MB.
		$postMaxSize = $mediaHelper->toBytes(ini_get('post_max_size'));

		// Maximum allowed size of script execution in MB.
		$memoryLimit = $mediaHelper->toBytes(ini_get('memory_limit'));

		// Check for the total size of post back data.
		if (($postMaxSize > 0 && $contentLength > $postMaxSize)
			|| ($memoryLimit != -1 && $contentLength > $memoryLimit))
		{
			$message = JText::_('COM_MEDIA_ERROR_WARNUPLOADTOOLARGE');
		}

		$uploadMaxSize = $params->get('upload_maxsize', 0) * 1024 * 1024;
		$uploadMaxFileSize = $mediaHelper->toBytes(ini_get('upload_max_filesize'));

		// Perform basic checks on file info before attempting anything
		// foreach ($files as &$file)
		// {
			$file['name']     = JFile::makeSafe($file['name']);
			$file['filepath'] = JPath::clean(implode(DIRECTORY_SEPARATOR, array($this->folder, $file['name'])));

			if (($file['error'] == 1)
				|| ($uploadMaxSize > 0 && $file['size'] > $uploadMaxSize)
				|| ($uploadMaxFileSize > 0 && $file['size'] > $uploadMaxFileSize))
			{
				// File size exceed either 'upload_max_filesize' or 'upload_maxsize'.
				$message = JText::_('COM_MEDIA_ERROR_WARNFILETOOLARGE');
			}

			if (JFile::exists($file['filepath']))
			{
				// A file with this name already exists
				$message = JText::_('COM_MEDIA_ERROR_FILE_EXISTS');
			}

			if (!isset($file['name']))
			{
				// No filename (after the name was cleaned by JFile::makeSafe)
				$this->setRedirect('index.php', JText::_('COM_MEDIA_INVALID_REQUEST'), 'error');

				return false;
			}
		// }

		// Set FTP credentials, if given
		JClientHelper::setCredentialsFromRequest('ftp');
		JPluginHelper::importPlugin('content');
		$dispatcher	= JEventDispatcher::getInstance();

		// foreach ($files as &$file)
		// {
			if (!$mediaHelper->canUpload($file))
			{
				// The file can't be uploaded
				$message = JText::_('COM_MEDIA_ERROR_UNABLE_TO_UPLOAD_FILE');
			}

			// Trigger the onContentBeforeSave event.
			$object_file = new JObject($file);
			$result = $dispatcher->trigger('onContentBeforeSave', array('com_media.file', &$object_file, true));

			if (in_array(false, $result, true))
			{
				// There are some errors in the plugins
				$message = JText::plural('COM_MEDIA_ERROR_BEFORE_SAVE', count($errors = $object_file->getErrors()), implode('<br />', $errors));
			}

			if (!JFile::upload($object_file->tmp_name, $object_file->filepath))
			{
				// Error in upload
				$message = JText::_('COM_MEDIA_ERROR_UNABLE_TO_UPLOAD_FILE');
			}
			else
			{
				// Trigger the onContentAfterSave event.
				$dispatcher->trigger('onContentAfterSave', array('com_media.file', &$object_file, true));
				// $this->setMessage(JText::sprintf('COM_MEDIA_UPLOAD_COMPLETE', substr($object_file->filepath, strlen(COM_MEDIA_BASE))));
			}
		// }

		$model = $this->getModel();
		$files = $model->getFiles();
		
		$k =0;
		$return = '<table class="table table-striped table-condensed">
								<tbody>';
		foreach($files as $file) {
			$return.='<tr class="row'.$k.'">
				<td width="1px" >
					<input type="radio" name="file" value="'.$file->name.'" onclick="loadFile();">
				</td>
				<td>
					<img src="'.$file->ico.'">&nbsp;'.$file->name.'
				</td>
				<td>
					'.$file->size.'
				</td>
				<td>
					'.$file->modified.'
				</td>
			</tr>';
			$k = 1 - $k;
		}
		$return.='</tbody></table>';
		echo $return.$message;
		return;
	}

	
	
	/**
	 * Upload one or more files
	 *
	 * @return  boolean
	 *
	 * @since   1.5
	 */
	public function load()
	{
		$params = JComponentHelper::getParams( 'com_collector' );
		
		// Check for request forgeries
		JSession::checkToken('request') or jexit(JText::_('JINVALID_TOKEN'));

		// Get some data from the request
		$collection	= $this->input->get('collection', '', '');
		$file		= $this->input->get('file', '', '');
		
		$folder		= JPath::clean(COM_COLLECTOR_BASE.DIRECTORY_SEPARATOR);

		$inputFileName = $folder.$file;
		/**  Identify the type of $inputFileName  **/
		$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($inputFileName);
		/**  Create a new Reader of the type that has been identified  **/
		$objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
		/**  Load $inputFileName to a PHPExcel Object  **/
		$objPHPExcel = $objReader->load($inputFileName);
		
		$sheet = $objPHPExcel->getSheet(0);
		$highestRow = $sheet->getHighestRow();
		$highestColumn = $sheet->getHighestColumn();
		$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

		$model = $this->getModel();
		$fields = $model->getFields();
		
		$table = '<p>';
		$table .= 'Ce fichier comporte '.$highestRow.' lignes et '.$highestColumnIndex.'('.$highestColumn.') colonnes.';
		$table .= '</p>';
		
		$table .= '<table class="table table-striped table-bordered">';
		$table .= '<tr>';
		$table .= '<th></th>';
		for ($column = 1; $column <= $highestColumnIndex; $column++) {
			$table .= '<th>';
			$table .= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
			$table .= '</th>';
		}
		$table .= '</tr>';

		$referenceRow=array();
		for ($row = 1; $row <= 10; $row++)
		{
			$table .= '<tr>';
			$table .= '<th>'.($row).'</th>';
			for ($column = 1; $column <= $highestColumnIndex; $column++) {
				$table .= '<td>';
				
				if (!$sheet->getCellByColumnAndRow( $column, $row )->isInMergeRange() || $sheet->getCellByColumnAndRow( $column, $row )->isMergeRangeValueCell()) {
					// Cell is not merged cell
					$value = $sheet->getCellByColumnAndRow( $column, $row )->getCalculatedValue();
					$referenceRow[$column]=$value;
					//This will store the value of cell in $referenceRow so that if the next row is merged then it will use this value for the attribute
				} else {
					// Cell is part of a merge-range
					$value=$referenceRow[$column];
					//The value stored for this column in $referenceRow in one of the previous iterations is the value of the merged cell
				}
				
				$table .= $value;
				$table .= '</td>';
			}
			$table .= '</tr>';
		}
		$table .= '</table>';

		$return = array('tablepreview' => $table );
		
		echo json_encode($return);
		return;
	}

	/**
	 * Check that the user is authorized to perform this action
	 *
	 * @param   string  $action  - the action to be peformed (create or delete)
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function authoriseUser($action)
	{
		if (!JFactory::getUser()->authorise('core.' . strtolower($action), 'com_media'))
		{
			// User is not authorised
			JFactory::getApplication()->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_' . strtoupper($action) . '_NOT_PERMITTED'),'warning');

			return false;
		}

		return true;
	}

	/**
	 * Method to return to Items view.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function back()
	{
		$collection	= $this->input->get('collection', '', '');
		
		// Redirect to the list screen.
		$this->setRedirect(JRoute::_('index.php?option=com_collector&view=items&collection='.$collection, false));
	}

	/**
	 * Method to insert Items from excel.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function insert()
	{
		$params = JComponentHelper::getParams( 'com_collector' );
		
		// Check for request forgeries
		JSession::checkToken('request') or jexit(JText::_('JINVALID_TOKEN'));

		// Get some data from the request
		$collection	= $this->input->get('collection', '', '');
		$file		= $this->input->get('file', '', '');
		$form		= $this->input->get('jform', '', '');
		$rowsend	= $this->input->get('row', '', '');
		$imported	= $this->input->get('imported', '', '');
		if ( $rowsend == '' )
		{
			$rowsend = $form['datafirstline'];
		}
		$folder		= JPath::clean(COM_COLLECTOR_BASE.DIRECTORY_SEPARATOR);

		$inputFileName = $folder.$file;
		/**  Identify the type of $inputFileName  **/
		$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($inputFileName);
		/**  Create a new Reader of the type that has been identified  **/
		$objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
		/**  Advise the Reader that we only want to load cell data  **/
		$objReader->setReadDataOnly(true);
		/**  Load $inputFileName to a PHPExcel Object  **/
		$objPHPExcel = $objReader->load($inputFileName);
		
		$sheet = $objPHPExcel->getSheet(0);
		$highestRow = $sheet->getHighestRow();
		$highestColumn = $sheet->getHighestColumn();
		
		// Authorize the user
		if (!$this->authoriseUser('create'))
		{
			return false;
		}
		
		for ($i = 0; $i < 10; $i++)
		{
			$row = $rowsend + $i;
			
			if ( $row > $highestRow )
			{
				echo json_encode(['state' => 'end', 'imported' => $imported, 'next' => $row]);
				return;
			}
			
			$model = $this->getModel();
			$fields = $model->getFields();
			$data = array();
			
			foreach($fields as $field) {
				$column = $form[$field->_field->tablecolumn];
				if ( $column == '' ) {
					$value = '';
				} else {
					$value = $sheet->getCell($column.$row)->getCalculatedValue();
				}
				$data[$field->_field->tablecolumn] = $field->getImportedValue($value);
			}
			
			$table = JTable::getInstance('Collector_items','Table');
			$table->initVersion($collection);
			// Bind the data.
			if (!$table->bind($data))
			{
				$this->setError($table->getError());
				return false;
			}
			
			// Prepare the row for saving
			$this->prepareTable($table);

			// Check the data.
			if (!$table->check())
			{
				$this->setError($table->getError());
				return false;
			}
			
			// Store the data.
			if ( $params->get('save_history') )
			{
				$version = null;
			}
			else
			{
				$version = $table->historyId;
			}
			if (!$table->storeVersion($version))
			{
				$this->setError($table->getError());
				return false;
			}
			
			$imported = $imported + 1;
		}
			
		// Redirect to the list screen.
		echo json_encode(['state' => 'next', 'imported' => $imported, 'next' => $row+1]);
	}
	
	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param	JTable	A JTable object.
	 *
	 * @return	void
	 * @since	1.6
	 */
	protected function prepareTable($table)
	{
		// Set the publish date to now
		if($table->state == 1 && intval($table->publish_up) == 0)
		{
			$table->publish_up = JFactory::getDate()->toSql();
		}

		// Reorder the articles within the category so the new article is first
		if (empty($table->id))
		{
			$table->reorder('collection = ' . (int) $table->collection . ' AND state >= 0');
		}
	}
}