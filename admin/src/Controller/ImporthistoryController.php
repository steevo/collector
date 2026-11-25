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
            if (!empty($result['values_removed'])) {
                $msg .= ' ' . Text::sprintf(
                    'COM_COLLECTOR_IMPORTHISTORY_ROLLBACK_VALUES_REMOVED',
                    count($result['values_removed']),
                    implode(', ', $result['values_removed'])
                );
            }
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

    // ── Objets absents du fichier : mise à la corbeille ───────────────────────

    public function trashmissing(): void
    {
        $this->checkToken();

        $importId   = $this->input->getInt('import_id');
        $collection = $this->input->getInt('collection');
        $itemIds    = (array) $this->input->get('missing_ids', [], 'ARRAY');
        $model      = $this->getModel();
        $import     = $model->getImport($importId);

        if (!Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_collector')) {
            $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'error');
        } elseif (!$import || (int) $import->collection !== $collection || !$itemIds) {
            $this->setMessage(Text::_('COM_COLLECTOR_IMPORTHISTORY_MISSING_SELECT_FIRST'), 'warning');
        } else {
            $count = $model->trashMissing($import, $collection, $itemIds);
            $this->setMessage(Text::sprintf('COM_COLLECTOR_IMPORTHISTORY_MISSING_TRASHED', $count));
        }

        $this->setRedirect(Route::_(
            'index.php?option=com_collector&view=importhistory&layout=detail'
            . '&import_id=' . $importId . '&collection=' . $collection, false
        ) . '#missing-items');
    }

    // ── Objet non retrouvé : fusion avec l'objet créé par erreur ──────────────

    public function mergeduplicate(): void
    {
        $this->checkToken();

        $importId   = $this->input->getInt('import_id');
        $collection = $this->input->getInt('collection');
        $targetId   = $this->input->getInt('target_id');
        $sourceId   = $this->input->getInt('source_id');
        $cols       = array_map('strval', (array) $this->input->get('take', [], 'ARRAY'));
        $model      = $this->getModel();
        $import     = $model->getImport($importId);
        $user       = Factory::getApplication()->getIdentity();

        if (!$user->authorise('core.edit', 'com_collector') || !$user->authorise('core.edit.state', 'com_collector')) {
            $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
        } elseif (!$import || (int) $import->collection !== $collection || !$targetId || !$sourceId) {
            $this->setMessage(Text::_('COM_COLLECTOR_IMPORTHISTORY_DUP_NOT_ALLOWED'), 'error');
        } elseif (($error = $model->mergeDuplicate($import, $collection, $targetId, $sourceId, $cols)) !== null) {
            $this->setMessage(Text::sprintf('COM_COLLECTOR_IMPORTHISTORY_DUP_ERROR', $error), 'error');
        } else {
            $this->setMessage(Text::sprintf('COM_COLLECTOR_IMPORTHISTORY_DUP_MERGED', $targetId, $sourceId));
        }

        $this->setRedirect(Route::_(
            'index.php?option=com_collector&view=importhistory&layout=detail'
            . '&import_id=' . $importId . '&collection=' . $collection, false
        ) . '#missing-items');
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