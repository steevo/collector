<?php

/**
 * @package     Joomla.Admin
 * @subpackage  Layout
 *
 * @copyright   (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\MediaHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var  string   $asset           The asset text
 * @var  string   $authorField     The label text
 * @var  integer  $authorId        The author id
 * @var  string   $class           The class text
 * @var  boolean  $disabled        True if field is disabled
 * @var  string   $folder          The folder text
 * @var  string   $id              The label text
 * @var  string   $link            The link text
 * @var  string   $name            The name text
 * @var  string   $preview         The preview image relative path
 * @var  integer  $previewHeight   The image preview height
 * @var  integer  $previewWidth    The image preview width
 * @var  string   $onchange        The onchange text
 * @var  boolean  $readonly        True if field is readonly
 * @var  integer  $size            The size text
 * @var  string   $value           The value text
 * @var  string   $src             The path and filename of the image
 * @var  array    $mediaTypes      The supported media types for the Media Manager
 * @var  array    $imagesExt       The supported extensions for images
 * @var  array    $audiosExt       The supported extensions for audios
 * @var  array    $videosExt       The supported extensions for videos
 * @var  array    $documentsExt    The supported extensions for documents
 * @var  string   $dataAttribute   Miscellaneous data attributes preprocessed for HTML output
 * @var  array    $dataAttributes  Miscellaneous data attribute for eg, data-*
 */

$script[] = '    function setImage(id){';
$script[] = '        var path = jQuery(id + "_src").value;';
$script[] = '        var title = jQuery(id + "_title").value;';
$script[] = '        var alt = jQuery(id + "_alt").value;';
$script[] = '        var listingmaxwidth = jQuery(id + "_listingmaxwidth").value;';
$script[] = '        var listingmaxheight = jQuery(id + "_listingmaxheight").value;';
$script[] = '        var detailmaxwidth = jQuery(id + "_detailmaxwidth").value;';
$script[] = '        var detailmaxheight = jQuery(id + "_detailmaxheight").value;';
$script[] = '        jQuery(id).value = path + "|" + title + "|" + alt + "|" + listingmaxwidth + "|" + listingmaxheight + "|" + detailmaxwidth + "|" + detailmaxheight;';
$script[] = '    }';

// Add the script to the document head.
Factory::getApplication()->getDocument()->addScriptDeclaration(implode("\n", $script));

$attr = '';

// Initialize some field attributes.
$attr .= !empty($class) ? ' class="form-control field-media-input ' . $class . '"' : ' class="form-control field-media-input"';
$attr .= !empty($size) ? ' size="' . $size . '"' : '';
$attr .= $dataAttribute;

// Initialize JavaScript field attributes.
// $attr .= !empty($onchange) ? ' onchange="' . $onchange . '"' : '';
$attr .= ' onchange="setImage(\'' . $id . '\')"';

switch ($preview) {
    case 'no': // Deprecated parameter value
    case 'false':
    case 'none':
        $showPreview = false;
        break;
    case 'yes': // Deprecated parameter value
    case 'true':
    case 'show':
    case 'tooltip':
    default:
        $showPreview = true;
        break;
}

// The value
if ($value == '' ) {
	$value = '||||||';
}
$imageInfos = explode('|',$value);
$value = $imageInfos[0];
$title = $imageInfos[1];
$alt = $imageInfos[2];
$listingMaxWidth = $imageInfos[3];
$listingMaxHeight = $imageInfos[4];
$detailMaxWidth = $imageInfos[5];
$detailMaxHeight = $imageInfos[6];

$name_src = explode(']',$name);
$name_src[0] = $name_src[0].'_src';
$name_src = implode(']',$name_src);
$name_title = explode(']',$name);
$name_title[0] = $name_title[0].'_title';
$name_title = implode(']',$name_title);
$name_alt = explode(']',$name);
$name_alt[0] = $name_alt[0].'_alt';
$name_alt = implode(']',$name_alt);
$name_listingmaxwidth = explode(']',$name);
$name_listingmaxwidth[0] = $name_listingmaxwidth[0].'_listingmaxwidth';
$name_listingmaxwidth = implode(']',$name_listingmaxwidth);
$name_listingmaxheight = explode(']',$name);
$name_listingmaxheight[0] = $name_listingmaxheight[0].'_listingmaxheight';
$name_listingmaxheight = implode(']',$name_listingmaxheight);
$name_detailmaxwidth = explode(']',$name);
$name_detailmaxwidth[0] = $name_detailmaxwidth[0].'_detailmaxwidth';
$name_detailmaxwidth = implode(']',$name_detailmaxwidth);
$name_detailmaxheight = explode(']',$name);
$name_detailmaxheight[0] = $name_detailmaxheight[0].'_detailmaxheight';
$name_detailmaxheight = implode(']',$name_detailmaxheight);

// Pre fill the contents of the popover
if ($showPreview) {
    $cleanValue = MediaHelper::getCleanMediaFieldValue($value);

    if ($cleanValue && file_exists(JPATH_ROOT . '/' . $cleanValue)) {
        $src = Uri::root() . $value;
    } else {
        $src = '';
    }

    $width = $previewWidth;
    $height = $previewHeight;
    $style  = ($width > 0) ? 'max-width:' . $width . 'px;' : '';
    $style .= ($height > 0) ? 'max-height:' . $height . 'px;' : '';

    $imgattr = [
        'id' => $id . '_preview',
        'class' => 'media-preview',
        'style' => $style,
    ];

    $img = HTMLHelper::_('image', $src, Text::_('JLIB_FORM_MEDIA_PREVIEW_ALT'), $imgattr);

    $previewImg      = '<div class="preview_img">' . $img . '</div>';
    $previewImgEmpty = '<div class="preview_empty"' . ($src ? ' class="hidden"' : '') . '>'
        . Text::_('JLIB_FORM_MEDIA_PREVIEW_EMPTY') . '</div>';

    $showPreview = 'static';
}

// The url for the modal
$url = ($readonly ? ''
    : ($link ?: 'index.php?option=com_media&view=media&tmpl=component&mediatypes=' . $mediaTypes
        . '&asset=' . $asset . '&author=' . $authorId)
    . '&fieldid={field-media-id}&path=' . $folder);

// Correctly route the url to ensure it's correctly using sef modes and subfolders
// The url for the modal
$url = '';

if (!$readonly) {
    $url = ($link ?: 'index.php?option=com_media&view=media&tmpl=component&mediatypes=' . $mediaTypes . '&asset=' . $asset . '&author=' . $authorId) . '&path=' . $folder;

    // Correctly route the url to ensure it's correctly using sef modes and subfolders
    $url = Route::_($url);
}

Text::script('JSELECT');
Text::script('JCLOSE');
Text::script('JFIELD_MEDIA_LAZY_LABEL');
Text::script('JFIELD_MEDIA_ALT_LABEL');
Text::script('JFIELD_MEDIA_ALT_CHECK_LABEL');
Text::script('JFIELD_MEDIA_ALT_CHECK_DESC_LABEL');
Text::script('JFIELD_MEDIA_CLASS_LABEL');
Text::script('JFIELD_MEDIA_FIGURE_CLASS_LABEL');
Text::script('JFIELD_MEDIA_FIGURE_CAPTION_LABEL');
Text::script('JFIELD_MEDIA_LAZY_LABEL');
Text::script('JFIELD_MEDIA_SUMMARY_LABEL');
Text::script('JFIELD_MEDIA_EMBED_CHECK_DESC_LABEL');
Text::script('JFIELD_MEDIA_DOWNLOAD_CHECK_DESC_LABEL');
Text::script('JFIELD_MEDIA_DOWNLOAD_CHECK_LABEL');
Text::script('JFIELD_MEDIA_EMBED_CHECK_LABEL');
Text::script('JFIELD_MEDIA_WIDTH_LABEL');
Text::script('JFIELD_MEDIA_TITLE_LABEL');
Text::script('JFIELD_MEDIA_HEIGHT_LABEL');
Text::script('JFIELD_MEDIA_UNSUPPORTED');
Text::script('JFIELD_MEDIA_DOWNLOAD_FILE');
Text::script('JLIB_APPLICATION_ERROR_SERVER');
Text::script('JLIB_FORM_MEDIA_PREVIEW_EMPTY', true);

$doc = Factory::getApplication()->getDocument();
$wam = $doc->getWebAssetManager();

$wam->useStyle('webcomponent.field-media')
    ->useScript('webcomponent.field-media')
    ->useScript('webcomponent.media-select');

$doc->addScriptOptions('media-picker-api', ['apiBaseUrl' => Uri::base(true) . '/index.php?option=com_media&format=json']);

$wam->useStyle('webcomponent.field-media')
    ->useScript('webcomponent.field-media');

if (count($doc->getScriptOptions('media-picker')) === 0) {
    $doc->addScriptOptions('media-picker', [
        'images'    => $imagesExt,
        // 'audios'    => $audiosExt,
        // 'videos'    => $videosExt,
        // 'documents' => $documentsExt,
    ]);
}

?>
<joomla-field-media class="field-media-wrapper"
    types="<?php echo $this->escape(implode(',', $mediaTypeNames)); ?>"
    base-path="<?php echo $this->escape(Uri::root()); ?>"
    root-folder="<?php echo $this->escape(ComponentHelper::getParams('com_media')->get('image_path', 'images')); ?>"
    url="<?php echo $url; ?>"
    input=".field-media-input"
    button-select=".button-select"
    button-clear=".button-clear"
    modal-title="<?php echo $this->escape(Text::_('JLIB_FORM_CHANGE_IMAGE')); ?>"
    preview="static"
    preview-container=".field-media-preview"
    preview-width="<?php echo $previewWidth; ?>"
    preview-height="<?php echo $previewHeight; ?>"
    supported-extensions="<?php echo $this->escape(json_encode(['images' => $imagesAllowedExt, 'audios' => $audiosAllowedExt, 'videos' => $videosAllowedExt, 'documents' => $documentsAllowedExt])); ?>">
    <?php if ($showPreview) : ?>
        <div class="field-media-preview">
            <?php echo ' ' . $previewImgEmpty; ?>
            <?php echo ' ' . $previewImg; ?>
        </div>
    <?php endif; ?>
    <div class="input-group">
        <input type="text" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="<?php echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>" <?php echo $attr; ?>>
        <?php if (!$disabled) : ?>
            <button type="button" class="btn btn-success button-select"><?php echo Text::_('JLIB_FORM_BUTTON_SELECT'); ?></button>
            <button type="button" class="btn btn-danger button-clear"><span class="icon-times" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('JLIB_FORM_BUTTON_CLEAR'); ?></span></button>
        <?php endif; ?>
    </div>
	<table class="input-group">
		<tr><td><div class="input-group-prepend">
			<label><?php echo Text::_('COM_COLLECTOR_IMAGE_TITLE_LABEL'); ?></label>
		</div></td><td>
		<input type="text" name="<?php echo $name_title; ?>" id="<?php echo $id.'_title'; ?>" value="<?php echo htmlspecialchars($title, ENT_COMPAT, 'UTF-8'); ?>" <?php echo $attr; ?>></td></tr>
		<tr><td><div class="input-group-prepend">
			<label><?php echo Text::_('COM_COLLECTOR_IMAGE_ALT_LABEL'); ?></label>
		</div></td><td>
		<input type="text" name="<?php echo $name_alt; ?>" id="<?php echo $id.'_alt'; ?>" value="<?php echo htmlspecialchars($alt, ENT_COMPAT, 'UTF-8'); ?>" <?php echo $attr; ?>></td></tr>
		<tr><td><div class="input-group-prepend">
			<label><?php echo Text::_('COM_COLLECTOR_IMAGE_LISTINGMAXWIDTH_LABEL'); ?></label>
		</div></td><td>
		<input type="text" name="<?php echo $name_listingmaxwidth; ?>" id="<?php echo $id.'_listingmaxwidth'; ?>" value="<?php echo $listingMaxWidth; ?>" <?php echo $attr; ?>></td></tr>
		<tr><td><div class="input-group-prepend">
			<label><?php echo Text::_('COM_COLLECTOR_IMAGE_LISTINGMAXHEIGHT_LABEL'); ?></label>
		</div></td><td>
		<input type="text" name="<?php echo $name_listingmaxheight; ?>" id="<?php echo $id.'_listingmaxheight'; ?>" value="<?php echo $listingMaxHeight; ?>" <?php echo $attr; ?>></td></tr>
		<tr><td><div class="input-group-prepend">
			<label><?php echo Text::_('COM_COLLECTOR_IMAGE_DETAILMAXWIDTH_LABEL'); ?></label>
		</div></td><td>
		<input type="text" name="<?php echo $name_detailmaxwidth; ?>" id="<?php echo $id.'_detailmaxwidth'; ?>" value="<?php echo $detailMaxWidth; ?>" <?php echo $attr; ?>></td></tr>
		<tr><td><div class="input-group-prepend">
			<label><?php echo Text::_('COM_COLLECTOR_IMAGE_DETAILMAXHEIGHT_LABEL'); ?></label>
		</div></td><td>
		<input type="text" name="<?php echo $name_detailmaxheight; ?>" id="<?php echo $id.'_detailmaxheight'; ?>" value="<?php echo $detailMaxHeight; ?>" <?php echo $attr; ?>></td></tr>
		<input type="hidden" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="<?php echo htmlspecialchars($value, ENT_QUOTES); ?>" />
    </table>
</joomla-field-media>
