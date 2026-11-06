<?php
namespace Steevo\Component\Collector\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;

class ImporthistoryController extends AdminController
{
    protected $text_prefix = 'COM_COLLECTOR_IMPORTHISTORY';
    protected $view_list   = 'importhistory';

    public function getModel(
        $name   = 'Importhistory',
        $prefix = 'Administrator',
        $config = ['ignore_request' => true]
    ) {
        return parent::getModel($name, $prefix, $config);
    }

    // ── Détail ────────────────────────────────────────────────────────────────

    public function detail(): void
    {
        $this->setRedirect(Route::_(
            'index.php?option=com_collector&view=importhistory&layout=detail'
            . '&import_id=' . $this->input->getInt('import_id')
            . '&collection=' . $this->input->getInt('collection'),
            false
        ));
    }

    // ── Vérification des modifications (AJAX) ─────────────────────────────────

    public function checkmodified(): void
    {
        $this->checkToken('request');

        $app        = Factory::getApplication();
        $importId   = $this->input->getInt('import_id');
        $collection = $this->input->getInt('collection');
        $model      = $this->getModel();

        $modifiedIds = $model->getModifiedItemIds($importId, $collection);

        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->sendHeaders();
        echo new JsonResponse([
            'modified_ids' => $modifiedIds,
            'count'        => count($modifiedIds),
        ]);
        $app->close();
    }

    // ── Rollback ──────────────────────────────────────────────────────────────

    public function rollback(): void
    {
        $this->checkToken();

        $importId   = $this->input->getInt('import_id');
        $collection = $this->input->getInt('collection');
        $itemIds    = array_map('intval', array_filter(
            (array) $this->input->get('item_ids', [], 'ARRAY')
        ));
        $force      = (bool) $this->input->getInt('force_modified', 0);
        $deleteMode = ComponentHelper::getParams('com_collector')
                        ->get('import_rollback_delete_mode', 'trash');

        $model  = $this->getModel();
        $result = $model->rollback($importId, $collection, $itemIds, $force, $deleteMode);

        if ($result['success']) {
            $msg = Text::sprintf('COM_COLLECTOR_IMPORTHISTORY_ROLLBACK_SUCCESS', $result['rolled_back']);
            if ($result['skipped'] > 0) {
                $msg .= ' ' . Text::sprintf(
                    'COM_COLLECTOR_IMPORTHISTORY_ROLLBACK_SKIPPED',
                    $result['skipped']
                );
            }
            $this->setMessage($msg);
        } else {
            $this->setMessage(
                Text::_('COM_COLLECTOR_IMPORTHISTORY_ROLLBACK_ERROR')
                . ' ' . implode(', ', $result['errors']),
                'error'
            );
        }

        $this->setRedirect(Route::_(
            'index.php?option=com_collector&view=importhistory&collection=' . $collection,
            false
        ));
    }

    // ── Suppression de l'entrée historique ────────────────────────────────────

    public function delete(): void
    {
        $this->checkToken('get');

        $importId   = $this->input->getInt('import_id');
        $collection = $this->input->getInt('collection');
        $model      = $this->getModel();

        if ($model->deleteImportHistory($importId)) {
            $this->setMessage(Text::_('COM_COLLECTOR_IMPORTHISTORY_DELETED'));
        } else {
            $this->setMessage(Text::_('COM_COLLECTOR_IMPORTHISTORY_DELETE_ERROR'), 'error');
        }

        $this->setRedirect(Route::_(
            'index.php?option=com_collector&view=importhistory&collection=' . $collection,
            false
        ));
    }
}