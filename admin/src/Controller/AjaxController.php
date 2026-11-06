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

use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The collector controller for ajax requests
 *
 * @since  3.9.0
 */
class AjaxController extends FormController
{
	/**
	 * Method to use ajax with fields.
	 *
	 * @return	mixed
	 * @since	3.0
	 */
	/*protected function ajax()
	{
		// Initialise variables.
		$input		= Factory::getApplication()->input;
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
					return Factory::getApplication()->enqueueMessage(Text::_('Field class ' . $fieldClass . ' not found in file.'),'error');
				}
			}
			else
			{
				return Factory::getApplication()->enqueueMessage(Text::_('Field type ' . $fieldType . ' not supported. File '.$path.' not found.'),'error');
			}
		}
		
		$return = $fieldClass::ajax();
	}*/
	
	/**
	 * Method to display an input hidden
	 * For AJAX request
	 *
	 * @access	public
	 */
	function params()
	{
		$model = $this->getModel('field');
		// $model->setState('field.id', $this->input->get('id'));
		
		$data['collection'] = $this->input->get('collection');
		$data['type'] = $this->input->get('type');
		$data['field'] = $this->input->get('id');
		
		$form = $model->getForm($data, true);
		// $form = $model->getForm();
		
		$params = $form->renderField('unik');
		$params .= $form->renderField('edit');
		$params .= $form->renderField('listing');
		$params .= $form->renderField('filter');
		$params .= $form->renderField('sort');
		
		echo $params;
		return;
	}
}