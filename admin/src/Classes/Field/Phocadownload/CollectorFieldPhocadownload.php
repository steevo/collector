<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Classes\Field\Phocadownload;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Steevo\Component\Collector\Administrator\Classes\CollectorField;

defined('_JEXEC') or die;

/**
 * Champ « Phoca Download » : un fichier de Phoca Download, affiché avec un lien de téléchargement.
 *
 * La valeur enregistrée est l'identifiant du fichier. Le téléchargement passe par Phoca Download,
 * qui vérifie les droits d'accès et compte les téléchargements.
 */
class CollectorFieldPhocadownload extends CollectorField
{
	public $type = 'phocadownload';

	/** Fichiers déjà lus, par identifiant */
	protected static $files = [];

	public function __construct($collection, $field, $item = 0)
	{
		$this->_collection = $collection;
		$this->_item       = $item;
		$this->_field      = $field;
	}

	/**
	 * Réglages du champ, avec les valeurs par défaut de field.xml.
	 */
	protected function attribs(): array
	{
		return (array) ($this->_field->attribs ?? []) + [
			'listing_display' => 'link',
			'detail_display'  => 'button',
			'show_size'       => 1,
			'restricted'      => 'text',
			'button_text'     => '',
		];
	}

	protected function alias(): string
	{
		return 'pd' . (int) $this->_field->id;
	}

	/**
	 * La jointure sert à la recherche sur le titre et le nom du fichier.
	 */
	function setQuery(&$query)
	{
		$column = $this->_field->tablecolumn;
		$alias  = $this->alias();

		$query->select('h.' . $column);
		$query->join('LEFT', '#__phocadownload AS ' . $alias . ' ON ' . $alias . '.id = h.' . $column);
	}

	function getSearchWhereClause(&$query, $search_all_value)
	{
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$text  = $db->quote('%' . $db->escape($search_all_value, true) . '%', false);
		$alias = $this->alias();

		return '(LOWER(' . $alias . '.title) LIKE LOWER(' . $text . ') OR LOWER(' . $alias . '.filename) LIKE LOWER(' . $text . '))';
	}

	/**
	 * Fichier Phoca Download et sa catégorie, ou null s'il n'existe pas ou n'est pas publié.
	 */
	public static function getFile($id): ?object
	{
		$id = (int) $id;

		if ($id <= 0) {
			return null;
		}

		if (!array_key_exists($id, static::$files)) {
			$db    = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true)
				->select('f.id, f.title, f.alias, f.filename, f.filesize, f.access, f.published, f.approved, f.publish_up, f.publish_down, f.hits')
				->select('c.id AS catid, c.alias AS catalias, c.access AS cataccess, c.published AS catpublished')
				->from($db->quoteName('#__phocadownload', 'f'))
				->join('LEFT', $db->quoteName('#__phocadownload_categories', 'c') . ' ON c.id = f.catid')
				->where('f.id = :id')
				->bind(':id', $id, ParameterType::INTEGER);

			try {
				$file = $db->setQuery($query)->loadObject() ?: null;
			} catch (\RuntimeException $e) {
				// Phoca Download n'est pas installé
				$file = null;
			}

			if ($file) {
				// Phoca Download enregistre « 0000-00-00 00:00:00 » quand il n'y a pas de date
				$now   = Factory::getDate()->toSql();
				$unset = fn ($date) => empty($date) || str_starts_with((string) $date, '0000');
				$file->available = $file->published == 1 && $file->approved == 1 && $file->catpublished == 1
					&& ($unset($file->publish_up) || $file->publish_up <= $now)
					&& ($unset($file->publish_down) || $file->publish_down >= $now);
			}

			static::$files[$id] = $file;
		}

		return static::$files[$id];
	}

	/**
	 * L'utilisateur peut-il télécharger le fichier (niveaux d'accès du fichier et de sa catégorie) ?
	 */
	public static function canAccess(object $file): bool
	{
		$levels = Factory::getApplication()->getIdentity()->getAuthorisedViewLevels();

		return \in_array((int) $file->access, $levels) && \in_array((int) $file->cataccess, $levels);
	}

	/**
	 * Lien de téléchargement par Phoca Download (non échappé).
	 */
	public static function downloadLink(object $file): string
	{
		return (string) Route::_('index.php?option=com_phocadownload&view=category&id=' . $file->catid . ':' . $file->catalias
			. '&download=' . $file->id . ':' . $file->alias, false);
	}

	/**
	 * Taille lisible : « 6,3 Mo ».
	 */
	public static function formatSize(int $bytes): string
	{
		$units = ['COM_COLLECTOR_PHOCADOWNLOAD_UNIT_B', 'COM_COLLECTOR_PHOCADOWNLOAD_UNIT_KB', 'COM_COLLECTOR_PHOCADOWNLOAD_UNIT_MB', 'COM_COLLECTOR_PHOCADOWNLOAD_UNIT_GB'];
		$i     = 0;
		$size  = (float) $bytes;

		while ($size >= 1024 && $i < \count($units) - 1) {
			$size /= 1024;
			$i++;
		}

		$number = number_format($size, $i === 0 ? 0 : 1, Text::_('DECIMALS_SEPARATOR'), Text::_('THOUSANDS_SEPARATOR'));

		return $number . ' ' . Text::_($units[$i]);
	}

	/**
	 * Chemin du fichier sur le serveur (dossier de téléchargement de Phoca Download).
	 */
	public static function filePath(object $file): string
	{
		$folder = trim((string) ComponentHelper::getParams('com_phocadownload')->get('download_folder', 'phocadownload'));
		$folder = preg_match('#^([a-z]:)?[\\\\/]#i', $folder) ? $folder : JPATH_ROOT . '/' . $folder;

		return rtrim($folder, '\\/') . '/' . ltrim($file->filename, '\\/');
	}

	/**
	 * Taille du fichier : celle enregistrée par Phoca Download, sinon celle du fichier sur le disque.
	 */
	public static function fileSize(object $file): int
	{
		if ((int) $file->filesize > 0) {
			return (int) $file->filesize;
		}

		$path = static::filePath($file);

		return is_file($path) ? (int) filesize($path) : 0;
	}

	function display($value, $listing = true, $params = array(), $itemId = 0)
	{
		$file = static::getFile($value);

		if (!$file || !$file->available) {
			return false;
		}

		$attribs = $this->attribs();
		$title   = htmlspecialchars($file->title, ENT_QUOTES, 'UTF-8');

		if (!static::canAccess($file)) {
			if ($attribs['restricted'] === 'hide') {
				return false;
			}

			return '<span class="collector-phocadownload collector-phocadownload-restricted">'
				. '<span class="icon-lock" aria-hidden="true"></span> ' . $title
				. ' <small class="text-muted">' . Text::_('COM_COLLECTOR_PHOCADOWNLOAD_RESTRICTED') . '</small></span>';
		}

		$size = '';

		if ($attribs['show_size'] && ($bytes = static::fileSize($file)) > 0) {
			$size = ' <small class="collector-phocadownload-size text-muted">(' . static::formatSize($bytes) . ')</small>';
		}

		$link = htmlspecialchars(static::downloadLink($file), ENT_QUOTES, 'UTF-8');
		$mode = $listing ? $attribs['listing_display'] : $attribs['detail_display'];

		switch ($mode) {
			case 'title':
				return '<span class="collector-phocadownload">' . $title . $size . '</span>';

			case 'button':
				$text = trim((string) $attribs['button_text']) !== '' ? Text::_($attribs['button_text']) : Text::_('COM_COLLECTOR_PHOCADOWNLOAD_DOWNLOAD');

				return '<span class="collector-phocadownload"><a class="btn btn-primary btn-sm" href="' . $link . '" title="' . $title . '">'
					. '<span class="icon-download" aria-hidden="true"></span> ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</a>' . $size . '</span>';

			default:
				return '<span class="collector-phocadownload"><a href="' . $link . '">'
					. '<span class="icon-download" aria-hidden="true"></span> ' . $title . '</a>' . $size . '</span>';
		}
	}

	/**
	 * Adresse de téléchargement, pour la balise {champ:url} des templates.
	 */
	function getUrl($value): ?string
	{
		$file = static::getFile($value);

		if (!$file || !$file->available || !static::canAccess($file)) {
			return '';
		}

		return static::downloadLink($file);
	}

	function displayInTitle($value)
	{
		$file = static::getFile($value);

		return $file ? $file->title : '';
	}

	/**
	 * À l'import, la valeur peut être l'identifiant, le nom du fichier (avec ou sans dossier) ou le titre.
	 */
	function getImportedValue($value, array $mapping = [])
	{
		$value = trim((string) $value);

		if ($value === '' || ctype_digit($value)) {
			return $value;
		}

		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$name  = str_replace('\\', '/', $value);
		$end   = '%/' . addcslashes(basename($name), '%_\\');
		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__phocadownload'))
			->where('(filename = :name OR filename LIKE :end OR title = :title)')
			->bind(':name', $name)
			->bind(':end', $end)
			->bind(':title', $value)
			->order('id');

		return (string) ($db->setQuery($query, 0, 1)->loadResult() ?: '');
	}

	function displayComparison($value, $otherValue = null, $side = 'v1')
	{
		$file = static::getFile($value);

		return $file ? htmlspecialchars($file->title . ' (' . $file->filename . ')', ENT_QUOTES, 'UTF-8') : false;
	}
}
