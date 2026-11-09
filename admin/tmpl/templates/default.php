<?php
/**
 * Joomla! component Collector
 *
 * @package     Collector
 * @copyright   Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.multiselect');

$user       = Factory::getApplication()->getIdentity();
$listOrder  = $this->escape($this->state->get('list.ordering'));
$listDirn   = $this->escape($this->state->get('list.direction'));
$collection = (int) $this->collection;
$canChange  = $user->authorise('core.edit.state', 'com_collector');
$canEdit    = $user->authorise('core.edit', 'com_collector');
?>
<form action="<?php echo Route::_('index.php?option=com_collector&view=templates'); ?>" method="post" name="adminForm" id="adminForm">

	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this, 'options' => ['selectorFieldName' => 'collection']]); ?>

	<div class="alert alert-info">
		<span class="icon-info-circle" aria-hidden="true"></span>
		<?php echo Text::_('COM_COLLECTOR_TEMPLATES_INTRO'); ?>
	</div>

	<?php if (empty($this->items)) : ?>
		<div class="alert alert-warning">
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php else : ?>
		<table class="table table-striped" id="templateList">
			<thead>
				<tr>
					<td class="w-1 text-center">
						<?php echo HTMLHelper::_('grid.checkall'); ?>
					</td>
					<th scope="col" class="w-1 text-center">
						<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 't.state', $listDirn, $listOrder); ?>
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_COLLECTOR_TEMPLATE_NAME_LABEL', 't.name', $listDirn, $listOrder); ?>
					</th>
					<th scope="col" class="w-15">
						<?php echo HTMLHelper::_('searchtools.sort', 'COM_COLLECTOR_TEMPLATE_CLIENT_LABEL', 't.client', $listDirn, $listOrder); ?>
					</th>
					<th scope="col" class="w-15 text-center">
						<?php echo Text::_('COM_COLLECTOR_TEMPLATE_USED'); ?>
					</th>
					<th scope="col" class="w-5 text-center">
						<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 't.id', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->items as $i => $item) : ?>
					<tr class="row<?php echo $i % 2; ?>">
						<td class="text-center">
							<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="text-center">
							<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'templates.', $canChange, 'cb'); ?>
						</td>
						<th scope="row">
							<?php if ($item->checked_out) : ?>
								<?php echo HTMLHelper::_('jgrid.checkedout', $i, '', $item->checked_out_time, 'templates.', $canChange); ?>
							<?php endif; ?>
							<?php if ($canEdit) : ?>
								<a href="<?php echo Route::_('index.php?option=com_collector&task=template.edit&id=' . (int) $item->id . '&collection=' . $collection); ?>">
									<?php echo $this->escape($item->name); ?>
								</a>
							<?php else : ?>
								<?php echo $this->escape($item->name); ?>
							<?php endif; ?>
						</th>
						<td>
							<?php echo Text::_((int) $item->client === 1 ? 'COM_COLLECTOR_TEMPLATE_CLIENT_LIST' : 'COM_COLLECTOR_TEMPLATE_CLIENT_ITEM'); ?>
						</td>
						<td class="text-center">
							<?php if ($item->used) : ?>
								<span class="badge bg-success"><?php echo Text::_('JYES'); ?></span>
							<?php endif; ?>
						</td>
						<td class="text-center">
							<?php echo (int) $item->id; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php echo $this->pagination->getListFooter(); ?>
	<?php endif; ?>

	<input type="hidden" name="task" value="">
	<input type="hidden" name="boxchecked" value="0">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
