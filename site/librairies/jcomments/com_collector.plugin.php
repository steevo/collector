<?php
/**
 * JComments plugin for Collector objects support
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2015 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

class jc_com_collector extends JCommentsPlugin
{
	function getObjectInfo($id, $language = null)
	{
        $db = JFactory::getDBO();
        $query = 'SELECT i.id, i.fulltitle, i.created_by, i.collection, i.access'
            .' , CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(\':\', c.id, c.alias) ELSE c.id END as catslug'
            .' , CASE WHEN CHAR_LENGTH(i.alias) THEN CONCAT_WS(\':\', i.id, i.alias) ELSE i.id END as slug'
            .' FROM #__collector_items AS i'
            .' LEFT JOIN #__collector AS c ON c.id = i.collection'
            .' WHERE i.id = '. $id
            ;
        $db->setQuery($query);
        $row = $db->loadObject();        
        
		$info = new JCommentsObjectInfo();

		if (!empty($row)) {
			$db->setQuery("SELECT id FROM #__menu WHERE link = 'index.php?option=com_jdownloads&view=category&catid=".$row->cat_id."' and published = 1");
			$Itemid = $db->loadResult();

			if (!$Itemid) {
				$Itemid = self::getItemid('com_jdownloads');
			}
			
			$Itemid = $Itemid > 0 ? '&amp;Itemid='.$Itemid : '';

            $info->category_id = $row->cat_id;
            $info->title = $row->fulltitle;
            $info->userid = $row->created_by;
            $info->access = $row->access;
            $info->link = JRoute::_('index.php?option=com_collector&amp;view=item&amp;id='.$row->slug.'&amp;collection='.$row->cat_id.$Itemid);
		}
		return $info;
	}
}