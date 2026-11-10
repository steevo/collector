<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

/**
 * Vérification des liens de fichiers.
 *
 * Les fichiers peuvent être rangés avec n'importe quel outil (gestionnaire de médias de Joomla,
 * FTP…). Ce modèle retrouve ensuite les liens cassés dans les objets (champs image, toutes versions)
 * et dans les listes prédéfinies, propose les fichiers de même nom trouvés dans images/ et corrige
 * les liens. Il liste aussi les fichiers de images/collector qui ne sont plus utilisés.
 */
class FilecheckModel extends BaseDatabaseModel
{
	/** Dossier dans lequel on cherche les fichiers déplacés */
	const SEARCH_ROOT = 'images';

	/** Extensions prises en compte pour les fichiers inutilisés */
	const FILE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'avif', 'pdf'];

	/** @var array|null  Index nom de fichier (minuscules) => chemins relatifs */
	private $index = null;

	/**
	 * Construit le rapport complet.
	 *
	 * @return  object  {broken: [], unused: [], stats: {}}
	 */
	public function getReport(): object
	{
		$references = $this->collectReferences();
		$this->buildIndex();

		$broken     = [];
		$usedPaths  = $this->defaults;
		$checked    = 0;

		foreach ($references as $path => $ref)
		{
			if (preg_match('#^(https?:)?//#i', $path))
			{
				continue;
			}

			$checked++;
			$usedPaths[$this->normalize($path)] = true;

			if (is_file(JPATH_ROOT . '/' . rawurldecode($path)))
			{
				continue;
			}

			$ref->path       = $path;
			$ref->candidates = $this->findCandidates($path);
			$broken[]        = $ref;
		}

		usort($broken, fn($a, $b) => strnatcasecmp($a->path, $b->path));

		// Fichiers de images/collector qui ne sont référencés nulle part
		$unused   = [];
		$prefix   = $this->getCollectorFolder() . '/';

		foreach ($this->index as $paths)
		{
			foreach ($paths as $rel)
			{
				if (stripos($rel, $prefix) !== 0 || isset($usedPaths[$this->normalize($rel)]))
				{
					continue;
				}

				$ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));

				if (in_array($ext, self::FILE_EXTENSIONS, true))
				{
					$unused[] = $rel;
				}
			}
		}

		natcasesort($unused);

		return (object) [
			'broken' => $broken,
			'unused' => array_values($unused),
			'stats'  => (object) [
				'checked'    => $checked,
				'broken'     => count($broken),
				'fixable'    => count(array_filter($broken, fn($b) => count($b->candidates) === 1)),
				'unused'     => count($unused),
			],
		];
	}

	/**
	 * Remplace un lien cassé par un autre fichier partout où il est utilisé.
	 *
	 * @param   string  $oldPath  Chemin enregistré (cassé)
	 * @param   string  $newPath  Chemin du fichier existant, relatif à la racine du site
	 *
	 * @return  int  Nombre d'enregistrements modifiés
	 *
	 * @throws  \RuntimeException  si le nouveau fichier n'existe pas ou sort du dossier images
	 */
	public function fixLink(string $oldPath, string $newPath): int
	{
		$newPath = $this->cleanRelative($newPath);

		if ($oldPath === '' || $newPath === '' || stripos($newPath, self::SEARCH_ROOT . '/') !== 0
			|| !is_file(JPATH_ROOT . '/' . $newPath))
		{
			throw new \RuntimeException('COM_COLLECTOR_FILECHECK_INVALID_TARGET');
		}

		$db      = $this->getDatabase();
		$updated = 0;
		$like    = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $oldPath);

		// Objets : la valeur d'un champ image est « chemin[#joomlaImage…]|description|titre|… ».
		// On remplace la première partie et on garde le reste.
		foreach ($this->getImageFields() as $field)
		{
			$table  = $db->quoteName('#__collector_items_history_' . (int) $field->collection);
			$column = $db->quoteName($field->tablecolumn);

			foreach ([$like . '|%', $like . '#%', $like] as $pattern)
			{
				$rest = 'SUBSTRING(' . $column . ', ' . (mb_strlen($oldPath) + 1) . ')';

				if ($pattern !== $like && str_ends_with($pattern, '#%'))
				{
					// Suffixe #joomlaImage obsolète : on le supprime, on garde ce qui suit le premier |
					$rest = "IF(LOCATE('|', " . $column . ') > 0, SUBSTRING(' . $column . ", LOCATE('|', " . $column . ')), \'\')';
				}

				try
				{
					$db->setQuery(
						'UPDATE ' . $table
						. ' SET ' . $column . ' = CONCAT(' . $db->quote($newPath) . ', ' . $rest . ')'
						. ' WHERE ' . $column . ' LIKE ' . $db->quote($pattern)
					)->execute();

					$updated += (int) $db->getAffectedRows();
				}
				catch (\Exception $e)
				{
					continue 2;
				}
			}
		}

		// Listes prédéfinies
		$query = $db->getQuery(true)
			->update($db->quoteName('#__collector_defined_content'))
			->set($db->quoteName('image') . ' = :new')
			->where($db->quoteName('image') . ' = :old')
			->bind(':new', $newPath)
			->bind(':old', $oldPath);
		$db->setQuery($query)->execute();
		$updated += (int) $db->getAffectedRows();

		// Images des collections
		$query = $db->getQuery(true)
			->update($db->quoteName('#__collector'))
			->set($db->quoteName('image') . ' = :new')
			->where($db->quoteName('image') . ' = :old')
			->bind(':new', $newPath)
			->bind(':old', $oldPath);
		$db->setQuery($query)->execute();
		$updated += (int) $db->getAffectedRows();

		return $updated;
	}

	/**
	 * Corrige tous les liens cassés pour lesquels un seul fichier de même nom existe.
	 *
	 * @return  array  [liens corrigés, enregistrements modifiés]
	 */
	public function autoFix(): array
	{
		$links   = 0;
		$records = 0;

		foreach ($this->getReport()->broken as $item)
		{
			if (count($item->candidates) !== 1)
			{
				continue;
			}

			$records += $this->fixLink($item->path, $item->candidates[0]);
			$links++;
		}

		return [$links, $records];
	}

	/**
	 * Rassemble toutes les références de fichiers, regroupées par chemin.
	 *
	 * @return  object[]  chemin => {items: [], defined: [], collections: [], versions: int}
	 */
	private function collectReferences(): array
	{
		$db   = $this->getDatabase();
		$refs = [];

		$get = function (string $path) use (&$refs) {
			if (!isset($refs[$path]))
			{
				$refs[$path] = (object) ['items' => [], 'defined' => [], 'collections' => [], 'versions' => 0];
			}

			return $refs[$path];
		};

		// Champs image des objets, toutes versions confondues
		foreach ($this->getImageFields() as $field)
		{
			$column = $db->quoteName('h.' . $field->tablecolumn);

			try
			{
				$db->setQuery(
					$db->getQuery(true)
						->select(['h.item', 'h.state', $column . ' AS value', 'i.fulltitle'])
						->from($db->quoteName('#__collector_items_history_' . (int) $field->collection, 'h'))
						->join('LEFT', $db->quoteName('#__collector_items', 'i'), 'i.id = h.item')
						->where($column . ' <> ' . $db->quote(''))
				);
				$rows = $db->loadObjectList();
			}
			catch (\Exception $e)
			{
				continue;
			}

			foreach ($rows as $row)
			{
				$path = $this->extractPath((string) $row->value);

				if ($path === '')
				{
					continue;
				}

				$ref = $get($path);
				$ref->versions++;
				$key = $field->collection . ':' . $row->item . ':' . $field->id;

				if (!isset($ref->items[$key]))
				{
					$ref->items[$key] = (object) [
						'collection' => (int) $field->collection,
						'item'       => (int) $row->item,
						'title'      => (string) $row->fulltitle,
						'field'      => $field->field,
						'current'    => false,
					];
				}

				// 1 = version active, 0 = en attente de validation
				if (in_array((int) $row->state, [0, 1], true))
				{
					$ref->items[$key]->current = true;
				}
			}

			// Image par défaut du champ : comptée comme utilisée, mais pas signalée si absente
			$attribs = json_decode((string) $field->attribs, true) ?: [];
			$default = $this->extractPath((string) ($attribs['default'] ?? ''));

			if ($default !== '')
			{
				$this->defaults[$this->normalize($default)] = true;
			}
		}

		// Listes prédéfinies
		$db->setQuery(
			$db->getQuery(true)
				->select(['c.id', 'c.content', 'c.image', 'c.defined', 'd.name'])
				->from($db->quoteName('#__collector_defined_content', 'c'))
				->join('LEFT', $db->quoteName('#__collector_defined', 'd'), 'd.id = c.defined')
				->where($db->quoteName('c.image') . ' <> ' . $db->quote(''))
		);

		foreach ($db->loadObjectList() as $row)
		{
			$ref = $get(trim($row->image));
			$ref->defined[] = (object) ['id' => (int) $row->id, 'content' => $row->content, 'list' => (string) $row->name, 'defined' => (int) $row->defined];
		}

		// Images des collections
		$db->setQuery(
			$db->getQuery(true)
				->select(['id', 'name', 'image'])
				->from($db->quoteName('#__collector'))
				->where($db->quoteName('image') . ' <> ' . $db->quote(''))
		);

		foreach ($db->loadObjectList() as $row)
		{
			$path = $this->extractPath((string) $row->image);

			if ($path !== '')
			{
				$get($path)->collections[] = (object) ['id' => (int) $row->id, 'name' => $row->name];
			}
		}

		foreach ($refs as $ref)
		{
			$ref->items = array_values($ref->items);
		}

		return $refs;
	}

	/** @var array  Images par défaut des champs (chemins normalisés) */
	private $defaults = [];

	/**
	 * Champs de type image de toutes les collections.
	 */
	private function getImageFields(): array
	{
		$db = $this->getDatabase();

		$db->setQuery(
			$db->getQuery(true)
				->select(['f.id', 'f.collection', 'f.field', 'f.tablecolumn', 'f.attribs'])
				->from($db->quoteName('#__collector_fields', 'f'))
				->join('INNER', $db->quoteName('#__collector_fields_type', 't'), 't.id = f.type')
				->where($db->quoteName('t.type') . ' = ' . $db->quote('image'))
		);

		return $db->loadObjectList() ?: [];
	}

	/**
	 * Extrait le chemin d'une valeur de champ image (« chemin#joomlaImage…|desc|titre… »).
	 */
	private function extractPath(string $value): string
	{
		$path = explode('|', $value)[0];
		$path = explode('#', $path)[0];

		return $this->cleanRelative($path);
	}

	private function cleanRelative(string $path): string
	{
		$path  = trim(str_replace('\\', '/', $path));
		$parts = array_filter(explode('/', $path), fn($p) => $p !== '' && $p !== '.' && $p !== '..');

		return implode('/', $parts);
	}

	private function normalize(string $path): string
	{
		return mb_strtolower(rawurldecode($this->cleanRelative($path)));
	}

	private function getCollectorFolder(): string
	{
		return \defined('COM_COLLECTOR_BASE')
			? $this->cleanRelative(substr(COM_COLLECTOR_BASE, strlen(JPATH_ROOT)))
			: 'images/collector';
	}

	/**
	 * Indexe tous les fichiers du dossier images par nom (en minuscules).
	 */
	private function buildIndex(): void
	{
		if ($this->index !== null)
		{
			return;
		}

		$this->index = [];
		$root        = JPATH_ROOT . '/' . self::SEARCH_ROOT;
		$rootLength  = strlen(JPATH_ROOT) + 1;

		if (!is_dir($root))
		{
			return;
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS)
		);

		// LEAVES_ONLY (par défaut) ne renvoie que les fichiers : pas de isFile(), coûteux sous Windows
		foreach ($iterator as $file)
		{
			if ($file->getFilename() === 'index.html')
			{
				continue;
			}

			$rel = str_replace('\\', '/', substr($file->getPathname(), $rootLength));
			$this->index[mb_strtolower($file->getFilename())][] = $rel;
		}
	}

	/**
	 * Fichiers existants portant le même nom qu'un lien cassé, ceux de images/collector en premier.
	 */
	private function findCandidates(string $path): array
	{
		$name       = mb_strtolower(basename(rawurldecode($path)));
		$candidates = $this->index[$name] ?? [];
		$prefix     = $this->getCollectorFolder() . '/';

		usort($candidates, function ($a, $b) use ($prefix) {
			$ia = stripos($a, $prefix) === 0 ? 0 : 1;
			$ib = stripos($b, $prefix) === 0 ? 0 : 1;

			return $ia <=> $ib ?: strnatcasecmp($a, $b);
		});

		return $candidates;
	}
}
