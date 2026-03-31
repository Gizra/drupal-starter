/**
 * @file
 * Write/Preview tab behaviour for the paragraph entity edit form.
 *
 * The "Write" tab shows the edit form fields.
 * The "Preview" tab submits the current form values via Drupal AJAX, stores
 * them server-side, then fetches the preview route (rendered with server_theme
 * via ParagraphPreviewThemeNegotiator) and injects the content inline.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Custom AJAX command sent by the server after storing the preview.
   *
   * Fetches the preview route (rendered with server_theme), extracts the
   * main content and its stylesheets, and injects them into the preview panel.
   */
  Drupal.AjaxCommands.prototype.pseActivatePreviewPanel = function (ajax, response) {
    const form = document.querySelector('.pse-edit-form');
    const previewPanel = form && form.querySelector('[data-pse-panel="preview"]');
    if (!previewPanel || !response.previewUrl) {
      return;
    }

    fetch(response.previewUrl)
      .then((res) => res.text())
      .then((html) => {
        const doc = new DOMParser().parseFromString(html, 'text/html');

        // Inject any stylesheets from the server_theme page that aren't already
        // present on the admin page, so Tailwind classes render correctly.
        doc.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
          if (!document.querySelector(`link[href="${link.getAttribute('href')}"]`)) {
            document.head.appendChild(link.cloneNode(true));
          }
        });

        // Extract and inject the main content area.
        const main = doc.querySelector('main') || doc.body;
        previewPanel.innerHTML = main.innerHTML;

        pseSetActivePanel(form, 'preview');
      });
  };

  /**
   * Toggles the visible panel and updates tab active state.
   *
   * @param {HTMLElement} form - The edit form element.
   * @param {string} panelName - 'write' or 'preview'.
   */
  function pseSetActivePanel(form, panelName) {
    form.querySelectorAll('[data-pse-target]').forEach((tab) => {
      tab.classList.toggle('pse-tab--active', tab.dataset.pseTarget === panelName);
    });

    const writePanel = form.querySelector('.pse-write-panel');
    const previewPanel = form.querySelector('[data-pse-panel="preview"]');

    if (writePanel) {
      writePanel.hidden = panelName !== 'write';
    }
    if (previewPanel) {
      previewPanel.hidden = panelName !== 'preview';
    }
  }

  Drupal.behaviors.paragraphsSimpleEditWritePreview = {
    attach(context) {
      once('pse-write-preview', '.pse-edit-form', context).forEach((form) => {
        const tabs = form.querySelector('.pse-tabs');
        if (!tabs) {
          return;
        }

        const previewPanel = form.querySelector('[data-pse-panel="preview"]');
        const actionsEl = form.querySelector('.form-actions');

        // Dynamically wrap all field elements (between tabs and preview panel)
        // in a "write panel" div so we can hide/show them as a unit.
        const writePanel = document.createElement('div');
        writePanel.className = 'pse-write-panel';

        const elementsToWrap = [];
        let el = tabs.nextElementSibling;
        const stopEl = previewPanel || actionsEl;
        while (el && el !== stopEl) {
          elementsToWrap.push(el);
          el = el.nextElementSibling;
        }

        if (elementsToWrap.length > 0) {
          tabs.after(writePanel);
          elementsToWrap.forEach((e) => writePanel.appendChild(e));
        }

        // Start on the Write panel.
        if (previewPanel) {
          previewPanel.hidden = true;
        }

        // Write tab click — switch panels locally without a server round-trip.
        const writeTab = tabs.querySelector('[data-pse-target="write"]');
        if (writeTab) {
          writeTab.addEventListener('click', () => pseSetActivePanel(form, 'write'));
        }

        // The Preview tab is a Drupal AJAX submit button.
        // After the server stores the preview entity, it returns the
        // pseActivatePreviewPanel command which fetches the themed preview
        // and injects it into the panel.
      });
    },
  };
}(Drupal, once));
