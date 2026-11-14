<?php
/**
 * Joomla! 3.0 component Collector
 *
 * @package 	Collector
 * @copyright   Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Collector is a Multi Purpose Listing Tool.
 * Originaly developped to list Collections
 * it can be used for several purpose.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Language\LanguageFactoryInterface;

ignore_user_abort( true ); 

/**
 * Script file of Collector component
 */
class com_collectorInstallerScript
{
    /**
	 * method to update the component
	 *
	 * @return void
	 */
	public function install($parent) 
	{
		$user	= Factory::getApplication()->getIdentity();
		$params   = ComponentHelper::getParams('com_languages');
		$frontend_lang = $params->get('site', 'en-GB');
		$language = Factory::getContainer()->get(LanguageFactoryInterface::class)->createLanguage($frontend_lang);
		
		// get language file for default layouts
		$language = Factory::getApplication()->getLanguage();
		$language->load('com_collector');
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		
		// check for root item
		$query = "SELECT id from `#__collector_defined_content` WHERE id=1;";
		$db->setQuery( $query );
		if (!$result = $db->LoadResult())
		{
			$query = "INSERT INTO `#__collector_defined_content` SET `id` = 1,`defined` = 0,`parent_id` = 0,`level` = 0,`path` = '',`content` = 'List_Item_Root',`image` = '', `lft` = 0,`rgt` = 1;";
			$db->setQuery( $query );
			$db->execute();
		}
		
		// check for fields types
		$query = "SELECT id from `#__collector_fields_type` WHERE id=1;";
		$db->setQuery( $query );
		if (!$result = $db->LoadResult())
		{
			$query = "INSERT INTO `#__collector_fields_type` (`id`, `type`, `state`, `unikable`, `sortable`, `searchable`, `filterable`, `intitle`) VALUES
				(1, 'line', 1, 1, 1, 1, 0, 1),
				(2, 'text', 1, 0, 0, 1, 0, 0),
				(3, 'define', 1, 0, 1, 1, 1, 1),
				(4, 'item', 0, 0, 1, 1, 1, 1),
				(5, 'image', 1, 0, 0, 1, 0, 0),
				(6, 'number', 1, 1, 1, 1, 1, 1),
				(7, 'date', 1, 1, 1, 1, 1, 1),
				(8, 'file', 1, 0, 0, 1, 0, 0),
				(9, 'phocadownload', 1, 0, 0, 1, 0, 0),
				(10, 'flipbook', 1, 0, 0, 0, 0, 0);";
			$db->setQuery( $query );
			$db->execute();
		}

		// check for files types
		$query = "SELECT id from `#__collector_files_type`;";
		$db->setQuery( $query );
		if (!$result = $db->LoadResult())
		{
			$query = "INSERT INTO `#__collector_files_type` (`id`, `name`, `text`) VALUES
				(1 , 'picture', 'IMG_DOC'),
				(2 , 'text', 'TXT_DOC'),
				(3 , 'calc', 'CALC_DOC'),
				(4 , 'pdf', 'PDF_DOC'),
				(5 , 'audio', 'AUDIO_DOC'),
				(6 , 'video', 'VIDEO_DOC'),
				(7 , 'other', 'OTHER_DOC');";
			$db->setQuery( $query );
			$db->execute();
		}
		
		$query = "SELECT id from `#__collector_files_ext`;";
		$db->setQuery( $query );
		if (!$result = $db->LoadResult())
		{
			$query = "INSERT INTO `#__collector_files_ext` (`id` , `ext`, `type` ,`ico` ,`state`) VALUES
				(1 , 'jpg', '1', 'picture.png', '1'),
				(2 , 'png', '1', 'picture.png', '1'),
				(3 , 'bmp', '1', 'picture.png', '1'),
				(4 , 'gif', '1', 'picture.png', '1'),
				(5 , 'xcf', '1', 'picture.png', '0'),
				(6 , 'doc', '2', 'page_white_word.png', '1'),
				(7 , 'odt', '2', 'page_white_word.png', '1'),
				(8 , 'txt', '2', 'page_white_word.png', '1'),
				(9 , 'rtf', '2', 'page_white_word.png', '1'),
				(10 , 'xls', '3', 'page_white_excel.png', '1'),
				(11 , 'ods', '3', 'page_white_excel.png', '1'),
				(12 , 'pdf', '4', 'page_white_acrobat.png', '1');";
			$db->setQuery( $query );
			$db->execute();
		}
		
		$imagepath = Uri::root().'administrator/components/com_collector/assets/images/collector_logo.png';
		
		$msg = array();
		$msg[] = '<center>';
		$msg[] = '<table width="100%" style="border:0px; margin-bottom:10px;">';
		$msg[] = '<tr>';
		$msg[] = '<td align="center" colspan="2">';
		$msg[] = '<img src="' . $imagepath . '" border="0" alt="Collector" /><br />Version 0.7.0';
		$msg[] = '</td>';
		$msg[] = '</tr>';
		$msg[] = '<tr>';
		$msg[] = '<td style="border:1px solid #999;background-color:aliceblue;" colspan="2">';
		$msg[] = '<b>' . Text::_('COM_COLLECTOR_INSTALL') . '</b>';
		$msg[] = '</td>';
		$msg[] = '</tr>';
		$msg[] = '</table>';
		$msg[] = '</center>';
		
		echo implode($msg);
	}
 
	/**
	 * method to update the component
	 *
	 * @return void
	 */
	public function update($parent) 
	{
		// $parent is the class calling this method

        $this->install($parent);
		
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = "SHOW COLUMNS FROM `#__collector_userslists` LIKE 'type'";
		$db->setQuery( $query );
		$db->execute();
		$exist = $db->getNumRows();
		
		if (!$exist) {
			$query = "ALTER TABLE `#__collector_userslists` ADD `type` INT(11) NOT NULL after `collection`";
			$db->setQuery( $query );
			$db->execute();
		}

		// Userlists creation if not exists
		// foreach collection
		$query = "SELECT id FROM `#__collector`;";
		$db->setQuery( $query );
		$collections = $db->loadObjectList();

		foreach ($collections AS $collection)
		{
			// collection InnoDB
			$query = "ALTER TABLE `#__collector_items_history_".$collection->id."` ENGINE=InnoDB;";
			$db->setQuery( $query );
			$db->execute();
			
			$table = '#__collector_items_history_' . (int) $collection->id;
			// Vérifier si la colonne existe déjà
			$db->setQuery('SHOW COLUMNS FROM ' . $db->quoteName($table) . ' LIKE ' . $db->quote('refuse_note'));
			if (!$db->loadResult()) {
				$db->setQuery('ALTER TABLE ' . $db->quoteName($table) . ' ADD COLUMN `refuse_note` text DEFAULT NULL AFTER `modification`');
				$db->execute();
			}
			
			// collection list
			$query = "SELECT id FROM `#__collector_userslists` WHERE collection = '".$collection->id."' AND type = '2'";
			$db->setQuery( $query );
			if (!$result = $db->LoadResult())
			{
				$query = "INSERT INTO `#__collector_userslists` SET `collection` = '".$collection->id."',`type` = 2,`name` = '".$db->escape(Text::_('COM_COLLECTOR_USERLIST_COLLECTION'))."',`state` = '0',`ordering` = '0',`access` = '1';";
				$db->setQuery( $query );
				$db->execute();
			}
			
			// wish list
			$query = "SELECT id FROM `#__collector_userslists` WHERE collection = '".$collection->id."' AND type = '0'";
			$db->setQuery( $query );
			if (!$result = $db->LoadResult())
			{
				$query = "INSERT INTO `#__collector_userslists` SET `collection` = '".$collection->id."',`type` = 0,`name` = '".$db->escape(Text::_('COM_COLLECTOR_USERLIST_WISH_LIST'))."',`state` = '0',`ordering` = '0',`access` = '1';";
				$db->setQuery( $query );
				$db->execute();
			}
			
			// swap list
			$query = "SELECT id FROM `#__collector_userslists` WHERE collection = '".$collection->id."' AND type = '1'";
			$db->setQuery( $query );
			if (!$result = $db->LoadResult())
			{
				$query = "INSERT INTO `#__collector_userslists` SET `collection` = '".$collection->id."',`type` = 1,`name` = '".$db->escape(Text::_('COM_COLLECTOR_USERLIST_SWAP_LIST'))."',`state` = '0',`ordering` = '0',`access` = '1';";
				$db->setQuery( $query );
				$db->execute();
			}
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = "SHOW COLUMNS FROM `#__collector_fields` LIKE 'next_sorted_field'";
		$db->setQuery( $query );
		$db->execute();
		$exist = $db->getNumRows();

		if (!$exist) {
			$query = "ALTER TABLE `#__collector_fields` ADD `next_sorted_field` varchar(30) NOT NULL default '0' after `required`";
			$db->setQuery( $query );
			$db->execute();
		}

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = "SHOW COLUMNS FROM `#__collector_fields` LIKE 'column_width'";
		$db->setQuery( $query );
		$db->execute();
		$exist = $db->getNumRows();

		if (!$exist) {
			$query = "ALTER TABLE `#__collector_fields` ADD `column_width` varchar(30) NOT NULL default '' after `next_sorted_field`";
			$db->setQuery( $query );
			$db->execute();
		}
		
		// 0.9.1 : ancienne gestion des templates par fichiers PHP (éditeur de code en ligne) supprimée
		$admin = JPATH_ADMINISTRATOR . '/components/com_collector';

		foreach (['/src/View/Htmledit', '/tmpl/htmledit'] as $dossier) {
			if (is_dir($admin . $dossier)) {
				Folder::delete($admin . $dossier);
			}
		}

		foreach (['/src/Model/HtmleditModel.php', '/src/Table/Collector_templatesTable.php'] as $fichier) {
			if (is_file($admin . $fichier)) {
				unlink($admin . $fichier);
			}
		}

		// 0.9.1 : fichiers hérités de Joomla 1.5 / 3 qui ne sont plus utilisés
		$site      = JPATH_SITE . '/components/com_collector';
		$obsoletes = [
			$site . '/collector.php',
			$site . '/controller.php',
			$site . '/assets/css/select.css',
			$admin . '/src/Classes/CollectorUser.php',
			$admin . '/src/Classes/collection.php',
			// type nombre : liste « méthode de recherche » remplacée par une liste standard avec showon
			$admin . '/src/Classes/Field/Number/searchmethod.php',
			// proposition d'objet : modèle Joomla 3 remplacé par ItemformModel
			$site . '/src/Model/ItemcformModel.php',
			// ancienne vue « itemc » (variante de la fiche) restée sur les sites mis à jour
			$site . '/src/Model/ItemcModel.php',
		];

		foreach (['Date', 'Define', 'File', 'Image', 'Item', 'Line', 'Number', 'Text'] as $type) {
			$obsoletes[] = $admin . '/src/Classes/Field/Collector' . $type . 'Field.php';
			$obsoletes[] = $admin . '/src/Classes/Field/' . $type . '/Collector' . $type . 'Field.php';
		}

		foreach ($obsoletes as $fichier) {
			if (is_file($fichier)) {
				unlink($fichier);
			}
		}

		foreach ([$site . '/helpers', $site . '/layouts/libraries', $admin . '/helpers', $admin . '/params', $admin . '/src/Plugin',
			// proposition d'objet : gabarits renommés de itemcform en itemform (nom de la vue)
			$site . '/tmpl/itemcform', $site . '/tmpl/itemc', $site . '/src/View/Itemc',
			// curseur du type nombre : bibliothèque jQuery remplacée par un curseur natif
			$admin . '/src/Classes/Field/Number/bootstrap-slider'] as $dossier) {
			if (is_dir($dossier)) {
				Folder::delete($dossier);
			}
		}

		// gabarit de comparaison des versions : dossier renommé en minuscules (Joomla le cherche ainsi sous Linux) ;
		// on vérifie le nom exact, car sous Windows les deux noms désignent le même dossier
		if (is_dir($admin . '/tmpl') && \in_array('Versionscompare', scandir($admin . '/tmpl'), true)
			&& \in_array('versionscompare', scandir($admin . '/tmpl'), true)) {
			Folder::delete($admin . '/tmpl/Versionscompare');
		}

		$repertoireObsolete = JPATH_SITE . '/administrator/components/com_collector/layouts/joomla/searchtools';

        // 2. Vérifier si le répertoire existe avant de tenter la suppression
        if (Folder::exists($repertoireObsolete)) {
            try {
                // 3. Supprimer le fichier
                Folder::delete($repertoireObsolete);
                
                // Optionnel : Log ou message pour dire que c'est fait
                // echo "<p>Fichier obsolète supprimé : " . basename($repertoireObsolete) . "</p>";
            } catch (\Exception $e) {
                // Gérer l'erreur silencieusement ou l'afficher
                Factory::getApplication()->enqueueMessage('Erreur lors de la suppression : ' . $e->getMessage(), 'warning');
            }
        }

		$this->convertLegacyImageValues();

		// Une collection d'utilisateur (type 2) n'est que privée (0) ou publique (2) :
		// l'ancienne valeur « matchs uniquement » (1) devient privée, comme elle s'affichait déjà
		try {
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$db->setQuery(
				'UPDATE #__collector_userlist AS ul'
				. ' INNER JOIN #__collector_userslists AS uls ON uls.id = ul.userslist'
				. ' SET ul.access = 0 WHERE ul.access = 1 AND uls.type = 2'
			)->execute();
		} catch (\Exception $e) {
			// tables absentes (installation partielle) : rien à convertir
		}
	}

	/**
	 * Champs Image : valeurs au format de Collector pour Joomla 3, « chemin|titre|alt|largeur|hauteur|… »,
	 * ramenées au simple chemin enregistré par le champ Média de Joomla 4 et plus.
	 * Une valeur dont les parties après le chemin ne sont pas vides est laissée telle quelle
	 * (rien n'est perdu ; l'affichage lit toujours le chemin avant la première barre).
	 * Sans effet une fois les valeurs converties.
	 */
	private function convertLegacyImageValues(): void
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		try {
			$fields = $db->setQuery(
				'SELECT f.id, f.collection, f.tablecolumn, f.attribs FROM #__collector_fields AS f'
				. ' INNER JOIN #__collector_fields_type AS t ON t.id = f.type'
				. " WHERE t.type = 'image'"
			)->loadObjectList();
		} catch (\Exception $e) {
			return;
		}

		$tables = $db->getTableList();

		foreach ($fields as $field) {
			$table  = $db->getPrefix() . 'collector_items_history_' . (int) $field->collection;
			$column = $db->quoteName($field->tablecolumn);

			if (!\in_array($table, $tables, true)) {
				continue;
			}

			// « chemin|||||| » → « chemin » ; « |||||| » → « »
			$db->setQuery(
				'UPDATE ' . $db->quoteName($table) . ' SET ' . $column . ' = SUBSTRING_INDEX(' . $column . ", '|', 1)"
				. ' WHERE ' . $column . " LIKE '%|%'"
				. ' AND REPLACE(SUBSTRING(' . $column . ', LOCATE(' . "'|', " . $column . ")), '|', '') = ''"
			)->execute();

			// Image par défaut du champ, enregistrée au même format
			$attribs = json_decode((string) $field->attribs, true);

			if (\is_array($attribs) && isset($attribs['default']) && str_contains((string) $attribs['default'], '|')) {
				$parts = explode('|', (string) $attribs['default']);

				if (trim(implode('', \array_slice($parts, 1))) === '') {
					$attribs['default'] = $parts[0];
					$db->setQuery(
						'UPDATE #__collector_fields SET attribs = ' . $db->quote(json_encode($attribs)) . ' WHERE id = ' . (int) $field->id
					)->execute();
				}
			}
		}
	}
	
	/**
	 * method to uninstall the component
	 *
	 * @return void
	 */
	public function uninstall($parent) 
	{
		// $parent is the class calling this method
		
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		
		// foreach collection
		$query = "SELECT id FROM `#__collector`;";
		$db->setQuery( $query );
		$collections = $db->loadObjectList();

		foreach ($collections AS $collection)
		{
			// drop history table
			$query = "DROP TABLE `#__collector_items_history_".$collection->id."`";
			$db->setQuery( $query );
			$db->execute();
		}

		/* delete comments */
		$jcomments = JPATH_SITE . DIRECTORY_SEPARATOR .'components' . DIRECTORY_SEPARATOR . 'com_jcomments' . DIRECTORY_SEPARATOR . 'jcomments.php';
		if (file_exists($jcomments)) {
			$query = "DELETE FROM #__jcomments "
				. " WHERE object_group = " . $db->Quote('com_collector');
			$db->setQuery($query);
			$db->execute();
			$query = "DELETE FROM #__jcomments_objects "
				. " WHERE object_group = " . $db->Quote('com_collector');
			$db->setQuery($query);
			$db->execute();
		}
	}
}