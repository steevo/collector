<?php
namespace Steevo\Component\Collector\Site\View\Usercollection;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Router\Route;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    protected $collection;
    protected $fields;
	protected $params = null;
    protected $userslists;
    protected $targetUser;
    protected $targetUserName;
    protected $currentType;
    protected $matchingUsers;
    protected $isOwnProfile;

    public function display($tpl = null): void
    {
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
		$this->params	= $app->getParams();
        /** @var \Steevo\Component\Collector\Site\Model\UsercollectionModel $model */
        $model = $this->getModel();

        $this->collection = $this->get('Collection');
        if (!$this->collection) {
            $app->enqueueMessage(Text::_('COM_COLLECTOR_ERROR_COLLECTION_NOT_FOUND'), 'error');
            return;
        }

        $this->currentType  = $app->input->getInt('type', 2);
        $this->targetUser   = $app->input->getInt('user', 0) ?: $user->id;
        $this->isOwnProfile = ($this->targetUser === $user->id);

        // Nom de l'utilisateur cible
        if (!$this->isOwnProfile) {
            $db = Factory::getDbo();
            $db->setQuery(
                $db->getQuery(true)
                   ->select('name')
                   ->from($db->quoteName('#__users'))
                   ->where($db->quoteName('id') . ' = ' . $this->targetUser)
            );
            $this->targetUserName = $db->loadResult() ?? '';
        }

        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->fields        = $this->get('Fields');
        $this->userslists    = $this->get('Userslists');

		// Récupérer l'ID de la userslist courante (type + collection)
		foreach ($this->userslists as $ul) {
			if ((int) $ul->type === $this->currentType) {
				$this->userslistId = (int) $ul->id;
				break;
			}
		}
		
		$typeRaw           = $app->input->getString('type', '2');
		$this->currentType = $typeRaw === 'matching' ? 'matching' : (int) $typeRaw;
		$this->showMatching = ($typeRaw === 'matching');

		$this->currentUserlist = null;
		if ($this->isOwnProfile && !$this->showMatching) {
			$this->currentUserlist = $this->get('CurrentUserlist');
		}

        // Charger les matchs pour type 0 ET type 1
		$this->matchingUsers = [];

		$model->setState('filter.type', 0);
		$matchType0 = $model->getMatchingUsers();

		$model->setState('filter.type', 1);
		$matchType1 = $model->getMatchingUsers();

		// Fusionner et grouper par utilisateur
		$merged = [];
		foreach ($matchType0 as $match) {
			$merged[$match->match_user_id] = [
				'user_id'   => $match->match_user_id,
				'user_name' => $match->match_user_name,
				'want'      => (int) $match->match_count,  // ils ont en échange ce que je cherche
				'have'      => 0,
			];
		}
		foreach ($matchType1 as $match) {
			if (isset($merged[$match->match_user_id])) {
				$merged[$match->match_user_id]['have'] = (int) $match->match_count;
			} else {
				$merged[$match->match_user_id] = [
					'user_id'   => $match->match_user_id,
					'user_name' => $match->match_user_name,
					'want'      => 0,
					'have'      => (int) $match->match_count,  // ils cherchent ce que j'ai en échange
				];
			}
		}
		$this->matchingUsers = array_values($merged);

		// Remettre le type courant
		$model->setState('filter.type', $this->currentType);

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->_prepareDocument();
        parent::display($tpl);
    }

    protected function _prepareDocument(): void
    {
        $app      = Factory::getApplication();
        $pathway  = $app->getPathway();
        $typeNames = [
            0 => Text::_('COM_COLLECTOR_USERLIST_TYPE_0'),
            1 => Text::_('COM_COLLECTOR_USERLIST_TYPE_1'),
            2 => Text::_('COM_COLLECTOR_USERLIST_TYPE_2'),
        ];

        $collectionName = $this->collection->name ?? '';
        $typeName       = $typeNames[$this->currentType] ?? '';

        // Fil d'Ariane
        $pathway->addItem($collectionName,
            Route::_('index.php?option=com_collector&view=collection&id=' . $this->collection->id)
        );
        if (!$this->isOwnProfile && $this->targetUserName) {
            $pathway->addItem($this->targetUserName, '');
        }
        $pathway->addItem($typeName, '');

        $app->getDocument()->setTitle($collectionName . ' — ' . $typeName);
    }
}