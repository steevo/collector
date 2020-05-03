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
		ajaxRequest();
	});
	
	function ajaxRequest()
	{
		var url="index.php?option=com_collector&format=raw&task=updatejoomla.update";
		var myRequest = new Request({
			url: url,
			method:'get',
			onComplete: function( response ) {
				var resp = JSON.decode( response );
				var updated = parseInt($("updated").value) + parseInt(resp['updated']);
				document.id('updated').value = updated;
				var total = parseInt($("updated").value) + parseInt(resp['remaining']);
				var text = updated + ' &eacute;l&eacute;ments &agrave; jour sur ' + total;
				document.id('update').set('html',text);
				if ( parseInt(resp['updated']) != 0 ){
					ajaxRequest();
				}
				else
				{
					var state = 'Mise &agrave; jour termin&eacute;e';
					document.id('state').set('html',state);
				}
			}
		});
		myRequest.send();
	}
	//-->
</script>

<table width="100%" >
<tr height="40px"><td align="right" >
	<div id="update" name="update" ></div>
</td></tr>
<tr height="40px" ><td align="center" >
	<h2><div id="state" ><img src="./components/com_collector/assets/images/loading.gif" /></br>Mise &agrave; jour en cours</div></h2>
</td></tr>


<form action="index.php" method="post" name="adminForm" >
<input type="hidden" id="updated" name="updated" value="0" />
</form>