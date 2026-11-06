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

namespace Steevo\Component\Collector\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Rules;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * Collector_defined table
 * @package		Collector
 */
class CollectorDefinedTable extends Table
{
	/**
	 * @param database A database connector object
	 */
	function __construct(DatabaseDriver $db)
	{
		parent::__construct( '#__collector_defined', 'id', $db );
	}
	
	/**
	 * Overloaded check function
	 *
	 * @access	public
	 * @return	boolean	True if the object is ok
	 * @see		JTable::check
	 */
	function check()
	{
		// check for valid name
		if( trim($this->name) == '' ) {
			$this->setError(Text::_( 'COM_COLLECTOR_WARNING_PROVIDE_VALID_NAME' ));
			return false;
		}
		
		if(empty($this->alias)) {
			$this->alias = $this->name;
		}
		$this->alias = ApplicationHelper::stringURLSafe($this->alias);
		
		if (trim(str_replace('-','',$this->alias)) == '') {
			$this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
		}
		
		return true;
	}
	
	/**
	 * Overrides JTable::store to set modified data and user id.
	 *
	 * @param   boolean  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 */
	public function store($updateNulls = false)
	{
		$date = Factory::getDate()->toSql();
        $user = Factory::getApplication()->getIdentity();

		// Set created date if not set.
        if (!(int) $this->created) {
            $this->created = $date;
        }
		
		if ($this->id) {
			// Existing item
			$this->modified_by	= $user->get('id');
			$this->modified		= $date;
		} else {
			// Field created_by can be set by the user, so we don't touch it if it's set.
            if (empty($this->created_by)) {
                $this->created_by = $user->get('id');
            }

            // Set modified to created date if not set
            if (!(int) $this->modified) {
                $this->modified = $this->created;
            }

            // Set modified_by to created_by user if not set
            if (empty($this->modified_by)) {
                $this->modified_by = $this->created_by;
            }
		}
		// Verify that the alias is unique
		// $table = Table::getInstance('Collector_defined','Table');
		// if ($table->load(array('alias'=>$this->alias)) && ($table->id != $this->id || $this->id==0)) {
			// $this->setError(Text::_('COM_COLLECTOR_DATABASE_ERROR_COLLECTION_UNIQUE_ALIAS'));
			// return false;
		// }
		$store = parent::store($updateNulls);
		
		$listDir = JPATH_ROOT . '/images/collector/define/' . (int) $this->id;
		if (!is_dir($listDir)) {
			mkdir($listDir, 0755, true);
			// Protection
			file_put_contents($listDir . '/index.html', '');
		}

		return $store;
	}
	
	/**
	 * Overloaded delete function
	 *
	 * @access	public
	 * @return	boolean	True if the object is ok
	 * @see		JTable::delete
	 */
	public function delete($pk = null)
	{
		if ($pk)
		{
			$query = 'DELETE FROM `#__collector_defined_content` WHERE defined = '.$pk;
			$this->_db->setQuery( $query );
			$this->_db->execute();
		}
		
		return parent::delete($pk);
	}
}