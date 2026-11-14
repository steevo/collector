<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Templates d'affichage des collections : HTML enregistré en base, avec des balises remplacées
 * à l'affichage. Aucun code n'est exécuté : seules les balises connues sont interprétées.
 *
 * Balises (le nom d'un champ est sa colonne, par exemple {fabricant}) :
 *   {champ}                  valeur du champ, telle que Collector l'affiche (image, liste, date…)
 *   {champ:label}            nom du champ
 *   {champ:value}            valeur brute, échappée
 *   {champ:text}             valeur en texte seul (sans lien ni image ; logo : son texte alternatif)
 *   {champ:url}              adresse d'une image, d'un fichier ou du téléchargement Phoca Download
 * Les valeurs affichées par {champ} peuvent contenir des liens : à l'intérieur d'un lien <a>,
 * préférer {champ:text} et {champ:url} (un lien ne peut pas en contenir un autre).
 *   {link}                   adresse de la fiche détaillée
 *   {id}, {hits}             identifiant et nombre de vues de l'élément
 *   {if:champ}…{else}…{/if}  bloc affiché si le champ a une valeur ({else} facultatif)
 *   {ifnot:champ}…{/if}      bloc affiché si le champ est vide
 * Vue liste : {items}…{/items} délimite le bloc répété pour chaque élément ;
 * sans ces balises, tout le template est répété.
 * Listes des membres (collection personnelle, souhaits, échanges) :
 *   {note}                   note du membre sur l'objet (avec Ajouter / Modifier sur ses propres listes)
 *   {actions}                bouton de retrait de la liste (sur ses propres listes)
 * Ces deux balises n'affichent rien dans la collection générale. Dans une liste de membre, si le
 * bloc répété ne les contient pas, elles sont ajoutées à la fin de chaque élément.
 */
abstract class CollectorTemplateHelper
{
    /** Vue liste */
    public const CLIENT_LIST = 1;

    /** Vue détaillée */
    public const CLIENT_ITEM = 0;

    /** Balises réservées (en plus des champs) */
    public const RESERVED = ['link', 'id', 'hits'];

    /** Balises des listes des membres, remplies par la vue (vides ailleurs) */
    public const USERLIST_TAGS = ['note', 'actions'];

    /**
     * Charge le template publié d'une collection pour une vue donnée.
     *
     * @return  object|null  null si aucun template (ou template dépublié / d'une autre collection)
     */
    public static function load(int $id, int $collection, int $client): ?object
    {
        if ($id <= 0 || $collection <= 0) {
            return null;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'name', 'content', 'css']))
            ->from($db->quoteName('#__collector_templates'))
            ->where($db->quoteName('id') . ' = :id')
            ->where($db->quoteName('collection') . ' = :collection')
            ->where($db->quoteName('client') . ' = :client')
            ->where($db->quoteName('state') . ' = 1')
            ->bind(':id', $id, ParameterType::INTEGER)
            ->bind(':collection', $collection, ParameterType::INTEGER)
            ->bind(':client', $client, ParameterType::INTEGER);

        $template = $db->setQuery($query)->loadObject();

        return $template && trim((string) $template->content) !== '' ? $template : null;
    }

    /**
     * Templates publiés d'une collection pour une vue donnée (sélecteur de vue du site).
     *
     * @return  array  liste d'objets {id, name}, triés par nom
     */
    public static function getPublished(int $collection, int $client): array
    {
        if ($collection <= 0) {
            return [];
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'name']))
            ->from($db->quoteName('#__collector_templates'))
            ->where($db->quoteName('collection') . ' = :collection')
            ->where($db->quoteName('client') . ' = :client')
            ->where($db->quoteName('state') . ' = 1')
            ->where('TRIM(' . $db->quoteName('content') . ') <> ' . $db->quote(''))
            ->order($db->quoteName('name'))
            ->bind(':collection', $collection, ParameterType::INTEGER)
            ->bind(':client', $client, ParameterType::INTEGER);

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Ajoute le CSS propre au template dans la page.
     */
    public static function addCss(object $template): void
    {
        $css = trim((string) ($template->css ?? ''));

        if ($css === '') {
            return;
        }

        // le CSS ne doit pas pouvoir refermer la balise <style>
        $css = str_ireplace('</style', '<\/style', $css);

        Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineStyle($css);
    }

    /**
     * Vue liste : rend tous les éléments.
     *
     * @param   object    $template  Template chargé par load()
     * @param   array     $fields    Champs de la collection (objets CollectorField)
     * @param   array     $items     Éléments à afficher
     * @param   mixed     $params    Paramètres de la vue
     * @param   callable  $link      Fonction (élément) => adresse de sa fiche
     * @param   array     $tags      Listes des membres : balise de USERLIST_TAGS => fonction (élément) => HTML
     */
    public static function renderList(object $template, array $fields, array $items, $params, callable $link, array $tags = []): string
    {
        $content = (string) $template->content;
        $before  = '';
        $after   = '';
        $block   = $content;

        if (preg_match('/^(.*?)\{items\}(.*)\{\/items\}(.*)$/s', $content, $parts)) {
            [, $before, $block, $after] = $parts;
        }

        $block = static::appendUserlistTags($block, $tags);
        $html  = '';

        foreach ($items as $item) {
            $html .= static::renderBlock($block, $fields, $item, $params, true, (string) $link($item), $tags);
        }

        return $before . $html . $after;
    }

    /**
     * Liste d'un membre : la note et le bouton de retrait restent disponibles même si le template
     * ne prévoit pas {note} ou {actions} ; on les ajoute à la fin de l'élément répété.
     */
    protected static function appendUserlistTags(string $block, array $tags): string
    {
        $missing = array_filter(
            static::USERLIST_TAGS,
            static fn ($tag) => isset($tags[$tag]) && !preg_match('/\{(?:(?:if|ifnot):)?' . $tag . '\}/', $block)
        );

        if (!$missing) {
            return $block;
        }

        $extra = implode(' ', array_map(static fn ($tag) => '{' . $tag . '}', $missing));

        // élément unique (<div>…</div>, <li>…</li>, <tr>…</tr>) : ajout à l'intérieur, avant sa fermeture
        if (preg_match('#^(.*)(</([a-z][a-z0-9]*)>\s*)$#is', $block, $m)) {
            $wrap = strtolower($m[3]) === 'tr'
                ? '<td class="collector-userlist-extra">' . $extra . '</td>'
                : '<div class="collector-userlist-extra">' . $extra . '</div>';

            return $m[1] . $wrap . $m[2];
        }

        return $block . '<div class="collector-userlist-extra">' . $extra . '</div>';
    }

    /**
     * Vue détaillée : rend un élément.
     */
    public static function renderItem(object $template, array $fields, object $item, $params, string $link = ''): string
    {
        return static::renderBlock((string) $template->content, $fields, $item, $params, false, $link);
    }

    /**
     * Remplace les conditions puis les balises d'un bloc pour un élément.
     */
    protected static function renderBlock(string $html, array $fields, object $item, $params, bool $listing, string $link, array $tags = []): string
    {
        $byName = [];

        foreach ($fields as $field) {
            if (!empty($field->_field->tablecolumn)) {
                $byName[$field->_field->tablecolumn] = $field;
            }
        }

        $cache = [];

        // affichage d'un champ (calculé une seule fois par élément)
        $display = function (string $name) use ($byName, $item, $params, $listing, &$cache): string {
            if (!array_key_exists($name, $cache)) {
                $field = $byName[$name];
                $value = $item->$name ?? null;
                $out   = $field->display($value, $listing, $params, (int) ($item->id ?? 0));

                $cache[$name] = $out === false || $out === null ? '' : (string) $out;
            }

            return $cache[$name];
        };

        $resolve = function (string $name, string $modifier) use ($byName, $item, $link, $display, $tags): ?string {
            // note et retrait d'une liste de membre : rien hors de ces listes
            if (\in_array($name, static::USERLIST_TAGS, true) && $modifier === '') {
                return isset($tags[$name]) ? (string) $tags[$name]($item) : '';
            }

            if (\in_array($name, static::RESERVED, true) && $modifier === '') {
                switch ($name) {
                    case 'link':
                        return htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
                    case 'id':
                        return (string) (int) ($item->id ?? 0);
                    case 'hits':
                        return (string) (int) ($item->hits ?? 0);
                }
            }

            if (!isset($byName[$name])) {
                return null;
            }

            switch ($modifier) {
                case 'label':
                    return htmlspecialchars(Text::_($byName[$name]->_field->field), ENT_QUOTES, 'UTF-8');
                case 'value':
                    return htmlspecialchars(strip_tags((string) ($item->$name ?? '')), ENT_QUOTES, 'UTF-8');
                case 'text':
                    return htmlspecialchars(static::plainText($display($name)), ENT_QUOTES, 'UTF-8');
                case 'url':
                    // un type de champ peut fournir sa propre adresse (téléchargement Phoca Download…)
                    $url = method_exists($byName[$name], 'getUrl')
                        ? $byName[$name]->getUrl((string) ($item->$name ?? ''))
                        : null;

                    return htmlspecialchars($url ?? static::fileUrl((string) ($item->$name ?? '')), ENT_QUOTES, 'UTF-8');
                default:
                    return $display($name);
            }
        };

        $isEmpty = function (string $name) use ($byName, $display, $item, $tags): bool {
            if ($name === 'note') {
                return !isset($tags['note']) || trim((string) ($item->userlist_note ?? '')) === '';
            }

            if ($name === 'actions') {
                return !isset($tags['actions']) || trim((string) $tags['actions']($item)) === '';
            }

            if (\in_array($name, static::RESERVED, true)) {
                return false;
            }

            if (!isset($byName[$name])) {
                return true;
            }

            return trim(strip_tags($display($name), '<img><svg><video><iframe><audio>')) === '';
        };

        // conditions, de la plus intérieure vers l'extérieur (imbrication possible)
        $pattern = '/\{(if|ifnot):([a-z0-9_]+)\}((?:(?!\{(?:if|ifnot):[a-z0-9_]+\}).)*?)\{\/if\}/s';

        do {
            $html = preg_replace_callback($pattern, function ($m) use ($isEmpty) {
                $branches = explode('{else}', $m[3], 2);
                $show     = $m[1] === 'if' ? !$isEmpty($m[2]) : $isEmpty($m[2]);

                return $show ? $branches[0] : ($branches[1] ?? '');
            }, $html, -1, $count);
        } while ($count > 0);

        // balises ; une balise inconnue est laissée telle quelle
        return preg_replace_callback('/\{([a-z0-9_]+)(?::(label|value|text|url))?\}/', function ($m) use ($resolve) {
            $out = $resolve($m[1], $m[2] ?? '');

            return $out ?? $m[0];
        }, $html);
    }

    /**
     * Texte seul d'un affichage de champ : sans balises HTML, ni liens, ni fenêtres.
     * Pour une valeur affichée en image (logo d'une liste), on prend son texte alternatif.
     */
    protected static function plainText(string $html): string
    {
        // le contenu des fenêtres modales (image agrandie) ne fait pas partie de la valeur
        $html = preg_replace('#<div[^>]*class="[^"]*\bmodal\b.*$#si', '', $html);
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')));

        if ($text === '' && preg_match_all('/<img[^>]*\b(?:alt|title)="([^"]+)"/i', $html, $m)) {
            $text = implode(', ', array_unique(array_map(
                static fn ($v) => html_entity_decode($v, ENT_QUOTES, 'UTF-8'),
                $m[1]
            )));
        }

        return $text;
    }

    /**
     * Adresse d'une image ou d'un fichier à partir de la valeur enregistrée ("chemin|description|…").
     */
    protected static function fileUrl(string $value): string
    {
        $path = trim(explode('|', $value)[0]);

        if ($path === '') {
            return '';
        }

        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        // nettoie les informations ajoutées par le sélecteur de médias de Joomla (#joomlaImage://…)
        $path = preg_replace('/#joomlaImage:.*$/', '', $path);

        return Uri::root(true) . '/' . ltrim(preg_replace('#^\./#', '', $path), '/');
    }

    /**
     * Balises disponibles pour une collection, pour l'aide à la saisie de l'administration.
     *
     * @return  array  liste de [balise, libellé]
     */
    public static function getTags(int $collection, int $client): array
    {
        $tags = [];

        if ($collection > 0) {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName(['field', 'tablecolumn']))
                ->from($db->quoteName('#__collector_fields'))
                ->where($db->quoteName('collection') . ' = :collection')
                ->order($db->quoteName('ordering'))
                ->bind(':collection', $collection, ParameterType::INTEGER);

            foreach ($db->setQuery($query)->loadObjectList() as $field) {
                $tags[] = ['{' . $field->tablecolumn . '}', Text::_($field->field)];
            }
        }

        $tags[] = ['{link}', Text::_('COM_COLLECTOR_TEMPLATE_TAG_LINK')];
        $tags[] = ['{id}', Text::_('COM_COLLECTOR_TEMPLATE_TAG_ID')];
        $tags[] = ['{hits}', Text::_('COM_COLLECTOR_TEMPLATE_TAG_HITS')];

        if ($client === static::CLIENT_LIST) {
            $tags[] = ['{items}{/items}', Text::_('COM_COLLECTOR_TEMPLATE_TAG_ITEMS')];
            $tags[] = ['{note}', Text::_('COM_COLLECTOR_TEMPLATE_TAG_NOTE')];
            $tags[] = ['{actions}', Text::_('COM_COLLECTOR_TEMPLATE_TAG_ACTIONS')];
        }

        return $tags;
    }
}
