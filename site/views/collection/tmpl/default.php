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

JHtml::stylesheet(Juri::base() . 'components/com_collector/assets/css/collection.css');
JHtml::stylesheet(Juri::base() . 'components/com_collector/assets/css/dropdown.css');
JHtml::stylesheet(Juri::base() . 'components/com_collector/assets/css/icomoon.css');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.calendar');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('bootstrap.framework');

$defaultListOrder	= $this->escape($this->state->get('list.default.ordering'));
$defaultListDirn	= $this->escape($this->state->get('list.default.direction'));
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$search_all_value = $this->state->get('filter.filter_search_all');

$this->fieldsDisplayed = array();

foreach ( $this->fields as $field )
{
	if ( $field->_field->listing ) {
		$this->fieldsDisplayed[] = $field;
	}
}
?>
<script language="javascript" type="text/javascript">
	// requete ajax pour ajout dans userlist
	jQuery(document).ready(function() {
		// lorsque je soumets le formulaire
		jQuery('#useritemAddForm').on('submit', function() {
			// je récupère les valeurs
			var item = jQuery('#useritemAddForm_item').val();
			// appel Ajax
			jQuery.ajax({
				url: jQuery(this).attr('action'), // le nom du fichier indiqué dans le formulaire
				type: jQuery(this).attr('method'), // la méthode indiquée dans le formulaire (get ou post)
				data: jQuery(this).serialize(), // je sérialise les données
				success: function(response) { // je récupère la réponse du fichier PHP
					jQuery('#dropdown'+item).html(response);
					SqueezeBox.initialize({});
					SqueezeBox.assign($$('#dropdown'+item+'  a.modal'), {
						parse: 'rel'
					});
				}
			});
			return false; // j'empêche le navigateur de soumettre lui-même le formulaire
		});
	});
	
	// requete ajax pour suppression dune userlist
	jQuery(document).ready(function() {
		// lorsque je soumets le formulaire
		jQuery('#useritemRemoveForm').on('submit', function() {
			// je récupère les valeurs
			var item = jQuery('#useritemRemoveForm_item').val();
			// appel Ajax
			jQuery.ajax({
				url: jQuery(this).attr('action'), // le nom du fichier indiqué dans le formulaire
				type: jQuery(this).attr('method'), // la méthode indiquée dans le formulaire (get ou post)
				data: jQuery(this).serialize(), // je sérialise les données
				success: function(response) { // je récupère la réponse du fichier PHP
					jQuery('#dropdown'+item).html(response);
					SqueezeBox.initialize({});
					SqueezeBox.assign($$('#dropdown'+item+'  a.modal'), {
						parse: 'rel'
					});
					jQuery.each(jQuery("input[name='cid[]']"), function(){
						jQuery(this).remove();
					});
				}
			});
			return false; // j'empêche le navigateur de soumettre lui-même le formulaire
		});
	});
	
	// Fonction d'initialisation des champs de recherche
	document.getElementsByClassName = function(cl) {
	var retnode = [];
	var myclass = new RegExp('\\b'+cl+'\\b');
	var elem = this.getElementsByTagName('*');
	for (var i = 0; i < elem.length; i++) {
	var classes = elem[i].className;
	if (myclass.test(classes)) retnode.push(elem[i]);
	}
	return retnode;
	};
	
	function initialiser()
	{
		var form = jQuery('#adminForm');

		jQuery( "[name='filter_order']" ).val('<?php echo $defaultListOrder; ?>');
		jQuery( "[name='filter_order_Dir']" ).val('<?php echo $defaultListDirn; ?>');
		jQuery( "[name='filter_search_all']" ).val('');
		
		<?php
		foreach ( $this->fields as $field )
		{
			// if (( $field->_field->filter ) && ($field->isFiltered($this->params)==false)) {
			if ( $field->_field->filter ) {
				echo $field->resetSearchArea($this->params);
			}
		}
		?>
		
		form.submit();
	}
	
	function submitsearchbox()
	{
		var form = document.adminForm;
		
		var requiredfilters = form.getElementsByClassName("required");
		for(var i = 0; i < requiredfilters.length; i++)
		{
			if(requiredfilters[i].value == '')
			{
				alert('<?php  echo JText::_('COM_COLLECTOR_ALERT_MUST_SELECT_FILTER'); ?>');
				return 0;
			}
		}
		
		<?php
		foreach ( $this->fields as $field )
		{
			echo $field->submitSearchArea();
		}
		?>
		
		form.submit();
	}
</script>
<div class="category-list<?php echo $this->pageclass_sfx;?>">
	<?php if ($this->params->get('show_page_heading', 1)) : ?>
		<h1>
			<?php echo $this->escape($this->params->get('page_heading')); ?>
		</h1>
	<?php endif; ?>
	<?php if ($this->params->get('show_title')) : ?>
		<h2>
			<?php echo $this->escape($this->collection->name); ?>
		</h2>
	<?php endif; ?>
	
	<?php $useDefList = (($this->params->get('show_author')) or ($this->params->get('show_create_date')) ); ?>

	<?php if ($useDefList) : ?>
		<dl class="article-info muted">
		<dt class="article-info-term"><?php  echo JText::_('COM_COLLECTOR_COLLECTION_INFO'); ?></dt>
	<?php endif; ?>
	<?php if ($this->params->get('show_create_date')) : ?>
		<dd class="create">
			<span class="icon-calendar"></span>
			<?php echo JText::sprintf('COM_COLLECTOR_CREATED_DATE_ON', JHtml::_('date',$this->collection->created, JText::_('DATE_FORMAT_LC3'))); ?>
		</dd>
	<?php endif; ?>
	<?php if ($this->params->get('show_author') && !empty($this->collection->author )) : ?>
		<dd class="createdby">
		<?php $author = $this->collection->created_by_alias ? $this->collection->created_by_alias : $this->collection->author; ?>
		<?php echo JText::sprintf('COM_COLLECTOR_WRITTEN_BY', $author); ?>
		</dd>
	<?php endif; ?>
	<?php if ($useDefList) : ?>
		</dl>
	<?php endif; ?>

	<?php if ($this->params->get('show_desc', 1)) : ?>
	<div class="category-desc">
		<?php echo JHtml::_('content.prepare', $this->collection->description); ?>
		<div class="clr"></div>
	</div>
	<?php endif; ?>
	
	<form action="<?php echo JRoute::_('index.php?option=com_collector&view=collection&id='.$this->collection->id.'&reset=0'); ?>" method="post" name="adminForm" id="adminForm">
	
		<fieldset class="filters">
		<?php if ($this->params->get('show_search_area', 1)) : ?>
			<div class="search-area">
			<?php if ( $this->params->get('show_word_filter', 1) ) : ?>
				<div class="search-element">
					<?php echo JText::_('COM_COLLECTOR_FILTER_SEARCH').'&#160;'; ?>
					<input type="text" name="filter_search_all" value="<?php echo $this->escape($this->state->get('filter.filter_search_all')); ?>" class="inputbox" style="margin-bottom:0px" title="<?php echo JText::_('COM_COLLECTOR_FILTER_SEARCH_DESC'); ?>" />
				</div>
			<?php endif; ?>

			<?php
			foreach ($this->fields as $field)
			{
				if ( $field->_field->filter == 1 )
				{
					echo '<div class="search-element">';
					echo $field->displayFilter($this->params,$this->state->get('filter.filterfield_'.$field->_field->tablecolumn));
					echo '</div>';
				}
			}
			?>
			</div>
			<div class="btn-toolbar search-area">
				<div class="btn-group">
					<button type="button" class="btn btn-primary" onclick="submitsearchbox()">
						<span class="icon-search"></span>&#160;<?php echo JText::_('COM_COLLECTOR_SEARCH') ?>
					</button>
				</div>
				<div class="btn-group">
					<button type="button" class="btn" onclick="initialiser();">
						<span class="icon-cancel"></span>&#160;<?php echo JText::_('COM_COLLECTOR_INIT') ?>
					</button>
				</div>
			</div>
		<?php endif; ?>

			<?php if (( $this->pagination->get('total') != 0 ) && ($this->params->get('show_pagination_limit'))) : ?>
				<div class="btn-group pull-right">
					<label for="limit" class="element-invisible">
						<?php echo JText::_('JGLOBAL_DISPLAY_NUM'); ?>
					</label>
					<?php echo $this->pagination->getLimitBox(); ?>
				</div>
			<?php endif; ?>
			
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
			<input type="hidden" name="limitstart" value="" />
		</fieldset>
		<?php if ( ( $this->searched == 1 ) || ( $this->params->get('show_entire_listing', 1) ) ) : ?>
			
			<?php if ( $this->pagination->get('total') == 0 ) : ?>
				<div class="alert alert-no-items">
					<?php echo JText::_('COM_COLLECTOR_NO_ITEMS_FOUND'); ?>
				</div>
			<?php else : ?>	

				<?php if ($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) : ?>
				<div class="pagination">
					<?php if ($this->params->def('show_pagination_results', 1)) : ?>
						<p class="counter">
							<?php echo $this->pagination->getResultsCounter(); ?>
						</p>
					<?php endif; ?>

					<?php echo $this->pagination->getPagesLinks(); ?>
				</div>
				<?php endif; ?>
			
				<?php echo $this->loadTemplate('items'); ?>
				
				<?php if (($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) && ($this->pagination->get('pages.total') > 1)) : ?>
				<div class="pagination">
					<?php echo $this->pagination->getPagesLinks(); ?>
				</div>
				<?php endif; ?>
			
			<?php endif; ?>
			
		<?php endif; ?>
	</form>
	
	<form action="<?php echo JRoute::_('index.php?option=com_collector&format=raw&tmpl=component&task=useritem.add'); ?>" method="post" name="useritemAddForm" id="useritemAddForm">
		<input type="hidden" id="useritemAddForm_userlist" name="userlist" value="" />
		<input type="hidden" id="useritemAddForm_item" name="item" value="" />
		<input type="hidden" id="useritemAddForm_comment" name="comment" value="" />
		<?php echo JHtml::_( 'form.token' ); ?>
	</form>
	
	<form action="<?php echo JRoute::_('index.php?option=com_collector&format=raw&tmpl=component&task=useritem.delete'); ?>" method="post" name="useritemRemoveForm" id="useritemRemoveForm">
		<input type="hidden" id="useritemRemoveForm_userlist" name="userlist" value="" />
		<input type="hidden" id="useritemRemoveForm_item" name="item" value="" />
		<!-- <input type="hidden" id="useritemRemoveForm_cid" name="cid[]" value="" /> -->
		<?php echo JHtml::_( 'form.token' ); ?>
	</form>
</div>
<div align="center">
	<?php echo JText::_('COM_COLLECTOR_POWERED_BY'); ?>
	<a href="http://steevo.fr/" target="blank">
		<img src="components/com_collector/assets/images/collector_logo_mini.png" border="0" alt="Collector Logo" align="top" />
	</a>
</div>