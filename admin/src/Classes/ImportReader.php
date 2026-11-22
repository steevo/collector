<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Classes;

defined('_JEXEC') or die;

/**
 * Lecteur unique des fichiers d'import (xlsx et csv).
 *
 * Utilisé par l'aperçu (étape 2), l'extraction des valeurs (étape 3) et
 * l'import lui-même (étape 5), pour que tous voient exactement les mêmes données.
 *
 * - xlsx : bibliothèque SimpleXLSX (https://github.com/shuchkin/simplexlsx, licence MIT),
 *   première feuille du classeur. SimpleXLSX ne gère pas les cellules fusionnées :
 *   la valeur de la cellule en haut à gauche de chaque zone fusionnée est recopiée
 *   dans toutes les cellules de la zone (fusions verticales et horizontales).
 * - csv : séparateur « ; » ou « , » détecté sur la première ligne, encodage UTF-8
 *   (avec ou sans BOM) ou Windows-1252 (enregistrement « CSV » d'Excel).
 *
 * Le résultat est mis en cache dans le dossier temporaire du système : les requêtes
 * AJAX de l'import ne relisent pas le fichier à chaque lot.
 */
final class ImportReader
{
    /** Extensions acceptées */
    public const EXTENSIONS = ['xlsx', 'csv'];

    /** À changer si le format du cache change (invalide les anciens caches) */
    private const CACHE_VERSION = 1;

    /** @var array<string, array<int, array<int, string>>> Cache mémoire par fichier */
    private static array $memory = [];

    private static string $lastError = '';

    /**
     * Dossier où sont déposés les fichiers d'import.
     */
    public static function dir(): string
    {
        return JPATH_ADMINISTRATOR . '/components/com_collector/import_tmp/';
    }

    /**
     * Toutes les lignes non vides du fichier.
     *
     * @return array<int, array<int, string>>  [numéro de ligne (1 = première) => [col 0, col 1, …]]
     *                                         Toutes les lignes ont le même nombre de colonnes.
     */
    public static function rows(string $filepath): array
    {
        self::$lastError = '';

        if (!is_file($filepath)) {
            self::$lastError = 'file not found';
            return [];
        }

        $key = md5(realpath($filepath) . '|' . filemtime($filepath) . '|' . filesize($filepath) . '|' . self::CACHE_VERSION);

        if (isset(self::$memory[$key])) {
            return self::$memory[$key];
        }

        $cacheFile = sys_get_temp_dir() . '/collector_import_' . $key . '.ser';

        if (is_file($cacheFile)) {
            $rows = @unserialize((string) file_get_contents($cacheFile), ['allowed_classes' => false]);
            if (is_array($rows)) {
                return self::$memory[$key] = $rows;
            }
        }

        $ext  = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $rows = $ext === 'csv' ? self::readCsv($filepath) : self::readXlsx($filepath);
        $rows = self::finalize($rows);

        if (self::$lastError === '') {
            @file_put_contents($cacheFile, serialize($rows));
        }

        return self::$memory[$key] = $rows;
    }

    /**
     * Numéro de la dernière ligne contenant des données (0 si fichier vide ou illisible).
     */
    public static function lastRow(string $filepath): int
    {
        $rows = self::rows($filepath);

        return $rows ? max(array_keys($rows)) : 0;
    }

    /**
     * Message d'erreur de la dernière lecture ('' si tout s'est bien passé).
     */
    public static function lastError(): string
    {
        return self::$lastError;
    }

    /**
     * Index 0-based d'une colonne à partir de ses lettres (A → 0, AA → 26).
     */
    public static function colIndex(string $letters): int
    {
        $idx = 0;
        foreach (str_split(strtoupper(trim($letters))) as $c) {
            $idx = $idx * 26 + ord($c) - 64;
        }

        return $idx - 1;
    }

    /**
     * Lettres d'une colonne à partir de son index 0-based (0 → A, 26 → AA).
     */
    public static function colLetter(int $idx): string
    {
        $s = '';
        $n = $idx + 1;
        while ($n > 0) {
            $r = ($n - 1) % 26;
            $s = chr(65 + $r) . $s;
            $n = intdiv($n - $r - 1, 26);
        }

        return $s;
    }

    // ── xlsx ─────────────────────────────────────────────────────────────────

    private static function readXlsx(string $filepath): array
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_collector/libraries/SimpleXLSX.php';

        $xlsx = \Shuchkin\SimpleXLSX::parse($filepath);
        if (!$xlsx) {
            self::$lastError = (string) \Shuchkin\SimpleXLSX::parseError();
            return [];
        }

        // Les dates sont rendues au format base de données (le champ Date l'accepte tel quel)
        $xlsx->setDateTimeFormat('Y-m-d H:i:s');

        $rows = [];
        foreach ($xlsx->readRows(0) as $i => $row) {
            $rows[$i + 1] = array_map([self::class, 'toString'], array_values($row));
        }

        // Cellules fusionnées : SimpleXLSX ne renseigne que la cellule en haut à gauche
        $sheet = $xlsx->worksheet(0);
        if ($sheet !== false && isset($sheet->mergeCells->mergeCell)) {
            foreach ($sheet->mergeCells->mergeCell as $mergeCell) {
                $ref = (string) $mergeCell['ref'];
                if (!preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/i', $ref, $m)) {
                    continue;
                }

                $col1 = self::colIndex($m[1]);
                $row1 = (int) $m[2];
                $col2 = self::colIndex($m[3]);
                $row2 = (int) $m[4];
                $value = $rows[$row1][$col1] ?? '';

                for ($r = $row1; $r <= $row2; $r++) {
                    for ($c = $col1; $c <= $col2; $c++) {
                        $rows[$r][$c] = $value;
                    }
                }
            }
        }

        return $rows;
    }

    // ── csv ──────────────────────────────────────────────────────────────────

    private static function readCsv(string $filepath): array
    {
        $content = file_get_contents($filepath);
        if ($content === false) {
            self::$lastError = 'unreadable file';
            return [];
        }

        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        } elseif (!mb_check_encoding($content, 'UTF-8')) {
            // « CSV (séparateur : point-virgule) » d'Excel sous Windows
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        // Séparateur : celui qui apparaît le plus sur la première ligne
        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiter = substr_count($firstLine, ',') > substr_count($firstLine, ';') ? ',' : ';';

        $h = fopen('php://temp', 'r+');
        fwrite($h, $content);
        rewind($h);

        $rows = [];
        $n    = 0;
        while (($row = fgetcsv($h, 0, $delimiter, '"', '')) !== false) {
            $n++;
            $rows[$n] = array_map([self::class, 'toString'], array_values($row));
        }
        fclose($h);

        return $rows;
    }

    // ── Commun ───────────────────────────────────────────────────────────────

    /**
     * Retire les lignes vides et rend toutes les lignes de même largeur.
     */
    private static function finalize(array $rows): array
    {
        $width = 0;
        foreach ($rows as $n => $row) {
            if (implode('', $row) === '') {
                unset($rows[$n]);
                continue;
            }
            $width = max($width, max(array_keys($row)) + 1);
        }

        foreach ($rows as &$row) {
            $full = array_fill(0, $width, '');
            foreach ($row as $c => $v) {
                $full[$c] = $v;
            }
            $row = $full;
        }
        unset($row);

        ksort($rows);

        return $rows;
    }

    /**
     * Convertit une valeur de cellule en texte (accents normalisés en forme composée).
     */
    private static function toString(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }
        if ($value === true) {
            return '1';
        }

        $value = (string) $value;

        if (function_exists('normalizer_normalize') && !normalizer_is_normalized($value)) {
            $value = normalizer_normalize($value) ?: $value;
        }

        return $value;
    }
}
