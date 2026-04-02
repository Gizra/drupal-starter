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
   * Scripts from the preview page are loaded in order so JS components such
   * as the Slick carousel initialise correctly.
   */
  Drupal.AjaxCommands.prototype.pseActivatePreviewPanel = function (ajax, response) {
    const form = document.querySelector('.pse-edit-form');
    const previewPanel = form && form.querySelector('[data-pse-panel="preview"]');
    if (!previewPanel || !response.previewUrl) {
      return;
    }

    // Show the preview panel with a loading message while the fetch runs.
    // The AJAX throbber disappears once the server stores the preview, but
    // the actual content has not loaded yet.
    const shadowRoot = previewPanel.shadowRoot
      || previewPanel.attachShadow({ mode: 'open' });
    shadowRoot.textContent = Drupal.t('Loading preview\u2026');
    pseSetActivePanel(form, 'preview');

    fetch(response.previewUrl)
      .then((res) => res.text())
      .then((html) => {
        const doc = new DOMParser().parseFromString(html, 'text/html');

        // Clear the shadow root (already attached above with loading text).
        shadowRoot.innerHTML = '';

        // Inject frontend stylesheets (theme + shared libraries) into both:
        // 1. The shadow root — so Tailwind utilities apply to preview content.
        // 2. document.head — so @property registrations (which give Tailwind
        //    CSS custom properties their initial-value) become document-scoped.
        //    @property rules inside shadow root <link> elements are not
        //    registered globally, so var(--tw-border-style) etc. would be
        //    undefined without this second injection.
        // Drupal core/module CSS is excluded to avoid conflicts with Tailwind.
        doc.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
          const href = link.getAttribute('href') || '';
          if (!href.includes('/themes/') && !href.includes('/libraries/')) {
            return;
          }
          shadowRoot.appendChild(link.cloneNode(true));
          if (!document.querySelector(`link[href="${CSS.escape(href)}"]`)) {
            document.head.appendChild(link.cloneNode(true));
          }
        });

        // Inject the main content into a wrapper inside the shadow root.
        const container = document.createElement('div');
        container.innerHTML = (doc.querySelector('main') || doc.body).innerHTML;
        shadowRoot.appendChild(container);

        // Load scripts from the preview page in dependency order, skipping
        // any already present. Pass the shadow container as context so Slick
        // (and other jQuery queries) scope to the injected content.
        const scriptSrcs = [...doc.querySelectorAll('script[src]')]
          .map((s) => s.getAttribute('src'))
          .filter(Boolean);

        pseLoadScripts(scriptSrcs, () => {
          requestAnimationFrame(() => {
            // Trigger all Drupal.behaviors on the injected content. Behaviors
            // register synchronously at script parse time so this is safe even
            // for dynamically loaded scripts.
            Drupal.attachBehaviors(container);
          });
        });
      })
      .catch(() => {
        shadowRoot.textContent = Drupal.t('Preview failed to load.');
        pseSetActivePanel(form, 'write');
      });
  };

  /**
   * Loads an array of script URLs sequentially, skipping already-loaded ones.
   *
   * @param {string[]} srcs - Ordered list of script URLs.
   * @param {Function} callback - Called after all scripts have been processed.
   */
  function pseLoadScripts(srcs, callback) {
    const pending = srcs.filter((src) => !document.querySelector(`script[src="${CSS.escape(src)}"]`));
    function loadNext(index) {
      if (index >= pending.length) {
        callback();
        return;
      }
      const script = document.createElement('script');
      script.src = pending[index];
      script.onload = () => loadNext(index + 1);
      script.onerror = () => loadNext(index + 1);
      document.head.appendChild(script);
    }
    loadNext(0);
  }

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
