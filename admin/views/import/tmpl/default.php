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

// no direct access

defined('_JEXEC') or die('Restricted access');

JHtml::stylesheet(Juri::base() . 'components/com_collector/assets/css/import.css');
JHtml::stylesheet(JURI::root() . 'media/jui/css/jquery.searchtools.css');

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('jquery.framework');
JHtml::_('bootstrap.tooltip');
JHTML::_('behavior.modal');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user		= JFactory::getUser();
$userId		= $user->get('id');

JFactory::getDocument()->addScriptDeclaration(
	'
	Joomla.submitbuttonfolder = function()
	{
		var form = document.getElementById("adminForm");

		CollectorFileLoader.showLoading();
		form.installtype.value = "folder"
		form.submit();
	};

	// Add spindle-wheel for installations:
	jQuery(document).ready(function($) {
		var outerDiv = $("#loader-file");
		
		CollectorFileLoader.getLoadingOverlay()
			.css("top", outerDiv.position().top - $(window).scrollTop())
			.css("left", "0")
			.css("width", "100%")
			.css("height", "100%")
			.css("display", "none")
			.css("margin-top", "-10px");
	});
	
	var CollectorFileLoader = {
		getLoadingOverlay: function () {
			return jQuery("#loading");
		},
		showLoading: function () {
			this.getLoadingOverlay().css("display", "block");
		},
		hideLoading: function () {
			this.getLoadingOverlay().css("display", "none");
		}
	};
	'
);

JFactory::getDocument()->addStyleDeclaration(
	'
	#loading {
		background: rgba(255, 255, 255, .8) url(\'' . JHtml::_('image', 'jui/ajax-loader.gif', '', null, true, true) . '\') 50% 15% no-repeat;
		position: fixed;
		opacity: 0.8;
		-ms-filter: progid:DXImageTransform.Microsoft.Alpha(Opacity = 80);
		filter: alpha(opacity = 80);
		overflow: hidden;
	}
	'
);
?>
<script language="javascript" type="text/javascript">
	<!--
	function changeCollection(select)
	{
		var collection = select.value;
		
		document.location.href="index.php?option=com_collector&view=import&collection="+collection;
	}
	function loadFile()
	{
		jQuery('#task').val('import.load');
		var form = jQuery('#adminForm');
		var formdata = (window.FormData) ? new FormData(form[0]) : null;
		var data = (formdata !== null) ? formdata : form.serialize();
 
		CollectorFileLoader.showLoading();
		jQuery.ajax({
			url: form.attr('action'),
			type: form.attr('method'),
			dataType: 'json', // selon le retour attendu
			data: form.serialize(),
			success: function (response) {
				CollectorFileLoader.hideLoading();
				jQuery('#tablepreview').html(response.tablepreview);
				jQuery('#fileconfig').show('slow');
			}
		});
	}
	//-->
</script>

<div id="loader-file" class="clearfix">
	<form action="index.php?option=com_collector&format=raw" method="post" name="adminForm" id="adminForm" >
	<?php if (!empty( $this->sidebar)) : ?>
		<div id="j-sidebar-container" class="span2">
			<?php echo $this->sidebar; ?>
		</div>
		<div id="j-main-container" class="span10">
	<?php else : ?>
		<div id="j-main-container">
	<?php endif;?>
			<div class="js-stools clearfix">
				<div class="clearfix">
					<div class="js-stools-container-bar">
						<div class="js-stools-field-filter js-stools-collection">
							<?php
							echo $this->collections;
							?>
						</div>
					</div>
				</div>
			</div>
			<div id="filesContainer" class="clearfix">
				<div class="control-group">
					<div class="control-label">
						<label id="jform_file-lbl" for="jform_file" class="hasTooltip required" title="" data-original-title="<strong>Type</strong><br />Type de champ">Fichier à importer<span class="star">&nbsp;*</span></label>
					</div>
					<?php $k = 0; ?>
					<div class="controls">
						<?php if (sizeof($this->files)==0) : ?>
							<div class="alert alert-no-items">
								<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
							</div>
						<?php else : ?>
							<div id="listFiles" >
								<table class="table table-striped table-condensed">
									<tbody>
									<?php foreach($this->files as $file) : ?>
										<tr class="<?php echo "row$k"; ?>">
											<td width="1px" >
												<input type="radio" name="file" value="<?php echo $file->name; ?>" onclick="loadFile();">
											</td>
											<td>
												<img src="<?php echo $file->ico; ?>">&nbsp;<?php echo $file->name; ?>
											</td>
											<td>
												<?php echo $file->size; ?>
											</td>
											<td>
												<?php echo $file->modified; ?>
											</td>
										</tr>
									<?php $k = 1 - $k; ?>
									<?php endforeach; ?>
									</tbody>
								</table>
							</div>						
						<?php endif;?>
								
						<button class="btn btn-primary" id="upload-submit">
							<a href="<?php echo JRoute::_( 'index.php?option=com_collector&amp;view=uploadimport&amp;tmpl=component');?>" class="modal" rel="{handler: 'iframe', size: {x: 500, y: 80}}" ><i class="icon-upload icon-white"></i> <?php echo JText::_('COM_MEDIA_START_UPLOAD');?></a>
						</button>
					</div>
				</div>
			</div>
			<div id="fileconfig" style="display: none;">
				<div class="form-horizontal">
					<div id="tablepreview">
						
					</div>
					<div class="row-fluid">
						<div class="control-group">
							<div class="control-label"><label id="jform_datafirstline-lbl" for="jform_datafirstline"><?php echo JText::_('COM_COLLECTOR_EXCEL_IMPORT_FIRST_LINE_DATA');?></label></div>
							<div class="controls"><input type="text" name="jform[datafirstline]" id="jform_datafirstline" value="2" size="20" maxlength="254"></div>
						</div>
						<?php  foreach ( $this->fields as $field )
						{
							echo '<div class="control-group">';
							echo '<div class="control-label"><label id="jform_'.$field->_field->tablecolumn.'-lbl" for="jform_'.$field->_field->tablecolumn.'">'.$field->_field->field.'</label></div>';
							echo '<div class="controls"><input type="text" name="jform['.$field->_field->tablecolumn.']" id="jform_'.$field->_field->tablecolumn.'" value="" size="20" maxlength="254"></div>';
							echo '</div>';
						} ?>
					</div>
				</div>
				<div class="btn-group">
					<a href="<?php echo JRoute::_( 'index.php?option=com_collector&amp;view=importvalid&amp;tmpl=component');?>" class="modal" rel="{handler: 'iframe', size: {x: 500, y: 350}}" >
						<button class="btn btn-primary" id="import-submit">
							<i class="icon-box-add icon-white"></i> <?php echo JText::_('JSUBMIT');?>
						</button>
					</a>
				</div>
				<input type="hidden" id="row" name="row" value="" />
				<input type="hidden" id="imported" name="imported" value="0" />
			</div>
			<input type="hidden" id='task' name="task" value="import.load" />
			<?php echo JHTML::_( 'form.token' ); ?>
		</div>
	</form>
</div>
<div id="loading"></div>