<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

/**
 * Field Image class
 *
 * @package	Collector
 */
class CollectorField_Image extends CollectorField
{
	/**
	 * type
	 * 
	 * @var string
	 */
	public $type = 'image';
	
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
		$query->where('id = ' . $collection);
		
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
		// image de substitution
		$imagedefault=$this->_field->attribs['default'];
		$imagedefault=explode('|',$imagedefault);
		
		if ($value == '' ) {
			$value = '||||||';
		}
		$imageInfos = explode('|',$value);
		$imageUrl = $imageInfos[0];
		$imagePath = $imageUrl;
		$imageName = JFile::getName(JPATH_SITE.'/'.$imagePath);
		$imageDesc = $imageInfos[1] ? $imageInfos[1] : ($imagedefault[1] ? $imagedefault[1] : $imageName);
		$imageTitle = $imageInfos[2] ? $imageInfos[2] : ($imagedefault[2] ? $imagedefault[2] : $imageName);
		if ($listing == true)
		{
			$largeurmax = $imageInfos[3] ? $imageInfos[3] : ($imagedefault[3] ? $imagedefault[3] : 100);
			$hauteurmax = $imageInfos[4] ? $imageInfos[4] : ($imagedefault[4] ? $imagedefault[4] : 80);
		}
		else
		{
			$largeurmax = $imageInfos[5] ? $imageInfos[5] : ($imagedefault[5] ? $imagedefault[5] : 500);
			$hauteurmax = $imageInfos[6] ? $imageInfos[6] : ($imagedefault[6] ? $imagedefault[6] : 400);
		}
		
		$image = '';

		if ($imageUrl == '')
		{
			$image=JPATH_SITE.'/'.$imagedefault[0]; // adresse de l'image
			// $image=JPATH_SITE.'/components/com_collector/assets/images/camera.png'; // adresse de l'image
			$URLimage='./'.$imagedefault[0]; // adresse de l'image
		}
		else
		{
			$image=JPATH_SITE.'/'.$imageUrl; // adresse de l'image
			if (!file_exists($image)) {
				$image=JPATH_SITE.'/'.$imagedefault[0]; // adresse de l'image
				$URLimage='./'.$imagedefault[0]; // adresse de l'image
			} else {
				$URLimage='./'.$imageUrl; // adresse de l'image
			}
		}

		$taille=getimagesize($image);
		$largeur=$taille[0];
		$hauteur=$taille[1];
		
		if ( ($hauteur/$hauteurmax) < ($largeur/$largeurmax) )
		{
			if ($largeur < $largeurmax)
			{
				$size= 'width:'.$largeur.'px;';
			}
			else
			{
				$size= 'width:'.$largeurmax.'px;';
			}
		}
		else
		{
			if ($hauteur < $hauteurmax)
			{
				$size= 'height:'.$hauteur.'px;';
			}
			else
			{
				$size= 'height:'.$hauteurmax.'px;';
			}
		}
		
		$valign = 'vertical-align:middle;';
		$style = ' style="' . $size . $valign . '"';
		
		$return = '<a class="modal" href="'.$URLimage.'" />';
		$return .= '<img alt="'.$imageDesc.'" title="'.$imageTitle.'" align="middle" border="1" src="'.$URLimage.'" '.$style.' />';
		$return .= '</a>';
		return $return;
	}
}

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldCollectorImage extends JFormFieldMedia
{
	protected $type = 'CollectorImage';
	
	protected static $initialised = false;
	
	protected function getInput()
	{
		$asset = $this->asset;

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
			$script[] = '	function jInsertFieldValue(value, id) {';
			$script[] = '		var old_value = document.id(id + "_src").value;';
			$script[] = '		if (old_value != value) {';
			$script[] = '			var elem = document.id(id + "_src");';
			$script[] = '			elem.value = value;';
			$script[] = '			elem.fireEvent("change");';
			$script[] = '			if (typeof(elem.onchange) === "function") {';
			$script[] = '				elem.onchange();';
			$script[] = '			}';
			$script[] = '			jMediaRefreshPreview(id);';
			$script[] = '			setImage(id);';
			$script[] = '		}';
			$script[] = '	}';

			$script[] = '	function jMediaRefreshPreview(id) {';
			$script[] = '		var value = document.id(id + "_src").value;';
			$script[] = '		var img = document.id(id + "_preview");';
			$script[] = '		if (img) {';
			$script[] = '			if (value) {';
			$script[] = '				img.src = "' . JUri::root() . '" + value;';
			$script[] = '				document.id(id + "_preview_empty").setStyle("display", "none");';
			$script[] = '				document.id(id + "_preview_img").setStyle("display", "");';
			$script[] = '			} else { ';
			$script[] = '				img.src = ""';
			$script[] = '				document.id(id + "_preview_empty").setStyle("display", "");';
			$script[] = '				document.id(id + "_preview_img").setStyle("display", "none");';
			$script[] = '			} ';
			$script[] = '		} ';
			$script[] = '	}';

			$script[] = '	function jMediaRefreshPreviewTip(tip)';
			$script[] = '	{';
			$script[] = '		var img = tip.getElement("img.media-preview");';
			$script[] = '		tip.getElement("div.tip").setStyle("max-width", "none");';
			$script[] = '		var id = img.getProperty("id");';
			$script[] = '		id = id.substring(0, id.length - "_preview".length);';
			$script[] = '		jMediaRefreshPreview(id);';
			$script[] = '		tip.setStyle("display", "block");';
			$script[] = '	}';

			$script[] = '    function setImage(id){';
			$script[] = '        var path = document.id(id + "_src").value;';
			$script[] = '        var title = document.id(id + "_title").value;';
			$script[] = '        var alt = document.id(id + "_alt").value;';
			$script[] = '        var listingmaxwidth = document.id(id + "_listingmaxwidth").value;';
			$script[] = '        var listingmaxheight = document.id(id + "_listingmaxheight").value;';
			$script[] = '        var detailmaxwidth = document.id(id + "_detailmaxwidth").value;';
			$script[] = '        var detailmaxheight = document.id(id + "_detailmaxheight").value;';
			$script[] = '        document.id(id).value = path + "|" + title + "|" + alt + "|" + listingmaxwidth + "|" + listingmaxheight + "|" + detailmaxwidth + "|" + detailmaxheight;';
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

		// The Preview.
		$showPreview = true;
		$showAsTooltip = false;

		// The value
		if ($this->value == '' ) {
			$this->value = '||||||';
		}
		$imageInfos = explode('|',$this->value);
		$value = $imageInfos[0];
		$title = $imageInfos[1];
		$alt = $imageInfos[2];
		$listingMaxWidth = $imageInfos[3];
		$listingMaxHeight = $imageInfos[4];
		$detailMaxWidth = $imageInfos[5];
		$detailMaxHeight = $imageInfos[6];

		switch ($this->preview)
		{
			case 'no': // Deprecated parameter value
			case 'false':
			case 'none':
				$showPreview = false;
				break;

			case 'yes': // Deprecated parameter value
			case 'true':
			case 'show':
				break;

			case 'tooltip':
			default:
				$showAsTooltip = true;
				$options = array(
					'onShow' => 'jMediaRefreshPreviewTip',
				);
				JHtml::_('behavior.tooltip', '.hasTipPreview', $options);
				break;
		}

		if ($showPreview)
		{
			if ($value && file_exists(JPATH_ROOT . '/' . $value))
			{
				$src = JUri::root() . $value;
			}
			else
			{
				$src = '';
			}

			$width = $this->previewWidth;
			$height = $this->previewHeight;
			$style = '';
			$style .= ($width > 0) ? 'max-width:' . $width . 'px;' : '';
			$style .= ($height > 0) ? 'max-height:' . $height . 'px;' : '';

			$imgattr = array(
				'id' => $this->id . '_preview',
				'class' => 'media-preview',
				'style' => $style,
			);

			$img = JHtml::image($src, JText::_('JLIB_FORM_MEDIA_PREVIEW_ALT'), $imgattr);
			$previewImg = '<div id="' . $this->id . '_preview_img"' . ($src ? '' : ' style="display:none"') . '>' . $img . '</div>';
			$previewImgEmpty = '<div id="' . $this->id . '_preview_empty"' . ($src ? ' style="display:none"' : '') . '>'
				. JText::_('JLIB_FORM_MEDIA_PREVIEW_EMPTY') . '</div>';

			if ($showAsTooltip)
			{
				$html[] = '<div class="media-preview add-on">';
				$tooltip = $previewImgEmpty . $previewImg;
				$options = array(
					'title' => JText::_('JLIB_FORM_MEDIA_PREVIEW_SELECTED_IMAGE'),
					'text' => '<i class="icon-eye"></i>',
					'class' => 'hasTipPreview'
				);

				$html[] = JHtml::tooltip($tooltip, $options);
				$html[] = '</div>';
			}
			else
			{
				$html[] = '<div class="media-preview add-on" style="height:auto">';
				$html[] = ' ' . $previewImgEmpty;
				$html[] = ' ' . $previewImg;
				$html[] = '</div>';
			}
		}

		$html[] = '	<input type="text" name="' . $this->name . '_src" id="' . $this->id . '_src" value="'
			. htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '" readonly="readonly"' . $attr . ' />';

		if ($this->value && file_exists(JPATH_ROOT . '/' . $this->value))
		{
			$folder = explode('/', $this->value);
			$folder = array_diff_assoc($folder, explode('/', JComponentHelper::getParams('com_media')->get('image_path', 'images')));
			array_pop($folder);
			$folder = implode('/', $folder);
		}
		elseif (file_exists(JPATH_ROOT . '/' . JComponentHelper::getParams('com_media')->get('image_path', 'images') . '/' . $this->directory))
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
					: 'index.php?option=com_media&amp;view=images&amp;tmpl=component&amp;asset=' . $asset . '&amp;author='
					. $this->form->getValue($this->authorField)) . '&amp;fieldid=' . $this->id . '&amp;folder=' . $folder) . '"'
				. ' rel="{handler: \'iframe\', size: {x: 800, y: 500}}">';
			$html[] = JText::_('JLIB_FORM_BUTTON_SELECT') . '</a><a class="btn hasTooltip" title="' . JText::_('JLIB_FORM_BUTTON_CLEAR') . '" href="#" onclick="';
			$html[] = 'jInsertFieldValue(\'\', \'' . $this->id . '\');';
			$html[] = 'return false;';
			$html[] = '">';
			$html[] = '<i class="icon-remove"></i></a>';
		}

		$html[] = '</div>';
		$html[] = '</td></tr>';
		
		$disable = ($this->disabled == true) ? 'disabled' : '';
		
		// title and alt text
		$html[] = '<tr><td>';
		$html[] = '<label class="hasTooltip" title="' . JText::_('COM_COLLECTOR_IMAGE_TITLE_DESC') . '" id="' . $this->id . '_title-lbl" for="' . $this->id . '_title" >' . JText::_('COM_COLLECTOR_IMAGE_TITLE_LABEL') . '</label>';
		$html[] = '</td><td nowrap="nowrap">';
		$html[] = '<input type="text" name="' . $this->name . '_title" id="' . $this->id . '_title" value="' . $title .'" size="4" onchange="setImage(\'' . $this->id . '\')" '.$disable.'>';
		$html[] = '</td></tr>';
		$html[] = '<tr><td>';
		$html[] = '<label class="hasTooltip" title="' . JText::_('COM_COLLECTOR_IMAGE_ALT_DESC') . '" id="' . $this->id . '_alt-lbl" for="' . $this->id . '_alt" >' . JText::_('COM_COLLECTOR_IMAGE_ALT_LABEL') . '</label>';
		$html[] = '</td><td nowrap="nowrap">';
		$html[] = '<input type="text" name="' . $this->name . '_alt" id="' . $this->id . '_alt" value="' . $alt .'" size="4" onchange="setImage(\'' . $this->id . '\')" '.$disable.'>';
		$html[] = '</td></tr>';
		
		// Listing size
		$html[] = '<tr><td>';
		$html[] = '<label class="hasTooltip" title="' . JText::_('COM_COLLECTOR_IMAGE_LISTINGMAXWIDTH_DESC') . '" id="' . $this->id . '_listingmaxwidth-lbl" for="' . $this->id . '_listingmaxwidth" >' . JText::_('COM_COLLECTOR_IMAGE_LISTINGMAXWIDTH_LABEL') . '</label>';
		$html[] = '</td><td nowrap="nowrap">';
		$html[] = '<input type="text" name="' . $this->name . '_listingmaxwidth" id="' . $this->id . '_listingmaxwidth" value="' . $listingMaxWidth .'" size="4" onchange="setImage(\'' . $this->id . '\')" '.$disable.'>';
		$html[] = '</td></tr>';
		$html[] = '<tr><td>';
		$html[] = '<label class="hasTooltip" title="' . JText::_('COM_COLLECTOR_IMAGE_LISTINGMAXHEIGHT_DESC') . '" id="' . $this->id . '_listingmaxheight-lbl" for="' . $this->id . '_listingmaxheight" >' . JText::_('COM_COLLECTOR_IMAGE_LISTINGMAXHEIGHT_LABEL') . '</label>';
		$html[] = '</td><td nowrap="nowrap">';
		$html[] = '<input type="text" name="' . $this->name . '_listingmaxheight" id="' . $this->id . '_listingmaxheight" value="' . $listingMaxHeight .'" size="4" onchange="setImage(\'' . $this->id . '\')" '.$disable.'>';
		$html[] = '</td></tr>';
		
		// Detail size
		$html[] = '<tr><td>';
		$html[] = '<label class="hasTooltip" title="' . JText::_('COM_COLLECTOR_IMAGE_DETAILMAXWIDTH_DESC') . '" id="' . $this->id . '_detailmaxwidth-lbl" for="' . $this->id . '_detailmaxwidth" >' . JText::_('COM_COLLECTOR_IMAGE_DETAILMAXWIDTH_LABEL') . '</label>';
		$html[] = '</td><td nowrap="nowrap">';
		$html[] = '<input type="text" name="' . $this->name . '_detailmaxwidth" id="' . $this->id . '_detailmaxwidth" value="' . $detailMaxWidth .'" size="4" onchange="setImage(\'' . $this->id . '\')" '.$disable.'>';
		$html[] = '</td></tr>';
		$html[] = '<tr><td>';
		$html[] = '<label class="hasTooltip" title="' . JText::_('COM_COLLECTOR_IMAGE_LISTINGMAXHEIGHT_DESC') . '" id="' . $this->id . '_detailmaxheight-lbl" for="' . $this->id . '_detailmaxheight" >' . JText::_('COM_COLLECTOR_IMAGE_LISTINGMAXHEIGHT_LABEL') . '</label>';
		$html[] = '</td><td nowrap="nowrap">';
		$html[] = '<input type="text" name="' . $this->name . '_detailmaxheight" id="' . $this->id . '_detailmaxheight" value="' . $detailMaxHeight .'" size="4" onchange="setImage(\'' . $this->id . '\')" '.$disable.'>';
		$html[] = '</td></tr>';
		
		$html[] = '</table>';

		$html[] = '    <input type="hidden" name="'.$this->name.'" id="'.$this->id.'" value="'.htmlspecialchars($this->value, ENT_QUOTES).'" />';
		
		return implode("\n", $html);
	}
}