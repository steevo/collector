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

JHtmlBehavior::framework();
?>

<script language="javascript" type="text/javascript">
	<!--
	window.addEvent("domready",function(){
		ajaxRequest();
	});
	
	function ajaxRequest()
	{
		var form = window.parent.document.getElementById('adminForm');
		var collection_id = '';
		for (var i = 0; true; i++) {
			var cbx = form['cb'+i];
			if (!cbx)
				break;
			if (cbx.checked == true) {
				collection_id = cbx.value;
			}
		}
		if (collection_id == '') {
			var state = '<?php echo JText::_('COM_COLLECTOR_COLLECTION_COPY_SELECT_A_COLLECTION'); ?>';
			document.id('state').set('html',state);
			return;
		}
		var new_name = form['batch-copy-name-id'].value;
		if (new_name == '') {
			var state = '<?php echo JText::_('COM_COLLECTOR_COLLECTION_COPY_NAME_A_COLLECTION'); ?>';
			document.id('state').set('html',state);
			return;
		}
		var copy_method = form['batch-copy-id'].value;
		var url="index.php?option=com_collector&format=raw&task=collection.copy";
		var myRequest = new Request({
			url: url,
			method:'post',
			onComplete: function( response ) {
				var resp = JSON.decode( response );
				var updated_fields = parseInt($("updated_fields").value) + parseInt(resp['updated_fields']);
				document.id('updated_fields').value = updated_fields;
				var total_fields = parseInt($("updated_fields").value) + parseInt(resp['remaining_fields']);
				var text_fields = updated_fields + '<?php echo JText::_('COM_COLLECTOR_COLLECTION_COPY_FIELDS_COPIED_OUT_OF'); ?>' + total_fields;
				document.id('update_fields').set('html',text_fields);
				var updated_items = parseInt($("updated_items").value) + parseInt(resp['updated_items']);
				document.id('updated_items').value = updated_items;
				var total_items = parseInt($("updated_items").value) + parseInt(resp['remaining_items']);
				var text_items = updated_items + '<?php echo JText::_('COM_COLLECTOR_COLLECTION_COPY_ITEMS_COPIED_OUT_OF'); ?>' + total_items;
				document.id('update_items').set('html',text_items);
				if ( parseInt(resp['remaining_fields']) != 0 ){
					ajaxRequest();
				}
				else
				{
					if ( parseInt(resp['remaining_items']) != 0 ){
						ajaxRequest();
					}
					else
					{
						var state = '<?php echo JText::_('COM_COLLECTOR_COLLECTION_COPY_END'); ?>';
						document.id('state').set('html',state);
					}
				}
			}
		});
		myRequest.send('id='+collection_id+'&name='+new_name+'&mode='+copy_method);
	}
	//-->
</script>

<table width="100%" >
<tr height="40px"><td align="right" >
	<div id="update_fields" name="update_fields" ></div>
</td></tr>
<tr height="40px"><td align="right" >
	<div id="update_items" name="update_items" ></div>
</td></tr>
<tr height="40px" ><td align="center" >
	<h2><div id="state" ><img src="./components/com_collector/assets/images/loading.gif" /></br>Copie en cours</div></h2>
</td></tr>


<form action="index.php" method="post" name="adminForm" >
<input type="hidden" id="updated_fields" name="updated_fields" value="0" />
<input type="hidden" id="updated_items" name="updated_items" value="0" />
</form>