<?php
/**
 * Joomla! component Collector
 *
 * Liste d'objets en tableau, commune à la collection et aux listes des membres.
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * $displayData :
 *   fields    array     champs de la collection (CollectorField)
 *   items     array     objets à afficher
 *   params    Registry  paramètres de la vue
 *   link      callable  (objet) => adresse de sa fiche
 *   sortable  bool      en-têtes de tri (formulaire « adminForm » avec filter_order et filter_order_Dir)
 *   listOrder string    tri en cours
 *   listDirn  string    sens du tri en cours
 *   columns   array     colonnes ajoutées à droite : [label, class, cell => (objet) => HTML]
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

$fields    = $displayData['fields'];
$items     = $displayData['items'];
$params    = $displayData['params'];
$link      = $displayData['link'];
$sortable  = !empty($displayData['sortable']);
$listOrder = (string) ($displayData['listOrder'] ?? '');
$listDirn  = (string) ($displayData['listDirn'] ?? '');
$columns   = $displayData['columns'] ?? [];

// Colonnes : champs affichés en liste, sauf ceux filtrés par le lien de menu quand on les masque
$hideFiltered = (bool) $params->get('hide_filter');
$displayed    = array_values(array_filter(
	$fields,
	static fn ($field) => $field->_field->listing && !($hideFiltered && $field->isFiltered($params))
));

HTMLHelper::_('bootstrap.tooltip', '[data-bs-toggle="tooltip"]');
?>
<?php if ($sortable) : ?>
<script>
Joomla.tableOrdering = function (order, dir, task) {
	var form = document.adminForm;

	form.filter_order.value = order;
	form.filter_order_Dir.value = dir;
	form.submit(task);
};
</script>
<?php endif; ?>
<table class="category table table-striped table-bordered table-hover">
	<thead>
		<tr>
		<?php foreach ($displayed as $row) : ?>
			<?php $style = (int) $row->_field->column_width ? ' style="min-width:' . (int) $row->_field->column_width . 'px"' : ''; ?>
			<th<?php echo $style; ?>>
			<?php
			if ($sortable && $row->_field->sort == 1) {
				// tri sur ce champ, puis sur les champs de tri suivants
				$order = $row->getOrderBy();
				$next  = explode('/', (string) $row->_field->next_sorted_field);

				if ($next[0] !== '0' && $next[0] !== '') {
					foreach ($next as $id) {
						foreach ($fields as $field) {
							if ($field->_field->id == $id) {
								$order .= '//' . $field->getOrderBy();
							}
						}
					}
				}

				echo HTMLHelper::_('grid.sort', $row->_field->field, $order, $listDirn, $listOrder);
			} else {
				echo $row->_field->field;
			}
			?>
			</th>
		<?php endforeach; ?>
		<?php foreach ($columns as $column) : ?>
			<th class="<?php echo $column['class'] ?? ''; ?>"><?php echo $column['label'] ?? ''; ?></th>
		<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($items as $i => $item) : ?>
		<?php $url = (string) $link($item); ?>
		<tr class="cat-list-row<?php echo 1 + ($i % 2); ?>" style="cursor:pointer">
		<?php foreach ($displayed as $field) : ?>
			<?php $name = $field->_field->tablecolumn; ?>
			<td<?php echo $field->makeLink ? ' onclick="location.href=\'' . htmlspecialchars($url, ENT_QUOTES) . '\';"' : ''; ?> style="vertical-align:middle">
				<?php echo $field->display($item->$name ?? '', true, $params, $item->id); ?>
			</td>
		<?php endforeach; ?>
		<?php foreach ($columns as $column) : ?>
			<td class="<?php echo $column['class'] ?? ''; ?>" style="vertical-align:middle"><?php echo $column['cell']($item); ?></td>
		<?php endforeach; ?>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
