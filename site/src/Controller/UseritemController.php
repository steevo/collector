<?php
namespace Steevo\Component\Collector\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

class UseritemController extends BaseController
{
    public function getModel($name = 'Useritem', $prefix = 'Site',
                             $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Ajoute un item dans une liste utilisateur.
     */
    public function add(): void
    {
        if (!Session::checkToken('request')) {
            $this->sendJson(false, Text::_('JINVALID_TOKEN'));
            return;
        }

        $app        = Factory::getApplication();
        $user       = $app->getIdentity();

        if (!$user->id) {
            $this->sendJson(false, Text::_('JERROR_ALERTNOAUTHOR'));
            return;
        }

        $itemId     = $app->input->getInt('item');
        $userslistId = $app->input->getInt('userslist');
        $note       = $app->input->getString('note', '');

        $model = $this->getModel();
        $model->setState('user.id', $user->id);

        $result = $model->add($userslistId, $itemId, $note);

        if ($result) {
            $this->sendJson(true, Text::_('COM_COLLECTOR_USERITEM_ADDED'), [
                'useritem_id' => $result,
                'in_list'     => true,
            ]);
        } else {
            $this->sendJson(false, $model->getError());
        }
    }

    /**
     * Retire un item d'une liste utilisateur.
     */
    public function remove(): void
    {
        if (!Session::checkToken('request')) {
            $this->sendJson(false, Text::_('JINVALID_TOKEN'));
            return;
        }

        $app         = Factory::getApplication();
        $user        = $app->getIdentity();

        if (!$user->id) {
            $this->sendJson(false, Text::_('JERROR_ALERTNOAUTHOR'));
            return;
        }

        $itemId      = $app->input->getInt('item');
        $userslistId = $app->input->getInt('userslist');

        $model = $this->getModel();
        $model->setState('user.id', $user->id);

        $result = $model->remove($userslistId, $itemId);

        if ($result) {
            $this->sendJson(true, Text::_('COM_COLLECTOR_USERITEM_REMOVED'), [
                'in_list' => false,
            ]);
        } else {
            $this->sendJson(false, $model->getError());
        }
    }

    /**
     * Met à jour la note d'un item dans une liste utilisateur.
     */
    public function updatenote(): void
    {
        if (!Session::checkToken('request')) {
            $this->sendJson(false, Text::_('JINVALID_TOKEN'));
            return;
        }

        $app         = Factory::getApplication();
        $user        = $app->getIdentity();

        if (!$user->id) {
            $this->sendJson(false, Text::_('JERROR_ALERTNOAUTHOR'));
            return;
        }

        $itemId      = $app->input->getInt('item');
        $userslistId = $app->input->getInt('userslist');
        $note        = $app->input->getString('note', '');

        $model = $this->getModel();
        $model->setState('user.id', $user->id);

        $result = $model->updateNote($userslistId, $itemId, $note);

        if ($result) {
            $this->sendJson(true, Text::_('COM_COLLECTOR_USERITEM_NOTE_UPDATED'));
        } else {
            $this->sendJson(false, $model->getError());
        }
    }

    /**
     * Retourne l'état des listes pour un item donné.
     */
    public function status(): void
    {
        $app         = Factory::getApplication();
        $user        = $app->getIdentity();
        $itemId      = $app->input->getInt('item');
        $collection  = $app->input->getInt('collection');

        if (!$user->id) {
            $this->sendJson(true, '', ['lists' => []]);
            return;
        }

        $model = $this->getModel();
        $model->setState('user.id', $user->id);

        $lists = $model->getItemStatus($collection, $itemId);
        $this->sendJson(true, '', ['lists' => $lists]);
    }
	
	public function setaccess(): void
	{
		if (!Session::checkToken('request')) {
			$this->sendJson(false, Text::_('JINVALID_TOKEN'));
			return;
		}

		$app        = Factory::getApplication();
		$user       = $app->getIdentity();
		$userlistId = $app->input->getInt('userlist');
		$access     = $app->input->getInt('access');

		// Vérifier que la liste appartient bien à l'utilisateur
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$db->setQuery(
			$db->getQuery(true)
			   ->select('uls.type')
			   ->from($db->quoteName('#__collector_userlist', 'ul'))
			   ->join('INNER', $db->quoteName('#__collector_userslists', 'uls') . ' ON uls.id = ul.userslist')
			   ->where($db->quoteName('ul.id')   . ' = ' . $userlistId)
			   ->where($db->quoteName('ul.user') . ' = ' . (int) $user->id)
		);
		$listType = $db->loadResult();

		if ($listType === null) {
			$this->sendJson(false, Text::_('JERROR_ALERTNOAUTHOR'));
			return;
		}

		// Valeurs : 0 privée, 1 matchs uniquement, 2 publique.
		// Une collection (type 2) n'est que privée ou publique : les matchs ne concernent
		// que les listes de souhaits et d'échanges.
		$allowed = (int) $listType === 2 ? [0, 2] : [0, 1, 2];

		if (!in_array($access, $allowed, true)) {
			$this->sendJson(false, Text::_('COM_COLLECTOR_USERLIST_INVALID_ACCESS'));
			return;
		}

		$db->setQuery(
			$db->getQuery(true)
			   ->update($db->quoteName('#__collector_userlist'))
			   ->set($db->quoteName('access') . ' = ' . $access)
			   ->where($db->quoteName('id')   . ' = ' . $userlistId)
		)->execute();

		$this->sendJson(true, Text::_('COM_COLLECTOR_USERLIST_ACCESS_UPDATED'));
	}

    private function sendJson(bool $success, string $message = '', array $data = []): void
	{
		$app = Factory::getApplication();
		$app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$app->sendHeaders();
		echo new JsonResponse(
			$success ? array_merge(['message' => $message], $data) : null,
			$success ? '' : $message,
			!$success
		);
		$app->close();
	}
}