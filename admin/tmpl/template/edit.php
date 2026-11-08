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
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$input = Factory::getApplication()->input;

// balises proposées pour chaque vue (1 = liste, 0 = fiche)
$tags = [];

foreach ($this->tags as $client => $list) {
    foreach ($list as [$tag, $label]) {
        $tags[$client][] = ['tag' => $tag, 'label' => $label];
    }
}
?>
<form action="<?php echo Route::_('index.php?option=com_collector&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="template-form" class="form-validate">

	<div class="row title-alias form-vertical mb-3">
		<div class="col-12 col-md-6">
			<?php echo $this->form->renderField('name'); ?>
		</div>
	</div>

	<div class="main-card p-3">
		<div class="row">
			<div class="col-lg-9">
				<?php echo $this->form->renderField('content'); ?>
				<?php echo $this->form->renderField('css'); ?>
			</div>
			<div class="col-lg-3">
				<?php echo $this->form->renderField('collection'); ?>
				<?php echo $this->form->renderField('client'); ?>
				<?php echo $this->form->renderField('state'); ?>
				<?php echo $this->form->renderField('id'); ?>

				<fieldset class="options-form mt-3">
					<legend><?php echo Text::_('COM_COLLECTOR_TEMPLATE_TAGS'); ?></legend>
					<p class="small text-muted"><?php echo Text::_('COM_COLLECTOR_TEMPLATE_TAGS_HELP'); ?></p>
					<div id="collector-tpl-tags" class="d-flex flex-wrap gap-1"></div>
					<button type="button" class="btn btn-secondary btn-sm mt-3" id="collector-tpl-starter">
						<span class="icon-wand" aria-hidden="true"></span>
						<?php echo Text::_('COM_COLLECTOR_TEMPLATE_STARTER'); ?>
					</button>
				</fieldset>

				<fieldset class="options-form mt-3">
					<legend><?php echo Text::_('COM_COLLECTOR_TEMPLATE_SYNTAX'); ?></legend>
					<dl class="small mb-0">
						<dt><code>{champ}</code></dt><dd><?php echo Text::_('COM_COLLECTOR_TEMPLATE_SYNTAX_FIELD'); ?></dd>
						<dt><code>{champ:label}</code></dt><dd><?php echo Text::_('COM_COLLECTOR_TEMPLATE_SYNTAX_LABEL'); ?></dd>
						<dt><code>{champ:text}</code></dt><dd><?php echo Text::_('COM_COLLECTOR_TEMPLATE_SYNTAX_TEXT'); ?></dd>
						<dt><code>{champ:url}</code></dt><dd><?php echo Text::_('COM_COLLECTOR_TEMPLATE_SYNTAX_URL'); ?></dd>
						<dt><code>{champ:value}</code></dt><dd><?php echo Text::_('COM_COLLECTOR_TEMPLATE_SYNTAX_VALUE'); ?></dd>
						<dt><code>{if:champ}…{else}…{/if}</code></dt><dd><?php echo Text::_('COM_COLLECTOR_TEMPLATE_SYNTAX_IF'); ?></dd>
						<dt><code>{ifnot:champ}…{/if}</code></dt><dd><?php echo Text::_('COM_COLLECTOR_TEMPLATE_SYNTAX_IFNOT'); ?></dd>
						<dt><code>{items}…{/items}</code></dt><dd><?php echo Text::_('COM_COLLECTOR_TEMPLATE_SYNTAX_ITEMS'); ?></dd>
					</dl>
				</fieldset>
			</div>
		</div>
	</div>

	<input type="hidden" name="task" value="">
	<input type="hidden" name="collection" value="<?php echo (int) $this->item->collection; ?>">
	<input type="hidden" name="return" value="<?php echo $input->getBase64('return'); ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

<script>
(() => {
	'use strict';

	const tags = <?php echo json_encode($tags, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
	const zone = document.getElementById('collector-tpl-tags');

	const client = () => {
		const coche = document.querySelector('input[name="jform[client]"]:checked');
		return coche ? coche.value : '1';
	};

	// insère du texte dans l'éditeur du contenu (CodeMirror ou zone de texte simple)
	const inserer = texte => {
		const editeur = window.Joomla && Joomla.editors && Joomla.editors.instances && Joomla.editors.instances['jform_content'];

		if (editeur && typeof editeur.replaceSelection === 'function') {
			editeur.replaceSelection(texte);
			return;
		}

		const zoneTexte = document.getElementById('jform_content');
		if (zoneTexte) {
			const debut = zoneTexte.selectionStart || 0;
			zoneTexte.setRangeText(texte, debut, zoneTexte.selectionEnd || debut, 'end');
			zoneTexte.focus();
		}
	};

	const contenu = () => {
		const editeur = window.Joomla && Joomla.editors && Joomla.editors.instances && Joomla.editors.instances['jform_content'];
		if (editeur && typeof editeur.getValue === 'function') {
			return editeur.getValue();
		}
		const zoneTexte = document.getElementById('jform_content');
		return zoneTexte ? zoneTexte.value : '';
	};

	const afficher = () => {
		zone.innerHTML = '';
		(tags[client()] || []).forEach(({ tag, label }) => {
			const bouton = document.createElement('button');
			bouton.type = 'button';
			bouton.className = 'btn btn-outline-secondary btn-sm';
			bouton.textContent = label;
			bouton.title = tag;
			bouton.addEventListener('click', () => inserer(tag));
			zone.append(bouton);
		});
	};

	// modèle de départ construit avec les champs de la collection
	const depart = () => {
		const noms = (tags[client()] || [])
			.filter(({ tag }) => !/^\{(link|id|hits|items)\b/.test(tag))
			.map(({ tag }) => tag.slice(1, -1));
		const lignes = noms.map(nom =>
			`\t{if:${nom}}<div class="collector-tpl-ligne"><span class="collector-tpl-label">{${nom}:label}</span> {${nom}}</div>{/if}`
		);

		if (client() === '1') {
			// le premier champ sert de titre et de lien vers la fiche ; les valeurs des champs
			// peuvent contenir leurs propres liens, elles restent donc hors du lien
			const [titre, ...autres] = noms;
			return '<div class="collector-tpl-grille">\n{items}\n<div class="collector-tpl-carte">\n'
				+ (titre ? `\t<h3><a href="{link}">{${titre}:text}</a></h3>\n` : '')
				+ autres.map(nom => `\t{if:${nom}}<div class="collector-tpl-ligne"><span class="collector-tpl-label">{${nom}:label}</span> {${nom}}</div>{/if}`).join('\n')
				+ '\n</div>\n{/items}\n</div>\n';
		}

		return '<div class="collector-tpl-fiche">\n' + lignes.join('\n') + '\n</div>\n';
	};

	document.getElementById('collector-tpl-starter').addEventListener('click', () => {
		if (contenu().trim() !== '' && !window.confirm(<?php echo json_encode(Text::_('COM_COLLECTOR_TEMPLATE_STARTER_CONFIRM')); ?>)) {
			return;
		}

		const editeur = window.Joomla && Joomla.editors && Joomla.editors.instances && Joomla.editors.instances['jform_content'];
		const texte = depart();

		if (editeur && typeof editeur.setValue === 'function') {
			editeur.setValue(texte);
		} else {
			document.getElementById('jform_content').value = texte;
		}
	});

	document.querySelectorAll('input[name="jform[client]"]').forEach(radio => radio.addEventListener('change', afficher));
	afficher();
})();
</script>
