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

require_once(JPATH_ROOT.'/administrator/components/com_collector/classes/field.php');

/**
 * Field Controller
 */
class CollectorControllerAjax extends JControllerLegacy
{
	/**
	 * Method to use ajax with fields.
	 *
	 * @return	mixed
	 * @since	3.0
	 */
	protected function ajax()
	{
		// Initialise variables.
		$input		= JFactory::getApplication()->input;
		$fieldType	= $input->get('fieldtype');
		
		$fieldClass = 'CollectorField_'.ucfirst($fieldType);
		
		if (!class_exists( $fieldClass ))
		{
			jimport('joomla.filesystem.file');
			$path = JPATH_ROOT.'/administrator/components/com_collector/classes/field/'.strtolower($fieldType).'/field.'.strtolower($fieldType).'.php';
			if( JFile::exists($path) )
			{
				require_once $path;

				if (!class_exists( $fieldClass ))
				{
					return JError::raiseError( 404, 'Field class ' . $fieldClass . ' not found in file.' );
				}
			}
			else
			{
				return JError::raiseError( 404, 'Field type ' . $fieldType . ' not supported. File '.$path.' not found.' );
			}
		}
		
		$return = $fieldClass::ajax();
	}
}