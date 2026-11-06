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

namespace Steevo\Component\Collector\Administrator\Model;

defined('_JEXEC') or die;

use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Steevo\Component\Collector\Administrator\Classes\Field\Date\CollectorFieldDate;
use Steevo\Component\Collector\Administrator\Classes\Field\Define\CollectorFieldDefine;
use Steevo\Component\Collector\Administrator\Classes\Field\File\CollectorFieldFile;
use Steevo\Component\Collector\Administrator\Classes\Field\Image\CollectorFieldImage;
use Steevo\Component\Collector\Administrator\Classes\Field\Item\CollectorFieldItem;
use Steevo\Component\Collector\Administrator\Classes\Field\Line\CollectorFieldLine;
use Steevo\Component\Collector\Administrator\Classes\Field\Number\CollectorFieldNumber;
use Steevo\Component\Collector\Administrator\Classes\Field\Text\CollectorFieldText;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Component\Categories\Administrator\Helper\CategoriesHelper;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * Field model
 * @package	Collector
 */
class FieldModel extends AdminModel
{
	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'CollectorFields', $prefix = 'Table', $config = array()) 
	{
		return parent::getTable($type, $prefix, $config);
	}
	
	/**
	 * Method to get a single record.
	 *
	 * @param	integer	The id of the primary key.
	 *
	 * @return	mixed	Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk)) {
			// Convert the params field to an array.
			$registry      = new Registry($item->attribs);
            $item->attribs = $registry->toArray();
			
			// initialize collection if new field
			$input = Factory::getApplication()->input;
			$item->collection = $input->get('collection');
			
			if ( $item->id != 0 ) {
				$db = $this->getDatabase();
				$query = $db->getQuery(true)
					->select('*')
					->from($db->quoteName('#__collector_fields_type'))
					->where($db->quoteName('id') . ' = :id')
					->bind(':id', $item->type, ParameterType::INTEGER);
				$type = $db->setQuery($query)->loadObject();
				
				$attribsName = 'attribs-'.$type->type;
				$item->$attribsName = $item->attribs;
			}
		}
		return $item;
	}
	
	/**
	 * Method to test whether a record can have its state edited.
	 *
	 * @param	object	$record	A record object.
	 *
	 * @return	boolean	True if allowed to change the state of the record. Defaults to the permission set in the component.
	 */
	protected function canEditState($record)
	{
		$user = Factory::getApplication()->getIdentity();

		// Check for existing field.
		if (!empty($record->id)) {
			return $user->authorise('core.edit.state', 'com_collector.field.'.(int) $record->id);
		}
		// New article, so check against the category.
		elseif (!empty($record->collection))
		{
			return $user->authorise('core.edit.state', 'com_collector.collection.' . (int) $record->collection);
		}
		// Default to component settings if neither field nor collection known.
		else {
			return parent::canEditState('com_collector');
		}
	}
	
	/**
	 * Method to check if you can save a record.
	 *
	 * @param	array	$data	An array of input data.
	 * @param	string	$key	The name of the key for the primary key.
	 *
	 * @return	boolean
	 */
	protected function canSave($data = array(), $key = 'id')
	{
		return Factory::getUser()->authorise('core.edit', 'com_collector');
	}
	
	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		Data for the form.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	mixed	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true) 
	{
		$path = JPATH_ROOT.'/administrator/components/com_collector/forms/field.xml';
		$formXML = simplexml_load_file($path);
		
		$fieldset = $formXML->xpath('/form/fieldset');
		
		$fields = $fieldset[0]->children();
		
		$input = Factory::getApplication()->input;
		$post = $input->post;
		$form = $post->get('jform',null,'ARRAY');
		
		if ( $form == null ) {
			$collection = $input->get('collection');
			$fieldType = $input->get('type');
		} else {
			$collection = $form['collection'];
			$fieldType = $form['type'];
		}
		
		// if ($fieldType!='')
		// {
			// $db = $this->getDbo();
			// $query = 'SELECT * FROM #__collector_fields_type' .
					// ' WHERE id = '.$fieldType;
			// $db->setQuery( $query );
			// $type = $db->loadObject();
			
			// foreach ( $fields as $field )
			// {
				// $arr = $field->attributes();
				// if (( $arr['name'] == 'unik' ) && ( $type->unikable == 0 ))
				// {
					// $field->addAttribute('readonly', 'true');
				// }
				// if (( $arr['name'] == 'sort' ) && ( $type->sortable == 0 ))
				// {
					// $field->addAttribute('readonly', 'true');
				// }
				// if (( $arr['name'] == 'filter' ) && ( $type->filterable == 0 ))
				// {
					// $field->addAttribute('readonly', 'true');
				// }
			// }
		// }
		
		$types = $this->getTypes();
		
		foreach ( $types as $type )
		{
			// Add attribs field
			$fieldObject = CollectorField::getInstance( $collection, $type );
			$fieldObject->getFieldsetAttribs($formXML);
		}
		
		// Get the form.
		$form = $this->loadForm('com_collector.field', $formXML->asXML(), array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) 
		{
			return false;
		}		
		
		$fieldType = $form->getValue('type');
		if ($fieldType=='')
		{
			$fieldType = $input->get('type');
		}
		
		if ($fieldType!='')
		{
			$db = $this->getDbo();
			$query = 'SELECT * FROM #__collector_fields_type' .
					' WHERE id = '.$fieldType;
			$db->setQuery( $query );
			$type = $db->loadObject();
			
			if ( $type->unikable == 0 )
			{
				$form->setFieldAttribute('unik','readonly', 'true');
			}
			if ( $type->sortable == 0 )
			{
				$form->setFieldAttribute('sort','readonly', 'true');
			}
			if ( $type->filterable == 0 )
			{
				$form->setFieldAttribute('filter','readonly', 'true');
			}
		}
		
		// Determine correct permissions to check.
		if ($id = (int) $this->getState('field.id')) {
			// Existing record. Can only edit in selected categories.
			$form->setFieldAttribute('field', 'action', 'core.edit');
			// Existing record. Can only edit own articles in selected categories.
			$form->setFieldAttribute('field', 'action', 'core.edit.own');
		}
		else {
			// New record. Can only create in selected categories.
			$form->setFieldAttribute('field', 'action', 'core.create');
		}

		// Modify the form based on Edit State access controls.
		if (!$this->canEditState((object) $data)) {
			// Disable fields for display.
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('publish_up', 'disabled', 'true');
			$form->setFieldAttribute('publish_down', 'disabled', 'true');
			$form->setFieldAttribute('state', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is an article you can edit.
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('publish_up', 'filter', 'unset');
			$form->setFieldAttribute('publish_down', 'filter', 'unset');
			$form->setFieldAttribute('state', 'filter', 'unset');
		}
		
		$item = $this->getItem($input->get('id'));
		
		if ($item->tablecolumn != '')
		{
			$form->setFieldAttribute('tablecolumn', 'readonly', 'true');
		}
		
		return $form;
	}
	
	/**
	 * Method to get a types.
	 *
	 * @return	mixed	Object on success, false on failure.
	 */
	public function getTypes()
	{
		$db = $this->getDbo();
		$query = 'SELECT * FROM #__collector_fields_type';
		$db->setQuery( $query );
		$types = $db->loadObjectList();
		
		return $types;
	}
	
	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData() 
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_collector.edit.field.data', array());
		if (empty($data)) 
		{
			$data = $this->getItem();
		}
		return $data;
	}
	
	/**
	 * Method to set a field as default
	 *
	 * @access	public
	 * @param	int		$item	field Id
	 * @return	boolean			True on success
	 */
	public function setHome( $item )
	{
		// Initialise variables.
		$table		= $this->getTable();
		$db			= $this->getDbo();
		
		if ($table->load($item)) {
			if ($table->home == 1) {
				Factory::getApplication()->enqueueMessage(Text::_('COM_COLLECTOR_ERROR_ALREADY_HOME'),'notice');
			}
			else {
				$table->home = 1;
				if (!$this->canSave($table)) {
					// Prune items that you can't change.
					Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'),'warning');
				}
				else if (!$table->check()) {
					// Prune the items that failed pre-save checks.
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
				else if (!$table->store()) {
					// Prune the items that could not be stored.
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
				// Clear home field for all other items
				$query = 'UPDATE #__collector_fields' .
						' SET home = 0' .
						' WHERE id<>'.$table->id.
						' AND collection='.$table->collection;
				$db->setQuery( $query );
				if ( !$db->execute() ) {
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Method to set unicity parameter
	 *
	 * @access	public
	 * @param	int		$state	Parameter state
	 * @return	string			Message to display
	 */
	public function unik($state)
	{
		$app = Factory::getApplication();
		
		// on initialise les variables
		$table		= $this->getTable();
		$cid		= $app->input->get('cid', array(0), 'post', 'array');
		
		foreach ( $cid as $id )
		{
			if ($table->load($id)) {
				$table->unik = $state;
				if (!$this->canSave($table)) {
					// Prune items that you can't change.
					Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'),'warning');
				}
				else if (!$table->check()) {
					// Prune the items that failed pre-save checks.
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
				else if (!$table->store()) {
					// Prune the items that could not be stored.
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
			}
		}
		
		// création du message de retour
		$msg ='';
		
		if ( $state == 1 )
		{
			$msg = 'COM_COLLECTOR_FIELD_UNIK';
		}
		else if ( $state == 0 )
		{
			$msg = 'COM_COLLECTOR_FIELD_NOUNIK';
		}
		
		$msg = count($cid) . ' ' . Text::_( $msg );
		
		return $msg;
	}
	
	/**
	 * Method to set filter parameter
	 *
	 * @access	public
	 * @param	int		$state	Parameter state
	 * @return	string			Message to display
	 */
	public function filter($state)
	{
		$app = Factory::getApplication();
		
		// on initialise les variables
		$table		= $this->getTable();
		$cid		= $app->input->get('cid', array(0), 'post', 'array');
		
		foreach ( $cid as $id )
		{
			if ($table->load($id)) {
				$table->filter = $state;
				if (!$this->canSave($table)) {
					// Prune items that you can't change.
					Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'),'warning');
				}
				else if (!$table->check()) {
					// Prune the items that failed pre-save checks.
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
				else if (!$table->store()) {
					// Prune the items that could not be stored.
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
			}
		}
		
		// création du message de retour
		$msg ='';
		
		if ( $state == 1 )
		{
			$msg = 'COM_COLLECTOR_FILTER_ENABLED';
		}
		else if ( $state == 0 )
		{
			$msg = 'COM_COLLECTOR_FILTER_DISABLED';
		}
		
		$msg = count($cid) . ' ' . Text::_( $msg );
		
		return $msg;
	}
	
	/**
	 * Method to set listing view parameter
	 *
	 * @access	public
	 * @param	int		$state	Parameter state
	 * @return	string			Message to display
	 */
	public function listing($state)
	{
		$app = Factory::getApplication();
		
		// on initialise les variables
		$table		= $this->getTable();
		$cid 		= $app->input->get('cid', array(0), 'post', 'array');
		
		foreach ( $cid as $id )
		{
			if ($table->load($id)) {
				$table->listing = $state;
				if (!$this->canSave($table)) {
					// Prune items that you can't change.
					Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'),'warning');
				}
				else if (!$table->check()) {
					// Prune the items that failed pre-save checks.
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
				else if (!$table->store()) {
					// Prune the items that could not be stored.
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
			}
		}
		
		// création du message de retour
		$msg ='';
		
		if ( $state == 1 )
		{
			$msg = 'COM_COLLECTOR_FIELD_DISPLAYED';
		}
		else if ( $state == 0 )
		{
			$msg = 'COM_COLLECTOR_FIELD_HIDE';
		}
		
		$msg = count($cid) . ' ' . Text::_( $msg );
		
		return $msg;
	}
	
	/**
	 * Method to set required parameter
	 *
	 * @access	public
	 * @param	int		$state	Parameter state
	 * @return	string			Message to display
	 */
	public function required($state)
	{
		$app = Factory::getApplication();
		
		// on initialise les variables
		$table		= $this->getTable();
		$cid 		= $app->input->get('cid', array(0), 'post', 'array');
		
		foreach ( $cid as $id )
		{
			if ($table->load($id)) {
				$table->required = $state;
				if (!$this->canSave($table)) {
					// Prune items that you can't change.
					Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'),'warning');
				}
				else if (!$table->check()) {
					// Prune the items that failed pre-save checks.
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
				else if (!$table->store()) {
					// Prune the items that could not be stored.
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
			}
		}
		
		// création du message de retour
		$msg ='';
		
		if ( $state == 1 )
		{
			$msg = 'COM_COLLECTOR_REQUIRED_ENABLED';
		}
		else if ( $state == 0 )
		{
			$msg = 'COM_COLLECTOR_REQUIRED_DISABLED';
		}
		
		$msg = count($cid) . ' ' . Text::_( $msg );
		
		return $msg;
	}
	
	/**
	 * Method to set sorting parameter
	 *
	 * @access	public
	 * @param	int		$state	Parameter state
	 * @return	string			Message to display
	 */
	public function sort($state)
	{
		$app = Factory::getApplication();
		
		// on initialise les variables
		$table		= $this->getTable();
		$cid 		= $app->input->get('cid', array(0), 'post', 'array');
		
		foreach ( $cid as $id )
		{
			if ($table->load($id)) {
				$table->sort = $state;
				if (!$this->canSave($table)) {
					// Prune items that you can't change.
					Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'),'warning');
				}
				else if (!$table->check()) {
					// Prune the items that failed pre-save checks.
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
				else if (!$table->store()) {
					// Prune the items that could not be stored.
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
			}
		}
		
		// création du message de retour
		$msg ='';
		
		if ( $state == 1 )
		{
			$msg = 'COM_COLLECTOR_SORT_ENABLED';
		}
		else if ( $state == 0 )
		{
			$msg = 'COM_COLLECTOR_SORT_DISABLED';
		}
		
		$msg = count($cid) . ' ' . Text::_( $msg );
		
		return $msg;
	}
	
	/**
	 * Method to set frontend edition parameter
	 *
	 * @access	public
	 * @param	int		$state	Parameter state
	 * @return	string			Message to display
	 */
	public function edit($state)
	{
		$app = Factory::getApplication();
		
		// on initialise les variables
		$table		= $this->getTable();
		$cid 		= $app->input->get('cid', array(0), 'post', 'array');
		
		foreach ( $cid as $id )
		{
			if ($table->load($id)) {
				$table->edit = $state;
				if (!$this->canSave($table)) {
					// Prune items that you can't change.
					Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'),'warning');
				}
				else if (!$table->check()) {
					// Prune the items that failed pre-save checks.
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
				else if (!$table->store()) {
					// Prune the items that could not be stored.
					Factory::getApplication()->enqueueMessage($table->getError(),'warning');
				}
			}
		}
		
		// création du message de retour
		$msg ='';
		
		if ( $state == 1 )
		{
			$msg = 'COM_COLLECTOR_FIELD_UNLOCKED';
		}
		else if ( $state == 0 )
		{
			$msg = 'COM_COLLECTOR_FIELD_LOCKED';
		}
		
		$msg = count($cid) . ' ' . Text::_( $msg );
		
		return $msg;
	}
	
	/**
     * A protected method to get a set of ordering conditions.
     *
     * @param    object    $table    A JTable object.
     *
     * @return    array    An array of conditions to add to ordering queries.
     */
    protected function getReorderConditions($table)
    {
		$condition = array();
		$condition[] = 'collection = '.(int) $table->collection;
		$condition[] = 'state >= 0';
        return $condition;
    }
}