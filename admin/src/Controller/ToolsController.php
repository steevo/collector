<?php
namespace Steevo\Component\Collector\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class ToolsController extends BaseController
{
    protected $default_view = 'tools';

	public function regeneratefulltitlesajax(): void
	{
		$app = Factory::getApplication();

		if (!Session::checkToken('request')) {
			$this->sendJson(null, Text::_('JINVALID_TOKEN'), true);
			return;
		}

		$collection = $app->input->getInt('collection');
		$mode       = $app->input->getString('mode', 'empty');
		$afterId    = $app->input->getInt('after_id', 0);

		$model  = $this->getModel('Tools');
		$result = $model->regenerateFulltitlesBatch($collection, $mode, $afterId, 20);

		$this->sendJson($result);
	}
	
	public function cleanorphanusersitems(): void
	{
		if (!Session::checkToken('get') && !Session::checkToken()) {
			die(Text::_('JINVALID_TOKEN'));
		}

		$app        = Factory::getApplication();
		$collection = $app->input->getInt('collection');

		$model   = $this->getModel('Tools');
		$deleted = $model->cleanOrphanUsersitems($collection);

		$app->enqueueMessage(
			Text::sprintf('COM_COLLECTOR_TOOLS_ORPHAN_USERSITEMS_FIXED', $deleted),
			'success'
		);

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=tools&collection=' . $collection, false
		));
	}
	
	public function fixmissingactiveversions(): void
	{
		if (!Session::checkToken('get') && !Session::checkToken()) {
			die(Text::_('JINVALID_TOKEN'));
		}

		$app        = Factory::getApplication();
		$collection = $app->input->getInt('collection');

		$model = $this->getModel('Tools');
		$fixed = $model->fixItemsWithoutActiveVersion($collection);

		$app->enqueueMessage(
			Text::sprintf('COM_COLLECTOR_TOOLS_MISSING_ACTIVE_FIXED', $fixed),
			'success'
		);

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=tools&collection=' . $collection, false
		));
	}
	
	public function fixtovalidatecounts(): void
	{
		if (!Session::checkToken('get') && !Session::checkToken()) {
			die(Text::_('JINVALID_TOKEN'));
		}

		$app        = Factory::getApplication();
		$collection = $app->input->getInt('collection');

		$model = $this->getModel('Tools');
		$fixed = $model->fixToValidateCounts($collection);

		$app->enqueueMessage(
			Text::sprintf('COM_COLLECTOR_TOOLS_TOVALIDATE_FIXED', $fixed),
			'success'
		);

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=tools&collection=' . $collection, false
		));
	}
	
	public function cleanarchivedversions(): void
	{
		if (!Session::checkToken('get') && !Session::checkToken()) {
			die(Text::_('JINVALID_TOKEN'));
		}

		$app        = Factory::getApplication();
		$collection = $app->input->getInt('collection');

		$model   = $this->getModel('Tools');
		$deleted = $model->cleanArchivedVersions($collection);

		$app->enqueueMessage(
			Text::sprintf('COM_COLLECTOR_TOOLS_ARCHIVED_FIXED', $deleted),
			'success'
		);

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=tools&collection=' . $collection, false
		));
	}
	
	public function cleanstaleimportsessions(): void
	{
		if (!Session::checkToken('get') && !Session::checkToken()) {
			die(Text::_('JINVALID_TOKEN'));
		}

		$app        = Factory::getApplication();
		$collection = $app->input->getInt('collection');

		$model   = $this->getModel('Tools');
		$deleted = $model->cleanStaleImportSessions($collection, 24);

		$app->enqueueMessage(
			Text::sprintf('COM_COLLECTOR_TOOLS_IMPORT_SESSIONS_FIXED', $deleted),
			'success'
		);

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=tools&collection=' . $collection, false
		));
	}
	
	public function cleanorphanversions(): void
	{
		if (!Session::checkToken('get') && !Session::checkToken()) {
			die(Text::_('JINVALID_TOKEN'));
		}

		$app        = Factory::getApplication();
		$collection = $app->input->getInt('collection');

		$model   = $this->getModel('Tools');
		$deleted = $model->cleanOrphanVersions($collection);

		$app->enqueueMessage(
			Text::sprintf('COM_COLLECTOR_TOOLS_ORPHAN_VERSIONS_FIXED', $deleted),
			'success'
		);

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=tools&collection=' . $collection, false
		));
	}

	private function sendJson($data, string $message = '', bool $isError = false): void
	{
		$app = Factory::getApplication();
		$app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$app->sendHeaders();
		echo new JsonResponse($data, $message, $isError);
		$app->close();
	}
	
	public function export(): void
	{
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app        = Factory::getApplication();
		$collection = $app->input->getInt('collection', 0);
		$mode       = $app->input->getString('export_mode', 'collection');

		$model    = $this->getModel('Tools');
		$sql      = $model->exportSql($collection, $mode);
		$filename = $model->saveExportFile($sql, $mode, $collection);

		$app->enqueueMessage(
			Text::sprintf('COM_COLLECTOR_TOOLS_EXPORT_SUCCESS', $filename),
			'success'
		);

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=tools&collection=' . $collection, false
		));
	}

	public function downloadexport(): void
	{
		Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

		$app      = Factory::getApplication();
		$filename = basename($app->input->getString('file', ''));
		$path     = \Steevo\Component\Collector\Administrator\Model\ToolsModel::EXPORT_DIR . $filename;

		if (!is_file($path) || pathinfo($path, PATHINFO_EXTENSION) !== 'sql') {
			$app->enqueueMessage(Text::_('COM_COLLECTOR_TOOLS_EXPORT_FILE_NOT_FOUND'), 'error');
			$this->setRedirect(Route::_('index.php?option=com_collector&view=tools', false));
			return;
		}

		$app->setHeader('Content-Type',        'application/octet-stream',                true);
		$app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true);
		$app->setHeader('Content-Length',      filesize($path),                            true);
		$app->setHeader('Cache-Control',       'no-cache, must-revalidate',               true);
		$app->sendHeaders();

		readfile($path);
		$app->close();
	}

	public function deleteexport(): void
	{
		Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

		$app        = Factory::getApplication();
		$filename   = $app->input->getString('file', '');
		$collection = $app->input->getInt('collection', 0);

		$model = $this->getModel('Tools');

		if ($model->deleteExportFile($filename)) {
			$app->enqueueMessage(Text::_('COM_COLLECTOR_TOOLS_EXPORT_DELETED'), 'success');
		} else {
			$app->enqueueMessage(Text::_('COM_COLLECTOR_TOOLS_EXPORT_DELETE_ERROR'), 'error');
		}

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=tools&collection=' . $collection, false
		));
	}

	public function import(): void
	{
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app        = Factory::getApplication();
		$collection = $app->input->getInt('collection', 0);
		$importMode = $app->input->getString('import_mode', 'merge');
		$file       = $app->input->files->get('sql_file');

		if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
			$app->enqueueMessage(Text::_('COM_COLLECTOR_TOOLS_IMPORT_NO_FILE'), 'error');
			$this->setRedirect(Route::_(
				'index.php?option=com_collector&view=tools&collection=' . $collection, false
			));
			return;
		}

		// Vérifier l'extension
		if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'sql') {
			$app->enqueueMessage(Text::_('COM_COLLECTOR_TOOLS_IMPORT_INVALID_FILE'), 'error');
			$this->setRedirect(Route::_(
				'index.php?option=com_collector&view=tools&collection=' . $collection, false
			));
			return;
		}

		$model  = $this->getModel('Tools');
		$result = $model->importSql(
			file_get_contents($file['tmp_name']),
			$importMode
		);

		if ($result['success']) {
			$app->enqueueMessage(
				Text::sprintf('COM_COLLECTOR_TOOLS_IMPORT_SUCCESS', $result['queries']),
				'success'
			);
		} else {
			$app->enqueueMessage(
				Text::sprintf('COM_COLLECTOR_TOOLS_IMPORT_ERROR', $result['error']),
				'error'
			);
		}

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=tools&collection=' . $collection, false
		));
	}
	
	public function importfromserver(): void
	{
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app        = Factory::getApplication();
		$filename   = basename($app->input->getString('server_file', ''));
		$importMode = $app->input->getString('import_mode', 'merge');
		$collection = $app->input->getInt('collection', 0);

		$path = \Steevo\Component\Collector\Administrator\Model\ToolsModel::EXPORT_DIR . $filename;

		if (!is_file($path) || pathinfo($path, PATHINFO_EXTENSION) !== 'sql') {
			$app->enqueueMessage(Text::_('COM_COLLECTOR_TOOLS_EXPORT_FILE_NOT_FOUND'), 'error');
			$this->setRedirect(Route::_(
				'index.php?option=com_collector&view=tools&collection=' . $collection, false
			));
			return;
		}

		$model  = $this->getModel('Tools');
		$result = $model->importSql(file_get_contents($path), $importMode);

		if ($result['success']) {
			$app->enqueueMessage(
				Text::sprintf('COM_COLLECTOR_TOOLS_IMPORT_SUCCESS', $result['queries']),
				'success'
			);
		} else {
			$app->enqueueMessage(
				Text::sprintf('COM_COLLECTOR_TOOLS_IMPORT_ERROR', $result['error']),
				'error'
			);
		}

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=tools&collection=' . $collection, false
		));
	}
	
	public function migratecollectionfolders(): void
	{
		if (!Session::checkToken('get') && !Session::checkToken()) {
			die(Text::_('JINVALID_TOKEN'));
		}

		$app   = Factory::getApplication();
		$model = new \Steevo\Component\Collector\Administrator\Model\FilesModel();

		$report = $model->migrateCollectionFolders();

		$success = count(array_filter($report, fn($r) => $r->status === 'success'));
		$errors  = count(array_filter($report, fn($r) => $r->status === 'error'));

		if ($errors > 0) {
			$app->enqueueMessage(
				Text::sprintf('COM_COLLECTOR_TOOLS_MIGRATE_FOLDERS_PARTIAL', $success, $errors),
				'warning'
			);
		} else {
			$app->enqueueMessage(
				Text::sprintf('COM_COLLECTOR_TOOLS_MIGRATE_FOLDERS_SUCCESS', $success),
				'success'
			);
		}

		// Stocker le rapport détaillé en session pour affichage
		$app->getSession()->set('collector.migrate_report', $report);

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=tools&active_tab=%23tab-files', false
		));
	}
}