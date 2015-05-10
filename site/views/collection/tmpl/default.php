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

JHtml::stylesheet(Juri::base() . 'components/com_collector/assets/css/collection.css');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.calendar');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

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
		var form = document.collectorForm;

		form.filter_order.value 		= '<?php echo $listOrder; ?>';
		form.filter_order_Dir.value		= '<?php echo $listDirn; ?>';
		form.filter_search_all.value	= '';
		
		<?php
		foreach ( $this->fields as $field )
		{
			if (( $field->_field->filter ) && ($field->isFiltered($this->params)==false)) {
				echo 'form.filterfield_'.$field->_field->tablecolumn.'.value = \'\';';
			}
		}
		?>
	}
	
	function submitsearchbox()
	{
		var form = document.collectorForm;
		
		var requiredfilters = form.getElementsByClassName("required");
		for(var i = 0; i < requiredfilters.length; i++)
		{
			if(requiredfilters[i].value == '')
			{
				alert('<?php  echo JText::_('COM_COLLECTOR_ALERT_MUST_SELECT_FILTER'); ?>');
				return 0;
			}
		}
		
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
	
	<form action="<?php echo JRoute::_('index.php?option=com_collector&view=collection&id='.$this->collection->id.'&reset=0'); ?>" method="post" name="collectorForm" id="collectorForm">
	
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
					<button type="button" class="btn" onclick="initialiser();this.form.submit();">
						<span class="icon-cancel"></span>&#160;<?php echo JText::_('COM_COLLECTOR_INIT') ?>
					</button>
				</div>
			</div>
		<?php endif; ?>

			<?php if (( count($this->items) != 0 ) && ($this->params->get('show_pagination_limit'))) : ?>
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
			
			<?php if ( count($this->items) == 0 ) : ?>
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
</div>