<?php
namespace Steevo\Component\Collector\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

class FilesController extends BaseController
{
    protected $default_view = 'files';

    private function sendJson($data, string $message = '', bool $error = false): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->sendHeaders();
        echo new JsonResponse($data, $message, $error);
        $app->close();
    }

    private function getContextAndId(): array
    {
        $app     = Factory::getApplication();
        $context = $app->input->getString('context', 'collection');
        $id      = $app->input->getInt('id', 0);
        return [$context, $id];
    }

    /**
     * Parcourir un dossier (AJAX)
     */
    public function browse(): void
    {
        if (!Session::checkToken('request')) {
            $this->sendJson(null, Text::_('JINVALID_TOKEN'), true);
            return;
        }

        $app     = Factory::getApplication();
        [$context, $id] = $this->getContextAndId();
        $subpath = $app->input->getString('subpath', '');

        $model = $this->getModel('Files');
        $model->ensureRootExists($context, $id);
        $result = $model->browse($context, $id, $subpath);

        $this->sendJson($result);
    }

    /**
     * Compter les références d'un fichier (AJAX)
     */
    public function references(): void
    {
        if (!Session::checkToken('request')) {
            $this->sendJson(null, Text::_('JINVALID_TOKEN'), true);
            return;
        }

        $app     = Factory::getApplication();
        $relPath = $app->input->getString('rel_path', '');

        $model = $this->getModel('Files');
        $refs  = $model->getReferences($relPath);

        $this->sendJson(['count' => count($refs), 'items' => $refs]);
    }

    /**
     * Déplacer un fichier (AJAX)
     */
    public function move(): void
    {
        if (!Session::checkToken('request')) {
            $this->sendJson(null, Text::_('JINVALID_TOKEN'), true);
            return;
        }

        $app      = Factory::getApplication();
        [$context, $id] = $this->getContextAndId();
        $from     = $app->input->getString('from', '');
        $to       = $app->input->getString('to', '');

        $model  = $this->getModel('Files');
        $result = $model->moveFile($context, $id, $from, $to);

        if ($result['moved']) {
            $this->sendJson($result, Text::sprintf('COM_COLLECTOR_FILES_MOVED', $result['references_updated']));
        } else {
            $this->sendJson(null, Text::_('COM_COLLECTOR_FILES_MOVE_ERROR'), true);
        }
    }

    /**
     * Renommer un fichier (AJAX)
     */
    public function rename(): void
    {
        if (!Session::checkToken('request')) {
            $this->sendJson(null, Text::_('JINVALID_TOKEN'), true);
            return;
        }

        $app     = Factory::getApplication();
        [$context, $id] = $this->getContextAndId();
        $subpath = $app->input->getString('subpath', '');
        $newName = $app->input->getString('new_name', '');

        $model  = $this->getModel('Files');
        $result = $model->renameFile($context, $id, $subpath, $newName);

        if ($result['renamed']) {
            $this->sendJson($result, Text::sprintf('COM_COLLECTOR_FILES_RENAMED', $result['references_updated']));
        } else {
            $this->sendJson(null, Text::_('COM_COLLECTOR_FILES_RENAME_ERROR'), true);
        }
    }

    /**
     * Supprimer un fichier (AJAX)
     */
    public function delete(): void
    {
        if (!Session::checkToken('request')) {
            $this->sendJson(null, Text::_('JINVALID_TOKEN'), true);
            return;
        }

        $app     = Factory::getApplication();
        [$context, $id] = $this->getContextAndId();
        $subpath = $app->input->getString('subpath', '');

        $model  = $this->getModel('Files');
        $result = $model->deleteFile($context, $id, $subpath);

        if ($result['deleted']) {
            $this->sendJson($result, Text::_('COM_COLLECTOR_FILES_DELETED'));
        } else {
            $this->sendJson(null, Text::_('COM_COLLECTOR_FILES_DELETE_ERROR'), true);
        }
    }

    /**
     * Créer un dossier (AJAX)
     */
    public function mkdir(): void
    {
        if (!Session::checkToken('request')) {
            $this->sendJson(null, Text::_('JINVALID_TOKEN'), true);
            return;
        }

        $app     = Factory::getApplication();
        [$context, $id] = $this->getContextAndId();
        $subpath = $app->input->getString('subpath', '');
        $name    = $app->input->getString('name', '');

        $model  = $this->getModel('Files');
        $result = $model->mkdir($context, $id, $subpath, $name);

        if ($result) {
            $this->sendJson(['name' => $name], Text::_('COM_COLLECTOR_FILES_DIR_CREATED'));
        } else {
            $this->sendJson(null, Text::_('COM_COLLECTOR_FILES_DIR_ERROR'), true);
        }
    }

    /**
     * Uploader un fichier (AJAX)
     */
    public function upload(): void
    {
        if (!Session::checkToken('request')) {
            $this->sendJson(null, Text::_('JINVALID_TOKEN'), true);
            return;
        }

        $app     = Factory::getApplication();
        [$context, $id] = $this->getContextAndId();
        $subpath = $app->input->getString('subpath', '');
        $file    = $app->input->files->get('file');

        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->sendJson(null, Text::_('COM_COLLECTOR_FILES_UPLOAD_ERROR'), true);
            return;
        }

        $model  = $this->getModel('Files');
        $result = $model->upload($context, $id, $subpath, $file);

        if ($result['uploaded']) {
            $this->sendJson($result, Text::sprintf('COM_COLLECTOR_FILES_UPLOADED', $result['filename']));
        } else {
            $this->sendJson(null, Text::_('COM_COLLECTOR_FILES_UPLOAD_ERROR'), true);
        }
    }
}