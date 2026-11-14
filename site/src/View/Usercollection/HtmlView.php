<?php
namespace Steevo\Component\Collector\Site\View\Usercollection;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Steevo\Component\Collector\Administrator\Helper\CollectorTemplateHelper;
use Steevo\Component\Collector\Administrator\Helper\ContactHelper;

/**
 * Listes d'un membre pour une collection : sa collection, ses souhaits, ses échanges,
 * les membres avec qui échanger (mes listes) et les objets en correspondance avec un membre.
 *
 * Onglets (paramètre « type ») : 2 collection, 0 souhaits, 1 échanges, « matching » membres
 * avec qui échanger (mes listes), « matches » objets en correspondance avec un autre membre.
 */
class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    protected $collection;
    protected $fields;
	protected $params = null;
    protected $userslists;
    protected $userslistId = 0;
    protected $currentUserlist;
    protected $targetUser;
    protected $targetUserName;
    protected $currentType;
    protected $matchingUsers = [];
    protected $isOwnProfile;
    protected $showMatching = false;

    /** Objets en correspondance avec le membre consulté */
    protected $showMatches = false;
    protected $matchesWant = [];   // il a en échange ce que je cherche
    protected $matchesHave = [];   // il cherche ce que j'ai en échange
    protected $matchesCount = 0;
    protected $contacts = [];

    /** Vue de la liste : tableau (0) ou template de liste de la collection, comme la collection */
    protected $listViews = [];
    protected $listView = 0;
    protected $listTemplate = null;

    public function display($tpl = null): void
    {
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
		$this->params	= $app->getParams();
        /** @var \Steevo\Component\Collector\Site\Model\UsercollectionModel $model */
        $model = $this->getModel();

        // Les collections d'utilisateurs ne sont visibles que connecté : connexion, puis retour ici
        if ($user->guest) {
            $app->enqueueMessage(Text::_('COM_COLLECTOR_YOU_MUST_LOGIN_FIRST'), 'notice');
            $app->redirect(Route::_('index.php?option=com_users&view=login&return=' . base64_encode(Uri::getInstance()->toString()), false));

            return;
        }

        $this->collection = $this->get('Collection');
        if (!$this->collection) {
            throw new \Exception(Text::_('COM_COLLECTOR_ERROR_COLLECTION_NOT_FOUND'), 404);
        }

        // Et seulement avec le niveau d'accès de la collection
        if (!\in_array((int) $this->collection->access, $user->getAuthorisedViewLevels(), true)) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->collection->slug = $this->collection->alias
            ? $this->collection->id . ':' . $this->collection->alias
            : (string) $this->collection->id;

        $this->targetUser   = $app->input->getInt('user', 0) ?: (int) $user->id;
        $this->isOwnProfile = ($this->targetUser === (int) $user->id);

        $typeRaw            = $app->input->getString('type', '2');
        $this->showMatching = $typeRaw === 'matching' && $this->isOwnProfile;
        $this->showMatches  = $typeRaw === 'matches' && !$this->isOwnProfile;
        $this->currentType  = $this->showMatching ? 'matching' : ($this->showMatches ? 'matches' : (int) $typeRaw);

        // Nom de l'utilisateur cible
        if (!$this->isOwnProfile) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery(
                $db->getQuery(true)
                   ->select('name')
                   ->from($db->quoteName('#__users'))
                   ->where($db->quoteName('id') . ' = ' . (int) $this->targetUser)
            );
            $this->targetUserName = $db->loadResult() ?? '';
        }

        $this->state      = $this->get('State');
        $this->fields     = $this->get('Fields');
        $this->userslists = $this->get('Userslists');

        // Liste affichée (onglets collection, souhaits, échanges)
        if (\is_int($this->currentType)) {
            $this->items      = $this->get('Items');
            $this->pagination = $this->get('Pagination');

            foreach ($this->userslists as $ul) {
                if ((int) $ul->type === $this->currentType) {
                    $this->userslistId = (int) $ul->id;
                    break;
                }
            }

            if ($this->isOwnProfile) {
                $this->currentUserlist = $this->get('CurrentUserlist');
            }
        }

        if ($this->isOwnProfile) {
            $this->matchingUsers = $this->loadMatchingUsers($model);
        } else {
            // Objets en correspondance avec ce membre : sur leur onglet, ou seulement leur nombre
            $this->matchesWant  = $model->getMatchItems((int) $this->targetUser, 1);
            $this->matchesHave  = $model->getMatchItems((int) $this->targetUser, 0);
            $this->matchesCount = \count($this->matchesWant) + \count($this->matchesHave);

            if ($this->showMatches) {
                $this->contacts = ContactHelper::getAllContactInfo(
                    (int) $this->targetUser,
                    Text::sprintf('COM_COLLECTOR_USERCOLLECTION_MATCHES_SUBJECT', $this->collection->name)
                );
            }
        }

        foreach (array_merge((array) $this->items, $this->matchesWant, $this->matchesHave) as $item) {
            $item->slug = $item->alias ? $item->id . ':' . $item->alias : (string) $item->id;
        }

        $this->loadListView();

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->_prepareDocument();
        parent::display($tpl);
    }

    /**
     * Membres avec qui échanger : ils ont en échange ce que je cherche (want),
     * ils cherchent ce que j'ai en échange (have).
     */
    protected function loadMatchingUsers($model): array
    {
        $type = $model->getState('filter.type');

        $model->setState('filter.type', 0);
        $matchType0 = $model->getMatchingUsers();

        $model->setState('filter.type', 1);
        $matchType1 = $model->getMatchingUsers();

        $model->setState('filter.type', $type);

        $merged = [];

        foreach ($matchType0 as $match) {
            $merged[$match->match_user_id] = [
                'user_id'   => (int) $match->match_user_id,
                'user_name' => $match->match_user_name,
                'want'      => (int) $match->match_count,
                'have'      => 0,
            ];
        }

        foreach ($matchType1 as $match) {
            $merged[$match->match_user_id] ??= [
                'user_id'   => (int) $match->match_user_id,
                'user_name' => $match->match_user_name,
                'want'      => 0,
                'have'      => 0,
            ];
            $merged[$match->match_user_id]['have'] = (int) $match->match_count;
        }

        return array_values($merged);
    }

    /**
     * Tableau ou template de liste : celui de la collection, ou celui choisi par le visiteur
     * avec le sélecteur de vue (choix commun avec la collection).
     */
    protected function loadListView(): void
    {
        $app      = Factory::getApplication();
        $id       = (int) $this->collection->id;
        $listView = (int) ($this->collection->template_list ?? 0);

        if ($this->params->get('show_view_switch', 0)) {
            $this->listViews = CollectorTemplateHelper::getPublished($id, CollectorTemplateHelper::CLIENT_LIST);

            if ($this->listViews) {
                $choice  = (int) $app->getUserStateFromRequest('com_collector.collection.' . $id . '.view', 'vue', $listView, 'int');
                $allowed = array_merge([0], array_map(static fn ($view) => (int) $view->id, $this->listViews));

                if (\in_array($choice, $allowed, true)) {
                    $listView = $choice;
                }
            }
        }

        $this->listView     = $listView;
        $this->listTemplate = CollectorTemplateHelper::load($listView, $id, CollectorTemplateHelper::CLIENT_LIST);

        if ($this->listTemplate) {
            CollectorTemplateHelper::addCss($this->listTemplate);
        } else {
            $this->listView = 0;
        }
    }

    protected function _prepareDocument(): void
    {
        $app      = Factory::getApplication();
        $pathway  = $app->getPathway();
        $typeNames = [
            0          => Text::_('COM_COLLECTOR_USERLIST_TYPE_0'),
            1          => Text::_('COM_COLLECTOR_USERLIST_TYPE_1'),
            2          => Text::_('COM_COLLECTOR_USERLIST_TYPE_2'),
            'matching' => Text::_('COM_COLLECTOR_USERCOLLECTION_MATCHING_TAB'),
            'matches'  => Text::_('COM_COLLECTOR_USERCOLLECTION_MATCHING_TAB'),
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
