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

// Require collection class
require_once(JPATH_ROOT.'/administrator/components/com_collector/classes/collection.php');

/**
 * User collection abstract class
 *
 * @package	Collector
 * @abstract
 */
class CollectorUser
{
	/**
	 * Collection Id
	 * 
	 * @var int
	 */
	var $_collection;
	
	/**
	 * Type of list [collection,manco,dispo]
	 * 
	 * @var string
	 */
	var $_type;
	
	/**
	 * List of items Ids
	 * 
	 * @var string
	 */
	var $_listNumber;
	
	/**
	 * List of items
	 * 
	 * @var array
	 */
	var $_list;
	
	/**
	 * User object
	 * 
	 * @var object JUser
	 */
	var $_user;
	
	/**
	 * Access level
	 * 
	 * @var int
	 */
	var $_access;
	
	/**
	 * Object constructor to set user collection
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @access	protected
	 * @param	int						$collection	Collection Id
	 * @param	int						$type	Type of list [collection,manco,dispo]
	 * @param	int						$user	User Id
	 */
	function __construct( $collection, $type, $user = null )
	{
		// Initialisation
		$this->_collection = $collection;
		$this->_type = $type;
		$this->_user = JFactory::getUser($user);
		
		// Recuperation des donnees utilisateur
		$user_lists = & JTable::getInstance('collector_users','Table');
		$user_lists->loadUserCollection($user,$collection);
		
		$access_list = $type.'_access';
		$this->_access = $user_lists->$access_list;
		$this->_listNumber = $user_lists->$type;
		
		// Recuperation de la liste complete
		$this->_loadList();
	}
	
	/**
	 * Returns a reference to the a user collection, always creating it
	 *
	 * @param	int						$collection	Collection Id
	 * @param	int						$type		Type of list [collection,manco,dispo]
	 * @param	int						$user		User Id
	 * @return	object CollectorUser				Reference to a user collection class
	 */
	function &getInstance( $collection, $type, $user = null )
	{
		// Get a handle to the Joomla! application object
		$application = JFactory::getApplication();

		$this->_type = $type;
		$collectionClass = 'CollectorUser_'.ucfirst($type);

		if (!class_exists( $collectionClass ))
		{
			jimport('joomla.filesystem.path');
			if($path = JPath::find(JPATH_ROOT.'/administrator/components/com_collector/classes/user', 'user.'.strtolower($type).'.php'))
			{
				require_once $path;

				if (!class_exists( $collectionClass ))
				{
					$application->enqueueMessage( 'Collection user class ' . $collectionClass . ' not found in file.', 'warning' );
					return $false;
				}
			}
			else
			{
				$application->enqueueMessage( 'Collection user ' . $type . ' not supported. File '.$path.' not found.', 'warning' );
				return $false;
			}
		}

		$instance = new $collectionClass($collection,$type,$user);

		return $instance;
	}
	
	/**
	 * Method to load items
	 *
	 * Can be overloaded/supplemented by the child class
	 *
	 * @access	private
	 */
	function _loadList()
	{
		$query = 'SELECT 
	}
}