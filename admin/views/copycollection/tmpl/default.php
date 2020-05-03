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

JHtmlBehavior::framework();
?>

<script language="javascript" type="text/javascript">
	<!--
	window.addEvent("domready",function(){
		document.id('toolbar-arrow-left-2').getFirst('button').addClass('disabled');
		document.id('toolbar-arrow-left-2').getFirst('button').removeProperty('onclick');
		ajaxRequest();
	});
	
	function ajaxRequest( new_col = 0 )
	{
		var collection_id = <?php echo $this->collection ?>;
		
		if (collection_id == '') {
			var state = '<?php echo JText::_('COM_COLLECTOR_COLLECTION_COPY_SELECT_A_COLLECTION'); ?>';
			document.id('state').set('html',state);
			return;
		}
		var copy_method = '<?php echo $this->copy_mode ?>';
		var assetgroup_id = '<?php echo $this->assetgroup_id ?>';
		var url="index.php?option=com_collector&format=raw&task=collection.copy";
		var myRequest = new Request({
			url: url,
			method:'post',
			onComplete: function( response ) {
				var resp = JSON.decode( response );
				new_col = parseInt(resp['new_col']);
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
					ajaxRequest(new_col);
				}
				else
				{
					if ( parseInt(resp['remaining_items']) != 0 ){
						ajaxRequest(new_col);
					}
					else
					{
						var state = '<?php echo JText::_('COM_COLLECTOR_COLLECTION_COPY_END'); ?>';
						document.id('state').set('html',state);
						document.id('toolbar-arrow-left-2').getFirst('button').removeClass('disabled');
						document.id('toolbar-arrow-left-2').getFirst('button').setProperty('onclick','Joomla.submitbutton(\'collections.back\')');
					}
				}
			}
		});
		myRequest.send('id='+collection_id+'&mode='+copy_method+'&assetgroup_id='+assetgroup_id+'&new_col='+new_col);
	}
	//-->
</script>

<?php if (!empty( $this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;?>


	<table style="width:500px; margin:auto;" >
		<tr height="40px"><td align="right" >
			<div id="update_fields" name="update_fields" ></div>
		</td></tr>
		<tr height="40px"><td align="right" >
			<div id="update_items" name="update_items" ></div>
		</td></tr>
		<tr height="40px" ><td align="center" >
			<h2><div id="state" ><img src="./components/com_collector/assets/images/loading.gif" /></br><?php echo JText::_('COM_COLLECTOR_COLLECTION_COPY_IN_PROGRESS'); ?></div></h2>
		</td></tr>
	</table>
</div>

<form action="index.php?option=com_collector" method="post" name="adminForm" id="adminForm" >
<input type="hidden" id="updated_fields" name="updated_fields" value="0" />
<input type="hidden" id="updated_items" name="updated_items" value="0" />
<input type="hidden" name="task" value="" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>