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

JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

?>


<script language="javascript" type="text/javascript">
	<!--
	function submitbutton(pressbutton)
	{
		var iframeList = window.frames['fileframe'];
		var form = document.adminForm;
		var folder = form.folderlist.value;
		
		form.folder.value = folder;
		
		if (pressbutton == 'cancel') {
			submitform( pressbutton );
			return;
		}
		
		submitform( pressbutton );
		
		window.parent.SqueezeBox.close();
	}
	
	function submitform(pressbutton){
		if (pressbutton) {
			document.adminForm.task.value=pressbutton;
		}
		if (typeof document.adminForm.onsubmit == "function") {
			if (document.adminForm.onsubmit()) {
				document.adminForm.submit();
			}
		} else {
			document.adminForm.submit();
		}
	}
	//-->
</script>

<form target="_parent" action="index.php" method="post" name="adminForm" >

	<div class="well">
		<div class="row">
			<div class="span9 control-group">
				<div class="control-label">
					<label class="control-label" for="folder"><?php echo JText::_('COM_COLLECTOR_DIRECTORY') ?></label>
				</div>
				<div class="controls">
					<?php echo $this->folderList; ?>
					<button class="btn" type="button" id="upbutton" title="<?php echo JText::_('COM_COLLECTOR_DIRECTORY_UP') ?>" ><?php echo JText::_('COM_COLLECTOR_UP') ?></button>
				</div>
			</div>
			<div class="pull-right">
				<button class="btn btn-primary" type="button" onclick="submitbutton('filemanager.<?php echo $this->task; ?>')"><?php echo JText::_('COM_COLLECTOR_PASTE_HERE') ?></button>
				<button class="btn" type="button" onclick="window.parent.SqueezeBox.close();"><?php echo JText::_('JCANCEL') ?></button>
			</div>
		</div>
	</div>

	<iframe src="index.php?option=com_collector&amp;view=folderlist&amp;tmpl=component&amp;folder=<?php echo $this->folder;?>" id="fileframe" name="fileframe" width="100%" height="373px" marginwidth="0" marginheight="0" scrolling="no" frameborder="0"></iframe>

	<input type="hidden" name="option" value="com_collector" />
	<?php
	foreach ( $this->elements as $element )
	{
	?>
		<input type="hidden" name="cid[]" value="<?php echo $element; ?>" />
	<?php
	}
	?>
	<input type="hidden" name="view" value="filemanager" />
	<input type="hidden" name="task" value="<?php echo $this->task; ?>" />
	<input type="hidden" name="folder" value="<?php echo $this->folder; ?>" />

</form>