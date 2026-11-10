<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Steevo\Component\Collector\Administrator\Classes\CollectorField;
use Steevo\Component\Collector\Administrator\Helper\CollectorTemplateHelper;

/**
 * Édition d'un template d'affichage de collection.
 */
class TemplateController extends FormController
{
    /** Nombre d'éléments montrés dans l'aperçu d'une vue liste */
    protected const PREVIEW_ITEMS = 6;

    /** Nombre maximal d'objets renvoyés par la recherche d'objets */
    protected const SEARCH_ITEMS = 200;

    /**
     * Recherche d'objets d'une collection par titre, en JSON (choix d'un objet dans le module Collector).
     * Paramètres : collection, search (facultatif), id (objet déjà choisi, toujours renvoyé).
     */
    public function items(): void
    {
        $user = $this->app->getIdentity();

        if (!Session::checkToken('get')
            || !($user->authorise('core.manage', 'com_collector') || $user->authorise('core.manage', 'com_modules'))) {
            $this->app->setHeader('status', 403, true);
            $this->app->sendHeaders();
            echo json_encode(['error' => Text::_('JERROR_ALERTNOAUTHOR')]);
            $this->app->close();
        }

        $collection = $this->input->getInt('collection');
        $search     = trim($this->input->getString('search', ''));
        $selected   = $this->input->getInt('id');

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'fulltitle', 'state']))
            ->from($db->quoteName('#__collector_items'))
            ->where($db->quoteName('collection') . ' = :collection')
            ->where($db->quoteName('state') . ' IN (0, 1)')
            ->order($db->quoteName('fulltitle'))
            ->bind(':collection', $collection, ParameterType::INTEGER);

        $total = (int) $db->setQuery((clone $query)->clear('select')->clear('order')->select('COUNT(*)'))->loadResult();

        if ($search !== '') {
            if (ctype_digit($search)) {
                $id = (int) $search;
                $query->where('(' . $db->quoteName('fulltitle') . ' LIKE :search OR ' . $db->quoteName('id') . ' = :sid)')
                    ->bind(':sid', $id, ParameterType::INTEGER);
            } else {
                $query->where($db->quoteName('fulltitle') . ' LIKE :search');
            }

            $like = '%' . $db->escape($search, true) . '%';
            $query->bind(':search', $like);
        }

        $rows = $db->setQuery($query, 0, static::SEARCH_ITEMS + 1)->loadObjectList();
        $more = \count($rows) > static::SEARCH_ITEMS;
        $rows = \array_slice($rows, 0, static::SEARCH_ITEMS);

        // l'objet déjà choisi reste disponible, même hors de la recherche
        if ($selected > 0 && !\in_array($selected, array_map(static fn ($row) => (int) $row->id, $rows), true)) {
            $query = $db->getQuery(true)
                ->select($db->quoteName(['id', 'fulltitle', 'state']))
                ->from($db->quoteName('#__collector_items'))
                ->where($db->quoteName('id') . ' = :id')
                ->where($db->quoteName('collection') . ' = :collection')
                ->bind(':id', $selected, ParameterType::INTEGER)
                ->bind(':collection', $collection, ParameterType::INTEGER);

            if ($row = $db->setQuery($query)->loadObject()) {
                array_unshift($rows, $row);
            }
        }

        $items = array_map(static fn ($row) => [
            'id'        => (int) $row->id,
            'title'     => trim(strip_tags((string) $row->fulltitle)) ?: '#' . $row->id,
            'published' => (int) $row->state === 1,
        ], $rows);

        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $this->app->sendHeaders();
        echo json_encode(['items' => $items, 'more' => $more, 'total' => $total], JSON_UNESCAPED_UNICODE);
        $this->app->close();
    }

    /**
     * Aperçu du template en cours d'édition, rendu avec des éléments de la collection.
     * Renvoie une page HTML complète, affichée dans un cadre de l'éditeur.
     */
    public function preview(): void
    {
        if (!Session::checkToken('post')) {
            $this->app->setHeader('status', 403, true);
            $this->app->sendHeaders();
            echo Text::_('JINVALID_TOKEN');
            $this->app->close();
        }

        $user = $this->app->getIdentity();

        if (!$user->authorise('core.edit', 'com_collector') && !$user->authorise('core.create', 'com_collector')) {
            $this->app->setHeader('status', 403, true);
            $this->app->sendHeaders();
            echo Text::_('JERROR_ALERTNOAUTHOR');
            $this->app->close();
        }

        $data       = $this->input->post->get('jform', [], 'array');
        $collection = (int) ($data['collection'] ?? 0);
        $client     = (int) ($data['client'] ?? CollectorTemplateHelper::CLIENT_LIST) === CollectorTemplateHelper::CLIENT_ITEM
            ? CollectorTemplateHelper::CLIENT_ITEM
            : CollectorTemplateHelper::CLIENT_LIST;
        $template   = (object) [
            'id'      => (int) ($data['id'] ?? 0),
            'content' => (string) ($data['content'] ?? ''),
            'css'     => (string) ($data['css'] ?? ''),
        ];

        // les champs affichent leurs valeurs avec les textes du site
        $this->app->getLanguage()->load('com_collector', JPATH_SITE);

        try {
            $body = $this->renderPreview($template, $collection, $client, $this->input->post->getInt('item'));
        } catch (\Throwable $e) {
            $body = '<p class="collector-preview-message">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $root = Uri::root();
        $css  = str_ireplace('</style', '<\/style', $template->css);

        $this->app->setHeader('Content-Type', 'text/html; charset=utf-8', true);
        $this->app->sendHeaders();

        echo '<!doctype html><html><head><meta charset="utf-8">'
            . '<base href="' . htmlspecialchars($root, ENT_QUOTES, 'UTF-8') . '">'
            . '<link rel="stylesheet" href="' . htmlspecialchars($root, ENT_QUOTES, 'UTF-8') . 'media/vendor/bootstrap/css/bootstrap.min.css">'
            . '<link rel="stylesheet" href="' . htmlspecialchars($root, ENT_QUOTES, 'UTF-8') . 'media/com_collector/css/theme.css">'
            . '<style>body{margin:0;padding:1rem;background:#fff;color:#212529}'
            . '.collector-preview-message{color:#6c757d;font-style:italic;margin:0}'
            // aperçu seulement : les liens et contrôles ne doivent pas ouvrir une autre page dans le cadre
            . 'a,area,button,input,select,textarea,label,summary{pointer-events:none!important;cursor:default!important}</style>'
            . ($css !== '' ? '<style>' . $css . '</style>' : '')
            . '</head><body>' . $body . '</body></html>';

        $this->app->close();
    }

    /**
     * Rendu du template avec les champs et quelques éléments publiés de la collection.
     */
    protected function renderPreview(object $template, int $collection, int $client, int $itemId): string
    {
        if (trim($template->content) === '') {
            return '<p class="collector-preview-message">' . Text::_('COM_COLLECTOR_TEMPLATE_PREVIEW_EMPTY') . '</p>';
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // champs publiés de la collection, comme sur le site
        $query = $db->getQuery(true)
            ->select('f.*, t.type AS type')
            ->from($db->quoteName('#__collector_fields', 'f'))
            ->join('LEFT', $db->quoteName('#__collector_fields_type', 't') . ' ON t.id = f.type')
            ->where('f.collection = :collection')
            ->where('f.state = 1')
            ->order('f.ordering')
            ->bind(':collection', $collection, ParameterType::INTEGER);

        $fields = [];

        foreach ($db->setQuery($query)->loadObjectList() as $field) {
            $field->attribs = (new Registry($field->attribs))->toArray();
            $fields[]       = CollectorField::getInstance($collection, $field);
        }

        // éléments publiés, dans leur version en cours
        $query = $db->getQuery(true)
            ->select('i.id, i.alias, i.collection, i.hits, i.created, i.modified')
            ->from($db->quoteName('#__collector_items', 'i'))
            ->join('INNER', $db->quoteName('#__collector_items_history_' . $collection, 'h') . ' ON h.item = i.id')
            ->where('i.collection = :collection')
            ->where('i.state = 1')
            ->where('h.state = 1')
            ->order('i.ordering')
            ->bind(':collection', $collection, ParameterType::INTEGER);

        foreach ($fields as $field) {
            $field->setQuery($query);
        }

        if ($client === CollectorTemplateHelper::CLIENT_ITEM && $itemId > 0) {
            $query->where('i.id = :item')->bind(':item', $itemId, ParameterType::INTEGER);
        }

        $items = $db->setQuery($query, 0, $client === CollectorTemplateHelper::CLIENT_ITEM ? 1 : static::PREVIEW_ITEMS)->loadObjectList();

        if (!$items) {
            return '<p class="collector-preview-message">' . Text::_('COM_COLLECTOR_TEMPLATE_PREVIEW_NO_ITEM') . '</p>';
        }

        $params = ComponentHelper::getParams('com_collector');
        $class  = 'collector-template collector-template-' . ($client === CollectorTemplateHelper::CLIENT_ITEM ? 'item' : 'list')
            . ' collector-template-' . $template->id;

        $html = $client === CollectorTemplateHelper::CLIENT_ITEM
            ? CollectorTemplateHelper::renderItem($template, $fields, $items[0], $params, '#')
            : CollectorTemplateHelper::renderList($template, $fields, $items, $params, static fn () => '#');

        return '<div class="' . $class . '">' . $html . '</div>';
    }

    protected function allowAdd($data = []): bool
    {
        return $this->app->getIdentity()->authorise('core.create', 'com_collector');
    }

    protected function allowEdit($data = [], $key = 'id'): bool
    {
        return $this->app->getIdentity()->authorise('core.edit', 'com_collector');
    }

    protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id'): string
    {
        $append     = parent::getRedirectToItemAppend($recordId, $urlVar);
        $collection = $this->input->getInt('collection');

        return $collection ? $append . '&collection=' . $collection : $append;
    }

    protected function getRedirectToListAppend(): string
    {
        $append     = parent::getRedirectToListAppend();
        $collection = $this->input->getInt('collection');

        return $collection ? $append . '&collection=' . $collection : $append;
    }
}
