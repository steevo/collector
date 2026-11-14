<?php
/**
 * Joomla! component Collector
 *
 * Liste d'objets mise en page par un template de la collection, commune à la collection
 * et aux listes des membres.
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * $displayData :
 *   template  object    template chargé par CollectorTemplateHelper::load()
 *   fields    array     champs de la collection
 *   items     array     objets à afficher
 *   params    Registry  paramètres de la vue
 *   link      callable  (objet) => adresse de sa fiche
 *   tags      array     listes des membres : {note}, {actions} => (objet) => HTML
 */

\defined('_JEXEC') or die;

use Steevo\Component\Collector\Administrator\Helper\CollectorTemplateHelper;

$template = $displayData['template'];
?>
<div class="collector-template collector-template-list collector-template-<?php echo (int) $template->id; ?>">
	<?php echo CollectorTemplateHelper::renderList(
		$template,
		$displayData['fields'],
		$displayData['items'],
		$displayData['params'],
		$displayData['link'],
		$displayData['tags'] ?? []
	); ?>
</div>
