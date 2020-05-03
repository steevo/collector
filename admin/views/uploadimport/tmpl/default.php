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

?>
<script language="javascript" type="text/javascript">
	<!--
	jQuery(function () {
		jQuery('#my_form').on('submit', function (e) {
			// On empêche le navigateur de soumettre le formulaire
			e.preventDefault();
	 
			var form = jQuery(this);
			var formdata = (window.FormData) ? new FormData(form[0]) : null;
			var data = (formdata !== null) ? formdata : form.serialize();
	 
			jQuery.ajax({
				url: form.attr('action'),
				type: form.attr('method'),
				contentType: false, // obligatoire pour de l'upload
				processData: false, // obligatoire pour de l'upload
				dataType: 'html', // selon le retour attendu
				data: data,
				success: function (response) {
					// La réponse du serveur
					window.parent.jQuery('#listFiles').html(response);
					window.parent.SqueezeBox.close();
				}
			});
		});
	});

	//-->
</script>

<form id="my_form" method="post" action="<?php echo JRoute::_('index.php?option=com_collector&task=import.upload&format=raw');?>&amp;<?php echo JSession::getFormToken();?>=1" enctype="multipart/form-data">
	<input type="file" class="form-control" name="upload" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" >
	<button type="submit" class="btn btn-primary" ><i class="icon-upload icon-white"></i>Envoyer</button>
</form>
