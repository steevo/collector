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

/**
 * Field Define class
 *
 * @package	Collector
 */
class CollectorField_Define extends CollectorField
{
	/**
	 * type
	 * 
	 * @var string
	 */
	public $type = 'define';
	
	/**
	 * Object constructor to set field
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @access	protected
	 * @param	int								$collection	Collection Id
	 * @param	object TableCollector_fields	$field		TableCollector_fields object
	 * @param	int								$item		Item Id
	 */
	function __construct( $collection, $field, $item = 0 )
	{
		// Initialisation
		$this->_collection = $collection;
		$this->_item = $item;
		$this->_field = $field;
	}
	
	/**
	 * Gets the field attributes for the form definition
	 *
	 * @return string
	 */
	function getFieldAttributes($attributes = array())
	{
		$attributes = array(
			'list'			=> $this->_field->attribs['list'],
			'default'		=> ''
		);
		
		return parent::getFieldAttributes($attributes);
	}
	
	/**
	 * Method to add field to query
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	JDatabaseQuery object		$query
	 */
	function setQuery(&$query)
	{
		$query->select('d'.$this->_field->id.'.content AS `'.$this->_field->tablecolumn.'`');
		$query->join('LEFT', '#__collector_defined_content AS d'.$this->_field->id.' ON d'.$this->_field->id.'.id = h.'.$this->_field->tablecolumn);
		return;
	}
	
	/**
	 * Method to add where clause to query on search value
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	JDatabaseQuery object		$query
	 * @param	string						$search_all_value
	 */
	function getSearchWhereClause(&$query,$search_all_value)
	{
		$db = JFactory::getDbo();
		$text = $db->quote('%' . $db->escape($search_all_value, true) . '%', false);
		$where = 'LOWER(d'.$this->_field->id.'.content) LIKE LOWER(' . $text . ')';
		return $where;
	}
	
	/**
	 * Method to add where clause to query on filter value
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	JDatabaseQuery object		$query
	 * @param	mixed						$value
	 */
	function setFilterWhereClause(&$query,$value)
	{
		if ( $value != '' )
		{
			// $query->where('h.'.$this->_field->tablecolumn.' = "'.$value.'"');
			$query->where('d'.$this->_field->id.'.path LIKE "%|'.$value.'|%"');
			return true;
		}
		return false;
	}
	
	/**
	 * Method to add order by clause
	 *
	 * Can be overloaded/supplemented by the child class
	 */
	function getOrderBy()
	{
		$orderBy = 'd'.$this->_field->id.'.lft';
		return $orderBy;
	}
	
	/**
	 * Method to display filter in search area
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	JRegistry object		$params
	 * @param	mixed					$value
	 * @param	boolean					$menu
	 */
	function displayFilter($params,$value,$menu=false)
	{
		// Initiliase variables.
		$app	= JFactory::getApplication();
		$html = array();
		$attr = '';
		
		$user	= JFactory::getUser();
		$groups	= implode(',', $user->getAuthorisedViewLevels());
		
		if ($menu) {
			$prefix = 'jform[params][filter][';
			$suffix = ']';
		} else {
			$prefix = '';
			$suffix = '';
		}
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('c.id AS value, c.content AS text, c.level')
			->from('#__collector_defined_content AS c')
			->join('LEFT', $db->quoteName('#__collector_defined_content') . ' AS b ON c.lft > b.lft AND c.rgt < b.rgt');

		if ($defined = $this->_field->attribs['list'])
		{
			$query->where('c.defined = ' . $db->quote($defined));
		}
		else
		{
			$query->where('c.defined != ' . $db->quote(''));
		}

		$query->group('c.id, c.content, c.level, c.lft, c.rgt, c.defined, c.parent_id')
			->order('c.lft ASC');

		// Get the options.
		$db->setQuery($query);
		
		try
		{
			$options = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}
		
		// Pad the option text with spaces using depth level as a multiplier.
		for ($i = 0, $n = count($options); $i < $n; $i++)
		{
			$options[$i]->text = str_repeat('- ', $options[$i]->level-1) . $options[$i]->text;
		}
		
		// Filter unselected items
		if ( ($this->_field->attribs['hide_unselected']) && ($menu == false) ) {
			$query = $db->getQuery(true)
				->select('h.' . $this->_field->tablecolumn . ' AS value')
				->from('#__collector_items_history_' . $this->_field->collection . ' AS h')
				->join('LEFT', $db->quoteName('#__collector_items') . ' AS i ON h.item = i.id');

			$query->where('i.access IN ('.$groups.')');
			// Filter by start and end dates.
			if ((!$user->authorise('core.edit.state', 'com_collector.collection.'.(int) $this->_field->collection)) &&  (!$user->authorise('core.edit', 'com_collector.collection.'.(int) $this->_field->collection))){
				$nullDate	= $db->quote($db->getNullDate());
				$nowDate	= $db->quote(JFactory::getDate()->toSql());

				$query->where('(i.publish_up = '.$nullDate.' OR i.publish_up <= '.$nowDate.')')
					->where('(i.publish_down = '.$nullDate.' OR i.publish_down >= '.$nowDate.')')
					->where('i.state = 1');
			}
			$query->group('value');

			// Get the options.
			$db->setQuery($query);
			
			try
			{
				$optionsSelected = $db->loadObjectList('value');
			}
			catch (RuntimeException $e)
			{
				JError::raiseWarning(500, $e->getMessage());
			}
			
			$tempDelete = array();
			
			$old_level = 0;
			$tempDelete[$old_level] = array();
			
			for ($i = 0, $n = count($options); $i < $n; $i++)
			{
				$actual_level = $options[$i]->level;
				
				if ( $actual_level == $old_level )
				{
					foreach ( $tempDelete[$actual_level] as $key => $deleteId )
					{
						unset($options[$deleteId]);
					}
				}
				else if ( $actual_level < $old_level )
				{
					for ($level = $actual_level; $level <= $old_level; $level++)
					{
						foreach ( $tempDelete[$level] as $key => $deleteId)
						{
							unset($options[$deleteId]);
						}
					}
				}
				else
				{
					$tempDelete[$actual_level] = array();
				}
				
				if (!array_key_exists($options[$i]->value, $optionsSelected))
				{
					$tempDelete[$actual_level][] = $i;
				}
				else
				{
					for ($level = 0; $level < $actual_level; $level++)
					{
						$tempDelete[$level] = array();
					}
				}
				
				$old_level = $actual_level;
			}
			
			for ($level = 0; $level <= $actual_level; $level++)
			{
				foreach ( $tempDelete[$level] as $key => $deleteId)
				{
					unset($options[$deleteId]);
				}
			}
		}
		
		array_unshift($options,array('value' => '', 'text' => ''));
		
		// Initialize some field attributes.
		if ( (!$params->get('show_entire_listing')) && ($menu == false) ) {
			$requiredparams = $params->get('required');
			$required = $requiredparams['filterfield_'.$this->_field->tablecolumn]?' required':'';
		} else {
			$required = '';
		}
		$attr .= ' class="inputbox'.$required.'"';
		$attr .= ' size="1"';

		if ( $this->valueFilterMenu == null ) {
			$this->isFiltered($params);
		}
		
		if ( ($menu == true) || (!$this->valueFilterMenu) || ( $this->valueFilterMenu && (!$params->get('hide_filter'))) )
		{
			if ( (!$params->get('show_entire_listing')) && ($menu == false) ) {
				$requiredparams = $params->get('required');
				$required = $requiredparams['filterfield_'.$this->_field->tablecolumn]?'<span class="star">&nbsp;*</span>&nbsp;&nbsp;':'&nbsp;&nbsp;';
			} else {
				$required = '&nbsp;&nbsp;';
			}
			// Create a regular list.
			$html[] = $this->_field->field . $required;
			if ($menu == true) {
				$html[] = '</td><td>';
			}
			$html[] = JHtml::_('select.genericlist', $options, $prefix.'filterfield_'.$this->_field->tablecolumn.$suffix, trim($attr), 'value', 'text', $value);
		}
		else
		{
			$html[] = '<input type=hidden name=\''.$prefix.'filter_field_'.$this->_field->tablecolumn.$suffix.'\' value=\''.$value.'\' />';
		}

		if ($menu == true) {
			$return = '<td>'.implode($html).'</td>';
		} else {
			$return = implode($html);
		}
		
		return $return;
	}
	
	/**
	 * Method to display field
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	string					$value		Field value
	 * @param	boolean					$listing	
	 * @param	JRegistry object		$params
	 */
	function display($value,$listing=true,$params=array())
	{
		$db = JFactory::getDBO();
		
		$query = 'SELECT id, content, image, defined';
		$query .= ' FROM #__collector_defined_content';
		$query .= ' WHERE content = "'.$value.'"';

		$db->setQuery( $query );

		$result = $db->loadObject();

		if ( $result != null )
		{
			if ( JFile::exists($result->image) )
			{
				$query = 'SELECT width, height';
				$query .= ' FROM #__collector_defined';
				$query .= ' WHERE id = "'.$result->defined.'"';

				$db->setQuery( $query );

				$image = $db->loadObject();

				$imagePath=$result->image;
				$imagePath=JPATH_SITE.'/'.$imagePath;
				$taille=getimagesize($imagePath);
				$largeur=$taille[0];
				$hauteur=$taille[1];

				$largeurmax = $image->width ? $image->width : $largeur;
				$hauteurmax = $image->height ? $image->height : $hauteur;

				if ( ($hauteur/$hauteurmax) < ($largeur/$largeurmax) )
				{
					if ($largeur < $largeurmax)
					{
						$size= ' width="'.$largeur.'"';
					}
					else
					{
						$size= ' width="'.$largeurmax.'"';
					}
				}
				else
				{
					if ($hauteur < $hauteurmax)
					{
						$size= ' height="'.$hauteur.'"';
					}
					else
					{
						$size= ' height="'.$hauteurmax.'"';
					}
				}
				
				$return = '<img src="'.$result->image.'" alt="'.$result->content.'" title="'.$result->content.'" '.$size.' />';
			} else {
				$return = $result->content;
			}
			if ( $this->_field->attribs['show_fieldlink'] && ( $this->_field->filter == 1 ) )
			{
				// $link = 'javascript:setSearchField(\'filter_field_'.$this->_field->tablecolumn.'\',\''.$value.'\')';
				$link= 'index.php?option=com_collector&view=collection&id='.$this->_field->collection.'&filterfield_'.$this->_field->tablecolumn.'='.$result->id;
				$return = '<a href="'.JRoute::_($link).'">'.$return.'</a>';
			}
		} else {
			$return = false;
		}
		return $return;
	}
	
	/**
	 * Method to display value in fulltitle
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @param	string		$value	Field value
	 */
	function rebuild($value)
	{
		$db = JFactory::getDBO();
		
		$query = 'SELECT content, image, defined';
		$query .= ' FROM #__collector_defined_content';
		$query .= ' WHERE id = "'.$value.'"';

		$db->setQuery( $query );

		$result = $db->loadObject();

		if ( $result != null )
		{
			$return = $result->content;
		} else {
			$return = '';
		}
		return $return;
	}
	
	/**
	 * Method to use ajax
	 *
	 * Can be overloaded/supplemented by the child class
	 */
	function ajax()
	{
		$input		= JFactory::getApplication()->input;
		$action		= $input->get('action');
		
		switch ($action) {
			case 'populate' :
				$defined		= $input->get('defined');
				$parent_id		= $input->get('parent_id');
				$level			= $input->get('level');
				
				$db = JFactory::getDBO();
				$query = $db->getQuery(true);
				
				// Select the required fields from the table.
				$query->select('c.id AS value, c.defined, c.path, c.parent_id, c.level, c.content AS text, c.lft, c.rgt');
				$query->from('#__collector_defined_content as c');
				
				// Filter by list
				$query->where('c.defined = '.$defined);
				
				// Filter by parent
				$query->where('c.parent_id = '.$parent_id);
				
				// Filter by level
				$query->where('c.level = '.$level);
				
				// Add the list ordering clause.
				$query->order($db->escape('c.lft') . ' ' . $db->escape('ASC'));
				
				$code = JHTML::_('select.genericlist', $select, 'jform[request][id]', ' class=”inputbox” size="1" ', 'value', 'text', $default, 'jform_request_id');
				
				break;
		}
		
		return;
	}
}

require_once(JPATH_ROOT.'/libraries/joomla/form/fields/list.php');

/**
 * Form Field class for the Collector.
 *
 * @package		Collector
 */
class JFormFieldCollectorDefine extends JFormFieldList
{
	protected $type = 'CollectorDefine';
	
	protected function getOptions()
	{
		// Get defined Id
		$defined = $this->element['list'];
		
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		
		// Select the required fields from the table.
		$query = $db->getQuery(true)
			->select('c.id AS value, c.content AS text, c.level')
			->from('#__collector_defined_content AS c')
			->join('LEFT', $db->quoteName('#__collector_defined_content') . ' AS b ON c.lft > b.lft AND c.rgt < b.rgt')
			->where('c.defined = ' . $db->quote($defined));
		
		$query->group('c.id, c.content, c.level, c.lft, c.rgt, c.defined, c.parent_id')
			->order('c.lft ASC');
		
		// Get the options.
		$db->setQuery( $query );
		
		try
		{
			$options = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}

		// Pad the option text with spaces using depth level as a multiplier.
		for ($i = 0, $n = count($options); $i < $n; $i++)
		{
			$options[$i]->text = str_repeat('- ', $options[$i]->level-1) . $options[$i]->text;
		}
		
		array_unshift($options,array('value' => 0, 'text' => ''));
		
		return $options;
	}
}