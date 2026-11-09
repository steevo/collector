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

// balises proposées pour chaque collection et chaque vue (1 = liste, 0 = fiche)
$tags = [];

foreach ($this->tags as $collection => $clients) {
    foreach ($clients as $client => $list) {
        $tags[$collection][$client] = [];

        foreach ($list as [$tag, $label]) {
            $tags[$collection][$client][] = ['tag' => $tag, 'label' => $label];
        }
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
				<div id="collector-tpl-unknown" class="alert alert-warning" role="status" hidden>
					<span class="icon-warning" aria-hidden="true"></span>
					<?php echo Text::_('COM_COLLECTOR_TEMPLATE_UNKNOWN_TAGS'); ?>
					<span id="collector-tpl-unknown-list"></span>
				</div>
				<?php echo $this->form->renderField('css'); ?>

				<fieldset class="options-form mt-3">
					<legend><?php echo Text::_('COM_COLLECTOR_TEMPLATE_PREVIEW'); ?></legend>
					<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
						<p class="small text-muted mb-0 flex-grow-1"><?php echo Text::_('COM_COLLECTOR_TEMPLATE_PREVIEW_HELP'); ?></p>
						<label class="visually-hidden" for="collector-tpl-preview-item"><?php echo Text::_('COM_COLLECTOR_TEMPLATE_PREVIEW_ITEM'); ?></label>
						<select id="collector-tpl-preview-item" class="form-select form-select-sm w-auto" hidden></select>
						<span id="collector-tpl-preview-wait" class="spinner-border spinner-border-sm text-secondary" role="status" hidden>
							<span class="visually-hidden"><?php echo Text::_('JGLOBAL_LOADING'); ?></span>
						</span>
					</div>
					<iframe id="collector-tpl-preview" title="<?php echo $this->escape(Text::_('COM_COLLECTOR_TEMPLATE_PREVIEW')); ?>"
						sandbox="allow-same-origin" class="w-100 border rounded bg-white" style="min-height:150px;height:300px"></iframe>
				</fieldset>
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

	// collection choisie dans le formulaire : ses champs donnent les balises
	const champCollection = document.getElementById('jform_collection');
	const collection = () => (champCollection ? champCollection.value : '');
	const balises = () => ((tags[collection()] || {})[client()] || []);

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
		balises().forEach(({ tag, label }) => {
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
		const noms = balises()
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

	// signale les balises du contenu qui ne correspondent à rien pour la collection et la vue choisies
	// (même syntaxe que le rendu du site : une balise inconnue y est affichée telle quelle)
	const alerte = document.getElementById('collector-tpl-unknown');
	const liste  = document.getElementById('collector-tpl-unknown-list');
	let dernier  = null;

	const verifier = (force = false) => {
		const texte = contenu();
		if (!force && texte === dernier) {
			return;
		}
		dernier = texte;

		const champs   = new Set();
		const reserves = new Set();
		balises().forEach(({ tag }) => {
			const nom = tag.slice(1, -1);
			if (['link', 'id', 'hits'].includes(nom)) {
				reserves.add(nom);
			} else if (/^[a-z0-9_]+$/.test(nom)) {
				champs.add(nom);
			}
		});
		const structure = client() === '1' ? ['else', 'items'] : ['else'];

		const inconnues = new Set();
		for (const [balise, condition, nom] of texte.matchAll(/\{(?:(if|ifnot):)?([a-z0-9_]+)\}/g)) {
			if (condition ? !(champs.has(nom) || reserves.has(nom)) : !(champs.has(nom) || reserves.has(nom) || structure.includes(nom))) {
				inconnues.add(balise);
			}
		}
		for (const [balise, nom] of texte.matchAll(/\{([a-z0-9_]+):(?:label|value|text|url)\}/g)) {
			if (!champs.has(nom)) {
				inconnues.add(balise);
			}
		}

		liste.innerHTML = '';
		[...inconnues].forEach((balise, i) => {
			if (i) {
				liste.append(' ');
			}
			const code = document.createElement('code');
			code.textContent = balise;
			liste.append(code);
		});
		alerte.hidden = inconnues.size === 0;
	};

	// aperçu : rendu par le serveur avec des éléments de la collection, relancé à chaque modification
	const exemples  = <?php echo json_encode($this->previewItems, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
	const cadre     = document.getElementById('collector-tpl-preview');
	const choixItem = document.getElementById('collector-tpl-preview-item');
	const attente   = document.getElementById('collector-tpl-preview-wait');
	const jeton     = [...document.querySelectorAll('#template-form input[type="hidden"][value="1"]')]
		.find(champ => /^[0-9a-f]{32}$/.test(champ.name));
	const adresse   = <?php echo json_encode(Route::_('index.php?option=com_collector&task=template.preview', false)); ?>;

	const valeur = id => {
		const editeur = window.Joomla && Joomla.editors && Joomla.editors.instances && Joomla.editors.instances[id];
		if (editeur && typeof editeur.getValue === 'function') {
			return editeur.getValue();
		}
		const champ = document.getElementById(id);
		return champ ? champ.value : '';
	};

	const remplirExemples = () => {
		const actuel = choixItem.value;
		choixItem.innerHTML = '';
		(exemples[collection()] || []).forEach(({ id, title }) => {
			const option = new Option(title || ('#' + id), id);
			choixItem.append(option);
		});
		if ([...choixItem.options].some(option => option.value === actuel)) {
			choixItem.value = actuel;
		}
		choixItem.hidden = client() !== '0' || choixItem.options.length === 0;
	};

	let envoye  = null;
	let minuteur = null;
	let requete = null;

	const ajusterCadre = () => {
		try {
			const doc = cadre.contentDocument;
			if (doc && doc.documentElement) {
				cadre.style.height = Math.min(Math.max(doc.documentElement.scrollHeight, 150), 1200) + 'px';
			}
		} catch (e) {
			// cadre inaccessible : on garde sa hauteur
		}
	};
	cadre.addEventListener('load', ajusterCadre);

	const apercu = (force = false) => {
		const donnees = {
			content: valeur('jform_content'),
			css: valeur('jform_css'),
			collection: collection(),
			client: client(),
			item: client() === '0' ? choixItem.value : '',
		};
		const cle = JSON.stringify(donnees);
		if (!force && cle === envoye) {
			return;
		}
		envoye = cle;

		clearTimeout(minuteur);
		minuteur = setTimeout(() => {
			if (requete) {
				requete.abort();
			}
			requete = new AbortController();

			const corps = new FormData();
			corps.append('jform[content]', donnees.content);
			corps.append('jform[css]', donnees.css);
			corps.append('jform[collection]', donnees.collection);
			corps.append('jform[client]', donnees.client);
			corps.append('jform[id]', <?php echo (int) $this->item->id; ?>);
			corps.append('item', donnees.item);
			if (jeton) {
				corps.append(jeton.name, '1');
			}

			attente.hidden = false;
			fetch(adresse, { method: 'POST', body: corps, signal: requete.signal, credentials: 'same-origin' })
				.then(reponse => reponse.text())
				.then(html => {
					cadre.srcdoc = html;
					attente.hidden = true;
				})
				.catch(erreur => {
					if (erreur.name !== 'AbortError') {
						attente.hidden = true;
					}
				});
		}, 400);
	};

	choixItem.addEventListener('change', () => apercu(true));

	const actualiser = () => {
		afficher();
		verifier(true);
		remplirExemples();
		apercu();
	};

	document.querySelectorAll('input[name="jform[client]"]').forEach(radio => radio.addEventListener('change', actualiser));
	if (champCollection) {
		champCollection.addEventListener('change', () => {
			// retour, après enregistrement, sur la liste des templates de la nouvelle collection
			const retour = document.querySelector('#template-form input[name="collection"]');
			if (retour) {
				retour.value = collection();
			}
			actualiser();
		});
	}
	afficher();

	// l'éditeur (CodeMirror ou zone de texte) n'a pas d'événement commun : on relit le contenu régulièrement
	remplirExemples();
	window.addEventListener('load', () => {
		verifier(true);
		apercu(true);
	});
	setInterval(() => {
		verifier();
		apercu();
	}, 300);
})();
</script>
