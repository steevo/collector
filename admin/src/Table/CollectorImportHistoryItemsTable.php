<?php
namespace Steevo\Component\Collector\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class CollectorImportHistoryItemsTable extends Table
{
    /** @var int */
    public $id = null;

    /** @var int */
    public $import_id = null;

    /** @var int */
    public $item_id = null;

    /** @var string 'imported' | 'merged' */
    public $action = '';

    /** @var int|null */
    public $version_before = null;

    /** @var int|null */
    public $version_after = null;
	
	/** @var string|null */
	public $rolled_back_at = null;

    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__collector_import_history_items', 'id', $db);
    }

    public function check(): bool
    {
        if (empty($this->import_id)) {
            $this->setError('import_id is required');
            return false;
        }
        if (empty($this->item_id)) {
            $this->setError('item_id is required');
            return false;
        }
        if (!in_array($this->action, ['imported', 'merged'], true)) {
            $this->setError('action must be imported or merged');
            return false;
        }
        return true;
    }
}