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

/**
 * Item Controller
 *
 * @package  	Collector
 */
class CollectorControllerItem extends JControllerForm
{
	public function delete()
    {
        $app = JFactory::getApplication();
		
		// Check for request forgeries
        JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
 
        // Get item to remove from the request.
        $id = $app->input->getInt('id');
        $collection = $app->input->getInt('collection');
 
		// Get the model.
		$model = $this->getModel();

		// Remove the items.
		if ($model->delete($id))
		{
			$this->setMessage(JText::_('COM_COLLECTOR_ITEM_DELETED'));
		}
		else
		{
			$this->setMessage($model->getError());
		}
 
        $this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=collection&collection=' . $collection, false));
    }
	
	/**
	 * Method override to check if you can add a new record.
	 *
	 * @param	array	An array of input data.
	 *
	 * @return	boolean
	 * @since	1.6
	 */
	protected function allowAdd($data = array())
	{
		// Initialise variables.
		$user	= JFactory::getUser();
		$collection	= (int) isset($data['collection']) ? $data['collection'] : 0;
		
		$allow	= $user->authorise('core.create', 'com_collector.collection.'.$collection);

		if ($allow === null) {
			// In the absense of better information, revert to the component permissions.
			return parent::allowAdd();
		}
		else {
			return $allow;
		}
	}
	
	/**
	 * Method override to check if you can edit an existing record.
	 *
	 * @param	array	$data	An array of input data.
	 * @param	string	$key	The name of the key for the primary key.
	 *
	 * @return	boolean
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		// Initialise variables.
		$recordId	= (int) isset($data[$key]) ? $data[$key] : 0;
		$collection	= (int) isset($data['collection']) ? $data['collection'] : 0;
		$user		= JFactory::getUser();
		$userId		= $user->get('id');

		// Check general edit permission first.
		$allowItem = $user->authorise('core.edit', 'com_collector.item.'.$recordId);
		if ($allowItem === null)
		{
			$allowCollection = $user->authorise('core.edit', 'com_collector.collection.'.$collection);
			if ( $allowCollection === null )
			{
				$allow = $user->authorise('core.edit', 'com_collector');
			}
			elseif ($allowCollection)
			{
				return true;
			}
		}
		elseif ($allowItem)
		{
			return true;
		}

		// Fallback on edit.own.
		// First test if the permission is available.
		$allowItem = $user->authorise('core.edit.own', 'com_collector.item.'.$recordId);
		if ($allowItem === null)
		{
			$allowCollection = $user->authorise('core.edit.own', 'com_collector.collection.'.$collection);
			if ( $allowCollection === null )
			{
				$allow = $user->authorise('core.edit.own', 'com_collector');
			}
			elseif ($allowCollection)
			{
				return true;
			}
		}
		elseif ($allowItem)
		{
			return true;
		}

		// Since there is no asset tracking, revert to the component permissions.
		return parent::allowEdit($data, $key);
	}
	
	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param	string	$name	The model name. Optional.
	 * @param	string	$prefix	The class prefix. Optional.
	 * @param	array	$config	Configuration array for model. Optional.
	 *
	 * @return	object	The model.
	 */
	public function getModel($name = 'itemform', $prefix = '', $config = array('ignore_request' => false))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}
	
	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param    int        $recordId    The primary key id for the item.
	 * @param    string    $urlVar        The name of the URL variable for the id.
	 *
	 * @return    string    The arguments to append to the redirect URL.
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$app = JFactory::getApplication();
		
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		
		$collection = $app->input->get('collection');
		
		$item = $app->input->get('id');
		
		if (empty($collection))
		{
			$form = $app->input->get('jform');
			
			$collection = $form['collection'];
		}
		
		if (empty($item))
		{
			$form = $app->input->get('jform');
			
			$item = $form['id'];
		}
		
		$append .= '$view=item&collection='.$collection.'&id='.$item;
		
		return $append;
    }
	
	/**
	* Gets the URL arguments to append to a list redirect.
	*
	* @return    string    The arguments to append to the redirect URL.
	*/
	protected function getRedirectToListAppend()
	{
		$app = JFactory::getApplication();
		
		$append = parent::getRedirectToListAppend();
		
		$collection = $app->input->get('collection');
		
		if (empty($collection))
		{
			$form = $app->input->get('jform');
			
			$collection = $form['collection'];
		}
		
		$append .= '&view=collection&id='.$collection;
		
		return $append;
	}
}