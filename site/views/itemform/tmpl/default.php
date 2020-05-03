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

JHtml::stylesheet(Juri::base() . 'components/com_collector/assets/css/tabs.css');

if ( $this->item->id != 0 ) {
	if ( $this->params->get('navigation',1) ) {
		echo '<div width="100%" align="right">'.$this->navigation.'</div>';
	}

	echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => '#edit'));

	echo JHtml::_('bootstrap.addTab', 'myTab', 'index.php?option=com_collector&view=item&collection='.$this->item->collection.'&id='.$this->item->id, JText::_('COM_COLLECTOR_ARTICLE'));

	echo JHtml::_('bootstrap.endTab');

	echo JHtml::_('bootstrap.addTab', 'myTab', '#edit', JText::_('COM_COLLECTOR_EDIT'));

	if (($this->params->get('allow_front_mod')) && ($this->item->params->get('access-edit')))
	{
		echo $this->loadTemplate('edit');
	}
	else
	{
		echo '<div class="alert alert-no-items">';
		echo JText::_('COM_COLLECTOR_NOT_ALLOWED_EDIT_ITEM');
		echo '</div>';
	}

	echo JHtml::_('bootstrap.endTab');

	echo JHtml::_('bootstrap.endTabSet');
}
else
{
	echo $this->loadTemplate('edit');
}
?>


<br />
<div align="center">
	<?php echo JText::_('COM_COLLECTOR_POWERED_BY'); ?>
	<a href="http://steevo.fr/" target="blank">
		<img src="components/com_collector/assets/images/collector_logo_mini.png" border="0" alt="Collector Logo" align="top" />
	</a>
</div>