<?php
/**
 * Joomla! component Collector
 *
 * Pagination d'une liste d'objets (collection et listes des membres) : liens des pages et nombre
 * de résultats dans un même bloc flexible ; sur un écran étroit, le compteur passe à la ligne
 * au lieu d'être recouvert par les boutons.
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * $displayData :
 *   pagination   Pagination  pagination de la liste
 *   showCounter  bool        afficher « Résultats x à y sur z »
 */

\defined('_JEXEC') or die;

$pagination = $displayData['pagination'];
$links      = $pagination->getPagesLinks();
$counter    = !empty($displayData['showCounter']) ? $pagination->getResultsCounter() : '';

if (trim(strip_tags($links)) === '' && trim(strip_tags($counter)) === '') {
	return;
}
?>
<div class="collector-pagination">
	<div class="collector-pagination-links"><?php echo $links; ?></div>
	<?php if ($counter !== '') : ?>
	<p class="counter collector-pagination-counter"><?php echo $counter; ?></p>
	<?php endif; ?>
</div>
