/**
 * Collector : pages de versions d'un objet (comparaison, aperçu) dans une fenêtre intégrée à la page.
 *
 * Page des versions : Joomla.collectorVersionDialog.open(url) affiche la page dans un <dialog>
 * (cadre intégré) au lieu d'une fenêtre de navigateur séparée. Si la page des versions est elle-même
 * dans un cadre (fenêtre « Historique des versions » de la fiche objet), la fenêtre s'ouvre dans la
 * page principale, quand celle-ci charge aussi ce script.
 * Page affichée : Joomla.collectorVersionDialog.finish(url) envoie la page des versions qui l'a ouverte
 * à cette adresse après une action (validation, refus, rétablissement) et referme la fenêtre ;
 * closeSelf() la referme. Ouverte seule (lien direct), la page continue de fonctionner.
 */
(function () {
	'use strict';

	var dialog = null;
	var frame  = null;
	var owner  = null;

	function addStyle() {
		var style = document.createElement('style');
		style.textContent =
			'.collector-version-dialog{padding:0;border:0;border-radius:.5rem;overflow:hidden;'
			+ 'width:min(1100px,96vw);max-width:96vw;max-height:94vh;background:var(--body-bg,#fff);'
			+ 'box-shadow:0 .5rem 2rem rgba(0,0,0,.35)}'
			+ '.collector-version-dialog::backdrop{background:rgba(0,0,0,.5)}'
			+ '.collector-version-dialog iframe{display:block;width:100%;height:80vh;max-height:90vh;border:0}';
		document.head.appendChild(style);
	}

	function build() {
		addStyle();

		dialog = document.createElement('dialog');
		dialog.className = 'collector-version-dialog';
		frame = document.createElement('iframe');
		dialog.appendChild(frame);
		document.body.appendChild(dialog);

		// Clic à côté de la fenêtre : fermeture
		dialog.addEventListener('click', function (event) {
			if (event.target === dialog) {
				dialog.close();
			}
		});

		dialog.addEventListener('close', function () {
			frame.removeAttribute('src');
			frame.style.height = '';
		});

		frame.addEventListener('load', function () {
			var doc;

			try {
				doc = frame.contentDocument;
			} catch (e) {
				return;
			}

			if (!doc || !doc.body || !frame.getAttribute('src')) {
				return;
			}

			frame.title = doc.title;

			// Hauteur ajustée au contenu, entre 420 px et 90 % de la fenêtre
			var height = Math.max(doc.documentElement.scrollHeight, doc.body.scrollHeight);
			frame.style.height = Math.min(Math.max(height, 420), window.innerHeight * 0.9) + 'px';

			// Échap dans la page affichée ferme aussi la fenêtre (sauf une modale ouverte dans la page)
			doc.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && !doc.querySelector('.modal.show')) {
					dialog.close();
				}
			});
		});
	}

	/** API de la page principale, si la page courante est dans un cadre et que la page principale la charge */
	function topApi() {
		try {
			if (window.top !== window && window.top.Joomla && window.top.Joomla.collectorVersionDialog) {
				return window.top.Joomla.collectorVersionDialog;
			}
		} catch (e) {
			// page principale d'une autre origine
		}

		return null;
	}

	/** API de la page qui affiche la page courante dans sa fenêtre intégrée */
	function parentApi() {
		try {
			if (window.parent !== window && window.parent.Joomla && window.parent.Joomla.collectorVersionDialog) {
				return window.parent.Joomla.collectorVersionDialog;
			}
		} catch (e) {
			// page parente d'une autre origine
		}

		return null;
	}

	/** Page des versions sans le gabarit (cadre de la fiche objet) : on le garde après l'action */
	function keepTmpl(url, location) {
		if (/[?&]tmpl=component/.test(location.search) && !/[?&]tmpl=/.test(url)) {
			return url + (url.indexOf('?') < 0 ? '?' : '&') + 'tmpl=component';
		}

		return url;
	}

	/**
	 * Ouvre la page dans la fenêtre intégrée.
	 * @param {string} url
	 * @param {Window} [from]  page des versions qui suivra les actions (par défaut : la page courante)
	 */
	function open(url, from) {
		var api = from ? null : topApi();

		if (api) {
			api.open(new URL(url, window.location.href).href, window);

			return;
		}

		if (!dialog) {
			build();
		}

		owner = from || window;
		frame.style.height = '';
		frame.src = url;
		dialog.showModal();
	}

	function close() {
		if (dialog && dialog.open) {
			dialog.close();
		}
	}

	/** Action faite dans la page affichée : la page des versions va à l'adresse, la fenêtre se ferme */
	function done(url) {
		var target = window;

		try {
			if (owner && owner !== window && !owner.closed && owner.location) {
				target = owner;
			}
		} catch (e) {
			target = window;
		}

		close();
		target.location.href = keepTmpl(new URL(url, window.location.href).href, target.location);
	}

	function finish(url) {
		var api = parentApi();

		if (api) {
			api.done(new URL(url, window.location.href).href);
		} else if (window.opener && !window.opener.closed) {
			window.opener.location.href = url;
			window.close();
		} else {
			window.location.href = url;
		}
	}

	function closeSelf() {
		var api = parentApi();

		if (api) {
			api.close();
		} else {
			window.close();
		}
	}

	window.Joomla = window.Joomla || {};
	window.Joomla.collectorVersionDialog = {open: open, close: close, done: done, finish: finish, closeSelf: closeSelf};
})();
