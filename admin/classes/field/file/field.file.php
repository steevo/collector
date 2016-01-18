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
	}
	
	/**
	 * Gets the field attributes for the form definition
	 *
	 * @return string
	 */
	function getFieldAttributes($attributes = array())
	{
		$attributes = array(
		);
		
		return parent::getFieldAttributes($attributes);
	}
	
	/**
	 * Method to add field to query
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	JDatabaseQuery object		$query
	 */
	function setQuery(&$query)
	{
		$query->select('jd'.$this->_field->id.'.file_id AS `'.$this->_field->tablecolumn.'`');
		$query->join('LEFT', '#__jdownloads_files AS jd'.$this->_field->id.' ON jd'.$this->_field->id.'.file_id = h.'.$this->_field->tablecolumn);
		return;
	}
	
	/**
	 * Method to add where clause to query on search value
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	JDatabaseQuery object		$query
	 * @param	string						$search_all_value
	 */
	function getSearchWhereClause(&$query,$search_all_value)
	{
		$db = JFactory::getDbo();
		$text = $db->quote('%' . $db->escape($search_all_value, true) . '%', false);
		$where = 'LOWER(jd'.$this->_field->id.'.file_title) LIKE LOWER(' . $text . ')';
		return $where;
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
		$db = JFactory::getDbo();
		if ( $value == '' ) {
			$default = $this->_field->attribs['default'];
			if ( $default != '' ) {
				$value = $default;
			}
		}
		if ( $value == '' ) {
			return false;
		} else {
			$listing_template = $this->_field->attribs['listing_template'];
			$detail_template = $this->_field->attribs['detail_template'];
			
			if (( $listing && ( $listing_template == 0 ) ) || ( !$listing && ( $detail_template == 0 ) )) {
				$return = JHtml::_('content.prepare', '{jd_file file==' .$value. '}');
			} else if ( $listing && ( $listing_template == 1 ) ) {
				$query = $db->getQuery(true);
	
				// Check file name in jdownloads.
				$query->select('file_title, file_pic');
				$query->from('#__jdownloads_files');
				$query->where('file_id = "'.$value.'"');
				$db->setQuery($query);
				$db->execute();
				$file = $db->loadAssoc();
				$return = '<img src="'.JURI::base().'components/com_jdownloads/assets/images/jdownloads/fileimages/'.$file['file_pic'].'" style="vertical-align:middle;" border="0" width="32" height="32" alt="" title="" />';
				$return .= $file['file_title'];
			} else if (( $listing && ( $listing_template == 2 ) ) || ( !$listing && ( $detail_template == 2 ) )) {
				$query = $db->getQuery(true);
	
				// Check file name in jdownloads.
				$query->select('file_title, file_alias, file_pic');
				$query->from('#__jdownloads_files');
				$query->where('file_id = "'.$value.'"');
				$db->setQuery($query);
				$db->execute();
				$file = $db->loadAssoc();
				$url = JURI::base().'index.php?option=com_jdownloads&view=download&id='.$value.':'.$file['file_alias'];
				$return = '<img src="'.JURI::base().'components/com_jdownloads/assets/images/jdownloads/fileimages/'.$file['file_pic'].'" style="vertical-align:middle;" border="0" width="32" height="32" alt="" title="" />';
				$return .= '<a href="'.$url.'">'.$file['file_title'].'</a>';
			}
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
	
	protected function getInput()
	{
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();
		
		// Create a new query object.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		
		// Check if jdownloads is installed.
		$query->select('enabled');
		$query->from('#__extensions');
		$query->where('type = "component" AND element = "com_jdownloads"');
		$db->setQuery($query);
		$db->execute();
		$num_rows = $db->getNumRows();
		
		if ( $num_rows == '0' ) {
			return JText::_('COM_COLLECTOR_SHOULD_INSTALL_COMPONENT_JDOWNLOADS');
		} else if ( !$db->loadResult() ) {
			return JText::_('COM_COLLECTOR_SHOULD_ENABLE_COMPONENT_JDOWNLOADS');
		}
		
		$query = $db->getQuery(true);
		
		// Check if jdownloads button plugin is installed.
		$query->select('extension_id');
		$query->from('#__extensions');
		$query->where('type = "plugin" AND element = "jdownloads" AND folder = "editors-xtd"');
		$db->setQuery($query);
		$db->execute();
		$num_rows = $db->getNumRows();
		
		if ( $num_rows == '0' ) {
			return JText::_('COM_COLLECTOR_SHOULD_INSTALL_PLUGIN_BUTTON_JDOWNLOADS');
		}
		
		$query = $db->getQuery(true);
		
		// Check file name in jdownloads.
		$query->select('file_title');
		$query->from('#__jdownloads_files');
		$query->where('file_id = "'.$this->value.'"');
		$db->setQuery($query);
		$db->execute();
		$file_name = $db->loadResult();
		
		$document->addStyleSheet( JURI::root().'plugins/editors-xtd/jdownloads/assets/css/jdownloads.css', 'text/css', null, array() );

		/*
		 * Javascript to insert the link
		 * View element calls jSelectDownloadContent when an download is clicked
		 * jSelectDownload creates the content tag, sends it to the editor,
		 * and closes the select frame.
		 */
		$js = "
		function jSelectDownload(id, title, catid, object, link, lang)
		{
			var vars = {};
			
			url = SqueezeBox.url;
			
			url.replace( 
				/[?&]+([^=&]+)=?([^&]*)?/gi, // regexp
				function( m, key, value ) { // callback
					vars[key] = value !== undefined ? value : '';
				}
			);

			document.getElementById(vars['id_field']).value = id;
			document.getElementById(vars['id_field']+'_title').value = title;
			SqueezeBox.close();
		}";
		$document->addScriptDeclaration($js);

		$link = 'index.php?option=com_jdownloads&amp;view=list&amp;layout=modallist&amp;tmpl=component&amp;e_name='.$this->name.'&amp;id_field='.$this->id.'&amp;' . JSession::getFormToken() . '=1';
		$onclick	= ' onclick="document.id(\''.$this->id.'\').value=\'\';document.id(\''.$this->id.'_title\').value=\'\';"';

		$input = '<div class="input-append"><input type="text" id="' . $this->id . '_title" value="' . $file_name . '" readonly="readonly" />';
		$input .= '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value="' . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '" readonly="readonly" /> <a class="btn btn-primary" title="'.JText::_('COM_COLLECTOR_FIELD_FILE_DELETE_JDOWNLOAD_FILE').'" ' . $onclick . '><span class="icon-cancel"></span></a></div>';
		
		JHtml::_('behavior.modal');
		$button = new JObject;
		$button->modal = true;
		$button->class = 'btn';
		$button->link = $link;
		$button->text = JText::_('COM_COLLECTOR_SELECT_JDOWNLOADS_BUTTON_TEXT');
		$button->name = 'file-add';
		$button->options = "{handler: 'iframe', size: {x: 950, y: 500}}";

		// $buttons = array($button);

		return $input.JLayoutHelper::render('joomla.editors.buttons.button', $button);
	}
}