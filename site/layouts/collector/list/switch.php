<?php
/**
 * Joomla! component Collector
 *
 * Sélecteur de vue d'une liste : tableau ou templates de liste publiés de la collection.
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * $displayData :
 *   views   array     templates de liste publiés ({id, name})
 *   active  int       vue affichée (0 = tableau)
 *   url     callable  (id de la vue) => adresse
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$views = array_merge(
	[(object) ['id' => 0, 'name' => Text::_('COM_COLLECTOR_VIEW_STANDARD')]],
	$displayData['views']
);

// icônes intégrées : ne dépendent pas des polices d'icônes du template du site
$icons = [
	'table'    => '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" d="M2 3.5h12M2 8h12M2 12.5h12"/></svg>',
	'template' => '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><g fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1.75" y="1.75" width="5" height="5" rx="1"/><rect x="9.25" y="1.75" width="5" height="5" rx="1"/><rect x="1.75" y="9.25" width="5" height="5" rx="1"/><rect x="9.25" y="9.25" width="5" height="5" rx="1"/></g></svg>',
];
?>
<nav class="collector-view-switch" aria-label="<?php echo Text::_('COM_COLLECTOR_VIEW_SWITCH_LABEL'); ?>">
	<span class="collector-view-switch-label"><?php echo Text::_('COM_COLLECTOR_VIEW_SWITCH_LABEL'); ?></span>
	<?php // même balisage que la pagination de Joomla : le sélecteur prend les couleurs que le template donne à sa pagination ?>
	<ul class="pagination collector-view-switch-group">
		<?php foreach ($views as $view) : ?>
			<?php $active = (int) $view->id === (int) $displayData['active']; ?>
		<li class="page-item<?php echo $active ? ' active' : ''; ?>">
			<?php if ($active) : ?>
			<span class="page-link" aria-current="true">
			<?php else : ?>
			<a class="page-link" href="<?php echo $displayData['url']((int) $view->id); ?>">
			<?php endif; ?>
				<?php echo $icons[(int) $view->id ? 'template' : 'table']; ?>
				<?php echo htmlspecialchars($view->name, ENT_QUOTES, 'UTF-8'); ?>
			<?php echo $active ? '</span>' : '</a>'; ?>
		</li>
		<?php endforeach; ?>
	</ul>
</nav>
