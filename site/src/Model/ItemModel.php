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

namespace Steevo\Component\Collector\Site\Model;

use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Steevo\Component\Collector\Administrator\Classes\Field\Date\CollectorFieldDate;
use Steevo\Component\Collector\Administrator\Classes\Field\Define\CollectorFieldDefine;
use Steevo\Component\Collector\Administrator\Classes\Field\File\CollectorFieldFile;
use Steevo\Component\Collector\Administrator\Classes\Field\Image\CollectorFieldImage;
use Steevo\Component\Collector\Administrator\Classes\Field\Item\CollectorFieldItem;
use Steevo\Component\Collector\Administrator\Classes\Field\Line\CollectorFieldLine;
use Steevo\Component\Collector\Administrator\Classes\Field\Number\CollectorFieldNumber;
use Steevo\Component\Collector\Administrator\Classes\Field\Text\CollectorFieldText;
use Steevo\Component\Collector\Administrator\Helper\CollectorHelper;
use Steevo\Component\Collector\Administrator\Helper\ProposalNotifier;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\Database\DatabaseInterface;

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

/**
 * Filelist model
 * @package	Collector
 */
class ItemModel extends AdminModel
{
	/**
	 * fields
	 * 
	 * @var array
	 */
	private $_fields;
	
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since	1.6
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load state from the request.
		$pkc = $app->input->getInt('collection');
		$pki = $app->input->getInt('id');

		// À l'enregistrement, la collection vient du formulaire
		if (!$pkc) {
			$pkc = (int) ($app->input->get('jform', [], 'array')['collection'] ?? 0);
		}
		$this->setState('collection.id', $pkc);
		$this->setState('item.id', $pki);

		// Load the parameters. Merge Global and Menu Item params into new object
		$params = $app->getParams();
		$menuParams = new Registry;

		if ($menu = $app->getMenu()->getActive()) {
			$menuParams = $menu->getParams();
		}

		$mergedParams = clone $menuParams;
		$mergedParams->merge($params);

		$this->setState('params', $mergedParams);

		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);
		
		$itemid = $app->input->getInt('collection', 0) . ':' . $app->input->getInt('Itemid', 0);
		
		$search_all_value = $app->getUserStateFromRequest('com_collector.collection.'.$itemid.'.filter_search_all', 'filter_search_all', '');
		$this->setState('filter.filter_search_all', $search_all_value);
		
		$orderCol = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.filter_order', 'filter_order', 'i.ordering', 'string');
		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.filter_order_Dir',	'filter_order_Dir', '', 'cmd');
		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
			$listOrder = 'ASC';
		}
		$this->setState('list.direction', $listOrder);
		
		$limit = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.limit', 'limit', $params->get('display_num')?$params->get('display_num'):$app->getCfg('list_limit'));
		$this->setState('list.limit', $limit);
	}

	/**
	 * Method to load default collection Id in <var>_collection</var>
	 *
	 * @access	private
	 */
	public function getCollection()
	{
		$user		= Factory::getApplication()->getIdentity();
		
		if ( empty($this->_collection) )
		{
			$app = Factory::getApplication();
			
			$collectionId = $this->getState('collection.id');
			
			if (!$collectionId) {
				$collectionId = $app->input->getInt('collection', 0);
			}
			
			if (!$collectionId) {
				$collectionId = (int) $app->getUserState('com_collector.current_collection', 0);
			}
			
			if (!$collectionId) {
				return false;
			}
			
			// Create a new query object.
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true);
			
			// Select the required fields from the table.
			$query->select('c.*, u.name AS author');
			$query->from('#__collector AS c');
			$query->join('LEFT', '#__users AS u ON u.id = c.created_by');
			// Filter by start and end dates.
			$nullDate = $db->Quote($db->getNullDate());
			$nowDate = $db->Quote(Factory::getDate()->toSql());

			$query->where('((c.created_by = '.$user->id.') OR (c.state = 1 AND ( c.publish_up IS NULL OR c.publish_up = '.$nullDate.' OR c.publish_up <= '.$nowDate.') AND ( c.publish_down IS NULL OR c.publish_down = '.$nullDate.' OR c.publish_down >= '.$nowDate.' )))');
			
			// Add the filder on ID
			$query->where('c.id = '.$collectionId);
			
			$db->setQuery( $query );
			
			$this->_collection = $db->loadObject();

			// Collection inconnue ou non publiée
			if (!$this->_collection) {
				$this->_collection = null;

				return false;
			}

			if ($this->_collection->custom == 0)
			{
				$query = $db->getQuery(true);
				$query->select('id');
				$query->from('#__collector_fields');
				$query->where('collection = '.$collectionId);
				$query->where('home = 1');
				
				$db->setQuery( $query );
				
				$this->_collection->custom = $db->loadResult();
			}
			 
			$this->_collection->custom = explode('/',$this->_collection->custom);
		}
		
		return $this->_collection;
	}
	
	/**
	 * Method to load fields informations
	 *
	 * @access	private
	 * @return	mixed			Array of fields objects. False if no fields loaded.
	 */
	public function getFields()
	{
		if ( empty($this->_fields) )
		{
			if ( $this->_collection->id == '0' )
			{
				return false;
			}
			else
			{
				$collection = $this->_collection->id;
			}
			
			$db		= $this->getDbo();
			$query	= $db->getQuery(true);
			
			$user		= Factory::getApplication()->getIdentity();
			$aid		= (int) $user->get('aid', 0);
			
			$jnow		= Factory::getDate();
			$now		= $jnow->toSql();
			$nullDate	= $db->getNullDate();
			
			$query->select('f.*, u.name AS author');
			$query->from('#__collector_fields AS f');
			
			// Join over the type.
			$query->select('t.type AS type');
			$query->join('LEFT', '#__collector_fields_type AS t ON t.id = f.type');
			
			$query->join('LEFT', '#__users AS u ON u.id = f.created_by');
			$query->where('collection = ' . $collection);
			
			$query->where('( f.created_by = ' . (int) $user->id . ' OR ( f.state = 1 AND ( f.publish_up IS NULL OR f.publish_up = '.$db->Quote($nullDate).' OR f.publish_up <= '.$db->Quote($now).' ) AND ( f.publish_down IS NULL OR f.publish_down = '.$db->Quote($nullDate).' OR f.publish_down >= '.$db->Quote($now).' ) ) )');
			
			// Filter by access level.
			if ($access = $this->getState('filter.access')) {
				$user	= Factory::getApplication()->getIdentity();
				$groups	= implode(',', $user->getAuthorisedViewLevels());
				$query->where('f.access IN ('.$groups.')');
			}
			$query->order('ordering');
			
			$db->setQuery($query);
			$fields = $db->loadObjectList();
			
			if ( ! $fields ) {
				return false;
			}
			
			foreach ($fields as $field)
			{
				$registry = new Registry;
				$registry->loadString($field->attribs);
				$field->attribs = $registry->toArray();
				$this->_fields[] = CollectorField::getInstance( $this->_collection->id, $field );
				
				// custom title
				if ( ($key = array_search($field->id, $this->_collection->custom))!==false )
				{
					$this->_collection->custom[$key] = $field->tablecolumn;
				}
			}
		}
		return $this->_fields;
	}
	
	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'CollectorItems', $prefix = 'Table', $config = array()) 
	{
		return parent::getTable($type, $prefix, $config);
	}
	
	/**
	 * Method to get an ojbect.
	 *
	 * @param	integer	The id of the object to get.
	 *
	 * @return	mixed	Object on success, false on failure.
	 */
	public function getItem($id = null)
	{
		// Get collection
		$collection	= $this->getCollection();
		$fields		= $this->getFields();

		$user	= Factory::getApplication()->getIdentity();
		
		if (empty($this->_item))
		{
			if (empty($id)) {
				$id = (int) $this->getState('item.id');
			}
			
			try
			{
				// Create a new query object.
				$db			= $this->getDbo();
				$query		= $db->getQuery(true);
				
				// Select the required fields from the table.
				$query->select(
					$this->getState(
						'item.select',
						'i.id, i.asset_id, i.alias, i.fulltitle, i.collection, i.ordering, i.state, i.hits, '.
						'i.created, i.created_by, i.created_by_alias, ' .
						'i.access, i.publish_up, i.publish_down, i.checked_out, i.checked_out_time, ' .
						// use created if modified is 0
						'CASE WHEN i.modified = ' . $db->quote($db->getNullDate()) . ' THEN i.created ELSE i.modified END as modified, ' .
							'i.modified_by'
					)
				);
				$query->from('#__collector_items AS i');
				
				// Join over the values.
				$query->select('h.metakey, h.metadesc, h.metadata, h.modification')
					->join('LEFT', '#__collector_items_history_'.$collection->id.' AS h ON h.item = i.id');
				foreach($fields as $field)
				{
					$field->setQuery($query);
				}
				
				// Join on category table.
				$query->select('c.access AS collection_access')
					->join('LEFT', '#__collector AS c on c.id = i.collection');
				
				// Join over the users for the author and modified_by names.
				$query->select("CASE WHEN i.created_by_alias > ' ' THEN i.created_by_alias ELSE ua.name END AS author");
				$query->join('LEFT', '#__users AS ua ON ua.id = i.created_by');
				$query->select("uam.name as modified_by_name");
				$query->join('LEFT', '#__users AS uam ON uam.id = i.modified_by');
				
				
				// Proposition (objet sans version validée) ouverte par un validateur : sa version en attente ; ouverte
				// par son auteur (pour la corriger) : la sienne
				$proposalVersion = $user->guest ? 0 : $this->getProposalVersionId((int) $collection->id, (int) $id);

				if ($proposalVersion && !$this->canValidateCollection((int) $collection->id))
				{
					$ownProposal     = $this->getOwnPendingVersion((int) $collection->id, (int) $id);
					$proposalVersion = $ownProposal ? (int) $ownProposal->id : 0;
				}

				$query->where($proposalVersion ? 'h.id = ' . $proposalVersion : 'h.state = 1');
				$query->where('i.collection = '.$collection->id);
				$query->where('i.id = '.$id);
				
				if ((!$user->authorise('core.edit.state', 'com_collector')) && (!$user->authorise('core.edit', 'com_collector'))) {
					// Filter by start and end dates.
					$nullDate = $db->quote($db->getNullDate());
					$date = Factory::getDate();

					$nowDate = $db->quote($date->toSql());

					$query->where('(i.publish_up IS NULL OR i.publish_up = ' . $nullDate . ' OR i.publish_up <= ' . $nowDate . ')')
						->where('(i.publish_down IS NULL OR i.publish_down = ' . $nullDate . ' OR i.publish_down >= ' . $nowDate . ')');
				}
				
				$db->setQuery($query);

				$data = $db->loadObject();

				if (empty($data))
				{
					return Factory::getApplication()->enqueueMessage(Text::_('COM_COLLECTOR_ERROR_ITEM_NOT_FOUND'),'error');
				}

				$data->proposal_version = $proposalVersion;
				
				$stateParams = $this->getState('params');
				if ($stateParams === null) {
					$app        = Factory::getApplication();
					$params     = $app->getParams();
					$menuParams = new \Joomla\Registry\Registry();
					if ($menu = $app->getMenu()->getActive()) {
						$menuParams = $menu->getParams();
					}
					$stateParams = clone $menuParams;
					$stateParams->merge($params);
				}
				$data->params = clone $stateParams;
				
				$registry = new Registry;
				$registry->loadString($data->metadata);
				$data->metadata = $registry;
				
				// Technically guest could edit an article, but lets not check that to improve performance a little.
				$userId = $user->get('id');
				$assetCol = 'com_collector.collection.' . $data->collection;
				$assetItem = 'com_collector.item.' . $data->id;

				// Check general edit permission first.
				if ($user->authorise('core.edit', $assetItem)) {
					$data->params->set('access-edit', true);
				} elseif ($user->authorise('core.edit', $assetItem) === null) {
					if ($user->authorise('core.edit', $assetCol)) {
						$data->params->set('access-edit', true);
					} elseif ($user->authorise('core.edit', $assetCol) === null) {
						if ($user->authorise('core.edit', 'com_collector')) {
							$data->params->set('access-edit', true);
						}
					}
				}

				// Now check if edit.own is available.
				elseif (!empty($userId) && $user->authorise('core.edit.own', $assetItem)) {
					// Check for a valid user and that they are the owner.
					if ($userId == $data->created_by) {
						$data->params->set('access-edit', true);
					}
				}
				elseif (!empty($userId) && ($user->authorise('core.edit.own', $assetItem) === null))
				{
					if ($user->authorise('core.edit.own', $assetCol))
					{
						$data->params->set('access-edit', true);
					}
					elseif ($user->authorise('core.edit.own', $assetCol) === null)
					{
						if ($user->authorise('core.edit.own', 'com_collector'))
						{
							$data->params->set('access-edit', true);
						}
					}
				}

				// Proposer une modification (sans pouvoir modifier directement) : formulaire de proposition, enregistré
				// en attente de validation ; sa propre proposition encore en attente y est reprise
				$data->own_pending = null;

				if (!$data->params->get('access-edit') && CollectorHelper::canPropose((int) $data->collection, (int) $data->id, $user))
				{
					$data->params->set('access-propose', true);
					$data->own_pending = $this->getOwnPendingVersion((int) $data->collection, (int) $data->id);
				}

				// Compute view access permissions.
				$user = Factory::getApplication()->getIdentity();
				$groups = $user->getAuthorisedViewLevels();

				$data->params->set('access-view', in_array($data->access, $groups) && in_array($data->collection_access, $groups));
				
				// Get item title.
				$data->titleItem = '';
				foreach($collection->custom as $custom)
				{
					if ($data->titleItem != '')
					{
						$data->titleItem .= ' ';
					}
					$data->titleItem .= $data->$custom;
				}
				
				$this->_item = $data;
			}
			catch (Exception $e)
			{
				if ($e->getCode() == 404)
				{
					// Need to go thru the error handler to allow Redirect to work.
					Factory::getApplication()->enqueueMessage($e->getMessage(),'error');
				}
				else
				{
					$this->setError($e);
					$this->_item = false;
				}
			}
		}

		return $this->_item;
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
		$fields = $this->getFieldsCol();
		
		$path = JPATH_ROOT.'/administrator/components/com_collector/forms/item.xml';
		$formXML = simplexml_load_file($path);
		
		foreach ( $fields as $field )
		{
			$child = $formXML->addChild('field');
			$attributes = $field->getFieldAttributes();
			foreach ($attributes as $key => $value) {
				$child->addAttribute($key,$value);
			}
		}
		
		// Get the form.
		$form = $this->loadForm('com_collector.item', $formXML->asXML(), array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) 
		{
			return false;
		}
		
		// Determine correct permissions to check.
		if ($id = (int) $this->getState('item.id')) {
			// Existing record. Can only edit in selected categories.
			$form->setFieldAttribute('item', 'action', 'core.edit');
			// Existing record. Can only edit own articles in selected categories.
			$form->setFieldAttribute('item', 'action', 'core.edit.own');
		}
		else {
			// New record. Can only create in selected categories.
			$form->setFieldAttribute('item', 'action', 'core.create');
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
		
		$itemId = !empty($data['id']) ? (int) $data['id'] : (int) $this->getState('item.id');
		$item   = $itemId ? $this->getItem($itemId) : null;
	
		// Proposition : le commentaire, destiné aux validateurs, reste toujours modifiable
		if ($item && $item->modification != '' && !$item->params->get('access-propose')) {
			$form->setFieldAttribute('modification', 'readonly', 'true');
		}
		
		return $form;
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
		$data = Factory::getApplication()->getUserState('com_collector.edit.item.data', array());
		if (empty($data)) 
		{
			$data = $this->getItemForm();
		}
		return $data;
	}
	
	/**
	* Method to save the form data.
	*
	* @param   array  $data  The form data.
	*
	* @return  boolean  True on success, False on error.
	* @since   11.1
	*/
	public function save($data)
	{
		// Initialise variables;
		$app = Factory::getApplication();
		// Options du lien de menu (sauvegarde de l'historique, validation automatique), sinon de Collector
		$params = $app->isClient('site') ? $app->getParams() : ComponentHelper::getParams('com_collector');
		$table = $this->getTable();
		$key = $table->getKeyName();
		// Objet du formulaire (0 : nouvel objet) ; l'« id » de la requête peut être celui d'une collection (lien de menu)
		$pk = array_key_exists($key, $data) ? (int) $data[$key] : (int) $this->getState($this->getName() . '.id');
		$isNew = true;
		$collection = $data['collection'];

		// Validation d'une proposition (objet sans version validée), par un validateur : les corrections
		// éventuelles vont dans la version en attente elle-même, qui est validée, et l'objet est publié
		$proposalVersion = 0;

		if ($pk > 0 && $app->get('com_collector.validate_proposal') && $this->canValidateCollection((int) $collection))
		{
			$proposalVersion = $this->getProposalVersionId((int) $collection, (int) $pk);

			if (!$proposalVersion)
			{
				$this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_NOT_PENDING'));
				return false;
			}
		}

		// Alter the title for save as copy
		if ($app->input->get('task') == 'save2copy')
		{
			$data['hits'] = 0;
			$data['state'] = 0;
		}
		
		// Include the content plugins for the on save events.
		PluginHelper::importPlugin('content');

		// Allow an exception to be thrown.
		try
		{
			// Load the row if saving an existing record.
			if ($pk > 0)
			{
				$table->loadVersion($collection, $pk, $proposalVersion ?: null);
				$isNew = false;
			} else {
				$table->initVersion($collection);
			}
		
			// Bind the data.
			if (!$table->bind($data))
			{
				$this->setError($table->getError());
				return false;
			}

			// Proposition validée : on garde son commentaire de modification (« version initiale »…), que
			// le chargement d'une version vide, sauf si le validateur en a saisi un autre
			if ($proposalVersion && trim((string) ($table->modification ?? '')) === '')
			{
				$db = $this->getDbo();
				$table->modification = (string) $db->setQuery(
					'SELECT ' . $db->quoteName('modification') . ' FROM ' . $db->quoteName('#__collector_items_history_' . (int) $collection)
					. ' WHERE id = ' . (int) $proposalVersion
				)->loadResult();
			}

			$user = $app->getIdentity();

			// Droit de modifier directement (créer, pour un nouvel objet) ; sinon, droit de proposer seulement
			$canDirect = $isNew
				? ($user->authorise('core.create', 'com_collector.collection.' . (int) $collection) || $user->authorise('core.create', 'com_collector'))
				: CollectorHelper::canEditDirectly((int) $collection, (int) $pk, (int) $table->created_by, $user);
			$proposer  = !$canDirect && CollectorHelper::canPropose((int) $collection, $isNew ? 0 : (int) $pk, $user);

			// Modification d'un objet : directe pour qui valide la collection, sinon option « Validation automatique »
			// de Collector (onglet Modification)
			$autoValidate = CollectorHelper::canValidate((int) $collection, $user) ? 1 : (int) $params->get('auto_validate', 1);

			// Nouvel objet proposé depuis le site par un utilisateur qui ne gère pas la publication, ou sans état
			// choisi (onglet Publication masqué) : option « Publication automatique » (onglet Proposition d'objet,
			// lien de menu sinon Collector) :
			// - oui : version validée et objet publié immédiatement ;
			// - non : objet dépublié, version en attente : il apparaît dans les objets à valider, la validation le publie
			if ($isNew && (!$this->canEditState((object) $data) || !isset($data['state']) || $data['state'] === '')) {
				$autoValidate = (int) (bool) $app->getParams()->get('auto_publish', 1);
				$table->state = $autoValidate;
			}

			// Proposition (droit « Proposer une modification » seulement) : toujours en attente de validation ; un
			// nouvel objet proposé reste dépublié jusqu'à sa validation. La proposition encore en attente du membre
			// sur cet objet est remplacée par la nouvelle (une seule par membre et par objet).
			$replacedVersion = null;

			if ($proposer && !$proposalVersion)
			{
				$autoValidate = 0;

				if ($isNew)
				{
					$table->state = 0;
				}
				else
				{
					$replacedVersion = $this->getOwnPendingVersion((int) $collection, (int) $pk);
				}
			}

			// Prepare the row for saving
			$this->prepareTable($table);
	
			// Check the data.
			if (!$table->check())
			{
				$this->setError($table->getError());
				return false;
			}
	
			// Trigger the onContentBeforeSave event.
			$result = $app->triggerEvent($this->event_before_save, array($this->option . '.' . $this->name, $table, $isNew, $data));
			if (in_array(false, $result, true))
			{
				$this->setError($table->getError());
				return false;
			}
	
			// Store the data.
			if ($proposalVersion)
			{
				// Proposition validée : sa version en attente, corrigée et validée
				$version      = $proposalVersion;
				$autoValidate = 1;
			}
			elseif ( $params->get('save_history') )
			{
				$version = null;
			}
			else
			{
				$version = $table->historyId;
			}
			if (!$table->storeVersion($version, $autoValidate))
			{
				$this->setError($table->getError());
				return false;
			}

			// Proposition validée : l'objet est publié et n'a plus cette version à valider
			if ($proposalVersion)
			{
				$db = $this->getDbo();
				$db->setQuery(
					$db->getQuery(true)
						->update($db->quoteName('#__collector_items'))
						->set($db->quoteName('state') . ' = 1')
						->set($db->quoteName('to_validate') . ' = GREATEST(0, ' . $db->quoteName('to_validate') . ' - 1)')
						->where($db->quoteName('id') . ' = ' . (int) $pk)
				)->execute();
			}

			// Proposition précédente du membre sur cet objet, remplacée par celle-ci
			if ($replacedVersion)
			{
				$table->deleteVersion((int) $replacedVersion->id);
			}

			// Version en attente : le contrôleur l'annonce au membre (au lieu de « enregistré »)
			if (!$autoValidate)
			{
				$app->set('com_collector.version_pending', 1);

				// Les validateurs en sont prévenus (option « Adresses à prévenir »)
				if (!empty($table->history->id))
				{
					ProposalNotifier::notifyProposal((int) $collection, (int) $table->history->id);
				}
			}
	
			// Clean the cache.
			$this->cleanCache();
	
			// Trigger the onContentAfterSave event.
			$app->triggerEvent($this->event_after_save, array($this->option . '.' . $this->name, $table, $isNew));
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
	
			return false;
		}
	
		$pkName = $table->getKeyName();
	
		if (isset($table->$pkName))
		{
			$this->setState($this->getName() . '.id', $table->$pkName);
		}
		$this->setState($this->getName() . '.new', $isNew);
	
		return true;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 * @since   11.1
	 */
	public function getItemForm($pk = null)
	{
		// Initialise variables.
		$pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName().'.id');
		$table	= $this->getTable();
		$input = Factory::getApplication()->input;
		$post = $input->input;
		
		// $form = $post->get('jform',null,'ARRAY');
		// $collection = $input->get( 'collection', $form['collection'], 'int');
		$collection = $input->get( 'collection', 0, 'int');

		if (!$collection) {
			$collection = (int) ($input->get('jform', [], 'array')['collection'] ?? 0);
		}

		if ($pk > 0) {
			// Proposition en attente du membre sur cet objet : le formulaire la reprend (pour la corriger), rapportée
			// sur la version en cours ; sinon la version en cours (proposition sans version validée : sa version en attente)
			$createdBy  = (int) $this->getDbo()->setQuery('SELECT created_by FROM #__collector_items WHERE id = ' . (int) $pk)->loadResult();
			$ownPending = !CollectorHelper::canEditDirectly((int) $collection, (int) $pk, $createdBy) && CollectorHelper::canPropose((int) $collection, (int) $pk)
				? $this->getOwnPendingVersion((int) $collection, (int) $pk) : null;

			$version = $ownPending ? (int) $ownPending->id : ($this->getProposalVersionId((int) $collection, (int) $pk) ?: null);

			// Attempt to load the row
			$return = $table->loadVersion($collection, $pk, $version);

			// Check for a table object error.
			if ($return === false && $table->getError()) {
				$this->setError($table->getError());
				return false;
			}

			if ($ownPending) {
				$table->collection = (int) $collection;

				foreach ($table->rebasePendingVersion((int) $ownPending->id)['data'] as $column => $value) {
					$table->$column = $value;
				}

				$table->modification = (string) $ownPending->modification;
			}
		}
		else
		{
			$return = $table->initVersion($collection);
			unset($table->history);
		}

		// Convert to the JObject before adding other data.
		$properties = $table->getProperties(1);
		$item       = ArrayHelper::toObject($properties, CMSObject::class);

		if (property_exists($item, 'params')) {
			$registry = new Registry;
			$registry->loadString($item->params);
			$item->params = $registry->toArray();
		}

		// Convert the params field to an array.
		$registry = new Registry;
		$registry->loadString($item->metadata);
		$item->metadata = $registry->toArray();
		
		return $item;
	}
	
	/**
	 * Method to load listdrop of collections
	 *
	 * @access	public
	 */
	public function getFieldsCol()
	{
		$item = $this->loadFormData();
		
		if ( empty($this->_fields) )
		{
			$collection = $this->getCollection();
			
			// Create a new query object.
			$db = $this->getDbo();
			$query = $db->getQuery(true);
			
			// Select the required fields from the table.
			$query->select('f.id, f.field, f.description, f.tablecolumn, f.required, f.attribs');
			$query->from('#__collector_fields as f');
			
			// Join over the type.
			$query->select('t.type AS type');
			$query->join('LEFT', '#__collector_fields_type AS t ON t.id = f.type');
			
			// Filter by collection
			$query->where('f.collection = '.$collection->id);
			
			// Add the list ordering clause.
			$query->order($db->escape('f.ordering ASC'));
			
			$db->setQuery( $query );
			
			$fields = $db->loadObjectList();
			
			foreach ($fields as $field)
			{
				$registry = new Registry;
				$registry->loadString($field->attribs);
				$field->attribs = $registry->toArray();
				$fieldObjects[] = CollectorField::getInstance( $collection->id, $field, $item->id );
			}
			$this->_fields = $fieldObjects;
		}
		
		return $this->_fields;
	}
	
	/**
	 * Increment the hit counter for the article.
	 *
	 * @param	int		Optional primary key of the article to increment.
	 *
	 * @return	boolean	True if successful; false otherwise and internal error set.
	 */
	public function hit($pk = 0)
	{
		$input = Factory::getApplication()->input;
		$hitcount = $input->getInt('hitcount', 1);

		if ($hitcount)
		{
			// Initialise variables.
			$pk = (!empty($pk)) ? $pk : (int) $this->getState('item.id');
			$db = $this->getDbo();

			$db->setQuery(
					'UPDATE #__collector_items' .
					' SET hits = hits + 1' .
					' WHERE id = '.(int) $pk
			);

			if (!$db->execute()) {
					$this->setError($db->getErrorMsg());
					return false;
			}
		}

		return true;
	}

	/**
	 * Method to get navigation
	 * Put values in <var>_prev</var> and <var>_next</var>
	 *
	 * @access	public
	 * @return	void
	 */
	function getPrevNext()
	{
		$db			= $this->getDbo();
		$nullDate	= $db->getNullDate();
		$jnow		= Factory::getDate();
		$now		= $jnow->toSql();
		$query		= $db->getQuery(true);
		
		$user	= Factory::getApplication()->getIdentity();
		$groups	= implode(',', $user->getAuthorisedViewLevels());
		
		$app		= Factory::getApplication();
		$collection	= $this->getCollection();
		$fields		= $this->getFields();
		$params		= $app->getParams();
		
		$this->_prev_id = 0;
		$this->_next_id = 0;

		$query->select('i.id, i.alias, i.fulltitle');
		$query->from('#__collector_items AS i');
		
		// Join over the values.
		$query->join('LEFT', '#__collector_items_history_'.$collection->id.' AS h ON h.item = i.id');
		foreach($fields as $field)
		{
			$field->setQuery($query);
		}
		
		// Join over the users for the author and modified_by names.
		$query->select("CASE WHEN i.created_by_alias > ' ' THEN i.created_by_alias ELSE ua.name END AS author");
		$query->join('LEFT', '#__users AS ua ON ua.id = i.created_by');
		$query->join('LEFT', '#__users AS uam ON uam.id = i.modified_by');
		
		// Filter by item state : publiés seulement pour les visiteurs, publiés et dépubliés
		// pour ceux qui peuvent modifier ; jamais les objets à la corbeille ni archivés
		if (!$user->authorise('core.edit.state', 'com_collector.collection.' . (int) $collection->id)
			&& !$user->authorise('core.edit', 'com_collector.collection.' . (int) $collection->id))
		{
			$query->where('i.state = 1');
		}
		else
		{
			$query->where('i.state IN (0, 1)');
		}

		// Filter by history state.
		$query->where('h.state = 1');
		
		// Filter by collection.
		$query->where('i.collection = '.$collection->id);
		
		// Filter by access level.
		$query->where('i.access IN ('.$groups.')');
		
		// Filter by start and end dates.
		if ((!$user->authorise('core.edit.state', 'com_collector.collection.'.(int) $collection->id)) &&  (!$user->authorise('core.edit', 'com_collector.collection.'.(int) $collection->id))){
			$nullDate	= $db->quote($db->getNullDate());
			$nowDate	= $db->quote(Factory::getDate()->toSql());

			$query->where('(i.publish_up IS NULL OR i.publish_up = '.$nullDate.' OR i.publish_up <= '.$nowDate.')')
				->where('(i.publish_down IS NULL OR i.publish_down = '.$nullDate.' OR i.publish_down >= '.$nowDate.')');
		}
		
		$whereSearch = array();
		
		$itemid = $app->input->getInt('collection', 0) . ':' . $app->input->getInt('Itemid', 0);
		
		$filter = $params->get('filter');
		
		foreach($fields as $field)
		{
			if ( $field->_field->filter == 1 )
			{
				$nameFilterCollection = 'filterfield_'.$field->_field->tablecolumn;
				if (isset($filter[$nameFilterCollection])) {
					$valueFilterMenu = $filter[$nameFilterCollection];
				} else {
					$valueFilterMenu = '';
				}

				$filtervalue = $app->getUserStateFromRequest('com_collector.collection.' . $itemid . '.'.$nameFilterCollection, $nameFilterCollection, $valueFilterMenu);
				
				$field->setFilterWhereClause($query,$filtervalue,$params);
			}
			
			$search_all_value = $this->getState('filter.filter_search_all');
			if ( $search_all_value != '' )
			{
				$whereSearch[] = $field->getSearchWhereClause($query,$search_all_value);
			}
		}
		
		if (count($whereSearch) > 0 ) {
			$query->where('('.implode(' OR ', $whereSearch).')');
		}
		
		$orderby = $this->getState('list.ordering').' '.$this->getState('list.direction');
		$query->order($orderby);
		
		$db->setQuery($query);
		
		$items = $db->loadRowList();

		$total = count($items);

		$id = $this->getState('item.id');
		
		$key = 0;
		for($i=0;$i<count($items);$i++)
		{
			if ($items[$i][0] == $id)
			{
				$key = $i;
				break;
			}
		}
		
		if ( $key != 0 )
		{
			$this->_prev_id = $items[$key - 1][0].':'.$items[$key - 1][1];
			$this->_prev_title = $items[$key - 1][2];
		}
		if ( $key != $total - 1 )
		{
			$this->_next_id = $items[$key + 1][0].':'.$items[$key + 1][1];
			$this->_next_title = $items[$key + 1][2];
		}
		
		$limit = $this->getState('list.limit');
		
		$this->_limitstart = $limit ? ((int)($key/$limit))*$limit : 0;
	}
	
	/**
	 * Version d'une proposition : objet qui n'a encore aucune version validée, sa plus ancienne version
	 * en attente. 0 pour un objet déjà validé (ses versions en attente sont des modifications).
	 */
	public function getProposalVersionId(int $collection, int $id): int
	{
		if ($collection <= 0 || $id <= 0)
		{
			return 0;
		}

		$db    = $this->getDbo();
		$table = $db->quoteName('#__collector_items_history_' . $collection);

		$active = (int) $db->setQuery('SELECT COUNT(*) FROM ' . $table . ' WHERE item = ' . $id . ' AND state = 1')->loadResult();

		if ($active)
		{
			return 0;
		}

		return (int) $db->setQuery('SELECT MIN(id) FROM ' . $table . ' WHERE item = ' . $id . ' AND state = 0')->loadResult();
	}

	/**
	 * L'utilisateur peut-il valider les versions de cette collection (droit de modifier)
	 */
	public function canValidateCollection(int $collection): bool
	{
		return CollectorHelper::canValidate($collection);
	}

	/**
	 * Proposition encore en attente de l'utilisateur sur un objet (la plus récente), ou null
	 *
	 * @return  object|null  id, modified, modification
	 */
	public function getOwnPendingVersion(int $collection, int $id): ?object
	{
		$user = Factory::getApplication()->getIdentity();

		if ($user->guest || $collection <= 0 || $id <= 0)
		{
			return null;
		}

		$db = $this->getDbo();
		$db->setQuery(
			'SELECT id, modified, modification FROM ' . $db->quoteName('#__collector_items_history_' . $collection)
			. ' WHERE item = ' . $id . ' AND state = 0 AND modified_by = ' . (int) $user->id
			. ' ORDER BY id DESC',
			0,
			1
		);

		return $db->loadObject() ?: null;
	}

	/**
	 * Navigation d'une fiche ouverte depuis une liste de membre (paramètre « liste » du lien) :
	 * 0, 1 ou 2 pour la liste des souhaits, des échanges ou la collection d'un membre (« liste_user »,
	 * vide pour la mienne), « matches1 » / « matches0 » pour les sections des échanges possibles avec
	 * un membre. Précédent et suivant parcourent la liste dans l'ordre et avec les filtres de sa page ;
	 * le retour ramène à la page de la liste où se trouve l'objet.
	 *
	 * @return  string|null  HTML de la navigation, null hors d'une liste de membre (navigation de la collection)
	 */
	protected function getUserlistNavigation(): ?string
	{
		$app   = Factory::getApplication();
		$input = $app->getInput();
		$liste = $input->getCmd('liste', '');

		if ($liste === '' || $app->getIdentity()->guest) {
			return null;
		}

		$collection = $this->getCollection();
		$cid        = (int) $collection->id;
		$other      = $input->getInt('liste_user', 0);
		$isMatches  = (bool) preg_match('/^matches([01])$/', $liste, $m);

		if (!$isMatches && !\in_array($liste, ['0', '1', '2'], true)) {
			return null;
		}

		if ($isMatches && (!$other || $other === (int) $app->getIdentity()->id)) {
			return null;
		}

		$model = $app->bootComponent('com_collector')->getMVCFactory()->createModel('Usercollection', 'Site', ['ignore_request' => false]);

		if (!$model) {
			return null;
		}

		// Le modèle des listes lit la collection, le type et le membre dans la requête : ceux de la liste
		$saved = [];

		foreach (['collection', 'type', 'user'] as $name) {
			$saved[$name] = $input->get($name, null, 'raw');
		}

		$input->set('collection', $cid);
		$input->set('type', $isMatches ? 'matches' : $liste);
		$input->set('user', $other);

		try {
			if ($isMatches) {
				$items = $model->getMatchItems($other, (int) $m[1]);
				$limit = 0;
			} else {
				// Filtres, tri et pagination mémorisés de la page de la liste ; tous les objets, sans pagination
				$model->getState();
				$limit = (int) $model->getState('list.limit');
				$model->setState('list.start', 0);
				$model->setState('list.limit', 0);
				$items = $model->getItems() ?: [];
			}
		} finally {
			foreach ($saved as $name => $value) {
				$input->set($name, $value);
			}
		}

		$id  = (int) $this->getState('item.id');
		$pos = null;

		foreach (array_values($items) as $i => $row) {
			if ((int) $row->id === $id) {
				$pos = $i;
				break;
			}
		}

		// Objet retiré de la liste entre-temps : navigation de la collection
		if ($pos === null) {
			return null;
		}

		$items   = array_values($items);
		$Itemid  = $input->getInt('Itemid', 0);
		$listUrl = 'index.php?option=com_collector&view=usercollection&collection=' . $cid
			. ($other ? '&user=' . $other : '') . '&type=' . ($isMatches ? 'matches' : $liste)
			. ($limit ? '&limitstart=' . ((int) floor($pos / $limit) * $limit) : '')
			. ($Itemid ? '&Itemid=' . $Itemid : '');
		$itemUrl = fn ($row) => Route::_('index.php?option=com_collector&view=item&collection=' . $cid . '&id=' . (int) $row->id . ':' . $row->alias
			. '&liste=' . $liste . ($other ? '&liste_user=' . $other : '') . ($Itemid ? '&Itemid=' . $Itemid : ''));
		$prev    = $items[$pos - 1] ?? null;
		$next    = $items[$pos + 1] ?? null;

		return $this->navigationHtml(
			$prev ? $itemUrl($prev) : null, (string) ($prev->fulltitle ?? ''),
			Route::_($listUrl),
			$next ? $itemUrl($next) : null, (string) ($next->fulltitle ?? '')
		);
	}

	/**
	 * Barre de navigation d'une fiche : précédent, retour à la liste, suivant.
	 * Même balisage que la pagination de Joomla : elle prend les couleurs que le template donne à sa
	 * pagination (mode sombre compris). Icônes intégrées ; sur mobile, les libellés sont masqués.
	 *
	 * @param   string|null  $prevUrl  adresse de l'objet précédent (routée), null s'il n'y en a pas
	 * @param   string|null  $nextUrl  adresse de l'objet suivant (routée), null s'il n'y en a pas
	 */
	protected function navigationHtml(?string $prevUrl, string $prevTitle, string $listUrl, ?string $nextUrl, string $nextTitle): string
	{
		$esc  = fn ($text) => htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
		$svg  = fn ($path) => '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" d="' . $path . '"/></svg>';
		$prevIcon = $svg('M10 3 5 8l5 5');
		$nextIcon = $svg('M6 3l5 5-5 5');
		$listIcon = '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><g fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1.75" y="1.75" width="5" height="5" rx="1"/><rect x="9.25" y="1.75" width="5" height="5" rx="1"/><rect x="1.75" y="9.25" width="5" height="5" rx="1"/><rect x="9.25" y="9.25" width="5" height="5" rx="1"/></g></svg>';

		$item = function (?string $url, string $icon, string $label, string $title, bool $iconAfter = false) use ($esc): string {
			$content = $iconAfter
				? '<span class="collector-item-nav-label">' . $esc($label) . '</span>' . $icon
				: $icon . '<span class="collector-item-nav-label">' . $esc($label) . '</span>';

			if ($url === null) {
				return '<li class="page-item disabled"><span class="page-link" aria-disabled="true">' . $content . '</span></li>';
			}

			return '<li class="page-item"><a class="page-link" href="' . $url . '" title="' . $esc($title) . '" aria-label="' . $esc($title) . '">' . $content . '</a></li>';
		};

		$list = Text::_('COM_COLLECTOR_RETURN_TO_LIST');

		return '<nav class="collector-item-nav" aria-label="' . $esc(Text::_('COM_COLLECTOR_ITEM_NAVIGATION')) . '"><ul class="pagination">'
			. $item($prevUrl, $prevIcon, Text::_('JPREV'), $prevTitle !== '' ? Text::_('JPREV') . ' : ' . $prevTitle : Text::_('JPREV'))
			. $item($listUrl, $listIcon, Text::_('COM_COLLECTOR_ITEM_NAVIGATION_LIST'), $list)
			. $item($nextUrl, $nextIcon, Text::_('JNEXT'), $nextTitle !== '' ? Text::_('JNEXT') . ' : ' . $nextTitle : Text::_('JNEXT'), true)
			. '</ul></nav>';
	}

	/**
	 * Method to get html navigation
	 * Put data in <var>_navigation</var>
	 *
	 * @access	public
	 * @return	string Navigation html
	 */
	function getNavigation()
	{
		$app = Factory::getApplication();
		
		// Fiche ouverte depuis une liste de membre : précédent, suivant et retour dans cette liste
		if (empty($this->_navigation) && ($userlist = $this->getUserlistNavigation()) !== null)
		{
			$this->_navigation = $userlist;
		}

		// Load the content if it doesn't already exist
		if (empty($this->_navigation))
		{
			$this->_navigation = '';

			$this->getPrevNext();
			
			$prev = $this->_prev_id;
			$next = $this->_next_id;
			$link_base = 'index.php';
			$link_back = $link_base;

			$option = $app->input->get('option', 0, 'get');
			$collection = $app->input->get('collection', 0, 'get');
			$item = $app->input->get('id', 0, 'get');
			$Itemid = $app->input->get('Itemid', 0, 'get');

			$link_base .= '?option='.$option.'&view=item&collection='.$collection.'&Itemid='.$Itemid;
			$link_back .= '?option='.$option.'&view=collection&reset=0&id='.$collection.'&limitstart='.$this->_limitstart.'&Itemid='.$Itemid;

			$this->_navigation = $this->navigationHtml(
				$prev ? Route::_($link_base . '&id=' . $prev) : null, (string) ($this->_prev_title ?? ''),
				Route::_($link_back),
				$next ? Route::_($link_base . '&id=' . $next) : null, (string) ($this->_next_title ?? '')
			);
		}
		return $this->_navigation;
	}
}