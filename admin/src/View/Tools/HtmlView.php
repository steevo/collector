<?php
namespace Steevo\Component\Collector\Administrator\View\Tools;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Steevo\Component\Collector\Administrator\Model\FilesModel;
use Joomla\Database\DatabaseInterface;

class HtmlView extends BaseHtmlView
{
    public int     $collection = 0;
    public array   $collections = [];
    public int     $emptyFulltitles = 0;

    public function display($tpl = null): void
    {
        /** @var \Steevo\Component\Collector\Administrator\Model\ToolsModel $model */
		$model = $this->getModel();
		$app = Factory::getApplication();

        // Même logique de sélection de collection que les autres vues
        $collectionFromInput = $app->input->getInt('collection', 0);
        $collectionFromShared = (int) $app->getUserState('com_collector.current_collection', 0);

        $this->collection = $collectionFromInput ?: $collectionFromShared;
		$this->exportFiles = $model->getExportFiles();

		// Données pour le gestionnaire de fichiers
		$this->filesContext   = $app->input->getString('context', '');
		$this->filesContextId = $app->input->getInt('context_id', 0);

		$filesModel    = new FilesModel();
		$this->defines = $filesModel->getDefineGroups();
		$this->collectionsToMigrate = $filesModel->countCollectionsToMigrate();
		$this->migrateReport        = $app->getSession()->get('collector.migrate_report');
		$app->getSession()->set('collector.migrate_report', null);

		// Collections déjà chargées via $this->collections
		if ($this->filesContextId && $this->filesContext) {
			$filesModel->ensureRootExists($this->filesContext, $this->filesContextId);
		}

		if ($this->collection) {
            $app->setUserState('com_collector.current_collection', $this->collection);

            $this->emptyFulltitles = $model->countEmptyFulltitles($this->collection);
			$this->totalItems = $model->countTotalItems($this->collection);
			$this->orphanUsersitems = $model->countOrphanUsersitems($this->collection);
			$this->itemsWithoutActiveVersion = $model->countItemsWithoutActiveVersion($this->collection);
			$this->toValidateMismatch = $model->countToValidateMismatch($this->collection);
			$this->archivedToClean = $model->countArchivedVersionsToClean($this->collection);
			$this->staleImportSessions = $model->countStaleImportSessions($this->collection, 24);
			$this->orphanVersions = $model->countOrphanVersions($this->collection);
        }
		
		$this->invalidCollectionItems = $model->getItemsWithInvalidCollection();
		$this->orphanDefinedContent = $model->getOrphanDefinedContent();
		
		$this->tableStats      = [];
		$this->itemCountByState = [];

		if ($this->collection) {
			$this->tableStats       = $model->getTableStats($this->collection);
			$this->itemCountByState = $model->getItemsCountByState($this->collection);
		}

        // Liste des collections pour le sélecteur
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery(
            $db->getQuery(true)
               ->select('id, name')
               ->from($db->quoteName('#__collector'))
               ->order('name')
        );
        $this->collections = $db->loadObjectList();

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_COLLECTOR_TOOLS_TITLE'), 'wrench');
    }
}