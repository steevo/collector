/**
 * Collector — liste ordonnée de champs (titre personnalisé, tri suivant).
 *
 * Les champs choisis forment une liste qu'on réordonne par glisser-déposer ou avec les flèches ;
 * la liste déroulante ajoute un champ encore libre. La valeur du champ caché est la suite des
 * identifiants séparés par « / », ou 0 si la liste est vide.
 *
 * @copyright  Copyright (C) 2010 - 2026 Philippe Ousset. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
(() => {
  'use strict';

  const setup = (box) => {
    const input = document.getElementById(box.dataset.input);
    const list = box.querySelector('[data-role="list"]');
    const add = box.querySelector('[data-role="add"]');
    const addButton = box.querySelector('[data-role="add-button"]');
    const preview = box.querySelector('[data-role="preview"]');
    const names = JSON.parse(box.dataset.names || '{}');
    const order = Object.keys(names); // ordre des champs dans la collection
    const addLabel = add.getAttribute('aria-label');

    let selected = String(input.value || '0').split('/').filter((id) => id !== '0' && names[id] !== undefined);
    let dragged = null;

    const button = (icon, label, action, extra = '') => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = `btn btn-sm btn-outline-secondary ${extra}`;
      b.dataset.action = action;
      b.setAttribute('aria-label', label);
      b.title = label;
      b.innerHTML = `<span class="icon-${icon}" aria-hidden="true"></span>`;
      return b;
    };

    const render = (focus) => {
      input.value = selected.length ? selected.join('/') : '0';
      input.dispatchEvent(new Event('change', { bubbles: true }));

      list.replaceChildren();

      if (!selected.length) {
        const li = document.createElement('li');
        li.className = 'list-group-item text-muted small';
        li.textContent = box.dataset.empty;
        list.append(li);
      }

      selected.forEach((id, i) => {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex align-items-center gap-2';
        li.dataset.id = id;
        li.draggable = true;

        const handle = document.createElement('span');
        handle.className = 'icon-ellipsis-v text-muted';
        handle.style.cursor = 'grab';
        handle.setAttribute('aria-hidden', 'true');

        const rank = document.createElement('span');
        rank.className = 'badge bg-secondary';
        rank.textContent = String(i + 1);

        const name = document.createElement('span');
        name.className = 'flex-grow-1';
        name.textContent = names[id];

        const up = button('arrow-up', box.dataset.up, 'up');
        const down = button('arrow-down', box.dataset.down, 'down');
        const remove = button('times', box.dataset.remove, 'remove', 'text-danger');
        up.disabled = i === 0;
        down.disabled = i === selected.length - 1;

        li.append(handle, rank, name, up, down, remove);
        list.append(li);
      });

      // Champs encore libres, dans l'ordre de la collection
      add.replaceChildren(new Option(addLabel, ''));
      order.filter((id) => !selected.includes(id)).forEach((id) => add.append(new Option(names[id], id)));
      add.disabled = add.options.length === 1;
      addButton.disabled = add.disabled;

      const text = selected.map((id) => names[id]).join(box.dataset.join);
      preview.textContent = text ? box.dataset.preview.replace('%s', text) : '';

      if (focus) {
        list.querySelector(`[data-id="${focus.id}"] [data-action="${focus.action}"]:not(:disabled)`)?.focus();
      }
    };

    list.addEventListener('click', (e) => {
      const b = e.target.closest('[data-action]');
      if (!b) {
        return;
      }
      const id = b.closest('[data-id]').dataset.id;
      const i = selected.indexOf(id);

      if (b.dataset.action === 'up' && i > 0) {
        [selected[i - 1], selected[i]] = [selected[i], selected[i - 1]];
      } else if (b.dataset.action === 'down' && i < selected.length - 1) {
        [selected[i + 1], selected[i]] = [selected[i], selected[i + 1]];
      } else if (b.dataset.action === 'remove') {
        selected.splice(i, 1);
        render();
        add.focus();
        return;
      }
      render({ id, action: b.dataset.action });
    });

    const addSelected = () => {
      if (add.value) {
        selected.push(add.value);
        render();
        add.focus();
      }
    };
    addButton.addEventListener('click', addSelected);
    add.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        addSelected();
      }
    });

    // Glisser-déposer des lignes
    list.addEventListener('dragstart', (e) => {
      dragged = e.target.closest('[data-id]');
      if (dragged) {
        dragged.classList.add('opacity-50');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragged.dataset.id);
      }
    });
    list.addEventListener('dragover', (e) => {
      const over = e.target.closest('[data-id]');
      if (!dragged || !over || over === dragged) {
        return;
      }
      e.preventDefault();
      const rect = over.getBoundingClientRect();
      over[e.clientY > rect.top + rect.height / 2 ? 'after' : 'before'](dragged);
    });
    list.addEventListener('dragend', () => {
      if (dragged) {
        dragged.classList.remove('opacity-50');
        selected = [...list.querySelectorAll('[data-id]')].map((li) => li.dataset.id);
        dragged = null;
        render();
      }
    });

    render();
  };

  const init = () => document.querySelectorAll('.collector-ordered').forEach(setup);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
