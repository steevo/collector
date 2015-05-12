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

/**
 * Field File class
 *
 * @package	Collector
 */
class CollectorField_File extends CollectorField
{
	/**
	 * type
	 * 
	 * @var string
	 */
	public $type = 'file';
	
	/**
	 * Object constructor to set field
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @access	protected
	 * @param	int								$collection	Collection Id
	 * @param	object TableCollector_fields	$field		TableCollector_fields object
	 * @param	int								$item		Item Id
	 */
	function __construct( $collection, $field, $item = 0 )
	{
		// Initialisation
		$this->_collection = $collection;
		$this->_item = $item;
		$this->_field = $field;
		
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query->select('alias');
		$query->from('#__collector');
		
		// Add the filder on ID
		$query->where('id = '.$collection);
		
		$db->setQuery( $query );
		
		$this->directory = 'images/collector/collection/'.$db->loadResult();
	}
	
	/**
	 * Gets the field attributes for the form definition
	 *
	 * @return string
	 */
	function getFieldAttributes($attributes = array())
	{
		$attributes = array(
			'directory'		=> $this->directory,
			'size'			=> "60"
		);
		
		return parent::getFieldAttributes($attributes);
	}
	
	/**
	 * Method to display field
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	string					$value		Field value
	 * @param	boolean					$listing	
	 * @param	JRegistry object		$params
	 */
	function display($value,$listing=true,$params=array())
	{
		$db = JFactory::getDBO();
		
		$fileInfos = explode('|',$value);
		$fileUrl = $fileInfos[0];
		
		if ( $fileUrl == '' )
		{
			$return = false;
		}
		else
		{
			$filePath = $fileUrl;
			$fileName = JFile::getName(JPATH_SITE.'/'.$filePath);
			$fileDesc = $fileInfos[1] ? $fileInfos[1] : $fileName;
			$fileTitle = $fileInfos[2] ? $fileInfos[1] : $fileName;
			
			$ext = JFile::getExt(JPATH_SITE.'/'.$filePath);
			$query = 'SELECT ico';
			$query .= ' FROM #__collector_files_ext';
			$query .= ' WHERE ext="'.$ext.'"';
			$db->setQuery( $query );
			$ico = $db->loadResult();
			$URLico='./administrator/components/com_collector/assets/images/'.$ico;
			$return = '<a href="'.$fileUrl.'" target="_blank" title="'.$fileTitle.'" ><img src="'.$URLico.'" /> '. $fileDesc . '</a>';
		}
		return $return;
	}
}

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldCollectorFile extends JFormFieldMedia
{
	protected $type = 'CollectorFile';
	
	protected static $initialised = false;
	
	protected function getInput()
	{
		$asset = $this->asset;

		$extensions = $this->getExtensions();
		
		if ($asset == '')
		{
			$asset = JFactory::getApplication()->input->get('option');
		}

		if (!self::$initialised)
		{
			// Load the modal behavior script.
			JHtml::_('behavior.modal');

			// Build the script.
			$script = array();
			$script[] = '	function cInsertFieldValue(value, id) {';
			$script[] = '		var old_value = document.id(id + "_src").value;';
			$script[] = '		if (old_value != value) {';
			$script[] = '			var elem = document.id(id + "_src");';
			$script[] = '			elem.value = value;';
			$script[] = '			elem.fireEvent("change");';
			$script[] = '			if (typeof(elem.onchange) === "function") {';
			$script[] = '				elem.onchange();';
			$script[] = '			}';
			$script[] = '			cMediaRefreshPreview(id);';
			$script[] = '			setFile(id);';
			$script[] = '		}';
			$script[] = '	}';

			$script[] = '	function cMediaRefreshPreview(id) {';
			$script[] = '		var tab_ext = new Array();';
			foreach ( $extensions as $ext ) {
				$script[] = '		tab_ext["'.$ext->ext.'"] = "'.$ext->ico.'";';
			}
			$script[] = '		var value = document.id(id + "_src").value;';
			$script[] = '		var fileExt = value.substring(value.lastIndexOf(".")+1);';
			$script[] = '		var img = document.id(id + "_preview");';
			$script[] = '		if (img) {';
			$script[] = '			if (value) {';
			$script[] = '				img.src = "' . JUri::root() . '" + "administrator/components/com_collector/assets/images/" + tab_ext[fileExt];';
			$script[] = '			} else { ';
			$script[] = '				img.src = ""';
			$script[] = '			} ';
			$script[] = '		} ';
			$script[] = '	}';

			$script[] = '	function cMediaRefreshPreviewTip(tip)';
			$script[] = '	{';
			$script[] = '		var img = tip.getElement("img.media-preview");';
			$script[] = '		tip.getElement("div.tip").setStyle("max-width", "none");';
			$script[] = '		var id = img.getProperty("id");';
			$script[] = '		id = id.substring(0, id.length - "_preview".length);';
			$script[] = '		jMediaRefreshPreview(id);';
			$script[] = '		tip.setStyle("display", "block");';
			$script[] = '	}';

			$script[] = '    function setFile(id){';
			$script[] = '        var path = document.id(id + "_src").value;';
			$script[] = '        var title = document.id(id + "_title").value;';
			$script[] = '        document.id(id).value = path + "|" + title;';
			$script[] = '    }';

			// Add the script to the document head.
			JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

			self::$initialised = true;
		}

		$html = array();
		$attr = '';

		// Initialize some field attributes.
		$attr .= !empty($this->class) ? ' class="input-small ' . $this->class . '"' : 'class="input-small"';
		$attr .= !empty($this->size) ? ' size="' . $this->size . '"' : '';

		// Initialize JavaScript field attributes.
		$attr .= !empty($this->onchange) ? ' onchange="' . $this->onchange . '"' : '';
		
		// The table
		$html[] = '<table class="table table-bordered table-striped table-hover">';
		$html[] = '<tr><td colspan="2" >';
		
		// The text field.
		$html[] = '<div class="input-prepend input-append">';

		// The value
		if ($this->value == '' ) {
			$this->value = '|';
		}
		$fileInfos = explode('|',$this->value);
		$value = $fileInfos[0];
		$title = $fileInfos[1];
		
		if ($value && file_exists(JPATH_ROOT . '/' . $value))
		{
			$ext = JFile::getExt($value);
			$ico = JUri::root() . 'administrator/components/com_collector/assets/images/'.$extensions[$ext]->ico;
		}
		else
		{
			$ico = '';
		}

		$html[] = '<div class="media-preview add-on">';

		$html[] = '<img id="' . $this->id . '_preview" src="' . $ico . '" >';

		$html[] = '</div>';

		$html[] = '	<input type="text" name="' . $this->name . '_src" id="' . $this->id . '_src" value="'
			. htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '" readonly="readonly"' . $attr . ' />';

		if ($this->value && file_exists(JPATH_ROOT . '/' . $this->value))
		{
			$folder = explode('/', $this->value);
			$folder = array_diff_assoc($folder, explode('/', JComponentHelper::getParams('com_collector')->get('file_path', 'images')));
			array_pop($folder);
			$folder = implode('/', $folder);
		}
		elseif (file_exists(JPATH_ROOT . '/' . JComponentHelper::getParams('com_collector')->get('file_path', 'images') . '/' . $this->directory))
		{
			$folder = $this->directory;
		}
		else
		{
			$folder = '';
		}

		// The button.
		if ($this->disabled != true)
		{
			JHtml::_('bootstrap.tooltip');

			$html[] = '<a class="modal btn" title="' . JText::_('JLIB_FORM_BUTTON_SELECT') . '" href="'
				. ($this->readonly ? ''
				: ($this->link ? $this->link
					: 'index.php?option=com_collector&amp;view=files&amp;tmpl=component&amp;asset=' . $asset . '&amp;author='
					. $this->form->getValue($this->authorField)) . '&amp;fieldid=' . $this->id . '&amp;folder=' . $folder) . '"'
				. ' rel="{handler: \'iframe\', size: {x: 800, y: 500}}">';
			$html[] = JText::_('JLIB_FORM_BUTTON_SELECT') . '</a><a class="btn hasTooltip" title="' . JText::_('JLIB_FORM_BUTTON_CLEAR') . '" href="#" onclick="';
			$html[] = 'cInsertFieldValue(\'\', \'' . $this->id . '\');';
			$html[] = 'return false;';
			$html[] = '">';
			$html[] = '<i class="icon-remove"></i></a>';
		}

		$html[] = '</div>';
		$html[] = '</td></tr>';
		
		// title and alt text
		$html[] = '<tr><td>';
		$html[] = '<label id="' . $this->id . '_title-lbl" for="' . $this->id . '_title" class="">' . JText::_('COM_COLLECTOR_FILE_TITLE_LABEL') . '</label>';
		$html[] = '</td><td nowrap="nowrap">';
		$html[] = '<input type="text" name="' . $this->name . '_title" id="' . $this->id . '_title" value="' . $title .'" size="4" onchange="setFile(\'' . $this->id . '\')">';
		$html[] = '</td></tr>';
		
		$html[] = '</table>';

		$html[] = '    <input type="hidden" name="'.$this->name.'" id="'.$this->id.'" value="'.htmlspecialchars($this->value, ENT_QUOTES).'" />';
		
		return implode("\n", $html);
	}
	
	/**
	 * Retrieve extensions available
	 *
	 * @access	public
	 * @return	object	Array of extensions objects
	 */
	function getExtensions()
	{
		$query = 'SELECT e.id, t.text, e.type, e.ext, e.ico, e.state';
		$query .= ' FROM `#__collector_files_ext` AS e';
		$query .= ' LEFT JOIN `#__collector_files_type` AS t ON e.type = t.id';
		$query .= ' WHERE e.state = "1"';
		
		$db = JFactory::getDBO();
		
		$db->setQuery($query);
		$db->execute();
		
		$this->_ext = $db->loadObjectList ( 'ext' );
		
		return $this->_ext;
	}
}