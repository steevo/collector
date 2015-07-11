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
 * Item Controller
 *
 * @package  	Collector
 */
class CollectorControllerUseritem extends JControllerForm
{
	public function add()
    {
        $app = JFactory::getApplication();

		// Check for request forgeries
        JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

        // Get item to remove from the request.
		$data = array();
        $item = $app->input->getInt('item');
        $userlist = $app->input->getInt('userlist');
        $comment = $app->input->getString('comment');
		$data['itemid'] = $item;
		$data['userlist'] = $userlist;
		$data['comment'] = $comment;

		// Get the model.
		$model = $this->getModel();

		// Add the item.
		$response = $model->add($data);

        echo $response;
    }
	
	public function delete()
    {
        $app = JFactory::getApplication();

		// Check for request forgeries
        JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

        // Get item to remove from the request.
        $data = array();
		$item = $app->input->getInt('item');
		$userlist = $app->input->getInt('userlist');
		$cid = $app->input->get('cid', array(), 'array');
		$data['itemid'] = $item;
		$data['userlist'] = $userlist;
		$data['cid'] = $cid;
		
		// Get the model.
		$model = $this->getModel();

		// Remove the items.
		$response = $model->delete($data);

        echo $response;
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
	public function getModel($name = 'useritem', $prefix = '', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
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
		
		$form = $app->input->getVar('jform');
		
		$append .= '&view=collection&id='.$form['collection'];
		
		return $append;
	}
}