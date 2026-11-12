<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Steevo\Component\Collector\Administrator\Classes\Field\Flipbook;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Steevo\Component\Collector\Administrator\Classes\CollectorField;

defined('_JEXEC') or die;

/**
 * Champ « Liseuse » : un livre du composant Liseuse (com_flipbook), affiché avec un lien de lecture en ligne.
 *
 * La valeur enregistrée est l'identifiant du livre. Les droits de lecture sont vérifiés par la liseuse,
 * qui montre les pages en libre accès (la couverture) aux visiteurs qui n'ont pas le niveau d'accès du livre.
 */
class CollectorFieldFlipbook extends CollectorField
{
	public $type = 'flipbook';

	/** Livres déjà lus, par identifiant */
	protected static $books = [];

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
			'button_text'     => '',
			'show_pages'      => 1,
			'restricted'      => 'show',
		];
	}

	/**
	 * Livre publié, ou null s'il n'existe pas, n'est pas publié ou si la liseuse n'est pas installée.
	 */
	public static function getBook($id): ?object
	{
		$id = (int) $id;

		if ($id <= 0) {
			return null;
		}

		if (!array_key_exists($id, static::$books)) {
			$db    = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true)
				->select('id, title, alias, page_count, page_offset, access')
				->from($db->quoteName('#__flipbook_books'))
				->where('id = :id')
				->where('state = 1')
				->bind(':id', $id, ParameterType::INTEGER);

			try {
				$book = $db->setQuery($query)->loadObject() ?: null;
			} catch (\RuntimeException $e) {
				// La liseuse n'est pas installée
				$book = null;
			}

			static::$books[$id] = $book && (int) $book->page_count > 0 ? $book : null;
		}

		return static::$books[$id];
	}

	/**
	 * L'utilisateur a-t-il accès à tout le livre ?
	 */
	public static function canRead(object $book): bool
	{
		return \in_array((int) $book->access, Factory::getApplication()->getIdentity()->getAuthorisedViewLevels());
	}

	/**
	 * Adresse de la liseuse, éventuellement ouverte à une page (numéro imprimé), non échappée.
	 */
	public static function readerLink(object $book, int $page = 0): string
	{
		return (string) Route::_('index.php?option=com_flipbook&view=book&id=' . (int) $book->id . ':' . $book->alias
			. ($page > 0 ? '&page=' . $page : ''), false);
	}

	function display($value, $listing = true, $params = array(), $itemId = 0)
	{
		$book = static::getBook($value);

		if (!$book) {
			return false;
		}

		$attribs  = $this->attribs();
		$readable = static::canRead($book);

		if (!$readable && $attribs['restricted'] === 'hide') {
			return false;
		}

		$mode = $listing ? $attribs['listing_display'] : $attribs['detail_display'];

		if ($mode === 'hide') {
			return false;
		}

		$text  = trim((string) $attribs['button_text']) !== '' ? Text::_($attribs['button_text']) : Text::_('COM_COLLECTOR_FLIPBOOK_READ');
		$text  = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
		$link  = htmlspecialchars(static::readerLink($book), ENT_QUOTES, 'UTF-8');
		$title = htmlspecialchars($book->title, ENT_QUOTES, 'UTF-8');
		$extra = '';

		if ($attribs['show_pages']) {
			$extra .= ' <small class="collector-flipbook-pages text-muted">(' . Text::plural('COM_COLLECTOR_FLIPBOOK_N_PAGES', (int) $book->page_count) . ')</small>';
		}

		if (!$readable) {
			$extra .= ' <small class="collector-flipbook-restricted text-muted">' . Text::_('COM_COLLECTOR_FLIPBOOK_RESTRICTED') . '</small>';
		}

		if ($mode === 'button') {
			return '<span class="collector-flipbook"><a class="btn btn-outline-primary btn-sm" href="' . $link . '" title="' . $title . '">'
				. '<span class="icon-book" aria-hidden="true"></span> ' . $text . '</a>' . $extra . '</span>';
		}

		return '<span class="collector-flipbook"><a href="' . $link . '" title="' . $title . '">'
			. '<span class="icon-book" aria-hidden="true"></span> ' . $text . '</a>' . $extra . '</span>';
	}

	/**
	 * Adresse de la liseuse, pour la balise {champ:url} des templates.
	 */
	function getUrl($value): ?string
	{
		$book = static::getBook($value);

		return $book ? static::readerLink($book) : '';
	}

	function displayInTitle($value)
	{
		$book = static::getBook($value);

		return $book ? $book->title : '';
	}

	/**
	 * À l'import, la valeur peut être l'identifiant, l'alias, le dossier ou le titre du livre.
	 */
	function getImportedValue($value, array $mapping = [])
	{
		$value = trim((string) $value);

		if ($value === '' || ctype_digit($value)) {
			return $value;
		}

		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__flipbook_books'))
			->where('(alias = :alias OR folder = :folder OR title = :title)')
			->bind(':alias', $value)
			->bind(':folder', $value)
			->bind(':title', $value)
			->order('id');

		try {
			return (string) ($db->setQuery($query, 0, 1)->loadResult() ?: '');
		} catch (\RuntimeException $e) {
			return '';
		}
	}

	function displayComparison($value, $otherValue = null, $side = 'v1')
	{
		$book = static::getBook($value);

		return $book ? htmlspecialchars($book->title, ENT_QUOTES, 'UTF-8') : false;
	}
}
