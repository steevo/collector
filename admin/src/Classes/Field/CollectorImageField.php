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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\MediaField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class CollectorImageField extends MediaField
{
	protected $type = 'CollectorImage';
	
	protected static $initialised = false;
	
	/**
     * The directory.
     *
     * @var    string
     * @since  3.2
     */
    protected $directory = 'collector';

    protected function getInput()
	{
		$asset = $this->asset;

		if ($asset == '')
		{
			$asset = Factory::getApplication()->input->get('option');
		}

		if (!self::$initialised)
		{
			// Load the modal behavior script.
			// HTMLHelper::_('behavior.modal');

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
			$script[] = '				img.src = "' . Uri::root() . '" + value;';
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
			Factory::getApplication()->getDocument()->addScriptDeclaration(implode("\n", $script));

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

		/*switch ($this->preview)
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
				// HTMLHelper::_('behavior.tooltip', '.hasTipPreview', $options);
				break;
		}

		if ($showPreview)
		{
			if ($value && file_exists(JPATH_ROOT . '/' . $value))
			{
				$src = Uri::root() . $value;
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

			$img = HTMLHelper::image($src, Text::_('JLIB_FORM_MEDIA_PREVIEW_ALT'), $imgattr);
			$previewImg = '<div id="' . $this->id . '_preview_img"' . ($src ? '' : ' style="display:none"') . '>' . $img . '</div>';
			$previewImgEmpty = '<div id="' . $this->id . '_preview_empty"' . ($src ? ' style="display:none"' : '') . '>'
				. Text::_('JLIB_FORM_MEDIA_PREVIEW_EMPTY') . '</div>';

			if ($showAsTooltip)
			{
				$html[] = '<div class="media-preview add-on">';
				$tooltip = $previewImgEmpty . $previewImg;
				$options = array(
					'title' => Text::_('JLIB_FORM_MEDIA_PREVIEW_SELECTED_IMAGE'),
					'text' => '<i class="icon-eye"></i>',
					'class' => 'hasTipPreview'
				);

				$html[] = HTMLHelper::tooltip($tooltip, $options);
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

		$name = explode(']',$this->name);
		$name[0] = $name[0].'_src';
		$name = implode(']',$name);
		$html[] = '	<input type="text" name="' . $name . '" id="' . $this->id . '_src" value="'
			. htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '" readonly="readonly"' . $attr . ' />';

		if ($this->value && file_exists(JPATH_ROOT . '/' . $this->value))
		{
			$folder = explode('/', $this->value);
			$folder = array_diff_assoc($folder, explode('/', ComponentHelper::getParams('com_media')->get('image_path', 'images')));
			array_pop($folder);
			$folder = implode('/', $folder);
		}
		elseif (file_exists(JPATH_ROOT . '/' . ComponentHelper::getParams('com_media')->get('image_path', 'images') . '/' . $this->directory))
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
			HTMLHelper::_('bootstrap.tooltip');

			$html[] = '<a class="modal btn" title="' . Text::_('JLIB_FORM_BUTTON_SELECT') . '" href="'
				. ($this->readonly ? ''
				: ($this->link ? $this->link
					: 'index.php?option=com_media&amp;view=images&amp;tmpl=component&amp;asset=' . $asset . '&amp;author='
					. $this->form->getValue($this->authorField)) . '&amp;fieldid=' . $this->id . '&amp;folder=' . $folder) . '"'
				. ' rel="{handler: \'iframe\', size: {x: 800, y: 500}}">';
			$html[] = Text::_('JLIB_FORM_BUTTON_SELECT') . '</a><a class="btn hasTooltip" title="' . Text::_('JLIB_FORM_BUTTON_CLEAR') . '" href="#" onclick="';
			$html[] = 'jInsertFieldValue(\'\', \'' . $this->id . '\');';
			$html[] = 'return false;';
			$html[] = '">';
			$html[] = '<i class="icon-remove"></i></a>';
		}

		$html[] = '</div>';
		$html[] = '</td></tr>';
		
		$disable = ($this->disabled == true) ? 'disabled' : '';*/
		
		$html[] = $this->getRenderer($this->layout)->render($this->collectLayoutData());
		
		// title and alt text
		$html[] = '</td><td nowrap="nowrap">';
		$name = explode(']',$this->name);
		$name[0] = $name[0].'_title';
		$name = implode(']',$name);
		$html[] = '<tr><td>';
		$html[] = '<label class="hasTooltip" title="' . Text::_('COM_COLLECTOR_IMAGE_TITLE_DESC') . '" id="' . $this->id . '_title-lbl" for="' . $this->id . '_title" >' . Text::_('COM_COLLECTOR_IMAGE_TITLE_LABEL') . '</label>';
		$html[] = '<input type="text" name="' . $name . '" id="' . $this->id . '_title" value="' . $title .'" size="4" onchange="setImage(\'' . $this->id . '\')" '.$disable.'>';
		$html[] = '</td></tr>';
		$name = explode(']',$this->name);
		$name[0] = $name[0].'_alt';
		$name = implode(']',$name);
		$html[] = '<tr><td>';
		$html[] = '<label class="hasTooltip" title="' . Text::_('COM_COLLECTOR_IMAGE_ALT_DESC') . '" id="' . $this->id . '_alt-lbl" for="' . $this->id . '_alt" >' . Text::_('COM_COLLECTOR_IMAGE_ALT_LABEL') . '</label>';
		$html[] = '</td><td nowrap="nowrap">';
		$html[] = '<input type="text" name="' . $name . '" id="' . $this->id . '_alt" value="' . $alt .'" size="4" onchange="setImage(\'' . $this->id . '\')" '.$disable.'>';
		$html[] = '</td></tr>';
		
		// Listing size
		$name = explode(']',$this->name);
		$name[0] = $name[0].'_listingmaxwidth';
		$name = implode(']',$name);
		$html[] = '<tr><td>';
		$html[] = '<label class="hasTooltip" title="' . Text::_('COM_COLLECTOR_IMAGE_LISTINGMAXWIDTH_DESC') . '" id="' . $this->id . '_listingmaxwidth-lbl" for="' . $this->id . '_listingmaxwidth" >' . Text::_('COM_COLLECTOR_IMAGE_LISTINGMAXWIDTH_LABEL') . '</label>';
		$html[] = '</td><td nowrap="nowrap">';
		$html[] = '<input type="text" name="' . $name . '" id="' . $this->id . '_listingmaxwidth" value="' . $listingMaxWidth .'" size="4" onchange="setImage(\'' . $this->id . '\')" '.$disable.'>';
		$html[] = '</td></tr>';
		$name = explode(']',$this->name);
		$name[0] = $name[0].'_listingmaxheight';
		$name = implode(']',$name);
		$html[] = '<tr><td>';
		$html[] = '<label class="hasTooltip" title="' . Text::_('COM_COLLECTOR_IMAGE_LISTINGMAXHEIGHT_DESC') . '" id="' . $this->id . '_listingmaxheight-lbl" for="' . $this->id . '_listingmaxheight" >' . Text::_('COM_COLLECTOR_IMAGE_LISTINGMAXHEIGHT_LABEL') . '</label>';
		$html[] = '</td><td nowrap="nowrap">';
		$html[] = '<input type="text" name="' . $name . '" id="' . $this->id . '_listingmaxheight" value="' . $listingMaxHeight .'" size="4" onchange="setImage(\'' . $this->id . '\')" '.$disable.'>';
		$html[] = '</td></tr>';
		
		// Detail size
		$name = explode(']',$this->name);
		$name[0] = $name[0].'_detailmaxwidth';
		$name = implode(']',$name);
		$html[] = '<tr><td>';
		$html[] = '<label class="hasTooltip" title="' . Text::_('COM_COLLECTOR_IMAGE_DETAILMAXWIDTH_DESC') . '" id="' . $this->id . '_detailmaxwidth-lbl" for="' . $this->id . '_detailmaxwidth" >' . Text::_('COM_COLLECTOR_IMAGE_DETAILMAXWIDTH_LABEL') . '</label>';
		$html[] = '</td><td nowrap="nowrap">';
		$html[] = '<input type="text" name="' . $name . '" id="' . $this->id . '_detailmaxwidth" value="' . $detailMaxWidth .'" size="4" onchange="setImage(\'' . $this->id . '\')" '.$disable.'>';
		$html[] = '</td></tr>';
		$name = explode(']',$this->name);
		$name[0] = $name[0].'_detailmaxheight';
		$name = implode(']',$name);
		$html[] = '<tr><td>';
		$html[] = '<label class="hasTooltip" title="' . Text::_('COM_COLLECTOR_IMAGE_LISTINGMAXHEIGHT_DESC') . '" id="' . $this->id . '_detailmaxheight-lbl" for="' . $this->id . '_detailmaxheight" >' . Text::_('COM_COLLECTOR_IMAGE_LISTINGMAXHEIGHT_LABEL') . '</label>';
		$html[] = '</td><td nowrap="nowrap">';
		$html[] = '<input type="text" name="' . $name . '" id="' . $this->id . '_detailmaxheight" value="' . $detailMaxHeight .'" size="4" onchange="setImage(\'' . $this->id . '\')" '.$disable.'>';
		$html[] = '</td></tr>';
		
		$html[] = '</table>';

		$html[] = '    <input type="hidden" name="'.$this->name.'" id="'.$this->id.'" value="'.htmlspecialchars($this->value, ENT_QUOTES).'" '.$disable.'/>';
		
		return implode("\n", $html);
	}
}