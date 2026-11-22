<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\View\Import;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Steevo\Component\Collector\Administrator\Classes\ImportReader;

class HtmlView extends BaseHtmlView
{
    public int    $step       = 1;
    public int    $collection = 0;
    public array  $files      = [];
    public array  $fields     = [];
    public array  $session    = [];
    public array  $preview    = [];
    /** @var array<string, string[]> Valeurs uniques du fichier par colonne define [tablecolumn => ['val1',…]] */
    public array  $fileValues = [];
    /** @var array|null Config de mapping sauvegardée pour cette collection */
    public ?array $savedConfig = null;
    public string $collectionsSelect = '';
	public ?\Joomla\CMS\Form\Form $publishingForm = null;
	public bool $hasResumableSession = false;
	public array $resumeInfo = [];
	public array $autoDetectedMapping = [];

    public function display($tpl = null)
    {
        $app        = Factory::getApplication();
        $model      = $this->getModel();
        $userId     = $app->getIdentity()->get('id');

        $this->collection        = $model->getCollection();
        $this->fields            = $model->getFields();
        $this->collectionsSelect  = $model->getCollectionsSelect();

        // Charger la config de mapping sauvegardée (pré-remplissage étapes 2 et 3)
        $this->savedConfig = $model->getConfig($this->collection) ?? [];

        // Charger la session persistante (détermine l'étape courante)
        $dbSession  = $model->getSession($this->collection, $userId);
		
        // L'étape demandée par URL peut avancer mais pas reculer
        // si aucune session n'existe
        $requestedStep = $app->input->getInt('step', 1);

		// Détecter une session d'import interrompue (reprise possible)
		if ($dbSession
			&& (int)($dbSession['step'] ?? 1) >= 5
			&& (int)($dbSession['current_row'] ?? 0) > (int)($dbSession['first_line'] ?? 2)
			&& (int)($dbSession['current_row'] ?? 0) <= (int)($dbSession['total_rows'] ?? 0)
		) {
			$done  = (int)($dbSession['imported'] ?? 0)
				   + (int)($dbSession['skipped']  ?? 0)
				   + (int)($dbSession['merged']   ?? 0)
				   + (int)($dbSession['errors']   ?? 0);
			$total = max(0, (int)($dbSession['total_rows'] ?? 0)
						  - (int)($dbSession['first_line']  ?? 2) + 1);

			if ($done < $total) {
				$this->hasResumableSession = true;
				$this->resumeInfo = [
					'file'       => $dbSession['file'] ?? '',
					'done'       => $done,
					'total'      => $total,
					'imported'   => (int)($dbSession['imported'] ?? 0),
					'skipped'    => (int)($dbSession['skipped']  ?? 0),
					'merged'     => (int)($dbSession['merged']   ?? 0),
					'errors'     => (int)($dbSession['errors']   ?? 0),
				];
			}
		}

        if ($dbSession) {
            // Respecter l'étape de la session sauf si on revient en arrière
            $this->step    = min($requestedStep, (int) ($dbSession['step'] ?? 1));
            $this->session = $dbSession;
        } else {
            $this->step    = 1;
            $this->session = [];
        }

        // Étape 1 : liste des fichiers disponibles
        $this->files = $this->getFileList();

        // Étape 2 : prévisualisation des 5 premières lignes de données
        if ($this->step >= 2 && !empty($this->session['file'])) {
			$this->fulltitleCols = $model->getFulttitleCols($this->collection);
            $this->preview = $this->loadPreview(
                $this->session['file'],
                (int) ($this->session['first_line'] ?? 2)
            );
			
			// Auto-détecter le mapping si aucun mapping n'est encore sauvegardé
			$hasExistingMapping = !empty($this->session['mapping'])
							   || !empty($this->savedConfig['col_mapping']);

			$this->autoDetectedMapping = $hasExistingMapping
				? []
				: $this->autoDetectMapping(
					$this->preview['header'] ?? [],
					$this->fields
				);
        }

        // Étape 3 : extraction des valeurs uniques du fichier pour chaque champ define
        if ($this->step >= 3 && !empty($this->session['file']) && !empty($this->session['mapping'])) {
			$sessionValueMap = (array) ($this->session['value_mapping'] ?? []);
			$configValueMap  = (array) ($this->savedConfig['value_mapping'] ?? []);

			foreach ($configValueMap as $col => $configColData) {
				if (!isset($sessionValueMap[$col])) {
					$sessionValueMap[$col] = $configColData;
					continue;
				}
				foreach (($configColData['values'] ?? []) as $configPair) {
					if (($configPair['db'] ?? '') === '') continue;
					foreach (($sessionValueMap[$col]['values'] ?? []) as &$sessionPair) {
						if (mb_strtolower(trim((string)($sessionPair['file'] ?? '')))
							=== mb_strtolower(trim((string)($configPair['file'] ?? '')))
							&& ($sessionPair['db'] ?? '') === '') {
							$sessionPair['db'] = $configPair['db'];
							unset($sessionPair['create'], $sessionPair['create_label']);
						}
					}
					unset($sessionPair);
				}
			}

			$this->session['value_mapping'] = $sessionValueMap;
			
            $this->fileValues = $this->loadFileValues(
                $this->session['file'],
                (array) $this->session['mapping'],
                (int) ($this->session['first_line'] ?? 2)
            );
        }
		
		// Étape 4 : formulaire de publication (champs Joomla natifs)
		if ($this->step >= 4) {
			$form = Factory::getContainer()->get(\Joomla\CMS\Form\FormFactoryInterface::class)->createForm('com_collector.import_publishing');
			$form->loadFile(JPATH_ADMINISTRATOR . '/components/com_collector/forms/import_publishing.xml');
			// Pré-remplir avec les valeurs déjà sauvegardées en session si l'utilisateur revient en arrière
			$saved = $this->session['publishing'] ?? [];
			if (!empty($saved)) {
				$form->bind($saved);
			}
			$this->publishingForm = $form;
		}

		ToolbarHelper::title(Text::_('COM_COLLECTOR_IMPORT_TITLE'), 'upload');
        
		$resetUrl = Route::_(
			'index.php?option=com_collector&task=import.reset&collection=' . $this->collection
			. '&' . Session::getFormToken() . '=1', false
		);

		ToolbarHelper::link($resetUrl, 'COM_COLLECTOR_IMPORT_RESTART', 'refresh');
		
		ToolbarHelper::link(
			Route::_('index.php?option=com_collector&view=importhistory&collection=' . $this->collection, false),
			'COM_COLLECTOR_IMPORTHISTORY_BTN',
			'clock'
		);

        ToolbarHelper::link(
            Route::_('index.php?option=com_collector&view=items&collection=' . $this->collection, false),
            'JTOOLBAR_CLOSE',
            'cancel'
        );
		
        parent::display($tpl);
    }

    private function getFileList(): array
    {
        $dir = ImportReader::dir();
        if (!is_dir($dir)) return [];

        $files = [];

        foreach (scandir($dir) as $name) {
            if ($name[0] === '.') continue;
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ImportReader::EXTENSIONS, true)) continue;
            $path    = $dir . $name;
            $size    = filesize($path);
            $files[] = [
                'name'     => $name,
                'ext'      => $ext,
                'size'     => $size < 1048576
                    ? round($size / 1024, 1) . ' Ko'
                    : round($size / 1048576, 1) . ' Mo',
                'modified' => date('Y-m-d H:i', filemtime($path)),
            ];
        }
        return $files;
    }

    /**
     * Aperçu : ligne 1 (en-têtes) et les 5 premières lignes de données.
     *
     * @return array{header: string[], rows: array<int, string[]>}  rows indexé par numéro de ligne
     */
    private function loadPreview(string $filename, int $firstLine): array
    {
        $rows = ImportReader::rows(ImportReader::dir() . $filename);
        if (!$rows) return [];

        $preview = [];
        foreach ($rows as $n => $row) {
            if ($n < $firstLine || $n === 1) continue;
            $preview[$n] = $row;
            if (count($preview) >= 5) break;
        }

        return ['header' => $rows[1] ?? [], 'rows' => $preview];
    }

    /**
     * Extrait les valeurs uniques présentes dans le fichier pour chaque champ define.
     * Seules les colonnes mappées à un champ ayant getImportMapping() !== null sont lues.
     *
     * Utilise le générateur readRows() pour éviter de charger tout le fichier en RAM.
     *
     * @param  string  $filename   Nom du fichier (dans import_tmp/)
     * @param  array   $colMap     Mapping [tablecolumn => 'A']
     * @param  int     $firstLine  Première ligne de données (1-based)
     * @return array<string, string[]>  [tablecolumn => ['val1', 'val2', ...]] (valeurs uniques triées)
     */
    public function loadFileValues(string $filename, array $colMap, int $firstLine): array
    {
        // Identifier les colonnes define et leur index dans le fichier
        $defineColIndexes = []; // [tablecolumn => colIndex 0-based]
        foreach ($this->fields as $field) {
            if ($field->getImportMapping() === null) continue;
            $tc     = $field->_field->tablecolumn;
            $letter = strtoupper(trim($colMap[$tc] ?? ''));
            if ($letter === '') continue;
            $defineColIndexes[$tc] = ImportReader::colIndex($letter);
        }

        if (empty($defineColIndexes)) return [];

        $unique = array_fill_keys(array_keys($defineColIndexes), []);

        // Même lecture que l'import (cellules fusionnées comprises)
        foreach (ImportReader::rows(ImportReader::dir() . $filename) as $n => $row) {
            if ($n < $firstLine) continue;
            foreach ($defineColIndexes as $tc => $colIdx) {
                $unique[$tc][] = trim((string) ($row[$colIdx] ?? ''));
            }
        }

        // Dédupliquer (comparaison exacte) puis trier alphabétiquement
        foreach ($unique as &$vals) {
            $vals = array_values(array_unique($vals));
            usort($vals, fn($a, $b) => strcasecmp($a, $b));
        }

        return $unique;
    }

	/**
	 * Tente de détecter automatiquement le mapping colonnes → champs
	 * en comparant les en-têtes du fichier aux noms des champs de la collection.
	 *
	 * @param  array  $headers  En-têtes du fichier (indexés 0-based)
	 * @param  array  $fields   Champs de la collection (CollectorField[])
	 * @return array            [tablecolumn => lettre] — uniquement les correspondances trouvées
	 */
	private function autoDetectMapping(array $headers, array $fields): array
	{
		$mapping = [];

		// Normaliser : minuscules, sans accents, sans espaces/tirets/underscores
		$normalize = function (string $s): string {
			$s = mb_strtolower($s);
			$s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
			return preg_replace('/[\s\-_\.]+/', '', $s);
		};

		// Indexer les en-têtes normalisés → lettre de colonne
		$normalizedHeaders = [];
		foreach ($headers as $idx => $header) {
			if (trim((string) $header) === '') continue;
			$n = $idx + 1; $letter = '';
			while ($n > 0) { $r = ($n - 1) % 26; $letter = chr(65 + $r) . $letter; $n = intdiv($n - $r - 1, 26); }
			$normalizedHeaders[$normalize((string) $header)] = $letter;
		}

		foreach ($fields as $field) {
			$col       = $field->_field->tablecolumn;
			$fieldName = $field->_field->field; // nom affiché du champ

			// Correspondances à tester dans l'ordre de priorité
			$candidates = array_unique([
				$normalize($fieldName),          // nom affiché du champ
				$normalize($col),                // nom technique (tablecolumn)
			]);

			foreach ($candidates as $candidate) {
				// 1. Correspondance exacte
				if (isset($normalizedHeaders[$candidate])) {
					$mapping[$col] = $normalizedHeaders[$candidate];
					break;
				}
				// 2. En-tête contient le nom du champ
				foreach ($normalizedHeaders as $normHeader => $letter) {
					if (str_contains($normHeader, $candidate) || str_contains($candidate, $normHeader)) {
						$mapping[$col] = $letter;
						break 2;
					}
				}
			}
		}

		return $mapping;
	}
}