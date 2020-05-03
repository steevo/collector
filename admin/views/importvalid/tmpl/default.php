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

?>
<script language="javascript" type="text/javascript">
	<!--
	function submitUpload() {
		parent.jQuery('#task').val('import.insert');
		ajaxSubmit();
	}
	
	function ajaxSubmit() {
		var form = parent.jQuery('#adminForm');
		
		jQuery.ajax({
			url: form.attr('action'),
			type: form.attr('method'),
			data: form.serialize(),
			dataType: 'json', // JSON
			success: function(json) {
				if(json.state == 'next') {
					jQuery('#import-result').html('Import en cours...');
					jQuery('#import-number').html(json.imported+' imported items');
					parent.jQuery('#imported').val(json.imported);
					parent.jQuery('#row').val(json.next);
					ajaxSubmit();
				} else if(json.state == 'end') {
					jQuery('#import-result').html('Import terminé');
					jQuery('#import-number').html(json.imported+' imported items');
				} else {
					jQuery('#import-result').html('Erreur : '+ json);
				}
			}
		});
	}

	//-->
</script>

<div id="import-result" >
	<a href="#" onclick="javascript:submitUpload();" >
		<button class="btn btn-primary" id="upload-submit">
			<i class="icon-box-add icon-white"></i> <?php echo JText::_('JSUBMIT');?>
		</button>
	</a>
</div>
<div id="import-number">
</div>