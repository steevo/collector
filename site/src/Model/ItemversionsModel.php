<?php
namespace Steevo\Component\Collector\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;
use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Steevo\Component\Collector\Administrator\Table\CollectorItemsTable;

class ItemversionsModel extends ListModel
{
    private ?object $_collection = null;
    private ?object $_item       = null;

    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['h.id', 'h.state', 'h.modified', 'h.modified_by'];
        }
        parent::__construct($config);
    }

    // ── State ─────────────────────────────────────────────────────────────────

    protected function populateState($ordering = null, $direction = null): void
    {
        $app = Factory::getApplication();
        $this->setState('collection.id', $app->input->getInt('collection'));
        $this->setState('item.id',       $app->input->getInt('item'));
        parent::populateState('h.modified', 'desc');
    }

    // ── Requête ───────────────────────────────────────────────────────────────

    protected function getListQuery()
    {
        $db         = $this->getDbo();
        $collection = $this->getCollection();
        $itemId     = (int) $this->getState('item.id');

        return $db->getQuery(true)
            ->select('h.id, h.item, h.state, h.modified, h.modified_by, h.modification, h.refuse_note')
            ->from($db->quoteName('#__collector_items_history_' . $collection->id, 'h'))
            ->leftJoin($db->quoteName('#__users', 'u') . ' ON u.id = h.modified_by')
            ->select('u.name AS modified_by_name')
            ->where($db->quoteName('h.item') . ' = ' . $itemId)
            ->order('h.modified DESC');
    }

    // ── Données ───────────────────────────────────────────────────────────────

    public function getCollection(): ?object
    {
        if ($this->_collection !== null) {
            return $this->_collection;
        }

        $collectionId = (int) $this->getState('collection.id');
        if (!$collectionId) return null;

        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select('id, name, alias, custom')
               ->from($db->quoteName('#__collector'))
               ->where($db->quoteName('id') . ' = ' . $collectionId)
               ->where($db->quoteName('state') . ' = 1')
        );
        $this->_collection = $db->loadObject();
        return $this->_collection;
    }

    public function getItem(): ?object
    {
        if ($this->_item !== null) {
            return $this->_item;
        }

        $itemId     = (int) $this->getState('item.id');
        $collection = $this->getCollection();
        if (!$itemId || !$collection) return null;

        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select('i.id, i.alias, i.fulltitle, i.collection')
               ->from($db->quoteName('#__collector_items', 'i'))
               ->where($db->quoteName('i.id')         . ' = ' . $itemId)
               ->where($db->quoteName('i.collection') . ' = ' . $collection->id)
        );
        $this->_item = $db->loadObject();
        return $this->_item;
    }

    public function getActiveVersionId(): int
    {
        $collection = $this->getCollection();
        $itemId     = (int) $this->getState('item.id');
        if (!$collection || !$itemId) return 0;

        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select('id')
               ->from($db->quoteName('#__collector_items_history_' . $collection->id))
               ->where($db->quoteName('item')  . ' = ' . $itemId)
               ->where($db->quoteName('state') . ' = 1')
               ->setLimit(1)
        );
        return (int) $db->loadResult();
    }

    public function getOldestPendingVersionId(): int
    {
        $collection = $this->getCollection();
        $itemId     = (int) $this->getState('item.id');
        if (!$collection || !$itemId) return 0;

        $db = $this->getDbo();
        $db->setQuery(
            $db->getQuery(true)
               ->select('MIN(id)')
               ->from($db->quoteName('#__collector_items_history_' . $collection->id))
               ->where($db->quoteName('item')  . ' = ' . $itemId)
               ->where($db->quoteName('state') . ' = 0')
        );
        return (int) $db->loadResult();
    }

    // ── Actions validation ────────────────────────────────────────────────────

    public function validate(int $versionId): bool
    {
        $collection = $this->getCollection();
        $itemId     = (int) $this->getState('item.id');
        $db         = $this->getDbo();

        // Vérifier state=0
        $db->setQuery(
            $db->getQuery(true)
               ->select('state')
               ->from($db->quoteName('#__collector_items_history_' . $collection->id))
               ->where($db->quoteName('id') . ' = ' . $versionId)
        );
        if ((int) $db->loadResult() !== 0) {
            $this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_NOT_PENDING'));
            return false;
        }

        // Vérifier ordre
        $oldestPending = $this->getOldestPendingVersionId();
        if ($oldestPending !== $versionId) {
            $this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_VALIDATE_ORDER_ERROR'));
            return false;
        }

        // Activer
        $table             = new CollectorItemsTable($db);
        $table->initVersion($collection->id);
        $table->load($itemId);
        $table->initVersion($collection->id);
        $table->loadVersion($collection->id, $itemId, $versionId);

        if (!$table->activateVersion($versionId)) {
            $this->setError($table->getError());
            return false;
        }

        // Si save_history=0, supprimer toutes les versions archivées
		$params = ComponentHelper::getParams('com_collector');
		if (!$params->get('save_history', 1)) {
			$db->setQuery(
				$db->getQuery(true)
				   ->delete($db->quoteName('#__collector_items_history_' . $collection->id))
				   ->where($db->quoteName('item')  . ' = ' . $itemId)
				   ->where($db->quoteName('state') . ' = 2')
			)->execute();
		}

        // Décrémenter to_validate
        $db->setQuery(
            $db->getQuery(true)
               ->update($db->quoteName('#__collector_items'))
               ->set($db->quoteName('to_validate') . ' = GREATEST(0, ' . $db->quoteName('to_validate') . ' - 1)')
               ->where($db->quoteName('id') . ' = ' . $itemId)
        )->execute();

        return true;
    }

    public function refuse(int $versionId, string $note = ''): bool
    {
        $collection = $this->getCollection();
        $itemId     = (int) $this->getState('item.id');
        $db         = $this->getDbo();

        // Vérifier state=0
        $db->setQuery(
            $db->getQuery(true)
               ->select('state')
               ->from($db->quoteName('#__collector_items_history_' . $collection->id))
               ->where($db->quoteName('id') . ' = ' . $versionId)
        );
        if ((int) $db->loadResult() !== 0) {
            $this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_NOT_PENDING'));
            return false;
        }

        // Archiver avec la note
		$db->setQuery(
			$db->getQuery(true)
			   ->update($db->quoteName('#__collector_items_history_' . $collection->id))
			   ->set($db->quoteName('state')       . ' = 2')
			   ->set($db->quoteName('refuse_note') . ' = ' . $db->quote($note))
			   ->where($db->quoteName('id')        . ' = ' . $versionId)
		)->execute();

        // Si save_history=0, supprimer toutes les versions archivées
		$params = ComponentHelper::getParams('com_collector');
		if (!$params->get('save_history', 1)) {
			$db->setQuery(
				$db->getQuery(true)
				   ->delete($db->quoteName('#__collector_items_history_' . $collection->id))
				   ->where($db->quoteName('item')  . ' = ' . $itemId)
				   ->where($db->quoteName('state') . ' = 2')
			)->execute();
		}
		
		// Décrémenter to_validate
        $db->setQuery(
            $db->getQuery(true)
               ->update($db->quoteName('#__collector_items'))
               ->set($db->quoteName('to_validate') . ' = GREATEST(0, ' . $db->quoteName('to_validate') . ' - 1)')
               ->where($db->quoteName('id') . ' = ' . $itemId)
        )->execute();

        return true;
    }

    public function restore(int $versionId): bool
    {
        $collection = $this->getCollection();
        $itemId     = (int) $this->getState('item.id');
        $db         = $this->getDbo();

        // Vérifier state=2
        $db->setQuery(
            $db->getQuery(true)
               ->select('state')
               ->from($db->quoteName('#__collector_items_history_' . $collection->id))
               ->where($db->quoteName('id') . ' = ' . $versionId)
        );
        $state = (int) $db->loadResult();

        if ($state === 1) {
            $this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_ALREADY_ACTIVE'));
            return false;
        }
        if ($state === 0) {
            $this->setError(Text::_('COM_COLLECTOR_ITEM_HISTORY_TO_BE_CHECKED_UNRESTORED'));
            return false;
        }

        $table = new CollectorItemsTable($db);
        $table->initVersion($collection->id);
        $table->load($itemId);
        $table->initVersion($collection->id);
        $table->loadVersion($collection->id, $itemId, $versionId);

        if (!$table->activateVersion($versionId)) {
            $this->setError($table->getError());
            return false;
        }

        return true;
    }
}