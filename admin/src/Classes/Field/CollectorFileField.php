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

namespace Steevo\Component\Collector\Administrator\Classes\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\MediaField;

defined('_JEXEC') or die;

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class CollectorFileField extends MediaField
{
	protected $type = 'CollectorFile';
	
	protected function getInput()
	{
		$app = Factory::getApplication();
		$document = Factory::getDocument();
		
		// Create a new query object.
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		
		// Check if jdownloads is installed.
		$query->select('enabled');
		$query->from('#__extensions');
		$query->where('type = "component" AND element = "com_jdownloads"');
		$db->setQuery($query);
		$db->execute();
		$num_rows = $db->getNumRows();
		
		if ( $num_rows == '0' ) {
			return Text::_('COM_COLLECTOR_SHOULD_INSTALL_COMPONENT_JDOWNLOADS');
		} else if ( !$db->loadResult() ) {
			return Text::_('COM_COLLECTOR_SHOULD_ENABLE_COMPONENT_JDOWNLOADS');
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
			return Text::_('COM_COLLECTOR_SHOULD_INSTALL_PLUGIN_BUTTON_JDOWNLOADS');
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
		$input .= '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value="' . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '" readonly="readonly" /> <a class="btn btn-primary" title="'.Text::_('COM_COLLECTOR_FIELD_FILE_DELETE_JDOWNLOAD_FILE').'" ' . $onclick . '><span class="icon-cancel"></span></a></div>';
		
		HTMLHelper::_('behavior.modal');
		$button = new JObject;
		$button->modal = true;
		$button->class = 'btn';
		$button->link = $link;
		$button->text = Text::_('COM_COLLECTOR_SELECT_JDOWNLOADS_BUTTON_TEXT');
		$button->name = 'file-add';
		$button->options = "{handler: 'iframe', size: {x: 950, y: 500}}";

		// $buttons = array($button);

		return $input.LayoutHelper::render('joomla.editors.buttons.button', $button);
	}
}