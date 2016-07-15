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

JHtml::stylesheet(Juri::base() . 'components/com_collector/assets/css/tabs.css');

JHtml::_('behavior.modal');

$app = JFactory::getApplication();
$Itemid = $app->input->getVar('Itemid', 0, 'get');

if ( $this->params->get('navigation',1) ) {
	echo '<div width="100%" align="right">'.$this->navigation.'</div>';
}

echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => '#details'));

echo JHtml::_('bootstrap.addTab', 'myTab', '#details', JText::_('COM_COLLECTOR_ARTICLE'));

echo $this->loadTemplate('details');

echo JHtml::_('bootstrap.endTab');

if (($this->params->get('allow_front_mod')) && ($this->item->params->get('access-edit')))
{
	echo JHtml::_('bootstrap.addTab', 'myTab', 'index.php?option=com_collector&view=itemform&collection='.$this->item->collection.'&id='.$this->item->id.'&Itemid='.$Itemid, JText::_('COM_COLLECTOR_EDIT'));

	// echo $this->loadTemplate('edit');

	echo JHtml::_('bootstrap.endTab');
}

echo JHtml::_('bootstrap.endTabSet');
?>


<br />
<div align="center">
	<?php echo JText::_('COM_COLLECTOR_POWERED_BY'); ?>
	<a href="http://www.steevo.fr/" target="blank">
		<img src="components/com_collector/assets/images/collector_logo_mini.png" border="0" alt="Collector Logo" align="top" />
	</a>
</div>