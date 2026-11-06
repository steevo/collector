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

namespace Steevo\Component\Collector\Administrator\Controller;

use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Steevo\Component\Collector\Administrator\Classes\Field\Date\CollectorFieldDate;
use Steevo\Component\Collector\Administrator\Classes\Field\Define\CollectorFieldDefine;
use Steevo\Component\Collector\Administrator\Classes\Field\File\CollectorFieldFile;
use Steevo\Component\Collector\Administrator\Classes\Field\Image\CollectorFieldImage;
use Steevo\Component\Collector\Administrator\Classes\Field\Item\CollectorFieldItem;
use Steevo\Component\Collector\Administrator\Classes\Field\Line\CollectorFieldLine;
use Steevo\Component\Collector\Administrator\Classes\Field\Number\CollectorFieldNumber;
use Steevo\Component\Collector\Administrator\Classes\Field\Text\CollectorFieldText;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


/**
 * Menu Controller
 */
class MenuController extends FormController
{
	/**
	 * Method to load filter fields in menu edition
	 */
	function listFilter()
	{
		$app = Factory::getApplication();
		$db = Factory::getContainer()->get(DatabaseInterface::class); 
		$input = $app->getInput();
		$collection = $input->getint( 'collection', 0);
		$menuId = $input->getint( 'itemId', '');
		$menuParams = new Registry;
		
		if ( $menuId != 0 )
		{
			$query = 'SELECT params';
			$query .= ' FROM `#__menu`';
			$query .= ' WHERE id = \''.$menuId.'\'';
			
			$db->setQuery( $query );
			
			$params = $db->loadResult();
			$menuParams->loadString($params);
			if ( $menuParams != null )
			{
				$filter = $menuParams->get('filter');
			}
		}
		
		if ( $collection == 0 )
		{
			$code[] = Text::_('COM_COLLECTOR_SELECT_COLLECTION');
		}
		else
		{
			$query = 'SELECT f.*, t.type AS type';
			$query .= ' FROM `#__collector_fields` AS f';
			$query .= ' LEFT JOIN `#__collector_fields_type` AS t ON f.type = t.id';
			$query .= ' WHERE collection = \''.$collection.'\'';
			$query .= ' AND filterable = 1';
			
			$db->setQuery( $query );
			
			$fields = $db->loadObjectList();
			
			if ($fields == null)
			{
				$code[] = Text::_('COM_COLLECTOR_NO_FILTER_AVAILABLE');
			}
			else
			{
				foreach ($fields as $field)
				{
					$registry = new Registry;
					$registry->loadString($field->attribs);
					$field->attribs = $registry->toArray();
					$_field = CollectorField::getInstance( $collection, $field );
					
					$paramName = 'filterfield_'.$field->tablecolumn;
					if (isset($filter->$paramName)) {
						$default = $filter->$paramName;
					} else {
						$default = 0;
					}
					
					$code[] = $_field->displayFilter($menuParams,$default,true);
				}
			}
		}
		
		echo '<table><tr>'.implode('<tr/><tr>',$code).'</tr></table>';
		return;
	}
	
	/**
	 * Method to load filter fields in menu edition
	 */
	function loadScripts()
	{
		$app = Factory::getApplication();
		$db = Factory::getContainer()->get(DatabaseInterface::class); 
		$input = $app->getInput();
		$collection = $input->getint( 'collection', 0);
		$menuId = $input->getint( 'itemId', '');
		$menuParams = new Registry;
		$code = array();
		
		if ( $menuId != 0 )
		{
			$query = 'SELECT params';
			$query .= ' FROM `#__menu`';
			$query .= ' WHERE id = \''.$menuId.'\'';
			
			$db->setQuery( $query );
			
			$params = $db->loadResult();
			$menuParams->loadString($params);
			if ( $menuParams != null )
			{
				$filter = $menuParams->get('filter');
			}
		}
		
		if ( $collection == 0 )
		{
			$code[] = Text::_('COM_COLLECTOR_SELECT_COLLECTION');
		}
		else
		{
			$query = 'SELECT f.*, t.type AS type';
			$query .= ' FROM `#__collector_fields` AS f';
			$query .= ' LEFT JOIN `#__collector_fields_type` AS t ON f.type = t.id';
			$query .= ' WHERE collection = \''.$collection.'\'';
			$query .= ' AND filterable = 1';
			
			$db->setQuery( $query );
			
			$fields = $db->loadObjectList();
			
			if ($fields == null)
			{
				$code[] = Text::_('COM_COLLECTOR_NO_FILTER_AVAILABLE');
			}
			else
			{
				foreach ($fields as $field)
				{
					$registry = new Registry;
					$registry->loadString($field->attribs);
					$field->attribs = $registry->toArray();
					$_field = CollectorField::getInstance( $collection, $field );
					
					$paramName = 'filterfield_'.$field->tablecolumn;
					if (isset($filter->$paramName)) {
						$default = $filter->$paramName;
					} else {
						$default = 0;
					}
					
					$code[] = $_field->getMenuJs();
				}
			}
		}
		
		echo implode($code);
		return;
	}
	
	/**
	 * Method to load filter fields in menu edition
	 */
	function listRequired()
	{
		$app = Factory::getApplication();
		$db = Factory::getContainer()->get(DatabaseInterface::class); 
		$input = $app->getInput();
		$collection = $input->getint( 'collection', 0);
		$menuId = $input->getint( 'itemId', '');
		$menuParams = new Registry;
		
		if ( $menuId != 0 )
		{
			$query = 'SELECT params';
			$query .= ' FROM `#__menu`';
			$query .= ' WHERE id = \''.$menuId.'\'';
			
			$db->setQuery( $query );
			
			$params = $db->loadResult();
			$menuParams->loadString($params);
			if ( $menuParams != null )
			{
				$filter = $menuParams->get('required');
			}
		}
		
		if ( $collection == 0 )
		{
			$code[] = Text::_('COM_COLLECTOR_SELECT_COLLECTION');
		}
		else
		{
			$query = 'SELECT f.*, t.type AS type';
			$query .= ' FROM `#__collector_fields` AS f';
			$query .= ' LEFT JOIN `#__collector_fields_type` AS t ON f.type = t.id';
			$query .= ' WHERE collection = \''.$collection.'\'';
			$query .= ' AND filterable = 1';
			
			$db->setQuery( $query );
			
			$fields = $db->loadObjectList();
			
			if ($fields == null)
			{
				$code[] = Text::_('COM_COLLECTOR_NO_FILTER_AVAILABLE');
			}
			else
			{
				foreach ($fields as $field)
				{
					$registry = new Registry;
					$registry->loadString($field->attribs);
					$field->attribs = $registry->toArray();
					$_field = CollectorField::getInstance( $collection, $field );
					
					$paramName = 'filterfield_'.$field->tablecolumn;
					if (isset($filter->$paramName)) {
						$default = $filter->$paramName;
					} else {
						$default = 0;
					}
					
					$code[] = $_field->displayRequired($menuParams,$default);
				}
			}
		}
		
		echo implode('<br/>',$code);
		return;
	}
	
	/**
	 * Method to load filter fields in menu edition
	 */
	function loadUserlists()
	{
		$app = Factory::getApplication();
		$db = Factory::getContainer()->get(DatabaseInterface::class); 
		$input = $app->getInput();
		$collection = $input->getint( 'collection', 0);
		$menuId = $input->getint( 'itemId', '');
		$menuParams = new Registry;
		$default = 0;
		
		if ( $menuId != 0 )
		{
			$query = 'SELECT params';
			$query .= ' FROM `#__menu`';
			$query .= ' WHERE id = \''.$menuId.'\'';
			
			$db->setQuery( $query );
			
			$params = $db->loadResult();
			$menuParams->loadString($params);
			if ( $menuParams != null )
			{
				$default = $menuParams->get('userslist');
			}
		}
		
		if ( $collection == 0 )
		{
			$code = Text::_('COM_COLLECTOR_SELECT_COLLECTION');
		}
		else
		{
			$query = 'SELECT ul.id AS value, ul.name AS text';
			$query .= ' FROM `#__collector_userslists` AS ul';
			$query .= ' WHERE collection = \''.$collection.'\'';
			
			$db->setQuery( $query );
			
			$userlists = $db->loadObjectList();
			
			if ($userlists == null)
			{
				$code = Text::_('COM_COLLECTOR_NO_USERLIST_AVAILABLE');
			}
			else
			{
				$init[0] = array('value' => '', 'text' => ' - ' . Text::_('COM_COLLECTOR_SELECT_A_USERLIST').' - ');
				$select = $init;
				foreach ( $userlists as $key => $value )
				{
					$select[$key+1]=$value;
				}
				
				$code = HTMLHelper::_('select.genericlist', $select, 'jform[request][userslist]', ' class=”inputbox” size="1" ', 'value', 'text', $default, 'jform_request_userslist');
			}
		}
		
		echo $code;
		return;
	}
	
	/**
	 * Method to load items list in menu edition
	 */
	function listItems()
	{
		$app = Factory::getApplication();
		$db = Factory::getContainer()->get(DatabaseInterface::class); 
		// recuperation du titre personnalise pour la collection
		$input = $app->getInput();
		$collection = $input->getint( 'collection', 0);
		$custom = array();
		$fieldCustom = array();
		$items = array();
		
		if ( $collection == 0 )
		{
			$code = Text::_('COM_COLLECTOR_SELECT_COLLECTION');
		}
		else
		{
			$menuId = $app->input->get( 'itemId', '', '', 'int');
			$menuparams = null;
			$default = 0;

			if ( $menuId != 0 )
			{
				$query = 'SELECT link';
				$query .= ' FROM `#__menu`';
				$query .= ' WHERE id = \''.$menuId.'\'';
				
				$db->setQuery( $query );
				
				$params = $db->loadResult();
				$paramsArray = explode("&", $params);
				
				foreach ( $paramsArray AS $param )
				{
					$param = explode("=", $param);
					$menuparams[$param[0]] = $param[1];
				}
			}
			
			if ( ( $menuparams != null ) AND ( isset($menuparams['id']) ) )
			{
				$default = $menuparams['id'];
			}

			$query = 'SELECT custom';
			$query .= ' FROM `#__collector`';
			$query .= ' WHERE id = \'' . $collection . '\'';

			$db->setQuery( $query );

			$row = $db->loadObjectList();

			if ( $row[0]->custom == "0" )
			{
				$query = 'SELECT id';
				$query .= ' FROM `#__collector_fields`';
				$query .= ' WHERE collection = "' . $collection . '"';
				$query .= ' AND home = "1"';

				$db->setQuery( $query );

				$row = $db->loadObjectList();

				$custom[] = $row[0]->id;
			}
			else
			{
				$custom = explode( '/', $row[0]->custom );
			}

			$query = 'SELECT f.*, u.name AS author, u.usertype, t.type AS type';
			$query .= ' FROM `#__collector_fields` AS f';
			$query .= ' LEFT JOIN #__collector_fields_type AS t ON t.id = f.type';
			$query .= ' LEFT JOIN #__users AS u ON u.id = f.created_by';
			$query .= ' WHERE collection = \'' . $collection . '\'';

			$db->setQuery( $query );

			$results = $db->loadObjectList();

			$i = 0;

			foreach ( $custom as $field )
			{
				for ($j = 0; $j < count($results); $j ++)
				{
					if ( $results[$j]->id == $field )
					{
						$registry = new Registry;
						$registry->loadString($results[$j]->attribs);
						$results[$j]->attribs = $registry->toArray();
						$fieldCustom[] = CollectorField::getInstance( $collection, $results[$j] );
					}
				}
				$i++;
			}

			// recherche des objets de la collection
			$query	= $db->getQuery(true);
			$query->select('c.id');
			$query->from('#__collector_items AS c');
			$query->join('LEFT', '#__collector_items_history_'.$collection.' AS h ON h.item = c.id');
			
			foreach ( $fieldCustom as $field )
			{
				$field->setQuery($query);
			}
			
			$query->where('c.collection = ' . $collection);
			$query->where('h.state = 1');

			$query->order('c.ordering');

			$db->setQuery( $query );

			$results = $db->loadObjectList();

			foreach ( $results as $row )
			{
				$item = new stdClass;
				$item->value = $row->id;

				$item->text = '';
				
				foreach ( $fieldCustom as $field )
				{
					$nameField = $field->_field->tablecolumn;
					$item->text .= $row->$nameField;
					$item->text .= ' ';
				}

				$items[] = $item;
			}
			
			$init[0] = array('value' => '', 'text' => ' - ' . Text::_('COM_COLLECTOR_SELECT_AN_ITEM').' - ');
			$select = $init;
			foreach ( $items as $key => $value )
			{
				$select[$key+1]=$value;
			}
			
			$code = HTMLHelper::_('select.genericlist', $select, 'jform[request][id]', ' class=”inputbox” size="1" ', 'value', 'text', $default, 'jform_request_id');
		}
		
		echo $code;
		return;
	}

}