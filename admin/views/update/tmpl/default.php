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

JHtml::_('jquery.framework');

$app	= JFactory::getApplication();
$from	= $app->input->getInt('from');

?>

<script language="javascript" type="text/javascript">
	<!--
	jQuery(function(){
		ajaxRequest();
	});

	function ajaxRequest()
	{
		// var state = '<img src="./components/com_collector/assets/images/loading.gif" /></br>Mise &agrave; jour en cours';
		// $("state").setHTML(state);
		var url="index.php?option=com_collector&format=raw&task=update.update<?php echo $from; ?>";
		jQuery.ajax(url,{
			method:"get",
			onComplete: function( response ) {
				var resp = Json.evaluate( response );
				// Other code to execute when the request completes.
				var updated = parseInt($("updated").value) + parseInt(resp['updated']);
				jQuery("updated").value = updated;
				var total = parseInt($("updated").value) + parseInt(resp['remaining']);
				var text = updated + ' &eacute;l&eacute;ments &agrave; jour sur ' + total;
				jQuery("update").setHTML(text);
				if ( parseInt(resp['updated']) != 0 ){
					ajaxRequest();
				}
				else
				{
					var state = 'Mise &agrave; jour termin&eacute;e';
					jQuery("state").setHTML(state);
				}
			}
		});
	}
	//-->
</script>

<table width="100%" >
<tr height="40px"><td align="right" >
	<div id="update" ></div>
</td></tr>
<tr height="40px" ><td align="center" >
	<h2><div id="state" ><img src="./components/com_collector/assets/images/loading.gif" /></br>Mise &agrave; jour en cours</div></h2>
	<!-- <h2><div id="state" ><a style="margin:10px;" class="btn" href="#" onclick="javascript:ajaxRequest();"><span class="icon-go"></span>&nbsp;'<?php // JText::_('JGo');?>'</a></div></h2> -->
</td></tr>


<form action="index.php" method="post" name="adminForm" >
<input type="hidden" id="updated" name="updated" value="0" />
</form>