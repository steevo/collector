<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Classes;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Rapprochement par ressemblance pour l'import.
 *
 * La détection des doublons exige l'égalité des champs de comparaison. Quand elle
 * ne trouve rien, on cherche ici les objets « qui ressemblent » : même fabricant
 * écrit autrement (Star-Model / Star-Models), référence avec ou sans espace
 * (RAC 042 / RAC042), année absente en base… Ces lignes sont proposées
 * « à vérifier » au lieu d'être importées comme nouveaux objets.
 *
 * Note par champ (de 0 à 1), pondérée : listes et dates comptent 1, textes
 * (références) 2, car une référence différente désigne presque toujours un autre objet.
 */
final class ImportSimilarity
{
    /** Note minimale pour proposer un objet */
    public const THRESHOLD = 0.8;

    /** Nombre maximal d'objets proposés par ligne */
    public const MAX_CANDIDATES = 3;

    /** @var array<int, array<int, array<string, string>>> Objets par collection */
    private static array $pool = [];

    /** @var array<int, string> Libellés des valeurs de listes */
    private static array $labels = [];

    /** @var array<string, float> Notes de ressemblance déjà calculées */
    private static array $labelCache = [];

    /**
     * Objets ressemblants à des données importées.
     *
     * @param  int    $collection
     * @param  array  $data    Données importées (après getImportedValue)
     * @param  array  $fields  Instances CollectorField
     * @param  array  $cols    Colonnes à comparer (champs de comparaison des doublons)
     * @return array<int, array{id: int, score: float}>  Du plus ressemblant au moins ressemblant
     */
    public static function find(int $collection, array $data, array $fields, array $cols): array
    {
        $byCol = [];
        foreach ($fields as $field) {
            $byCol[$field->_field->tablecolumn] = $field;
        }

        // Colonnes utiles : renseignées dans le fichier
        $use = [];
        foreach ($cols as $col) {
            $value = trim((string) ($data[$col] ?? ''));
            if ($value === '' || $value === '0' || !isset($byCol[$col])) {
                continue;
            }
            $use[$col] = (string) $byCol[$col]->_field->type;
        }

        if (count($use) < 2) {
            return []; // trop peu d'éléments pour juger d'une ressemblance
        }

        $pool = self::pool($collection, array_keys($use));
        $found = [];

        foreach ($pool as $id => $item) {
            $total = 0.0;
            $max   = 0.0;

            foreach ($use as $col => $type) {
                $a = (string) $data[$col];
                $b = (string) ($item[$col] ?? '');
                $weight = self::weight($type);
                $max   += $weight;
                $total += $weight * self::fieldScore($type, $a, $b);
            }

            $score = $max > 0 ? $total / $max : 0;
            if ($score >= self::THRESHOLD) {
                $found[] = ['id' => (int) $id, 'score' => round($score, 2)];
            }
        }

        usort($found, fn($x, $y) => $y['score'] <=> $x['score']);

        return array_slice($found, 0, self::MAX_CANDIDATES);
    }

    /**
     * Ressemblance de deux libellés (0 à 1) : identiques une fois normalisés → 1,
     * l'un contient l'autre → 0,9, sinon proportion de caractères communs.
     */
    public static function labelScore(string $a, string $b): float
    {
        $key = $a . "\x1F" . $b;
        if (isset(self::$labelCache[$key])) {
            return self::$labelCache[$key];
        }

        $x = self::normalize($a);
        $y = self::normalize($b);

        if ($x === '' || $y === '') {
            $score = 0.0;
        } elseif ($x === $y) {
            $score = 1.0;
        } elseif (min(strlen($x), strlen($y)) < 4) {
            // Trop court pour juger (« MF » / « MFA », « 10 » / « 150 ») : seule l'égalité compte
            $score = 0.0;
        } elseif (str_contains($x, $y) || str_contains($y, $x)) {
            $score = 0.9;
        } else {
            similar_text($x, $y, $percent);
            $score = $percent / 100;
        }

        return self::$labelCache[$key] = $score;
    }

    /**
     * Ressemblance de deux références : identiques une fois normalisées → 1,
     * l'une contient l'autre (7303 / 73030, 006 / 01006) → 0,8, sinon 0.
     */
    public static function refScore(string $a, string $b): float
    {
        $x = self::normalize($a);
        $y = self::normalize($b);

        if ($x === '' || $y === '') {
            // Référence sans lettre ni chiffre (« ** », « ? ») : inconnue, ni pour ni contre
            return 0.5;
        }
        if ($x === $y) {
            return 1.0;
        }
        if (min(strlen($x), strlen($y)) >= 3 && (str_contains($x, $y) || str_contains($y, $x))) {
            return 0.8;
        }

        return 0.0;
    }

    /**
     * Texte réduit à ses lettres et chiffres, sans accents ni casse (&amp; décodé).
     */
    public static function normalize(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = mb_strtolower(trim($value));

        if (class_exists(\Normalizer::class)) {
            $value = preg_replace('/\p{Mn}+/u', '', \Normalizer::normalize($value, \Normalizer::FORM_D) ?: $value) ?? $value;
        }

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    /**
     * Libellé d'une valeur de liste.
     */
    public static function label(string $id): string
    {
        self::loadLabels();

        return self::$labels[(int) $id] ?? $id;
    }

    // ── Interne ──────────────────────────────────────────────────────────────

    private static function weight(string $type): float
    {
        return in_array($type, ['define', 'date'], true) ? 1.0 : 2.0;
    }

    private static function fieldScore(string $type, string $a, string $b): float
    {
        $b = trim($b);

        // Valeur absente en base : ni pour ni contre
        if ($b === '' || $b === '0' || str_starts_with($b, '0000')) {
            return 0.5;
        }

        switch ($type) {
            case 'define':
                return $a === $b ? 1.0 : self::labelScore(self::label($a), self::label($b));

            case 'date':
                return substr(trim($a), 0, 4) === substr($b, 0, 4) ? 1.0 : 0.0;

            default:
                return self::refScore($a, $b);
        }
    }

    /**
     * Objets de la collection (version active), limités aux colonnes comparées.
     */
    private static function pool(int $collection, array $cols): array
    {
        sort($cols);
        $key = $collection . ':' . implode(',', $cols);

        if (isset(self::$pool[$key])) {
            return self::$pool[$key];
        }

        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $select = ['h.item'];
        foreach ($cols as $col) {
            $select[] = $db->quoteName('h.' . $col);
        }

        $db->setQuery(
            $db->getQuery(true)
               ->select($select)
               ->from($db->quoteName('#__collector_items_history_' . $collection, 'h'))
               ->join('INNER', $db->quoteName('#__collector_items', 'i') . ' ON i.id = h.item')
               ->where('h.state = 1')
               ->where('i.state >= 0')
        );

        $pool = [];
        foreach ($db->loadAssocList() ?: [] as $row) {
            $id = (int) $row['item'];
            unset($row['item']);
            $pool[$id] = $row;
        }

        return self::$pool[$key] = $pool;
    }

    private static function loadLabels(): void
    {
        if (self::$labels) {
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery('SELECT id, content FROM ' . $db->quoteName('#__collector_defined_content'));

        foreach ($db->loadAssocList() ?: [] as $row) {
            self::$labels[(int) $row['id']] = (string) $row['content'];
        }
    }
}
