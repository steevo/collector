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

$db = JFactory::getDBO();
$user = JFactory::getUser();
$config = JFactory::getConfig();
$now = JFactory::getDate();

?>

<script language="javascript" type="text/javascript">
	<!--
	function submitbutton(pressbutton)
	{
		var form = document.adminForm;
		
		if (pressbutton == 'cancel') {
			submitform( pressbutton );
			return;
		}
		
		// do field validation
		if (form.name.value == ""){
			alert( "<?php echo JText::_( 'COM_COLLECTOR_TEMPLATE_MUST_HAVE_A_TITLE', true ); ?>" );
		} else {
			submitform( pressbutton );
		}
	}
	//-->
</script>

<form action="index.php" method="post" name="adminForm">

	<table class="adminform">
		<tr>
			<th>
				<?php echo $this->file; ?>
			</th>
		</tr>
		<tr>
			<td>
				<textarea style="width:100%;height:500px" cols="110" rows="25" name="filecontent" class="inputbox"><?php echo $this->content; ?></textarea>
			</td>
		</tr>
	</table>

	<div class="clr"></div>

	<input type="hidden" name="name" value="<?php echo $this->row->alias; ?>" />
	<input type="hidden" name="option" value="com_collector" />
	<input type="hidden" name="view" value="templates" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="client" value="<?php echo $this->row->client;?>" />
	<input type="hidden" name="controller" value="templates" />

</form>