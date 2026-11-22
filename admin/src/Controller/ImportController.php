<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Steevo\Component\Collector\Administrator\Classes\ImportReader;
use Steevo\Component\Collector\Administrator\Table\CollectorItemsTable;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

/**
 * Contrôleur d'import Excel/CSV — Joomla 5/6.
 *
 * Navigation entre étapes par POST standard (pas d'AJAX pour les transitions).
 * AJAX uniquement pour la boucle d'import à l'étape 4.
 *
 * Étapes :
 *   1. Sélection / upload du fichier
 *   2. Mapping colonnes → champs
 *   3. Mapping valeurs (champs define uniquement, si existants)
 *   4. Import avec détection de doublons
 */
class ImportController extends AdminController
{
    private const ALLOWED_EXTS = ImportReader::EXTENSIONS;
    private const BATCH_SIZE   = 10;

    private function uploadDir(): string
    {
        return ImportReader::dir();
    }

    public function getModel(
        $name   = 'Import',
        $prefix = 'Administrator',
        $config = ['ignore_request' => true]
    ) {
        return parent::getModel($name, $prefix, $config);
    }

    // ── Navigation ───────────────────────────────────────────────────────────

    /** Retour à la liste des items */
    public function back(): void
    {
        $collection = $this->input->getInt('collection');
        $this->setRedirect(Route::_(
            'index.php?option=com_collector&view=items&collection=' . $collection, false
        ));
    }

    /** Reset complet : vide la session et revient à l'étape 1 */
    public function reset(): void
    {
        $app        = Factory::getApplication();
        $userId     = $app->getIdentity()->get('id');
        $collection = $this->input->getInt('collection');
        $this->getModel()->deleteSession($collection, $userId);
        $this->setRedirect(Route::_(
            'index.php?option=com_collector&view=import&collection=' . $collection, false
        ));
    }

    // ── Étape 1 : Upload / Sélection du fichier ──────────────────────────────

    /**
     * Upload d'un nouveau fichier.
     * Redirige vers l'étape 1 après l'upload (pas de changement d'étape).
     */
    public function upload(): void
    {
        if (!Session::checkToken()) {
            $this->setMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect($this->getStep1Url());
            return;
        }

        $upload = $this->input->files->get('upload', null, 'raw');

        if (!$upload || $upload['error'] !== UPLOAD_ERR_OK) {
            $this->setMessage(Text::_('COM_COLLECTOR_IMPORT_UPLOAD_ERROR'), 'error');
            $this->setRedirect($this->getStep1Url());
            return;
        }

        $ext = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTS, true)) {
            $this->setMessage(
                Text::sprintf('COM_COLLECTOR_IMPORT_INVALID_EXT', implode(', ', self::ALLOWED_EXTS)),
                'error'
            );
            $this->setRedirect($this->getStep1Url());
            return;
        }

        $dir = $this->uploadDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            file_put_contents($dir . 'index.html', '');
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($upload['name']));
        if (!move_uploaded_file($upload['tmp_name'], $dir . $safeName)) {
            $this->setMessage(Text::_('COM_COLLECTOR_IMPORT_UPLOAD_FAILED'), 'error');
            $this->setRedirect($this->getStep1Url());
            return;
        }

        $this->setMessage(Text::sprintf('COM_COLLECTOR_IMPORT_UPLOAD_OK', $safeName), 'success');
        $this->setRedirect($this->getStep1Url());
    }

    /**
     * Sélectionne un fichier existant et passe à l'étape 2.
     * Lit les premières lignes pour la prévisualisation.
     */
    public function selectfile(): void
    {
        if (!Session::checkToken()) {
            $this->setMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect($this->getStep1Url());
            return;
        }

        $collection = $this->input->getInt('collection');
        $filename   = $this->sanitize($this->input->getString('file', ''));

        if (!$filename || !is_file($this->uploadDir() . $filename)) {
            $this->setMessage(Text::_('COM_COLLECTOR_IMPORT_FILE_NOT_FOUND'), 'error');
            $this->setRedirect($this->getStep1Url());
            return;
        }

		$model     = $this->getModel();
		$totalRows = ImportReader::lastRow($this->uploadDir() . $filename);
		$userId    = Factory::getApplication()->getIdentity()->get('id');

		if ($totalRows === 0) {
			$error = ImportReader::lastError();
			$this->setMessage(
				$error !== ''
					? Text::sprintf('COM_COLLECTOR_IMPORT_FILE_UNREADABLE', $error)
					: Text::_('COM_COLLECTOR_IMPORT_FILE_EMPTY'),
				'error'
			);
			$this->setRedirect($this->getStep1Url());
			return;
		}
        
		// Récupérer les préférences sauvegardées pour cette collection
		$savedConfig = $model->getConfig($collection);

        // Initialise la session avec le fichier sélectionné
        $model->saveSession($collection, $userId, [
			'file'               => $filename,
			'first_line'         => $savedConfig['first_line']         ?? 2,
			'mapping'            => [],
			'value_mapping'      => $savedConfig['value_mapping']      ?? [],
			'current_row'        => 0,
			'total_rows'         => $totalRows,
			'imported'           => 0,
			'skipped'            => 0,
			'merged'             => 0,
			'step'               => 2,
			'duplicate_strategy' => $savedConfig['duplicate_strategy'] ?? 'unik',
			'composite_fields'   => $savedConfig['composite_fields']   ?? [],
		]);

        // Purger les anciens caches temporaires (> 1h) pour éviter de servir
        // des données périmées lors d'un import d'un nouveau fichier.
        foreach (glob(sys_get_temp_dir() . '/collector_*.ser') ?: [] as $f) {
            if (filemtime($f) < time() - 3600) @unlink($f);
        }

        $this->setRedirect(Route::_(
            'index.php?option=com_collector&view=import&collection=' . $collection . '&step=2', false
        ));
    }

    // ── Étape 2 : Mapping colonnes ───────────────────────────────────────────

    /**
     * Sauvegarde le mapping colonnes→champs et passe à l'étape suivante.
     * Si des champs define existent → étape 3, sinon → étape 4.
     */
    public function savemapping(): void
    {
        if (!Session::checkToken()) {
            $this->setMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect($this->getStep1Url());
            return;
        }

        $app        = Factory::getApplication();
        $collection = $this->input->getInt('collection');
        $userId     = $app->getIdentity()->get('id');
        $model      = $this->getModel();

        $session   = $model->getSession($collection, $userId);
        if (!$session) {
            $this->setRedirect($this->getStep1Url());
            return;
        }

        $firstLine = $this->input->getInt('first_line', 2);
        $colMap    = $this->input->get('colmap', [], 'ARRAY'); // [tablecolumn => 'A']
        $colMap    = array_map('strtoupper', array_map('trim', $colMap));

        $session['first_line']  = $firstLine;
        $session['mapping']     = $colMap;
        $session['current_row'] = $firstLine;
        $session['step']        = 3;

        $model->saveSession($collection, $userId, $session);

        // Vérifier si des champs define nécessitent un mapping de valeurs
        $fields      = $model->getFields();
        $hasDefine   = false;
        foreach ($fields as $field) {
            if ($field->getImportMapping() !== null) {
                $hasDefine = true;
                break;
            }
        }

        $nextStep = $hasDefine ? 3 : 4;
        $session['step'] = $nextStep;
		
		$strategy = $this->input->getString('duplicate_strategy', 'unik');
		$session['duplicate_strategy'] = $strategy;

		if ($strategy === 'composite') {
			$submitted = array_values(array_filter(
				(array) $this->input->get('composite_fields', [], 'ARRAY')
			));
			$compositeFields = !empty($submitted)
				? $submitted
				: ($session['composite_fields'] ?: []);
		} else {
			// strategy = 'unik' : on conserve la sélection composite précédente
			// pour la retrouver si l'utilisateur rebascule sur 'composite'
			$compositeFields = $session['composite_fields'] ?? [];
		}

		$session['composite_fields'] = $compositeFields;

		$model->saveSession($collection, $userId, $session);

        // Sauvegarder le mapping colonnes sans écraser le value_mapping existant
        $existingConfig  = $model->getConfig($collection);
        $existingValueMap = $existingConfig['value_mapping'] ?? [];

        $model->saveConfig($collection, $colMap, $existingValueMap, $firstLine,
                   $strategy, $compositeFields);

        $this->setRedirect(Route::_(
            'index.php?option=com_collector&view=import&collection=' . $collection . '&step=' . $nextStep,
            false
        ));
    }

    // ── Étape 3 : Mapping valeurs ───────────────────────────────────

    /**
     * Sauvegarde le mapping valeurs fichier→valeurs DB et passe à l'étape 4.
     */
    public function savevaluemapping(): void
    {
        if (!Session::checkToken()) {
            $this->setMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect($this->getStep1Url());
            return;
        }

        $app        = Factory::getApplication();
        $collection = $this->input->getInt('collection');
        $userId     = $app->getIdentity()->get('id');
        $model      = $this->getModel();

		$existingConfig = $model->getConfig($collection);
		$submittedMap   = $this->input->get('valuemap', [], 'ARRAY');

		// Fusionner : conserver les db résolus du config sauf si le formulaire
		// soumet explicitement une nouvelle valeur (create ou db non vide)
		$existingValueMap = $existingConfig['value_mapping'] ?? [];
		foreach ($existingValueMap as $col => $colData) {
			if (!isset($submittedMap[$col])) continue;
			foreach (($colData['values'] ?? []) as $idx => $existingPair) {
				$submitted = $submittedMap[$col]['values'][$idx] ?? [];
				$submittedDb = (string)($submitted['db'] ?? '');
				$existingDb  = (string)($existingPair['db'] ?? '');
				// Si le formulaire soumet db="" mais config a un ID valide → garder l'ID
				if ($submittedDb === '' && $existingDb !== '' && empty($submitted['create'])) {
					$submittedMap[$col]['values'][$idx]['db'] = $existingDb;
				}
			}
		}

		$valueMap = $submittedMap;

        $session = $model->getSession($collection, $userId);
        if (!$session) {
            $this->setRedirect($this->getStep1Url());
            return;
        }

        // Structure : valuemap[col][empty] = db_id
        //             valuemap[col][values][n][file] = 'texte'
        //             valuemap[col][values][n][db]   = db_id
        // $valueMap = $this->input->get('valuemap', [], 'ARRAY');

        $session['value_mapping'] = $valueMap;
        $session['step']          = 4;
        $model->saveSession($collection, $userId, $session);

        // Sauvegarder le mapping valeurs de façon permanente (merge avec col_mapping existant)
        $existingConfig = $model->getConfig($collection);
        $model->saveConfig(
            $collection,
            $existingConfig['col_mapping']   ?? (array) ($session['mapping'] ?? []),
            $valueMap,
            $existingConfig['first_line']    ?? (int) ($session['first_line'] ?? 2),
			$existingConfig['duplicate_strategy'] ?? 'unik',
			$existingConfig['composite_fields']   ?? []
        );

        $this->setRedirect(Route::_(
            'index.php?option=com_collector&view=import&collection=' . $collection . '&step=4',
            false
        ));
    }
	
	// ── Étape 4 : Paramètres de publication ─────────────────────────────────────

	public function savepublishing(): void
	{
		if (!Session::checkToken()) { /* ... */ }

		$app        = Factory::getApplication();
		$collection = $this->input->getInt('collection');
		$userId     = $app->getIdentity()->get('id');
		$model      = $this->getModel();

		$session = $model->getSession($collection, $userId);
		if (!$session) { $this->setRedirect($this->getStep1Url()); return; }

		// Charger le form pour bénéficier des filtres (user_utc, UINT, etc.)
		$form = Factory::getContainer()->get(\Joomla\CMS\Form\FormFactoryInterface::class)->createForm('com_collector.import_publishing');
		$form->loadFile(JPATH_ADMINISTRATOR . '/components/com_collector/forms/import_publishing.xml');
		$form->bind($this->input->post->getArray());
		$data = $form->filter($this->input->post->getArray());

		$session['publishing'] = [
			'state'        => (int) ($data['state']        ?? 1),
			'access'       => (int) ($data['access']       ?? 1),
			'publish_up'   => $data['publish_up']   ?: null,
			'publish_down' => $data['publish_down'] ?: null,
		];
		$session['step'] = 5;
		$model->saveSession($collection, $userId, $session);

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=import&collection=' . $collection . '&step=5', false
		));
	}

    // ── Étape 5 : Import AJAX ────────────────────────────────────────────────

    /**
     * Import d'un lot de lignes.
     * Appelé en AJAX répété depuis le JS.
     * Retourne JSON : {success, state:'next'|'end'|'conflict', ...}
     */
    public function insert(): void
    {
        $this->sendJson(function () {
            if (!Session::checkToken('request')) {
                return ['success' => false, 'message' => Text::_('JINVALID_TOKEN')];
            }

            $app        = Factory::getApplication();
            $collection = $this->input->getInt('collection');
            $userId     = $app->getIdentity()->get('id');
            $model      = $this->getModel();

            $session = $model->getSession($collection, $userId);
            if (!$session || empty($session['file'])) {
                return ['success' => false, 'message' => Text::_('COM_COLLECTOR_IMPORT_SESSION_NOT_FOUND')];
            }

            $filepath  = $this->uploadDir() . $session['file'];
            $allRows   = ImportReader::rows($filepath);
            $totalRows = $session['total_rows'];
            $rowStart  = $session['current_row'] ?: $session['first_line'];
            $imported  = $session['imported'];
            $skipped   = $session['skipped'];
            $merged    = $session['merged'];
			$errors    = $session['errors'] ?? 0;
			$importHistoryId   = (int) ($session['import_history_id'] ?? 0);
			$isDryRun          = !empty($session['dry_run']);
			$dryRunEntries     = [];
			$dryConflicts      = $session['dry_run_conflicts']      ?? 0;
			$dryMulticonflicts = $session['dry_run_multiconflicts'] ?? 0;
			$dryVirtual        = (array) ($session['dry_run_virtual']  ?? []);
            $colMap    = (array) $session['mapping'];
            $valueMap  = (array) ($session['value_mapping'] ?? []);
			$publishing = array_merge(
				[
					'state'        => 1,
					'access'       => 1,
					'publish_up'   => null,
					'publish_down' => null,
				],
				(array) ($session['publishing'] ?? [])
			);
            $params    = ComponentHelper::getParams('com_collector');

			// Créer l'entrée historique au premier batch si pas encore créée
			if (!$isDryRun && !$importHistoryId && $rowStart === (int) $session['first_line']) {
				$importHistoryId = $model->createImportHistory(
					$collection,
					$session['file'] ?? '',
					$session['duplicate_strategy'] ?? 'unik',
					$userId,
					false
				);
				$session['import_history_id'] = $importHistoryId;
				$model->saveSession($collection, $userId, $session);
			}

            if ($rowStart > $totalRows) {
                return ['success' => true, 'state' => 'end',
                        'imported' => $imported, 'skipped' => $skipped, 'merged' => $merged];
            }

            $fields = $model->getFields();

			$conflictResult = null;
			
			$importedItems = [];
			$mergedItems = [];
			
            for ($i = 0; $i < self::BATCH_SIZE; $i++) {
				$row = $rowStart + $i;
				if ($row > $totalRows) break;

				try {
					$rawRow = $allRows[$row] ?? null;
					if ($rawRow === null) continue; // ligne vide

					$updated = $this->refreshCreatedMappings($valueMap, $fields);
					if ($updated !== $valueMap) {
						$valueMap = $updated;
						$session['value_mapping'] = $valueMap;
					}

					$data     = $this->buildData($rawRow, $fields, $colMap, $valueMap, $collection);
					
					$updated = $this->refreshCreatedMappings($valueMap, $fields);
					if ($updated !== $valueMap) {
						$valueMap = $updated;
						$session['value_mapping'] = $valueMap;
					}

					$strategy      = $session['duplicate_strategy'] ?? 'unik';
					$compositeCols = (array) ($session['composite_fields'] ?? []);
					
					// ── Vérification intra-fichier (simulation uniquement) ───────────────────
					if ($isDryRun && !empty($compositeCols)) {
						foreach ($dryVirtual as $virtualEntry) {
							$match = true;
							foreach ($compositeCols as $col) {
								$importVal  = $this->normalizeComposite($data[$col]  ?? '', $fields, $col);
								$virtualVal = $this->normalizeComposite($virtualEntry[$col] ?? '', $fields, $col);
								if ($importVal !== $virtualVal) {
									$match = false;
									break;
								}
							}
							if ($match) {
								$dryConflicts++;
								$dryRunEntries[] = [
									'row'          => $row,
									'action'       => 'conflict',
									'intraFile'    => true,
									'importDisplay' => $model->getDisplayData($collection, $data, $fields) ?? [],
									'existingDisplay' => $virtualEntry['_display'] ?? [],
									'existingId'   => null,
									'existingRow'  => $virtualEntry['_row'] ?? null,
								];
								continue 2;
							}
						}
					}

					$existing = $model->checkDuplicate($collection, $data, $fields, $strategy, $compositeCols);
					
					if ($existing !== null) {
						if (is_array($existing)) {
							// Vérifier si un objet strictement identique existe parmi les matchs
							$skipIfIdenticalExists = $params->get('import_skip_if_identical_exists', 1);

							if ($skipIfIdenticalExists) {
								foreach ($existing as $ex) {
									if ($model->isIdentical($data, $ex, $fields)) {
										$skipped++;
										$session['current_row'] = $row + 1;
										$session['skipped']     = $skipped;
										$session['dry_run_virtual'] = $dryVirtual;
										if ($isDryRun) {

											$dryRunEntries[] = ['row' => $row, 'action' => 'skip_identical'];
											continue 2;
										}
										$this->persistSession($model, $collection, $userId, $session, $colMap, $valueMap);
										continue 2;
									}
								}
							}
							
							if ($isDryRun) {
								$dryMulticonflicts++;
								$matchList = [];
								foreach ($existing as $ex) {
									$matchList[] = [
										'id'      => (int) $ex->id,
										'display' => $model->getItemDisplayData($collection, (int) $ex->id, $fields),
									];
								}

								$dryRunEntries[] = [
									'row'           => $row,
									'action'        => 'multiconflict',
									'matches'       => $matchList,
									'importDisplay' => $model->getDisplayData($collection, $data, $fields),
								];
								continue;
							}
							
							// Plusieurs matchs — envoyer la liste pour que l'utilisateur choisisse
							$matchList = [];
							foreach ($existing as $ex) {
								$matchList[] = [
									'id'      => (int) $ex->id,
									'display' => $model->getItemDisplayData($collection, (int) $ex->id, $fields),
									'isIdentical' => $model->isIdentical($data, $ex, $fields),
								];
							}
							
							$session['current_row'] = $row;
							$session['imported']    = $imported;
							$session['skipped']     = $skipped;
							$session['merged']      = $merged;
							$session['errors']      = $errors;
							$conflictResult = [
								'success'         => true,
								'state'           => 'multiconflict',
								'conflictRow'     => $row,
								'multipleMatches' => true,
								'importData'      => $data,
								'importDisplay'   => $model->getDisplayData($collection, $data, $fields),
								'matches'         => $matchList,
								'imported'        => $imported,
								'skipped'         => $skipped,
								'merged'          => $merged,
								'errors'          => $errors,
							];
							break;
						} else {
							$isIdentical = $model->isIdentical($data, $existing, $fields);
							if ($isIdentical && $params->get('import_skip_identical', 0)) {
								$skipped++;
								$session['current_row'] = $row + 1;
								$session['skipped']     = $skipped;
								if ($isDryRun) {
									$dryRunEntries[] = ['row' => $row, 'action' => 'skip_identical'];
									continue;
								}
								continue;
							}
							$session['current_row'] = $row;
							$session['imported']    = $imported;
							$session['skipped']     = $skipped;
							$session['merged']      = $merged;
							$session['errors']      = $errors;

							if ($isDryRun) {
								$dryConflicts++;

								$dryRunEntries[] = [
									'row'             => $row,
									'action'          => 'conflict',
									'existingId'      => (int) $existing->id,
									'existingDisplay' => $model->getItemDisplayData($collection, (int) $existing->id, $fields),
									'importDisplay'   => $model->getDisplayData($collection, $data, $fields),
								];
								continue;
							}
							
							// Conflit simple — comportement actuel
							$conflictResult = [
								'success'         => true,
								'state'           => 'conflict',
								'conflictRow'     => $row,
								'multipleMatches' => false,
								'isIdentical'     => $isIdentical,
								'importData'      => $data,
								'importDisplay'   => $model->getDisplayData($collection, $data, $fields),
								'existingId'      => (int) $existing->id,
								'existingDisplay' => $model->getItemDisplayData($collection, (int) $existing->id, $fields),
								'imported'        => $imported,
								'skipped'         => $skipped,
								'merged'          => $merged,
								'errors'          => $errors,
								'dry_run'                => $isDryRun,
								'dry_run_conflicts'      => $dryConflicts,
								'dry_run_multiconflicts' => $dryMulticonflicts,
							];
							break;
						}
					}
					
					if ($isDryRun) {
						$imported++;
						$virtualEntry = array_intersect_key($data, array_flip($compositeCols));
						$virtualEntry['_display'] = $model->getDisplayData($collection, $data, $fields) ?? [];
						$virtualEntry['_row']     = $row;
						$dryVirtual[]             = $virtualEntry;
						$dryRunEntries[]          = ['row' => $row, 'action' => 'import'];
						continue;
					}

					$insertResult = $this->insertRow($collection, $data, $params, $publishing);
					if ($insertResult !== false) {
						$imported++;
						$importedItems[] = $insertResult;
						if (!$isDryRun && $importHistoryId) {
							$model->addImportHistoryItem(
								$importHistoryId,
								$insertResult['item_id'],
								'imported',
								null,
								$insertResult['version_after']
							);
						}
					} elseif ($imported === 0 && $i === 0) {
						return ['success' => false,
								'message' => 'Ligne ' . $row . ' : ' . ($this->lastInsertError ?: 'insertRow=false'),
								'debugData' => array_slice($data, 0, 5, true)];
					} else {
						$errors++;
					}

				} catch (\Throwable $e) {
					// L'exception est loguée mais l'import continue sur la ligne suivante
					error_log('ImportController insert row=' . $row . ' : ' . $e->getMessage());
					$errors++;
				}
			}

			if ($conflictResult !== null) {
				$this->persistSession($model, $collection, $userId, $session, $colMap, $valueMap);
				return $conflictResult;
			}

            $nextRow  = $rowStart + self::BATCH_SIZE;
            $isEnd    = $nextRow > $totalRows;

			$session['current_row'] = $nextRow;
            $session['imported']    = $imported;
            $session['skipped']     = $skipped;
            $session['merged']      = $merged;
			$session['errors']  = $errors;
			$session['dry_run_conflicts']      = $dryConflicts;
			$session['dry_run_multiconflicts'] = $dryMulticonflicts;
			$session['dry_run_virtual'] = $dryVirtual;
			if ($isDryRun && !empty($dryRunEntries)) {
				$existing = (array) ($session['dry_run_report'] ?? []);  // ← cast en array
				$session['dry_run_report'] = array_merge($existing, $dryRunEntries);
			}

			if (!$isDryRun && $importHistoryId) {
				$model->updateImportHistory($importHistoryId, $imported, $merged, $skipped, $errors);
			}
			
			if ($isDryRun && $importHistoryId) {
				$model->updateImportHistory(
					$importHistoryId,
					$imported, 0, $skipped, 0,
					$dryConflicts, $dryMulticonflicts
				);
			}

			$this->persistSession($model, $collection, $userId, $session, $colMap, $valueMap);
			
            return [
                'success'  => true,
                'state'    => $isEnd ? 'end' : 'next',
                'next'     => $nextRow,
                'imported' => $imported,
                'skipped'  => $skipped,
                'merged'   => $merged,
				'errors'   => $errors,
				'dry_run'         => $isDryRun,
				'dry_run_entries' => $dryRunEntries,
				'dry_run_conflicts'      => $dryConflicts,
				'dry_run_multiconflicts' => $dryMulticonflicts,
				'dry_run_virtual'        => $dryVirtual,
            ];
        });
    }
	
	private function normalizeComposite(string $value, array $fields, string $col): string
	{
		foreach ($fields as $field) {
			if ($field->_field->tablecolumn === $col) {
				return $field->normalizeForComparison($value);
			}
		}
		return $value;
	}
	
	private function refreshCreatedMappings(array $valueMap, array $fields): array
	{
		foreach ($fields as $field) {
			$col        = $field->_field->tablecolumn;
			$colMapping = $valueMap[$col] ?? null;
			if ($colMapping === null) continue;

			$updated = $field->refreshImportMapping($colMapping);
			if ($updated !== null) {
				$valueMap[$col] = $updated;
			}
		}
		return $valueMap;
	}
	
	private function mergeValueMaps(array $existing, array $current): array
	{
		foreach ($current as $col => &$colData) {
			if (!isset($colData['values'])) continue;
			$existingPairs = $existing[$col]['values'] ?? [];
			foreach ($colData['values'] as &$pair) {
				if (($pair['db'] ?? '') !== '') continue; // déjà résolu → pas toucher
				// Chercher l'ID correspondant dans la config existante
				foreach ($existingPairs as $ep) {
					if (mb_strtolower(trim((string)($ep['file'] ?? '')))
						=== mb_strtolower(trim((string)($pair['file'] ?? '')))
						&& ($ep['db'] ?? '') !== '') {
						$pair['db'] = $ep['db'];
						unset($pair['create'], $pair['create_label']);
						break;
					}
				}
			}
			unset($pair);
		}
		unset($colData);
		return $current;
	}
	
	private function persistSession(object $model, int $collection, int $userId,
                                array &$session, array $colMap, array $valueMap): void
	{
		$session['value_mapping'] = $valueMap;
		$model->saveSession($collection, $userId, $session);

		// Fusionner avec la config existante pour préserver les IDs résolus
		$existingConfig = $model->getConfig($collection) ?? [];
		$mergedValueMap  = $this->mergeValueMaps(
			$existingConfig['value_mapping'] ?? [],
			$valueMap
		);

		$model->saveConfig(
			$collection, $colMap, $mergedValueMap,
			$session['first_line']            ?? 2,
			$session['duplicate_strategy']    ?? 'unik',
			(array) ($session['composite_fields'] ?? [])
		);
	}

    /**
     * Résolution d'un conflit (skip / merge / import).
     * Appelé en AJAX depuis le JS après décision utilisateur.
     */
    public function resolve(): void
    {
        $this->sendJson(function () {
            if (!Session::checkToken('request')) {
                return ['success' => false, 'message' => Text::_('JINVALID_TOKEN')];
            }

            $app        = Factory::getApplication();
            $collection = $this->input->getInt('collection');
            $userId     = $app->getIdentity()->get('id');
            $model      = $this->getModel();

            $session = $model->getSession($collection, $userId);
            if (!$session) {
                return ['success' => false, 'message' => Text::_('COM_COLLECTOR_IMPORT_SESSION_NOT_FOUND')];
            }

            $decision   = $this->input->get('decision', 'skip', 'string');
            $existingId = $this->input->getInt('existingId');
            $rawImport  = $this->input->get('importData', [], 'RAW');
            $params     = ComponentHelper::getParams('com_collector');
            $fields     = $model->getFields();

            $validCols = array_map(fn($f) => $f->_field->tablecolumn, $fields);
            $data      = array_intersect_key((array) $rawImport, array_flip($validCols));
            $data['collection'] = $collection;
			
			$publishing = (array) ($session['publishing'] ?? [
				'state'        => 1,
				'access'       => 1,
				'publish_up'   => null,
				'publish_down' => null,
			]);
			
            $imported = $session['imported'];
            $skipped  = $session['skipped'];
            $merged   = $session['merged'];
			$errors   = $session['errors'] ?? 0;
			$importHistoryId = (int) ($session['import_history_id'] ?? 0);
			
			$importedItems = [];
			$mergedItems = [];

            try {
				switch ($decision) {
					case 'import':
						$insertResult = $this->insertRow($collection, $data, $params, $publishing);
						if ($insertResult !== false) {
							$imported++;
							$importedItems[] = $insertResult;
							if ($importHistoryId) {
								$model->addImportHistoryItem(
									$importHistoryId,
									$insertResult['item_id'],
									'imported',
									null,
									$insertResult['version_after']
								);
							}
						} else {
							$errors++;
						}
						break;
					case 'merge':
						$mergeResult = $model->mergeItem($collection, $existingId, $data, $params, $publishing);
						if ($mergeResult !== false) {
							$merged++;
							$mergedItems[] = $mergeResult;
							if ($importHistoryId) {
								$model->addImportHistoryItem(
									$importHistoryId,
									$mergeResult['item_id'],
									'merged',
									$mergeResult['version_before'],
									$mergeResult['version_after']
								);
							}
						} else {
							$errors++;
						}
						break;
					case 'skip':
					default:
						$skipped++;
						break;
				}
			} catch (\Throwable $e) {
				error_log('ImportController resolve row=' . $session['current_row'] . ' : ' . $e->getMessage());
				$errors++;
			}

            $nextRow = $session['current_row'] + 1;
            $isEnd   = $nextRow > $session['total_rows'];

            $session['current_row'] = $nextRow;
            $session['imported']    = $imported;
            $session['skipped']     = $skipped;
            $session['merged']      = $merged;
			$session['errors']      = $errors;
			
			if ($importHistoryId) {
				$model->updateImportHistory($importHistoryId, $imported, $merged, $skipped, $errors);
			}

            $model->saveSession($collection, $userId, $session);

            return [
                'success'  => true,
                'state'    => $isEnd ? 'end' : 'next',
                'next'     => $nextRow,
                'imported' => $imported,
                'skipped'  => $skipped,
                'merged'   => $merged,
				'errors'   => $errors,
            ];
        });
    }
	
	public function startdryrun(): void
	{
		if (!Session::checkToken('get')) {
			$this->setRedirect(Route::_('index.php?option=com_collector&view=import', false));
			return;
		}
		$collection = $this->input->getInt('collection');
		$userId     = Factory::getApplication()->getIdentity()->get('id');
		$model      = $this->getModel();
		$session    = $model->getSession($collection, $userId);

		if (!$session) {
			$this->setRedirect(Route::_('index.php?option=com_collector&view=import&collection=' . $collection, false));
			return;
		}

		// Réinitialiser les compteurs et activer le mode simulation
		$session['dry_run']                = true;
		$session['step']                   = 5;
		$session['imported']               = 0;
		$session['skipped']                = 0;
		$session['merged']                 = 0;
		$session['errors']                 = 0;
		$session['current_row']            = $session['first_line'];
		$session['dry_run_report']         = [];
		$session['dry_run_conflicts']      = 0;
		$session['dry_run_multiconflicts'] = 0;
		$session['dry_run_virtual']        = [];
		$importHistoryId = $model->createImportHistory(
			$collection,
			$session['file'] ?? '',
			$session['duplicate_strategy'] ?? 'unik',
			$userId,
			true  // dry_run = true
		);
		$session['import_history_id'] = $importHistoryId;
		
		$model->saveSession($collection, $userId, $session);

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=import&collection=' . $collection . '&step=5', false
		));
	}
	
	public function launchfromdryrun(): void
	{
		if (!Session::checkToken('get')) {
			$this->setRedirect(Route::_('index.php?option=com_collector&view=import', false));
			return;
		}

		$collection = $this->input->getInt('collection');
		$userId     = Factory::getApplication()->getIdentity()->get('id');
		$model      = $this->getModel();
		$session    = $model->getSession($collection, $userId);

		if (!$session) {
			$this->setRedirect(Route::_('index.php?option=com_collector&view=import&collection=' . $collection, false));
			return;
		}

		// Désactiver le mode simulation et réinitialiser les compteurs
		$session['dry_run']              = false;
		$session['dry_run_report']       = [];
		$session['dry_run_conflicts']    = 0;
		$session['dry_run_multiconflicts'] = 0;
		$session['import_history_id']    = 0;
		$session['imported']             = 0;
		$session['skipped']              = 0;
		$session['merged']               = 0;
		$session['errors']               = 0;
		$session['current_row']          = $session['first_line'];
		$model->saveSession($collection, $userId, $session);

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=import&collection=' . $collection . '&step=5', false
		));
	}

    // ── Helpers privés ────────────────────────────────────────────────────────

    private function sendJson(callable $callback): void
    {
        // Évite les timeouts PHP sur les gros fichiers
        @set_time_limit(300);
        $app = Factory::getApplication();
        try {
            $result = $callback();
        } catch (\Throwable $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }
        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->sendHeaders();
        echo json_encode($result);
        $app->close();
    }

    private function buildData(array $row, array $fields, array $colMap, array $valueMap, int $collection): array
    {
        $data = ['collection' => $collection];
        foreach ($fields as $field) {
            $col    = $field->_field->tablecolumn;
            $letter = strtoupper(trim($colMap[$col] ?? ''));
            $raw    = $letter !== '' ? (string) ($row[ImportReader::colIndex($letter)] ?? '') : '';
            $map    = $valueMap[$col] ?? [];
            $data[$col] = $field->getImportedValue($raw, (array) $map);
        }
		
        return $data;
    }

    private function insertRow(int $collection, array $data, object $params, array $publishing = []): array|false
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $table = new CollectorItemsTable($db);
        $table->initVersion($collection);
		
		// Appliquer les paramètres de publication
		$data['state']        = (int)   ($publishing['state']  ?? 1);
		$data['access']       = (int)   ($publishing['access'] ?? 1);
		$data['publish_up']   = $publishing['publish_up']   ?: null;
		$data['publish_down'] = $publishing['publish_down'] ?: null;
		
        if (!$table->bind($data)) {
            $this->lastInsertError = 'bind failed: ' . $table->getError();
            return false;
        }
        if (!$table->checkVersion()) {
            $this->lastInsertError = 'checkVersion failed: ' . $table->getError();
            return false;
        }
		
		$importDate          = Factory::getDate()->format(Text::_('DATE_FORMAT_LC2'));
		$table->modification = Text::sprintf('COM_COLLECTOR_IMPORT_VERSION_NOTE', $importDate);
        
        if (!(bool) $table->storeVersion(null, 1)) {
            $this->lastInsertError = 'storeVersion failed: ' . $table->getError();
            return false;
        }
        $this->lastInsertError = '';
        return [
			'item_id'       => (int) $table->id,
			'version_after' => (int) ($table->history->id ?? 0),
		];
    }

    /** @var string Dernière erreur d'insertion (debug) */
    private string $lastInsertError = '';

    private function getStep1Url(): string
    {
        return Route::_(
            'index.php?option=com_collector&view=import&collection=' . $this->input->getInt('collection'),
            false
        );
    }

    private function sanitize(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($name));
    }
}
