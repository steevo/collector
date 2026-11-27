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

    /**
     * Objet de la version, prêt pour les actions sur ses versions (CollectorItemsTable)
     */
    private function getItemTable(): CollectorItemsTable
    {
        $table             = new CollectorItemsTable($this->getDbo());
        $table->collection = (int) $this->getCollection()->id;
        $table->load((int) $this->getState('item.id'));

        return $table;
    }

    /**
     * Valide une version en attente (voir CollectorItemsTable::validatePendingVersion)
     */
    public function validate(int $versionId): bool
    {
        $table = $this->getItemTable();

        if (!$table->validatePendingVersion($versionId)) {
            $this->setError($table->getError());
            return false;
        }

        return true;
    }

    /**
     * Refuse une version en attente (voir CollectorItemsTable::refuseVersion)
     */
    public function refuse(int $versionId, string $note = ''): bool
    {
        $table = $this->getItemTable();

        if (!$table->refuseVersion($versionId, $note)) {
            $this->setError($table->getError());
            return false;
        }

        return true;
    }

    /**
     * Rétablit une ancienne version (voir CollectorItemsTable::restoreVersion)
     */
    public function restore(int $versionId): bool
    {
        $table = $this->getItemTable();

        if (!$table->restoreVersion($versionId)) {
            $this->setError($table->getError());
            return false;
        }

        return true;
    }
}
