<?php
namespace Steevo\Component\Collector\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Steevo\Component\Collector\Administrator\Model\VersionscompareModel;

class ItemversionsController extends BaseController
{
    protected $default_view = 'itemversions';

    public function getModel($name = 'Itemversions', $prefix = 'Site',
                             $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    // ── Utilitaire ────────────────────────────────────────────────────────────

    private function getRedirect(): string
    {
        $app        = Factory::getApplication();
        $collection = $app->input->getInt('collection');
        $item       = $app->input->getInt('item');

        return Route::_(
            'index.php?option=com_collector&view=itemversions'
            . '&collection=' . $collection
            . '&item=' . $item,
            false
        );
    }

    private function checkAccess(): bool
    {
        $app   = Factory::getApplication();
        $user  = $app->getIdentity();
        $model = $this->getModel();
        $model->setState('collection.id', $app->input->getInt('collection'));
        $model->setState('item.id',       $app->input->getInt('item'));

        $collection = $model->getCollection();
        if (!$collection) {
            $app->enqueueMessage(Text::_('COM_COLLECTOR_ERROR_COLLECTION_NOT_FOUND'), 'error');
            return false;
        }

        // Seuls les utilisateurs pouvant éditer peuvent valider/refuser
        if (!$user->authorise('core.edit', 'com_collector.collection.' . $collection->id)
            && !$user->authorise('core.edit', 'com_collector')) {
            $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            return false;
        }

        return true;
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function validate(): void
    {
        if (!Session::checkToken() && !Session::checkToken('get')) {
            Factory::getApplication()->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect($this->getRedirect());
            return;
        }

        if (!$this->checkAccess()) {
            $this->setRedirect($this->getRedirect());
            return;
        }

        $app       = Factory::getApplication();
        $versionId = $app->input->getInt('version_id');
        $model     = $this->getModel();
        $model->setState('collection.id', $app->input->getInt('collection'));
        $model->setState('item.id',       $app->input->getInt('item'));

        if ($model->validate($versionId)) {
            $app->enqueueMessage(Text::_('COM_COLLECTOR_ITEM_HISTORY_VALIDATED'), 'success');
        } else {
            $app->enqueueMessage($model->getError(), 'error');
        }

        $this->setRedirect($this->getRedirect());
    }

    // ── Refus ─────────────────────────────────────────────────────────────────

    public function refuse(): void
    {
        if (!Session::checkToken() && !Session::checkToken('get')) {
            Factory::getApplication()->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect($this->getRedirect());
            return;
        }

        if (!$this->checkAccess()) {
            $this->setRedirect($this->getRedirect());
            return;
        }

        $app        = Factory::getApplication();
        $versionId  = $app->input->getInt('version_id');
		$refuseNote = $app->input->getString('refuse_note', '');
        $model      = $this->getModel();
        $model->setState('collection.id', $app->input->getInt('collection'));
        $model->setState('item.id',       $app->input->getInt('item'));

        if ($model->refuse($versionId, $refuseNote)) {
            $app->enqueueMessage(Text::_('COM_COLLECTOR_ITEM_HISTORY_REFUSED'), 'success');
        } else {
            $app->enqueueMessage($model->getError(), 'error');
        }

        $this->setRedirect($this->getRedirect());
    }

    // ── Restauration ──────────────────────────────────────────────────────────

    public function restore(): void
    {
        if (!Session::checkToken() && !Session::checkToken('get')) {
            Factory::getApplication()->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect($this->getRedirect());
            return;
        }

        if (!$this->checkAccess()) {
            $this->setRedirect($this->getRedirect());
            return;
        }

        $app       = Factory::getApplication();
        $versionId = $app->input->getInt('version_id');
        $model     = $this->getModel();
        $model->setState('collection.id', $app->input->getInt('collection'));
        $model->setState('item.id',       $app->input->getInt('item'));

        if ($model->restore($versionId)) {
            $app->enqueueMessage(Text::_('COM_COLLECTOR_ITEM_HISTORY_RESTORED'), 'success');
        } else {
            $app->enqueueMessage($model->getError(), 'error');
        }

        $this->setRedirect($this->getRedirect());
    }
	
	public function validatefields(): void
	{
		if (!Session::checkToken() && !Session::checkToken('get')) {
			echo json_encode(['success' => false, 'message' => Text::_('JINVALID_TOKEN')]);
			Factory::getApplication()->close();
		}

		$app        = Factory::getApplication();
		$input      = $app->input;
		$collection = $input->getInt('collection');
		$item       = $input->getInt('item');
		$v2         = $input->getInt('v2');
		$fields     = $input->get('fields', [], 'ARRAY');
		$accepted   = $input->getInt('accepted_count', 0);

		// Vérifier les droits
		$user = $app->getIdentity();
		if (!$user->authorise('core.edit', 'com_collector.collection.' . $collection)
			&& !$user->authorise('core.edit', 'com_collector')) {
			echo json_encode(['success' => false, 'message' => Text::_('JERROR_ALERTNOAUTHOR')]);
			$app->close();
		}

		$model = new VersionscompareModel();
		$result = $model->validateVersion($collection, $item, $v2, $fields, $accepted);

		$redirectUrl = Route::_(
			'index.php?option=com_collector&view=itemversions'
			. '&collection=' . $collection . '&item=' . $item, false
		);

		$app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$app->sendHeaders();

		echo json_encode([
			'success'     => $result,
			'message'     => $result ? '' : $model->getError(),
			'redirectUrl' => $redirectUrl,
		]);
		$app->close();
	}
}