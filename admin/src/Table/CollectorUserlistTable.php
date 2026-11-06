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
 * Collector_userlist table
 * @package		Collector
 */
class CollectorUserlistTable  extends Table 
{
	/**
	 * @param database A database connector object
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct( '#__collector_userlist', 'id', $db );
	}
	
	/**
     * Initialise la liste d'un utilisateur pour un type de liste donné.
     * Crée l'entrée si elle n'existe pas encore.
     *
     * @param  int  $userslist  ID du type de liste
     * @return int  ID de la liste utilisateur
     */
    public function initList(int $userslist): int
    {
        $db   = $this->getDbo();
		$user = Factory::getApplication()->getIdentity();
		
		$db->setQuery(
            $db->getQuery(true)
               ->select('id')
               ->from($db->quoteName('#__collector_userlist'))
               ->where($db->quoteName('userslist') . ' = ' . $userslist)
               ->where($db->quoteName('user')      . ' = ' . (int) $user->id)
               ->setLimit(1)
        );
		$result = (int) $db->loadResult();
		
		if (!$result) {
            $this->user      = $user->id;
            $this->userslist = $userslist;
            $this->access    = 1;

            $this->check();
            $this->store();

            $result = (int) $this->id;
        }

        return $result;
    }
}