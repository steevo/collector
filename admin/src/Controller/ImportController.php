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
use Steevo\Component\Collector\Administrator\Classes\ImportSimilarity;
use Steevo\Component\Collector\Administrator\Classes\CollectorField;
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

    /** Décisions possibles face à un doublon */
    private const DECISIONS = ['skip', 'merge', 'import'];
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
        if (!Session::checkToken('get')) {
            $this->setMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect($this->getStep1Url());
            return;
        }

        $app        = Factory::getApplication();
        $userId     = $app->getIdentity()->get('id');
        $collection = $this->input->getInt('collection');
        $this->getModel()->deleteSession($collection, $userId);
        $this->setRedirect(Route::_(
            'index.php?option=com_collector&view=import&collection=' . $collection, false
        ));
    }

    /**
     * Abandon d'un import depuis le tableau de bord : supprime sa session
     * (la configuration et l'historique sont conservés ; les objets déjà
     * importés ou fusionnés restent, annulables depuis l'historique).
     */
    public function abandon(): void
    {
        $dashboard = Route::_('index.php?option=com_collector', false);

        if (!Session::checkToken('get')) {
            $this->setMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect($dashboard);
            return;
        }

        $app        = Factory::getApplication();
        $collection = $this->input->getInt('collection');
        // Session d'un autre administrateur possible : le tableau de bord les liste toutes
        $userId     = $this->input->getInt('user') ?: (int) $app->getIdentity()->get('id');

        if (!$app->getIdentity()->authorise('core.manage', 'com_collector')) {
            $this->setMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $this->setRedirect($dashboard);
            return;
        }

        $this->getModel()->deleteSession($collection, $userId);
        $this->setMessage(Text::_('COM_COLLECTOR_IMPORT_ABANDONED'));
        $this->setRedirect($dashboard);
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
			'similar_keys'       => $savedConfig['similar_keys']       ?? [],
			'scope_fields'       => $savedConfig['scope_fields']       ?? [],
			'similar_threshold'  => $savedConfig['similar_threshold']  ?? 80,
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

		// Rapprochement par ressemblance : champs suffisants et seuil (en %)
		$validCols = array_map(fn($f) => $f->_field->tablecolumn, $fields);
		$similarKeys = array_values(array_intersect(
			(array) $this->input->get('similar_keys', [], 'ARRAY'), $validCols
		));
		$similarThreshold = max(50, min(100, $this->input->getInt('similar_threshold', 80)));
		$session['similar_keys']      = $similarKeys;
		$session['similar_threshold'] = $similarThreshold;

		// Périmètre du fichier : champs qui délimitent les objets concernés (objets absents)
		$scopeFields = array_values(array_intersect(
			(array) $this->input->get('scope_fields', [], 'ARRAY'), $validCols
		));
		$session['scope_fields'] = $scopeFields;

		$model->saveSession($collection, $userId, $session);

        // Sauvegarder le mapping colonnes sans écraser le value_mapping existant
        $existingConfig  = $model->getConfig($collection);
        $existingValueMap = $existingConfig['value_mapping'] ?? [];

        $model->saveConfig($collection, $colMap, $existingValueMap, $firstLine,
                   $strategy, $compositeFields, $similarKeys, $similarThreshold, $scopeFields);

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

		// Import réel lancé directement (sans simulation) : repartir d'un état propre,
		// sans décisions enregistrées
		$session['dry_run']                = false;
		$session['dry_run_report']         = [];
		$session['dry_run_conflicts']      = 0;
		$session['dry_run_multiconflicts'] = 0;
		$session['dry_run_virtual']        = [];
		$session['import_history_id']      = 0;
		$session['imported']               = 0;
		$session['skipped']                = 0;
		$session['merged']                 = 0;
		$session['errors']                 = 0;
		$session['current_row']            = $session['first_line'];
		$session['decisions']              = [];
		$session['default_decision']       = 'ask';
		$session['similar_decision']       = 'ask';
		$session['intra_decision']         = 'ask';
		$session['own_rows']               = [];
		$session['claimed']                = [];
		$session['dry_run_matched']        = [];
		$session['dry_run_scope']          = [];
		$session['scope_values']           = [];
		$session['cand_rows']              = [];

		$model->saveSession($collection, $userId, $session);

		$this->setRedirect(Route::_(
			'index.php?option=com_collector&view=import&collection=' . $collection . '&step=5&start=1', false
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
			$drySimilar        = (int) ($session['dry_run_similar'] ?? 0);
			// Import réel : objets créés par cet import [id => ligne du fichier]
			$ownRows           = array_map('intval', (array) ($session['own_rows'] ?? []));
			// Objets en base déjà retenus par une ligne précédente : un objet = une ligne
			$claimed           = (array) ($session['claimed'] ?? []);
			// Objets en base retrouvés par le fichier, et valeurs communes à toutes les lignes
			// (pour lister à la fin les objets de même nature qu'aucune ligne n'a retrouvés)
			$dryMatched        = array_map('intval', (array) ($session['dry_run_matched'] ?? []));
			// Import réel : première ligne pour laquelle chaque objet a été trouvé [id => ligne]
			$candRows          = array_map('intval', (array) ($session['cand_rows'] ?? []));
			$dryScope          = (array) ($session['dry_run_scope'] ?? []);
			// Périmètre réglé pour la collection : valeurs présentes dans le fichier [colonne => [valeur => true]]
			$scopeFields       = (array) ($session['scope_fields'] ?? []);
			$scopeValues       = [];
			foreach ((array) ($session['scope_values'] ?? []) as $col => $values) {
				$scopeValues[$col] = array_fill_keys(array_map('strval', (array) $values), true);
			}
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
                if (!$isDryRun) {
                    $model->deleteSession($collection, $userId);
                }
                return ['success' => true, 'state' => 'end',
                        'imported' => $imported, 'skipped' => $skipped, 'merged' => $merged];
            }

            $fields = $model->getFields();
            // Champs présents dans le fichier : les seuls qui peuvent différer (un champ
            // comme Image, sans colonne, garde sa valeur en base et ne compte pas)
            $cmpFields = array_values(array_filter(
                $fields,
                fn($f) => trim((string) ($colMap[$f->_field->tablecolumn] ?? '')) !== ''
            ));

			$conflictResult = null;
			$autoLog        = []; // décisions appliquées automatiquement dans ce lot

			// Une simulation ne crée aucune valeur de liste ; un import réel note celles qu'il crée
			CollectorField::$deferCreation = $isDryRun;
			CollectorField::$createdValues = [];

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

					if (CollectorField::$createdValues) {
						if (!$isDryRun && $importHistoryId) {
							$model->addImportHistoryValues($importHistoryId, CollectorField::$createdValues);
						}
						CollectorField::$createdValues = [];
					}
					
					$updated = $this->refreshCreatedMappings($valueMap, $fields);
					if ($updated !== $valueMap) {
						$valueMap = $updated;
						$session['value_mapping'] = $valueMap;
					}

					$strategy      = $session['duplicate_strategy'] ?? 'unik';
					$compositeCols = (array) ($session['composite_fields'] ?? []);
					

					$existing = $model->checkDuplicate($collection, $data, $fields, $strategy, $compositeCols);

					// Un objet de la base ne correspond qu'à une seule ligne : on écarte ceux déjà
					// retenus par une ligne précédente. Les objets créés par cet import ne comptent
					// que si la base n'a plus rien de libre : ce sont alors des conflits entre lignes.
					$isFree   = fn($ex) => !isset($ownRows[(int) $ex->id]) && empty($claimed[(int) $ex->id]);
					$found    = $existing === null ? [] : (is_array($existing) ? $existing : [$existing]);
					$inBase   = array_values(array_filter($found, $isFree));

					// Aucun objet libre : objets dont la référence en base est incomplète
					// (« ** », « K*** ») et qui correspondent sur les autres champs → conflit
					if (!$inBase) {
						$withJokers = $model->checkDuplicate($collection, $data, $fields, $strategy, $compositeCols, true);
						$knownIds   = array_map(fn($ex) => (int) $ex->id, $found);
						$jokerFree  = array_values(array_filter(
							$withJokers === null ? [] : (is_array($withJokers) ? $withJokers : [$withJokers]),
							fn($ex) => $isFree($ex) && !in_array((int) $ex->id, $knownIds, true)
						));
						if ($jokerFree) {
							$found    = array_merge($found, $jokerFree);
							$inBase   = $jokerFree;
							$existing = $jokerFree;
						}
					}

					$allTaken = false;
					if ($existing !== null) {
						// Objets retrouvés (simulation et import réel) : pour lister à la fin les absents
						foreach ($found as $ex) {
							$dryMatched[] = (int) $ex->id;
							$candRows[(int) $ex->id] ??= $row;
						}
						$fromFile = array_values(array_filter($found, fn($ex) => isset($ownRows[(int) $ex->id])));
						if ($inBase) {
							$existing = count($inBase) === 1 ? $inBase[0] : $inBase;
						} elseif ($fromFile) {
							$existing = $fromFile[0];
						} else {
							$existing = null;
							$allTaken = true; // ses objets sont déjà pris par d'autres lignes
						}
					}

					foreach ($scopeFields as $col) {
						$value = (string) ($data[$col] ?? '');
						if ($value !== '' && $value !== '0' && !str_starts_with($value, CollectorField::NEW_PREFIX)) {
							$scopeValues[$col][$value] = true;
						}
					}

					// Nature commune des lignes (simulation et import réel), ex. Type = 405
					foreach ($this->comparisonCols($fields, $strategy, $compositeCols) as $col) {
						$value = (string) ($data[$col] ?? '');
						if (!array_key_exists($col, $dryScope)) {
							$dryScope[$col] = $value;
						} elseif ($dryScope[$col] !== $value) {
							$dryScope[$col] = null; // varie d'une ligne à l'autre
						}
					}

					// ── Vérification intra-fichier (simulation), si la base n'a rien trouvé :
					// même ordre que l'import réel (base, puis lignes du fichier, puis ressemblance)
					if ($isDryRun && $existing === null && !empty($compositeCols)) {
						foreach ($dryVirtual as $virtualEntry) {
							$match = true;
							foreach ($compositeCols as $col) {
								$importVal  = $this->normalizeComposite($data[$col]  ?? '', $fields, $col);
								$virtualVal = $this->normalizeComposite($virtualEntry[$col] ?? '', $fields, $col);
								if ($importVal === '') {
									continue; // comme la recherche réelle : une case vide ne compte pas
								}
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
									'diff'         => $model->getDiffFields($data, (object) ($virtualEntry['_data'] ?? []), $cmpFields),
									'existingId'   => null,
									'existingRow'  => $virtualEntry['_row'] ?? null,
								];
								continue 2;
							}
						}
					}

					// Aucun doublon strict : objets qui ressemblent (fabricant écrit autrement,
					// référence avec ou sans espace, année absente en base…) → à vérifier
					if ($existing === null) {
						$similar = ImportSimilarity::find($collection, $data, $fields,
							$this->comparisonCols($fields, $strategy, $compositeCols),
							array_merge(array_keys($ownRows), array_keys(array_filter($claimed))),
							(int) ($session['similar_threshold'] ?? 80) / 100,
							(array) ($session['similar_keys'] ?? []));

						if ($similar) {
							$candidates = array_map(fn($c) => $c + [
								'display' => $model->getItemDisplayData($collection, $c['id'], $fields),
							], $similar);

							foreach ($similar as $c) {
								$dryMatched[] = $c['id'];
								$candRows[(int) $c['id']] ??= $row;
							}

							if ($isDryRun) {
								$drySimilar++;
								$dryRunEntries[] = [
									'row'           => $row,
									'action'        => 'similar',
									'candidates'    => $candidates,
									'importDisplay' => $model->getDisplayData($collection, $data, $fields),
								];
								continue;
							}

							$auto = $this->autoDecisionSimilar($session, $row, array_column($similar, 'id'));
							if ($auto !== null) {
								$this->applyDecision($auto['action'], $auto['target'], $data, $collection, $publishing, $params,
									$model, $importHistoryId, $imported, $merged, $skipped, $errors, $auto['fields'] ?? null);
								if ($auto['action'] === 'import' && $this->lastCreatedItemId) {
									$ownRows[$this->lastCreatedItemId] = $row;
								} elseif ($auto['action'] !== 'import' && !empty($auto['target'])) {
									$claimed[(int) $auto['target']] = true;
								}
								$autoLog[] = ['row' => $row] + $auto;
								continue;
							}

							$session['current_row'] = $row;
							$session['imported']    = $imported;
							$session['skipped']     = $skipped;
							$session['merged']      = $merged;
							$session['errors']      = $errors;
							$conflictResult = [
								'success'         => true,
								'state'           => 'conflict',
								'similar'         => true,
								'score'           => $similar[0]['score'],
								'conflictRow'     => $row,
								'multipleMatches' => false,
								'isIdentical'     => false,
								'importData'      => $data,
								'importDisplay'   => $model->getDisplayData($collection, $data, $fields),
								'existingId'      => $similar[0]['id'],
								'existingDisplay' => $candidates[0]['display'],
								'imported'        => $imported,
								'skipped'         => $skipped,
								'merged'          => $merged,
								'errors'          => $errors,
							];
							break;
						}
					}
					
					if ($existing !== null) {
						if (is_array($existing)) {
							// Vérifier si un objet strictement identique existe parmi les matchs
							$skipIfIdenticalExists = $params->get('import_skip_if_identical_exists', 1);

							if ($skipIfIdenticalExists) {
								foreach ($existing as $ex) {
									if ($model->isIdentical($data, $ex, $cmpFields)) {
										$claimed[(int) $ex->id] = true;
										$session['claimed']     = $claimed;
										$skipped++;
										$session['current_row'] = $row + 1;
										$session['skipped']     = $skipped;
										$session['dry_run_virtual'] = $dryVirtual;
										if ($isDryRun) {

											$dryRunEntries[] = ['row' => $row, 'action' => 'skip_identical', 'existingId' => (int) $ex->id];
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
										'diff'    => $model->getDiffFields($data, $ex, $cmpFields),
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
							
							// Décision enregistrée (rapport de simulation ou « faire de même ») ?
							$auto = $this->autoDecision($session, $row, array_map(fn($ex) => (int) $ex->id, $existing));
							if ($auto !== null) {
								$this->applyDecision($auto['action'], $auto['target'], $data, $collection, $publishing, $params,
									$model, $importHistoryId, $imported, $merged, $skipped, $errors, $auto['fields'] ?? null);
								if ($auto['action'] === 'import' && $this->lastCreatedItemId) {
									$ownRows[$this->lastCreatedItemId] = $row;
								} elseif ($auto['action'] !== 'import' && !empty($auto['target'])) {
									$claimed[(int) $auto['target']] = true;
								}
								$autoLog[] = ['row' => $row] + $auto;
								continue;
							}

							// Plusieurs matchs — envoyer la liste pour que l'utilisateur choisisse
							$matchList = [];
							foreach ($existing as $ex) {
								$matchList[] = [
									'id'      => (int) $ex->id,
									'display' => $model->getItemDisplayData($collection, (int) $ex->id, $fields),
									'isIdentical' => $model->isIdentical($data, $ex, $cmpFields),
									'diff'        => $model->getDiffFields($data, $ex, $cmpFields),
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
							$isIdentical = $model->isIdentical($data, $existing, $cmpFields);
							if ($isIdentical && $params->get('import_skip_identical', 0)) {
								$claimed[(int) $existing->id] = true;
								$skipped++;
								$session['current_row'] = $row + 1;
								$session['skipped']     = $skipped;
								if ($isDryRun) {
									$dryRunEntries[] = ['row' => $row, 'action' => 'skip_identical', 'existingId' => (int) $existing->id];
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
									'diff'            => $model->getDiffFields($data, $existing, $cmpFields),
									'importDisplay'   => $model->getDisplayData($collection, $data, $fields),
								];
								continue;
							}
							
							// Objet créé plus haut par ce même import : conflit entre deux lignes du fichier
							$intraRow = $ownRows[(int) $existing->id] ?? null;
							if ($intraRow !== null) {
								$auto = $this->autoDecisionIntra($session, $row);
								if ($auto !== null) {
									$auto['target'] = (int) $existing->id;
									$this->applyDecision($auto['action'], (int) $existing->id, $data, $collection, $publishing, $params,
										$model, $importHistoryId, $imported, $merged, $skipped, $errors, $auto['fields'] ?? null);
									if ($auto['action'] === 'import' && $this->lastCreatedItemId) {
										$ownRows[$this->lastCreatedItemId] = $row;
									}
									$autoLog[] = ['row' => $row] + $auto;
									continue;
								}
								$session['own_rows'] = $ownRows;
								$conflictResult = [
									'success'         => true,
									'state'           => 'conflict',
									'intraFile'       => true,
									'existingRow'     => $intraRow,
									'conflictRow'     => $row,
									'multipleMatches' => false,
									'isIdentical'     => $isIdentical,
									'importData'      => $data,
									'importDisplay'   => $model->getDisplayData($collection, $data, $fields),
									'existingId'      => (int) $existing->id,
									'existingDisplay' => $model->getItemDisplayData($collection, (int) $existing->id, $fields),
									'diff'            => $model->getDiffFields($data, $existing, $cmpFields),
									'imported'        => $imported,
									'skipped'         => $skipped,
									'merged'          => $merged,
									'errors'          => $errors,
								];
								break;
							}

							// Décision enregistrée (rapport de simulation ou « faire de même ») ?
							$auto = $this->autoDecision($session, $row, [(int) $existing->id]);
							if ($auto !== null) {
								$this->applyDecision($auto['action'], $auto['target'], $data, $collection, $publishing, $params,
									$model, $importHistoryId, $imported, $merged, $skipped, $errors, $auto['fields'] ?? null);
								if ($auto['action'] === 'import' && $this->lastCreatedItemId) {
									$ownRows[$this->lastCreatedItemId] = $row;
								} elseif ($auto['action'] !== 'import' && !empty($auto['target'])) {
									$claimed[(int) $auto['target']] = true;
								}
								$autoLog[] = ['row' => $row] + $auto;
								continue;
							}

							// Conflit simple : demander à l'utilisateur
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
								'diff'            => $model->getDiffFields($data, $existing, $cmpFields),
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
						$virtualEntry['_data']    = $data;
						$dryVirtual[]             = $virtualEntry;
						$dryRunEntries[]          = ['row' => $row, 'action' => 'import'] + ($allTaken ? ['reassigned' => true] : []);
						continue;
					}

					$insertResult = $this->insertRow($collection, $data, $params, $publishing);
					if ($insertResult !== false) {
						$imported++;
						$importedItems[] = $insertResult;
						$ownRows[(int) $insertResult['item_id']] = $row;
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
				// Ce qui a été décidé dans le lot avant ce conflit reste acquis
				$session['own_rows']        = $ownRows;
				$session['claimed']         = $claimed;
				$session['dry_run_matched'] = array_values(array_unique($dryMatched));
				$session['cand_rows']       = $candRows;
				$session['dry_run_scope']   = $dryScope;
				$session['scope_values']    = $this->scopeSets($scopeValues);
				if ($importHistoryId) {
					$model->updateImportHistory($importHistoryId, $imported, $merged, $skipped, $errors);
				}
				$this->persistSession($model, $collection, $userId, $session, $colMap, $valueMap);
				$conflictResult['auto'] = $autoLog;
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
			$session['dry_run_similar'] = $drySimilar;
			$session['own_rows']        = $ownRows;
			$session['claimed']         = $claimed;
			$session['dry_run_matched'] = array_values(array_unique($dryMatched));
			$session['cand_rows']       = $candRows;
			$session['dry_run_scope']   = $dryScope;
			$session['scope_values']    = $this->scopeSets($scopeValues);

			// Fin de simulation : objets de même nature que le fichier qu'aucune ligne n'a retrouvés
			$dryMissing = null;
			if ($isDryRun && $isEnd) {
				$dryMissing = $model->getMissingItems($collection, $dryScope, $session['dry_run_matched'], $fields,
					$session['scope_values']);
				$session['dry_run_missing'] = $dryMissing;
			}

			if ($isDryRun && !empty($dryRunEntries)) {
				$existing = (array) ($session['dry_run_report'] ?? []);  // ← cast en array
				$session['dry_run_report'] = array_merge($existing, $dryRunEntries);
			}

			// Fin de simulation : un objet de la base pour une seule ligne, sur tout le fichier
			$dryReportFull = null;
			if ($isDryRun && $isEnd) {
				[$session['dry_run_report'], $counts] = $this->reconcileDryRun((array) $session['dry_run_report'], $claimed);
				$imported          = $counts['import'];
				$dryConflicts      = $counts['conflict'];
				$dryMulticonflicts = $counts['multiconflict'];
				$drySimilar        = $counts['similar'];
				$session['imported']               = $imported;
				$session['dry_run_conflicts']      = $dryConflicts;
				$session['dry_run_multiconflicts'] = $dryMulticonflicts;
				$session['dry_run_similar']        = $drySimilar;
				$dryReportFull = $session['dry_run_report'];
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

			// Import réel terminé : la session n'a plus lieu d'être (la configuration
			// et l'historique sont conservés). Une simulation garde sa session pour
			// son rapport et pour « Lancer l'import réel ».
			$missing = null;
			if ($isEnd && !$isDryRun) {
				$missing = $this->realImportMissing($model, $collection, $session, $fields);
				$model->deleteSession($collection, $userId);
			}

            return [
                'success'  => true,
                'state'    => $isEnd ? 'end' : 'next',
                'missing'    => $missing,
                'history_id' => $importHistoryId,
                'next'     => $nextRow,
                'imported' => $imported,
                'skipped'  => $skipped,
                'merged'   => $merged,
				'errors'   => $errors,
				'auto'     => $autoLog,
				'dry_run'         => $isDryRun,
				'dry_run_entries' => $dryRunEntries,
				'dry_run_conflicts'      => $dryConflicts,
				'dry_run_multiconflicts' => $dryMulticonflicts,
				'dry_run_similar'        => $drySimilar,
				'dry_run_missing'        => $dryMissing,
				'dry_run_report_full'    => $dryReportFull,
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

		// Un état non enregistré ferait retraiter le même lot indéfiniment : on s'arrête
		if (!$model->saveSession($collection, $userId, $session)) {
			throw new \RuntimeException(Text::sprintf('COM_COLLECTOR_IMPORT_SESSION_SAVE_ERROR', $model->getError()));
		}

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
			
			// Valeurs par défaut complétées (import lancé depuis la simulation : pas d'étape 4 enregistrée)
			$publishing = array_merge([
				'state'        => 1,
				'access'       => 1,
				'publish_up'   => null,
				'publish_down' => null,
			], (array) ($session['publishing'] ?? []));
			
            $imported = $session['imported'];
            $skipped  = $session['skipped'];
            $merged   = $session['merged'];
			$errors   = $session['errors'] ?? 0;
			$importHistoryId = (int) ($session['import_history_id'] ?? 0);
			
            if (!in_array($decision, self::DECISIONS, true)) {
                $decision = 'skip';
            }

            // Fusion : la fenêtre n'envoie que les valeurs cochées « prendre du fichier »
            $mergeFields = $decision === 'merge' ? array_keys(array_diff_key($data, ['collection' => 1])) : null;

            $this->applyDecision($decision, $existingId, $data, $collection, $publishing, $params,
                $model, $importHistoryId, $imported, $merged, $skipped, $errors, $mergeFields);
            if ($decision === 'import' && $this->lastCreatedItemId) {
                $ownRows = array_map('intval', (array) ($session['own_rows'] ?? []));
                $ownRows[$this->lastCreatedItemId] = (int) $session['current_row'];
                $session['own_rows'] = $ownRows;
            }

            if ($decision !== 'import' && $existingId > 0) {
                $claimed = (array) ($session['claimed'] ?? []);
                $claimed[$existingId] = true;
                $session['claimed'] = $claimed;
            }

            // « Faire de même pour les conflits suivants » : devient la règle générale
            if ($this->input->getInt('apply_to_next')) {
                if ($this->input->getCmd('kind') === 'similar') {
                    $session['similar_decision'] = $decision;
                } elseif ($this->input->getCmd('kind') === 'intra') {
                    $session['intra_decision'] = $decision;
                } else {
                    $session['default_decision'] = $decision;
                }
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

            $missing = null;
            if ($isEnd) {
                // Dernière ligne décidée : import réel terminé
                $missing = $this->realImportMissing($model, $collection, $session, $fields);
                $model->deleteSession($collection, $userId);
            } elseif (!$model->saveSession($collection, $userId, $session)) {
                return ['success' => false,
                        'message' => Text::sprintf('COM_COLLECTOR_IMPORT_SESSION_SAVE_ERROR', $model->getError())];
            }

            return [
                'success'  => true,
                'state'    => $isEnd ? 'end' : 'next',
                'missing'    => $missing,
                'history_id' => $importHistoryId,
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
		$session['decisions']              = [];
		$session['default_decision']       = 'ask';
		$session['similar_decision']       = 'ask';
		$session['intra_decision']         = 'ask';
		$session['own_rows']               = [];
		$session['claimed']                = [];
		$session['dry_run_similar']        = 0;
		$session['dry_run_matched']        = [];
		$session['dry_run_scope']          = [];
		$session['scope_values']           = [];
		$session['cand_rows']              = [];
		$session['dry_run_missing']        = [];
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
			'index.php?option=com_collector&view=import&collection=' . $collection . '&step=5&start=1', false
		));
	}
	
	public function launchfromdryrun(): void
	{
		if (!Session::checkToken('request')) {
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

		// Décisions prises dans le rapport de simulation :
		//   decisions[ligne] = 'skip' | 'import' | 'merge:ID' | 'merge:'  (absent = règle générale)
		$decisions   = [];
		$mergeFields = (array) $this->input->post->get('merge_fields', [], 'ARRAY');
		foreach ((array) $this->input->post->get('decisions', [], 'ARRAY') as $row => $value) {
			[$action, $target] = array_pad(explode(':', (string) $value, 2), 2, '');
			if ((int) $row > 0 && in_array($action, self::DECISIONS, true)) {
				$decisions[(int) $row] = [
					'action' => $action,
					'target' => $action === 'merge' && (int) $target > 0 ? (int) $target : null,
				];
				// Fusion : seulement les champs cochés « prendre du fichier » (fenêtre Comparer)
				$fields = trim((string) ($mergeFields[$row] ?? ''));
				if ($action === 'merge' && $fields !== '') {
					$decisions[(int) $row]['fields'] = array_values(array_filter(explode(',', $fields)));
				}
			}
		}
		// Lignes dont tous les objets possibles sont retenus par d'autres lignes : à importer
		// (l'import réel, qui avance ligne par ligne, ne peut pas le savoir à l'avance)
		foreach ((array) ($session['dry_run_report'] ?? []) as $entry) {
			if (!empty($entry['reassigned']) && !isset($decisions[(int) $entry['row']])) {
				$decisions[(int) $entry['row']] = ['action' => 'import', 'target' => null];
			}
		}

		$default = $this->input->post->getCmd('default_decision', 'ask');
		$session['decisions']        = $decisions;
		$session['default_decision'] = in_array($default, self::DECISIONS, true) ? $default : 'ask';
		$similarRule = $this->input->post->getCmd('similar_decision', 'ask');
		$session['similar_decision'] = in_array($similarRule, self::DECISIONS, true) ? $similarRule : 'ask';
		$intraRule = $this->input->post->getCmd('intra_decision', 'ask');
		$session['intra_decision']   = in_array($intraRule, self::DECISIONS, true) ? $intraRule : 'ask';
		$session['own_rows']         = [];
		$session['claimed']          = [];
		$session['dry_run_matched']  = [];
		$session['dry_run_scope']    = [];
		$session['scope_values']     = [];
		$session['cand_rows']        = [];

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
			'index.php?option=com_collector&view=import&collection=' . $collection . '&step=5&start=1', false
		));
	}

    // ── Helpers privés ────────────────────────────────────────────────────────

    /**
     * Décision à appliquer sans demander, ou null s'il faut demander à l'utilisateur.
     *
     * Décision de la ligne (rapport de simulation), sinon règle générale. Par prudence,
     * on demande quand même si la situation n'est plus celle de la simulation :
     * fusion vers un objet qui ne correspond plus, ou fusion sans cible alors que
     * plusieurs objets correspondent.
     *
     * @param  int[]  $matchIds  Objets en base correspondant à la ligne
     * @return array{action: string, target: int}|null
     */
    private function autoDecision(array $session, int $row, array $matchIds): ?array
    {
        $decision = $session['decisions'][$row] ?? null;

        if (is_array($decision) && in_array($decision['action'] ?? '', self::DECISIONS, true)) {
            $action = $decision['action'];
            $target = (int) ($decision['target'] ?? 0);

            if ($action !== 'merge') {
                return ['action' => $action, 'target' => count($matchIds) === 1 ? (int) $matchIds[0] : 0];
            }
            $fields = isset($decision['fields']) ? ['fields' => (array) $decision['fields']] : [];
            if ($target > 0) {
                return in_array($target, $matchIds, true) ? ['action' => 'merge', 'target' => $target] + $fields : null;
            }
            // Fusion sans cible (conflit entre deux lignes du fichier) : un seul objet possible
            return count($matchIds) === 1 ? ['action' => 'merge', 'target' => $matchIds[0]] + $fields : null;
        }

        $default = (string) ($session['default_decision'] ?? 'ask');

        if ($default === 'skip' || $default === 'import') {
            return ['action' => $default, 'target' => 0];
        }
        if ($default === 'merge' && count($matchIds) === 1) {
            return ['action' => 'merge', 'target' => $matchIds[0]];
        }

        return null;
    }

    /**
     * Décision pour un objet ressemblant (pas de doublon strict), ou null s'il faut demander.
     * Décision de la ligne (rapport de simulation), sinon règle des objets ressemblants.
     *
     * @param  int[]  $candidateIds  Objets ressemblants, du plus au moins ressemblant
     */
    private function autoDecisionSimilar(array $session, int $row, array $candidateIds): ?array
    {
        $decision = $session['decisions'][$row] ?? null;

        if (is_array($decision) && in_array($decision['action'] ?? '', self::DECISIONS, true)) {
            $action = $decision['action'];
            $target = (int) ($decision['target'] ?? 0);

            if ($action !== 'merge') {
                return ['action' => $action, 'target' => 0];
            }

            return in_array($target, $candidateIds, true)
                ? ['action' => 'merge', 'target' => $target] + (isset($decision['fields']) ? ['fields' => (array) $decision['fields']] : [])
                : null;
        }

        $rule = (string) ($session['similar_decision'] ?? 'ask');

        if ($rule === 'skip' || $rule === 'import') {
            return ['action' => $rule, 'target' => 0];
        }
        if ($rule === 'merge' && $candidateIds) {
            return ['action' => 'merge', 'target' => $candidateIds[0]];
        }

        return null;
    }

    /**
     * Fin de simulation : chaque objet de la base ne doit servir qu'à une seule ligne.
     * Les lignes identiques ont déjà retenu le leur ($claimed) ; on parcourt ensuite le
     * rapport dans l'ordre du fichier :
     *  - conflit simple : son objet est retenu, sauf s'il l'est déjà → la ligne est à importer ;
     *  - conflit multiple : on retire les objets déjà retenus (1 restant → conflit simple,
     *    aucun → à importer) ;
     *  - objet ressemblant : idem pour les objets proposés.
     *
     * @return array{0: array, 1: array<string, int>}  Rapport corrigé, nombre par catégorie
     */
    private function reconcileDryRun(array $report, array $claimed): array
    {
        $taken = array_filter($claimed);
        usort($report, fn($a, $b) => (int) $a['row'] <=> (int) $b['row']);
        $toImport = fn($e) => ['row' => $e['row'], 'action' => 'import', 'reassigned' => true];

        foreach ($report as &$e) {
            $action = $e['action'] ?? '';

            if ($action === 'conflict' && empty($e['intraFile']) && !empty($e['existingId'])) {
                $id = (int) $e['existingId'];
                if (!empty($taken[$id])) {
                    $e = $toImport($e);
                } else {
                    $taken[$id] = true;
                }
            } elseif ($action === 'multiconflict') {
                $free = array_values(array_filter($e['matches'] ?? [], fn($m) => empty($taken[(int) $m['id']])));
                if (!$free) {
                    $e = $toImport($e);
                } elseif (count($free) === 1) {
                    $m = $free[0];
                    $taken[(int) $m['id']] = true;
                    $e = [
                        'row'             => $e['row'],
                        'action'          => 'conflict',
                        'existingId'      => (int) $m['id'],
                        'existingDisplay' => $m['display'] ?? [],
                        'diff'            => $m['diff'] ?? null,
                        'importDisplay'   => $e['importDisplay'] ?? [],
                    ];
                } else {
                    $e['matches'] = $free;
                }
            }
        }
        unset($e);

        // Ensuite seulement, les objets ressemblants : un objet retenu par une correspondance
        // stricte (même plus bas dans le fichier) n'est plus proposé
        foreach ($report as &$e) {
            if (($e['action'] ?? '') !== 'similar') {
                continue;
            }
            $free = array_values(array_filter($e['candidates'] ?? [], fn($c) => empty($taken[(int) $c['id']])));
            if (!$free) {
                $e = $toImport($e);
            } else {
                $e['candidates'] = $free;
            }
        }
        unset($e);

        $counts = ['import' => 0, 'conflict' => 0, 'multiconflict' => 0, 'similar' => 0];
        foreach ($report as $e) {
            $a = $e['action'] ?? '';
            if (isset($counts[$a])) {
                $counts[$a]++;
            }
        }

        return [$report, $counts];
    }

    /** @var int Objet créé par la dernière décision « importer comme nouveau » (0 sinon) */
    private int $lastCreatedItemId = 0;

    /**
     * Décision pour un conflit entre deux lignes du fichier (l'objet en base a été créé
     * par une ligne précédente du même import), ou null s'il faut demander.
     * Décision de la ligne (rapport de simulation), sinon règle des conflits entre lignes.
     */
    private function autoDecisionIntra(array $session, int $row): ?array
    {
        $decision = $session['decisions'][$row] ?? null;

        if (is_array($decision) && in_array($decision['action'] ?? '', self::DECISIONS, true)) {
            return ['action' => $decision['action'], 'target' => 0]
                + (isset($decision['fields']) ? ['fields' => (array) $decision['fields']] : []);
        }

        $rule = (string) ($session['intra_decision'] ?? 'ask');

        return in_array($rule, self::DECISIONS, true) ? ['action' => $rule, 'target' => 0] : null;
    }

    /**
     * Colonnes de comparaison des doublons : champs choisis (stratégie « composite »)
     * ou champs marqués uniques.
     */
    private function comparisonCols(array $fields, string $strategy, array $compositeCols): array
    {
        if ($strategy === 'composite') {
            return array_values($compositeCols);
        }

        return array_values(array_filter(array_map(
            fn($f) => $f->_field->unik ? $f->_field->tablecolumn : null,
            $fields
        )));
    }

    /**
     * Applique une décision face à un doublon et met à jour compteurs et historique.
     * Fusion : les valeurs non vides de $data remplacent celles de l'objet existant.
     */
    private function applyDecision(string $decision, int $existingId, array $data, int $collection,
                                   array $publishing, object $params, object $model, int $importHistoryId,
                                   int &$imported, int &$merged, int &$skipped, int &$errors,
                                   ?array $fields = null): void
    {
        $this->lastCreatedItemId = 0;

        // Fusion : seulement les valeurs choisies « prendre du fichier » ; les autres restent.
        // Une valeur vide choisie explicitement efface celle de l'objet.
        if ($decision === 'merge' && $fields !== null) {
            $data = array_intersect_key($data, array_flip($fields)) + ['collection' => $data['collection'] ?? $collection];
        }

        try {
            switch ($decision) {
                case 'import':
                    $result = $this->insertRow($collection, $data, $params, $publishing);
                    if ($result === false) {
                        $errors++;
                        break;
                    }
                    $imported++;
                    $this->lastCreatedItemId = (int) $result['item_id'];
                    if ($importHistoryId) {
                        $model->addImportHistoryItem($importHistoryId, $result['item_id'], 'imported',
                            null, $result['version_after']);
                    }
                    break;

                case 'merge':
                    $result = $model->mergeItem($collection, $existingId, $data, $params, $publishing, $fields !== null);
                    if ($result === false) {
                        $errors++;
                        break;
                    }
                    $merged++;
                    if ($importHistoryId) {
                        $model->addImportHistoryItem($importHistoryId, $result['item_id'], 'merged',
                            $result['version_before'], $result['version_after']);
                    }
                    break;

                default:
                    $skipped++;
            }
        } catch (\Throwable $e) {
            error_log('ImportController ' . $decision . ' item=' . $existingId . ' : ' . $e->getMessage());
            $errors++;
        }
    }

    /**
     * Fin d'import réel : objets de la base de même nature que le fichier qu'aucune ligne
     * n'a retenus (ni identiques, ni fusionnés, ni désignés « c'est cet objet », ni créés).
     * Un objet seulement trouvé pour une ligne, puis écarté, y figure avec cette ligne.
     * Ils sont notés dans l'historique, où l'on peut les mettre à la corbeille.
     */
    private function realImportMissing(object $model, int $collection, array $session, array $fields): array
    {
        $kept = array_merge(
            array_map('intval', array_keys(array_filter((array) ($session['claimed'] ?? [])))),
            array_map('intval', array_keys((array) ($session['own_rows'] ?? [])))
        );

        $missing = $model->getMissingItems($collection, (array) ($session['dry_run_scope'] ?? []),
            array_values(array_unique($kept)), $fields, (array) ($session['scope_values'] ?? []));

        // Ligne pour laquelle l'objet avait été trouvé, puis écarté
        $candRows = array_map('intval', (array) ($session['cand_rows'] ?? []));
        $missing['rows'] = array_intersect_key($candRows, array_flip($missing['ids'] ?? []));
        foreach ($missing['items'] as &$item) {
            $item['row'] = $candRows[$item['id']] ?? null;
        }
        unset($item);

        $historyId = (int) ($session['import_history_id'] ?? 0);
        if ($historyId) {
            $model->saveImportHistoryMissing($historyId, $missing);
        }
        unset($missing['ids']);

        return $missing;
    }

    /** [colonne => [valeur => true]] → [colonne => [valeurs]] */
    private function scopeSets(array $scopeValues): array
    {
        return array_map(fn($values) => array_map('strval', array_keys($values)), $scopeValues);
    }

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
            if ($letter === '') {
                // Champ absent du fichier : rien à importer, la valeur en base est conservée
                $data[$col] = '';
                continue;
            }
            $raw    = (string) ($row[ImportReader::colIndex($letter)] ?? '');
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
		$data['publish_up']   = ($publishing['publish_up']   ?? null) ?: null;
		$data['publish_down'] = ($publishing['publish_down'] ?? null) ?: null;
		
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
