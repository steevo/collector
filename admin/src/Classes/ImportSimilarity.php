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
 * Note par champ (de 0 à 1), pondérée. La note, le poids et les « champs suffisants »
 * sont définis par chaque type de champ (CollectorField::similarityScore()…) : cette
 * classe ne connaît aucun type. Elle fournit aussi des outils de comparaison de textes.
 */
final class ImportSimilarity
{
    /** Note minimale pour proposer un objet */
    public const THRESHOLD = 0.8;

    /** Nombre maximal d'objets proposés par ligne */
    public const MAX_CANDIDATES = 3;

    /** @var array<int, array<int, array<string, string>>> Objets par collection */
    private static array $pool = [];


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
    /**
     * @param  array  $exclude    Objets à ignorer (créés par l'import, déjà retenus)
     * @param  float  $threshold  Note minimale (0 à 1), réglée par collection
     * @param  array  $keys       Champs suffisants, réglés par collection : s'ils sont
     *                            tous identiques, l'objet est proposé quelle que soit sa note
     */
    public static function find(int $collection, array $data, array $fields, array $cols, array $exclude = [],
                                float $threshold = self::THRESHOLD, array $keys = []): array
    {
        $prepared = self::prepare($data, $fields, $cols, $keys);
        if ($prepared === null) {
            return []; // trop peu d'éléments pour juger d'une ressemblance
        }

        $pool = self::pool($collection, array_unique(array_merge(array_keys($prepared['use']), array_keys($prepared['keys']))));

        // Objets créés par l'import en cours : ce sont d'autres lignes du même fichier,
        // déjà comparées entre elles (règle des conflits entre lignes), pas des doublons
        return self::rank($data, $prepared, $pool, $threshold, $exclude);
    }

    /**
     * Objets les plus ressemblants parmi $items [id => [colonne => valeur]].
     *
     * @param  array  $prepared  Résultat de prepare()
     * @return array<int, array{id: int, score: float, keys?: bool}>  Du plus ressemblant au moins ressemblant
     */
    public static function rank(array $data, array $prepared, array $items, float $threshold,
                                array $exclude = [], int $limit = self::MAX_CANDIDATES): array
    {
        $exclude = array_flip(array_map('intval', $exclude));
        $found   = [];

        foreach ($items as $id => $item) {
            if (isset($exclude[(int) $id])) {
                continue;
            }
            $total = 0.0;
            $max   = 0.0;

            foreach ($prepared['use'] as $col => $field) {
                $a = (string) $data[$col];
                $b = trim((string) ($item[$col] ?? ''));
                $weight = $field->getSimilarityWeight();
                $max   += $weight;
                // Valeur absente en base : ni pour ni contre
                $total += $weight * ($field->isEmptyValue($b) ? 0.5 : $field->similarityScore($a, $b));
            }

            $score  = $max > 0 ? $total / $max : 0;
            $byKeys = $prepared['keys'] && self::keysEqual($prepared['keys'], $data, $item);

            if ($score >= $threshold || $byKeys) {
                $found[] = ['id' => (int) $id, 'score' => round($score, 2)] + ($byKeys ? ['keys' => true] : []);
            }
        }

        usort($found, fn($x, $y) => $y['score'] <=> $x['score']);

        return array_slice($found, 0, $limit);
    }

    /**
     * Champs utiles à la comparaison d'une ligne : renseignés dans ses données, et champs
     * suffisants s'ils sont tous renseignés par une valeur qui identifie.
     *
     * @return array{use: array, keys: array}|null  null : trop peu d'éléments pour juger
     */
    public static function prepare(array $data, array $fields, array $cols, array $keys = []): ?array
    {
        $byCol = [];
        foreach ($fields as $field) {
            $byCol[$field->_field->tablecolumn] = $field;
        }

        // Colonnes utiles : renseignées dans le fichier
        $use = [];
        foreach ($cols as $col) {
            if (isset($byCol[$col]) && !$byCol[$col]->isEmptyValue((string) ($data[$col] ?? ''))) {
                $use[$col] = $byCol[$col];
            }
        }

        // Champs suffisants : tous renseignés dans le fichier par une valeur qui identifie
        // (« ** », « ? », « 51 » ne comptent pas : c'est le type de champ qui en juge)
        $keyFields = [];
        foreach ($keys as $col) {
            $value = trim((string) ($data[$col] ?? ''));
            if (!isset($byCol[$col]) || self::normalize($value) === '' || !$byCol[$col]->isSimilarityKey($value)) {
                $keyFields = [];
                break;
            }
            $keyFields[$col] = $byCol[$col];
        }

        if (count($use) < 2 && !$keyFields) {
            return null;
        }

        return ['use' => $use, 'keys' => $keyFields];
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
     * l'une contient l'autre (7303 / 73030, 006 / 01006) → 0,8, compatible avec
     * une référence à jokers (K*** / K69) → 0,8, sinon 0.
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
        if (self::wildcardMatch($a, $b) || self::wildcardMatch($b, $a)) {
            return 0.8;
        }
        if (min(strlen($x), strlen($y)) >= 3 && (str_contains($x, $y) || str_contains($y, $x))) {
            return 0.8;
        }

        return 0.0;
    }

    /**
     * Référence à jokers : « * » et « ? » y désignent des caractères inconnus
     * (K*** = K suivi de chiffres inconnus). Compatible si la partie connue
     * correspond, dans l'ordre, à l'autre référence (K*** / K69, 4*25 / 4125).
     */
    public static function wildcardMatch(string $pattern, string $value): bool
    {
        $pattern = html_entity_decode($pattern, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!preg_match('/[*?]/', $pattern)) {
            return false;
        }

        // Morceaux connus, normalisés comme les références, séparés par les jokers
        $parts = array_map([self::class, 'normalize'], preg_split('/[*?]+/', $pattern));
        if (implode('', $parts) === '') {
            return false; // que des jokers (« ** ») : référence inconnue, traitée à part
        }

        $value = self::normalize($value);
        if ($value === '') {
            return false;
        }

        $regex = '/^' . implode('[a-z0-9]*', array_map(fn($p) => preg_quote($p, '/'), $parts)) . '$/';

        return (bool) preg_match($regex, $value);
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


    // ── Interne ──────────────────────────────────────────────────────────────

    /** Les champs suffisants sont-ils tous identiques entre la ligne et l'objet ? */
    private static function keysEqual(array $keyFields, array $data, array $item): bool
    {
        foreach ($keyFields as $col => $field) {
            if (!$field->isSimilarityKeyEqual(trim((string) $data[$col]), trim((string) ($item[$col] ?? '')))) {
                return false;
            }
        }

        return true;
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

}
