<?php
/**
 * @package    Collector
 * @copyright  Copyright (C) 2010 - 2020 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

// Charge le validateur de formulaire Joomla.
// Sans cet appel, document.formvalidator est undefined et
// Joomla.submitbutton() lève une TypeError à l'exécution.
HTMLHelper::_('behavior.formvalidator');
// Styles de Collector (formulaire sur mobile : libellé au-dessus du champ)
HTMLHelper::stylesheet(\Joomla\CMS\Uri\Uri::base() . 'components/com_collector/assets/css/collection.css', ['version' => 'auto']);

$user   = Factory::getApplication()->getIdentity();
$itemId = (int) $this->item->id;
$collId = (int) $this->collection->id;

// ── Vérification des droits ───────────────────────────────────────────────────
// Édition : déjà garantie par l'onglet parent (access-edit), rappelée ici pour
// protéger les boutons individuellement.
$canEdit   = $this->item->params->get('access-edit', false);

// Proposition (droit « Proposer une modification » sans droit de modifier) : enregistrée en attente de validation
$canPropose = !$canEdit && $this->item->params->get('access-propose', false);
$ownPending = $canPropose ? ($this->item->own_pending ?? null) : null;

// Suppression : permission distincte de l'édition
$canDelete = $user->authorise('core.delete', 'com_collector.item.' . $itemId)
           || $user->authorise('core.delete', 'com_collector.collection.' . $collId)
           || $user->authorise('core.delete', 'com_collector');

// Cible du formulaire : utilise une URL propre plutôt que l'URL courante
// pour éviter que les paramètres GET ne se cumulent au fil des sauvegardes.
$formAction = Route::_('index.php?option=com_collector&view=item&collection=' . $collId . '&id=' . $itemId, false);
?>
<div class="edit item-page<?php echo $this->pageclass_sfx; ?>">
	<form method="post" action="<?php echo $formAction; ?>" name="adminForm" id="adminForm" class="form-validate form-vertical">

		<?php /* ── Champs cachés — placés EN PREMIER, hors des onglets, ──────────
		           pour être toujours inclus dans le POST quelle que soit
		           l'implémentation JS des onglets Bootstrap.
		*/ ?>
		<input type="hidden" name="task"             value="">
		<input type="hidden" name="jform[id]"        id="jform_id"         value="<?php echo $itemId; ?>">
		<input type="hidden" name="jform[collection]" id="jform_collection" value="<?php echo $collId; ?>">
		<?php echo HTMLHelper::_('form.token'); ?>

		<?php /* ── Proposition (objet sans version validée) : relecture puis validation ── */ ?>
		<?php $isProposal = !empty($this->item->proposal_version); ?>
		<?php if ($isProposal) : ?>
		<div class="alert alert-info">
			<?php echo Text::_('COM_COLLECTOR_PROPOSAL_REVIEW'); ?>
		</div>
		<?php endif; ?>

		<?php /* ── Proposition d'un membre : vérifiée avant d'apparaître sur la fiche ── */ ?>
		<?php if ($canPropose) : ?>
		<div class="alert alert-info">
			<?php echo Text::_('COM_COLLECTOR_PROPOSE_INTRO'); ?>
			<?php if ($ownPending) : ?>
			<br><?php echo Text::sprintf('COM_COLLECTOR_PROPOSE_OWN_PENDING', HTMLHelper::_('date', $ownPending->modified, Text::_('DATE_FORMAT_LC2'))); ?>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php /* ── Barre de boutons principale ────────────────────────────────── */ ?>
		<div class="btn-toolbar mb-3">
			<?php if ($canPropose) : ?>
			<div class="btn-group me-2">
				<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('item.save')">
					<span class="icon-ok" aria-hidden="true"></span>&#160;<?php echo Text::_('COM_COLLECTOR_PROPOSE_SUBMIT'); ?>
				</button>
			</div>
			<div class="btn-group me-2">
				<button type="button" class="btn btn-secondary" onclick="Joomla.submitbutton('item.cancel')">
					<span class="icon-cancel" aria-hidden="true"></span>&#160;<?php echo Text::_('JCANCEL'); ?>
				</button>
			</div>
			<?php endif; ?>
			<?php if ($canEdit) : ?>
			<div class="btn-group me-2">
				<?php if ($isProposal) : ?>
				<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('item.validateproposal')">
					<span class="icon-check" aria-hidden="true"></span>&#160;<?php echo Text::_('COM_COLLECTOR_PROPOSAL_VALIDATE'); ?>
				</button>
				<?php else : ?>
				<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('item.save')">
					<span class="icon-ok" aria-hidden="true"></span>&#160;<?php echo Text::_('JSAVE'); ?>
				</button>
				<?php endif; ?>
			</div>
			<div class="btn-group me-2">
				<button type="button" class="btn btn-secondary" onclick="Joomla.submitbutton('item.cancel')">
					<span class="icon-cancel" aria-hidden="true"></span>&#160;<?php echo Text::_('JCANCEL'); ?>
				</button>
			</div>
			<?php endif; ?>
			<?php if ($canDelete) : ?>
			<div class="btn-group">
				<button type="button" class="btn btn-danger"
				        onclick="if(confirm('<?php echo Text::_('JGLOBAL_CONFIRM_DELETE'); ?>')) { Joomla.submitbutton('item.delete'); }">
					<span class="icon-trash" aria-hidden="true"></span>&#160;<?php echo Text::_('JACTION_DELETE'); ?>
				</button>
			</div>
			<?php endif; ?>
		</div>

		<fieldset>
			<?php echo HTMLHelper::_('uitab.startTabSet', 'edition', ['active' => 'collector-fields', 'recall' => true, 'breakpoint' => 768]); ?>

			<?php /* Identifiant « collector-fields » et non « editor » : le template annule l'alignement des champs dans
			         « .edit.item-page #editor » (onglet de l'éditeur d'articles de Joomla) */ ?>
			<?php /* ── Onglet principal : champs de l'item ────────────────────── */ ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'edition', 'collector-fields', Text::sprintf('COM_COLLECTOR_EDIT_ITEM', $this->item->titleItem)); ?>

				<?php foreach ($this->fields as $field) : ?>
					<?php echo $this->form->renderField($field->_field->tablecolumn); ?>
				<?php endforeach; ?>

				<?php // Proposition : son commentaire (« version initiale ») est conservé à la validation, pas de champ à présenter ?>
				<?php if ($canPropose || (!$isProposal && $this->params->get('save_history') && $this->params->get('show_modification'))) : ?>
					<?php echo $this->form->renderField('modification'); ?>
				<?php endif; ?>

				<?php /* Répétition des boutons en bas de l'onglet pour les formulaires longs */ ?>
				<?php if ($canPropose) : ?>
				<div class="btn-toolbar mt-3">
					<div class="btn-group me-2">
						<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('item.save')">
							<span class="icon-ok" aria-hidden="true"></span>&#160;<?php echo Text::_('COM_COLLECTOR_PROPOSE_SUBMIT'); ?>
						</button>
					</div>
					<div class="btn-group">
						<button type="button" class="btn btn-secondary" onclick="Joomla.submitbutton('item.cancel')">
							<span class="icon-cancel" aria-hidden="true"></span>&#160;<?php echo Text::_('JCANCEL'); ?>
						</button>
					</div>
				</div>
				<?php endif; ?>
				<?php if ($canEdit) : ?>
				<div class="btn-toolbar mt-3">
					<div class="btn-group me-2">
						<?php if ($isProposal) : ?>
						<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('item.validateproposal')">
							<span class="icon-check" aria-hidden="true"></span>&#160;<?php echo Text::_('COM_COLLECTOR_PROPOSAL_VALIDATE'); ?>
						</button>
						<?php else : ?>
						<button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('item.save')">
							<span class="icon-ok" aria-hidden="true"></span>&#160;<?php echo Text::_('JSAVE'); ?>
						</button>
						<?php endif; ?>
					</div>
					<div class="btn-group">
						<button type="button" class="btn btn-secondary" onclick="Joomla.submitbutton('item.cancel')">
							<span class="icon-cancel" aria-hidden="true"></span>&#160;<?php echo Text::_('JCANCEL'); ?>
						</button>
					</div>
				</div>
				<?php endif; ?>

			<?php echo HTMLHelper::_('uitab.endTab'); ?>

			<?php // Publication et métadonnées : réservées à qui modifie directement (pas aux propositions) ?>
			<?php if ($canEdit) : ?>
			<?php /* ── Onglet Publication ─────────────────────────────────────── */ ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'edition', 'publishing', Text::_('COM_COLLECTOR_PUBLISHING')); ?>

				<?php echo $this->form->renderField('created_by_alias'); ?>
				<?php echo $this->form->renderField('state'); ?>
				<?php echo $this->form->renderField('publish_up'); ?>
				<?php echo $this->form->renderField('publish_down'); ?>
				<?php echo $this->form->renderField('access'); ?>

			<?php echo HTMLHelper::_('uitab.endTab'); ?>

			<?php /* ── Onglet Métadonnées ─────────────────────────────────────── */ ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'edition', 'metadata', Text::_('COM_COLLECTOR_METADATA')); ?>

				<?php echo $this->form->renderField('metadesc'); ?>
				<?php echo $this->form->renderField('metakey'); ?>

			<?php echo HTMLHelper::_('uitab.endTab'); ?>
			<?php endif; ?>

			<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
		</fieldset>
	</form>
</div>