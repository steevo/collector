<?php
namespace Steevo\Component\Collector\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class CollectorImportHistoryTable extends Table
{
    /** @var int */
    public $id = null;

    /** @var int */
    public $collection = null;

    /** @var string */
    public $file = '';

    /** @var string */
    public $strategy = 'unik';

    /** @var int */
    public $imported = 0;

    /** @var int */
    public $merged = 0;

    /** @var int */
    public $skipped = 0;

    /** @var int */
    public $errors = 0;
	
	/** @var int */
	public $conflicts = 0;

	/** @var int */
	public $multiconflicts = 0;

    /** @var int */
    public $dry_run = 0;

    /** @var string */
    public $created = '';

    /** @var int */
    public $created_by = 0;

    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__collector_import_history', 'id', $db);
    }

    public function check(): bool
    {
        if (empty($this->collection)) {
            $this->setError('Collection is required');
            return false;
        }
        if (empty($this->created)) {
            $this->created = Factory::getDate()->toSql();
        }
        if (empty($this->created_by)) {
            $this->created_by = Factory::getApplication()->getIdentity()->get('id');
        }
        return true;
    }

    /**
     * Met à jour les compteurs finaux de l'import.
     */
    public function updateCounters(
		int $imported, int $merged, int $skipped, int $errors,
		int $conflicts = 0, int $multiconflicts = 0
	): bool {
		if (!$this->id) return false;

		$db = $this->getDbo();
		$db->setQuery(
			$db->getQuery(true)
			   ->update($db->quoteName('#__collector_import_history'))
			   ->set($db->quoteName('imported')       . ' = ' . $imported)
			   ->set($db->quoteName('merged')         . ' = ' . $merged)
			   ->set($db->quoteName('skipped')        . ' = ' . $skipped)
			   ->set($db->quoteName('errors')         . ' = ' . $errors)
			   ->set($db->quoteName('conflicts')      . ' = ' . $conflicts)
			   ->set($db->quoteName('multiconflicts') . ' = ' . $multiconflicts)
			   ->where($db->quoteName('id')           . ' = ' . $this->id)
		);

		try {
			$db->execute();
			return true;
		} catch (\RuntimeException $e) {
			$this->setError($e->getMessage());
			return false;
		}
	}
}