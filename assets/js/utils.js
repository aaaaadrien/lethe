// Lethe - shared namespace, state and utilities.
'use strict';

window.Lethe = window.Lethe || {};

Lethe.state = {
  csrfToken: '',
  currentUser: null,
  currentView: 'dashboard',
};

Lethe.config = window.letheConfig || {};

// Client-side i18n. The catalog of the language detected on the server
// (Accept-Language header, English fallback) is embedded in the page as
// window.letheI18n; see src/Language.php and lang/*.php.
Lethe.i18n = {
  lang: (window.letheI18n && window.letheI18n.lang) || 'en',
  strings: (window.letheI18n && window.letheI18n.strings) || {},

  /**
   * Translate a key, substituting ":name" placeholders from params.
   * Falls back to the key itself when it is missing from the catalog.
   */
  t(key, params = {}) {
    let text = Lethe.i18n.strings[key];
    if (text === undefined) text = key;
    for (const name of Object.keys(params)) {
      text = text.split(':' + name).join(String(params[name]));
    }
    return text;
  },

  byteUnits() {
    return Lethe.i18n.lang === 'fr' ? ['o', 'Ko', 'Mo', 'Go', 'To'] : ['B', 'KB', 'MB', 'GB', 'TB'];
  },

  dateFormat() {
    return Lethe.i18n.lang === 'fr' ? 'd/m/Y H:i' : 'm/d/Y H:i';
  }
};

Lethe.utils = {
  escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  },

  formatBytes(bytes) {
    if (!bytes || bytes <= 0) return '0 ' + Lethe.i18n.byteUnits()[0];
    const units = Lethe.i18n.byteUnits();
    let size = bytes;
    let unit = 0;
    while (size >= 1024 && unit < units.length - 1) {
      size /= 1024;
      unit++;
    }
    return size.toFixed(2) + ' ' + units[unit];
  },

  /**
   * Format an ISO-8601 date (as stored in the database) using the locale
   * format (d/m/Y H:i in French, m/d/Y H:i otherwise).
   */
  formatDate(iso) {
    if (!iso) return '-';
    const d = new Date(iso);
    if (isNaN(d.getTime())) return iso;
    const pad = (n) => String(n).padStart(2, '0');
    const parts = {
      d: pad(d.getDate()),
      m: pad(d.getMonth() + 1),
      Y: d.getFullYear(),
      H: pad(d.getHours()),
      i: pad(d.getMinutes()),
    };
    return Lethe.i18n.dateFormat().replace(/Y|m|d|H|i/g, (token) => parts[token]);
  },

  /**
   * Fetch an API action (?action=...) with the CSRF header.
   * Resolves with the decoded JSON, rejects with an Error on failure.
   */
  async fetchAction(action, options = {}) {
    options.cache = 'no-store';
    options.headers = Object.assign({}, options.headers);
    if (Lethe.state.csrfToken) {
      options.headers['X-CSRF-Token'] = Lethe.state.csrfToken;
    }
    const res = await fetch('?action=' + encodeURIComponent(action), options);
    let data = null;
    try {
      data = await res.json();
    } catch (e) {
      data = null;
    }
    if (!res.ok || !data || data.status !== 'ok') {
      const message = (data && data.message) ? data.message : Lethe.i18n.t('error.server_status', { status: res.status });
      throw new Error(message);
    }
    return data;
  },

  /**
   * POST a JSON body to an API action.
   */
  async postAction(action, body = {}) {
    return Lethe.utils.fetchAction(action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
  },

  showToast(message, type = 'success') {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '1100';
    const bgClass = type === 'error' ? 'danger' : (type === 'warning' ? 'warning' : 'secondary');
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white bg-${bgClass} border-0 rounded-4`;
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = `
      <div class="d-flex">
        <div class="toast-body text-truncate">${Lethe.utils.escapeHtml(message)}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>`;
    document.body.appendChild(container);
    container.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => container.remove());
  },

  /**
   * Copy text to the clipboard with temporary visual feedback on the button.
   */
  copyToClipboard(button, text) {
    const originalLabel = button.dataset.originalLabel || button.innerHTML;
    button.dataset.originalLabel = originalLabel;

    const onCopied = () => {
      button.innerHTML = '<i class="bi bi-check-lg"></i> ' + Lethe.i18n.t('common.copied');
      setTimeout(() => { button.innerHTML = originalLabel; }, 1500);
    };
    const onFailed = () => {
      button.innerHTML = Lethe.i18n.t('common.failed');
      setTimeout(() => { button.innerHTML = originalLabel; }, 1500);
    };

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(onCopied, onFailed);
    } else {
      // Fallback for non-secure contexts (HTTP) where the Clipboard API is unavailable.
      const tmp = document.createElement('textarea');
      tmp.value = text;
      tmp.style.position = 'fixed';
      tmp.style.opacity = '0';
      document.body.appendChild(tmp);
      tmp.select();
      try {
        document.execCommand('copy');
        onCopied();
      } catch (e) {
        onFailed();
      }
      document.body.removeChild(tmp);
    }
  },

  /**
   * Generic confirmation modal. Resolves true when the user confirms.
   */
  confirm({ title = Lethe.i18n.t('common.confirm'), message = '', confirmLabel = Lethe.i18n.t('common.confirm'), danger = false } = {}) {
    return new Promise((resolve) => {
      const modalEl = document.getElementById('confirm-modal');
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      const titleEl = document.getElementById('confirm-modal-title');
      const messageEl = document.getElementById('confirm-modal-message');
      const btn = document.getElementById('confirm-modal-btn');

      titleEl.textContent = title;
      messageEl.textContent = message;
      btn.textContent = confirmLabel;
      btn.className = 'btn rounded-pill ' + (danger ? 'btn-outline-danger' : 'btn-accent');

      let settled = false;
      const finish = (value) => {
        if (settled) return;
        settled = true;
        btn.removeEventListener('click', onConfirm);
        modalEl.removeEventListener('hidden.bs.modal', onDismiss);
        resolve(value);
      };
      const onConfirm = () => {
        modal.hide();
        finish(true);
      };
      const onDismiss = () => finish(false);

      btn.addEventListener('click', onConfirm);
      modalEl.addEventListener('hidden.bs.modal', onDismiss);
      modal.show();
    });
  },
};
