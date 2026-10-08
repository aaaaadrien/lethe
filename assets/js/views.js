// Lethe - SPA views (rendered client-side into #content-area).
'use strict';

window.Lethe = window.Lethe || {};

const U = () => Lethe.utils;
const E = (s) => Lethe.utils.escapeHtml(s);
const T = (key, params) => Lethe.i18n.t(key, params);

Lethe.views = {

  /* ---------------- Dashboard ---------------- */
  async dashboard(container) {
    const user = Lethe.state.currentUser;
    const tiles = [
      { view: 'send_file', icon: 'bi-send', title: T('nav.send_file'), description: T('dashboard.tile_send_file') },
      { view: 'my_files', icon: 'bi-folder2', title: T('nav.my_files'), description: T('dashboard.tile_my_files') },
      { view: 'my_deposits', icon: 'bi-inbox', title: T('nav.my_deposits'), description: T('dashboard.tile_my_deposits') },
      { view: 'secret', icon: 'bi-envelope', title: T('nav.secret'), description: T('dashboard.tile_secret') },
      { view: 'my_secrets', icon: 'bi-key', title: T('nav.my_secrets'), description: T('dashboard.tile_my_secrets') },
    ];
    if (user.is_admin) {
      tiles.push({ view: 'users', icon: 'bi-people', title: T('nav.users'), description: T('dashboard.tile_users') });
    }

    container.innerHTML = `
      <div class="mb-4">
        <h2 class="h5 mb-1">${T('dashboard.welcome', { name: E(user.username) })}</h2>
        <p class="text-secondary mb-0">${T('dashboard.subtitle')}</p>
      </div>
      <div class="row g-3">
        ${tiles.map((t) => `
          <div class="col-12 col-md-6 col-lg-4">
            <a href="#" class="card dashboard-tile h-100" data-goto="${t.view}">
              <div class="card-body">
                <div class="tile-icon"><i class="bi ${t.icon}"></i></div>
                <h2>${E(t.title)}</h2>
                <p>${E(t.description)}</p>
              </div>
            </a>
          </div>`).join('')}
      </div>`;

    container.querySelectorAll('[data-goto]').forEach((el) => {
      el.addEventListener('click', (e) => {
        e.preventDefault();
        Lethe.loadView(el.dataset.goto);
      });
    });
  },

  /* ---------------- Send file ---------------- */
  async send_file(container) {
    const cfg = Lethe.config;
    container.innerHTML = `
      <div class="card">
        <div class="card-body p-4">
          <div id="file-drop-zone" class="file-drop-zone mb-3">
            <i class="bi bi-cloud-arrow-up d-block mb-2"></i>
            <p class="mb-1">${T('dropzone.text')}</p>
            <p class="small mb-0" style="color:#777">${T('sendfile.max_size', { size: U().formatBytes(cfg.maxFileSize) })}</p>
            <input type="file" id="file-input" class="d-none">
          </div>
          <div id="selected-file" class="mb-3 text-secondary small"></div>

          <form id="share-form">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="password">${T('sendfile.password')}</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="${T('sendfile.password_placeholder')}">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="expiration_days">${T('sendfile.expiration')}</label>
                <input type="number" name="expiration_days" id="expiration_days" class="form-control"
                       min="${cfg.minExpirationDays}" max="${cfg.maxExpirationDays}" value="${cfg.defaultExpirationDays}" required>
                <div class="form-text">${T('sendfile.expiration_hint', { min: cfg.minExpirationDays, max: cfg.maxExpirationDays })}</div>
              </div>
              <div class="col-12">
                <span class="form-label d-block">${T('sendfile.share_mode')}</span>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="delivery_method" id="delivery_link" value="link" checked>
                  <label class="form-check-label" for="delivery_link">${T('sendfile.method_link')}</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="delivery_method" id="delivery_email" value="email">
                  <label class="form-check-label" for="delivery_email">${T('sendfile.method_email')}</label>
                </div>
              </div>
              <div id="email-fields" class="col-12" style="display:none;">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label" for="recipient_email">${T('sendfile.recipient_email')}</label>
                    <input type="email" name="recipient_email" id="recipient_email" class="form-control">
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="custom_message">${T('sendfile.custom_message')}</label>
                    <textarea name="custom_message" id="custom_message" class="form-control" rows="4"
                              placeholder="${T('sendfile.custom_message_placeholder')}"></textarea>
                  </div>
                </div>
              </div>
            </div>

            <div id="progress-wrapper" class="mt-4" style="display:none;">
              <div class="progress">
                <div id="progress-bar" class="progress-bar" role="progressbar" style="width:0%">0%</div>
              </div>
            </div>

            <button type="submit" id="submit-btn" class="btn btn-accent rounded-pill mt-4 px-4" disabled>
              <i class="bi bi-send me-2"></i>${T('sendfile.submit')}
            </button>
          </form>

          <div id="result" class="mt-4" style="display:none;"></div>
          <div id="error" class="alert alert-danger mt-4" style="display:none;"></div>
        </div>
      </div>`;

    const dropZone = container.querySelector('#file-drop-zone');
    const fileInput = container.querySelector('#file-input');
    const selectedFileEl = container.querySelector('#selected-file');
    const submitBtn = container.querySelector('#submit-btn');
    const form = container.querySelector('#share-form');
    const progressWrapper = container.querySelector('#progress-wrapper');
    const progressBar = container.querySelector('#progress-bar');
    const resultEl = container.querySelector('#result');
    const errorEl = container.querySelector('#error');
    const deliveryLink = container.querySelector('#delivery_link');
    const deliveryEmail = container.querySelector('#delivery_email');
    const emailFields = container.querySelector('#email-fields');

    let selectedFile = null;

    const showError = (msg) => { errorEl.textContent = msg; errorEl.style.display = 'block'; };
    const hideError = () => { errorEl.style.display = 'none'; };

    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', (e) => {
      e.preventDefault();
      dropZone.classList.remove('dragover');
      if (e.dataTransfer.files.length) setFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', () => {
      if (fileInput.files.length) setFile(fileInput.files[0]);
    });

    function setFile(file) {
      if (file.size > cfg.maxFileSize) {
        showError(T('error.file_too_large'));
        selectedFile = null;
        submitBtn.disabled = true;
        selectedFileEl.textContent = '';
        return;
      }
      selectedFile = file;
      selectedFileEl.textContent = T('sendfile.file_selected', { name: file.name, size: U().formatBytes(file.size) });
      submitBtn.disabled = false;
      hideError();
    }

    [deliveryLink, deliveryEmail].forEach((el) => el.addEventListener('change', () => {
      emailFields.style.display = deliveryEmail.checked ? 'block' : 'none';
      container.querySelector('#recipient_email').required = deliveryEmail.checked;
    }));

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!selectedFile) return;
      hideError();
      resultEl.style.display = 'none';
      submitBtn.disabled = true;
      progressWrapper.style.display = 'block';

      try {
        const { uploadId, totalChunks } = await Lethe.upload.sendChunks(
          selectedFile,
          '?action=upload_chunk',
          {},
          (done, total) => {
            const percent = Math.round((done / total) * 100);
            progressBar.style.width = percent + '%';
            progressBar.textContent = percent + '%';
          }
        );

        const data = await U().postAction('finalize_upload', {
          upload_id: uploadId,
          total_chunks: totalChunks,
          original_name: selectedFile.name,
          file_size: selectedFile.size,
          password: form.password.value,
          expiration_days: parseInt(form.expiration_days.value, 10),
          delivery_method: deliveryEmail.checked ? 'email' : 'link',
          recipient_email: deliveryEmail.checked ? form.recipient_email.value : '',
          custom_message: form.custom_message.value,
        });

        progressWrapper.style.display = 'none';
        if (data.sent_by_email) {
          resultEl.innerHTML = `
            <div class="result-box">
              <div class="result-title"><i class="bi bi-check-circle"></i> ${T('sendfile.success_email')}</div>
              <p class="mb-0 text-secondary small">${T('sendfile.success_email_note')}</p>
            </div>`;
        } else {
          resultEl.innerHTML = `
            <div class="result-box">
              <div class="result-title"><i class="bi bi-check-circle"></i> ${T('sendfile.success')}</div>
              <div class="d-flex align-items-center flex-wrap gap-2">
                <code class="share-link flex-grow-1" id="result-link-text">${E(data.link)}</code>
                <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm-copy" data-link="${E(data.link)}">
                  <i class="bi bi-clipboard me-1"></i>${T('common.copy')}
                </button>
              </div>
              <p class="small mt-2 mb-0" style="color:#777">${T('sendfile.success_note')}</p>
            </div>`;
          resultEl.querySelector('.btn-sm-copy').addEventListener('click', (ev) => {
            U().copyToClipboard(ev.currentTarget, data.link);
          });
        }
        resultEl.style.display = 'block';

        form.reset();
        selectedFile = null;
        selectedFileEl.textContent = '';
        emailFields.style.display = 'none';
      } catch (err) {
        showError(err.message);
        progressWrapper.style.display = 'none';
      } finally {
        submitBtn.disabled = false;
      }
    });
  },

  /* ---------------- My files ---------------- */
  async my_files(container) {
    const data = await U().fetchAction('get_my_files');
    const files = data.files;

    const statusBadge = {
      active: `<span class="badge badge-success">${T('status.active')}</span>`,
      expired: `<span class="badge badge-danger">${T('status.expired')}</span>`,
      revoked: `<span class="badge badge-secondary">${T('status.revoked')}</span>`,
    };

    container.innerHTML = `
      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive-dark">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>${T('common.name')}</th>
                  <th>${T('common.size')}</th>
                  <th>${T('common.status')}</th>
                  <th>${T('common.expiration')}</th>
                  <th>${T('common.downloads')}</th>
                  <th class="text-end">${T('common.actions')}</th>
                </tr>
              </thead>
              <tbody>
                ${files.length === 0 ? `
                  <tr><td colspan="6">
                    <div class="empty-state">
                      <i class="bi bi-folder2"></i>
                      ${T('myfiles.empty')}
                    </div>
                  </td></tr>` : files.map((f) => `
                  <tr>
                    <td class="file-name">${E(f.name)}${f.has_password ? ` <span class="badge badge-info">${T('status.protected')}</span>` : ''}</td>
                    <td>${U().formatBytes(f.size)}</td>
                    <td>${statusBadge[f.status]}</td>
                    <td>${U().formatDate(f.expires_at)}</td>
                    <td>${f.download_count}</td>
                    <td class="text-end text-nowrap">
                      ${f.status === 'active' ? `
                        <button type="button" class="btn-icon accent" title="${T('action.copy_link')}" data-copy="${E(f.link)}">
                          <i class="bi bi-link-45deg"></i>
                        </button>` : ''}
                      ${f.status !== 'revoked' ? `
                        <button type="button" class="btn-icon" title="${T('action.revoke_link')}" data-revoke="${f.id}">
                          <i class="bi bi-slash-circle"></i>
                        </button>` : ''}
                      <button type="button" class="btn-icon danger" title="${T('common.delete')}" data-delete="${f.id}">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>`).join('')}
              </tbody>
            </table>
          </div>
        </div>
      </div>`;

    container.querySelectorAll('[data-copy]').forEach((btn) => {
      btn.addEventListener('click', () => U().copyToClipboard(btn, btn.dataset.copy));
    });

    container.querySelectorAll('[data-revoke]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const ok = await U().confirm({
          title: T('confirm.revoke.title'),
          message: T('confirm.revoke.message'),
          confirmLabel: T('confirm.revoke.label'),
          danger: true,
        });
        if (!ok) return;
        try {
          const res = await U().postAction('revoke_file', { id: parseInt(btn.dataset.revoke, 10) });
          U().showToast(res.message);
          Lethe.views.my_files(container);
        } catch (err) {
          U().showToast(err.message, 'error');
        }
      });
    });

    container.querySelectorAll('[data-delete]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const ok = await U().confirm({
          title: T('confirm.delete_file.title'),
          message: T('confirm.delete_file.message'),
          confirmLabel: T('common.delete'),
          danger: true,
        });
        if (!ok) return;
        try {
          const res = await U().postAction('delete_file', { id: parseInt(btn.dataset.delete, 10) });
          U().showToast(res.message);
          Lethe.views.my_files(container);
        } catch (err) {
          U().showToast(err.message, 'error');
        }
      });
    });
  },

  /* ---------------- My deposits ---------------- */
  async my_deposits(container) {
    const cfg = Lethe.config;
    container.innerHTML = `
      <div class="card mb-4">
        <div class="card-body">
          <h2 class="h6 mb-3">${T('mydeposits.create_title')}</h2>
          <form id="create-deposit-form" class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="deposit-label">${T('mydeposits.label')}</label>
              <input type="text" name="label" id="deposit-label" class="form-control" placeholder="${T('mydeposits.label_placeholder')}" required>
            </div>
            <div class="col-md-3">
              <label class="form-label" for="deposit-password">${T('sendfile.password')}</label>
              <input type="password" name="password" id="deposit-password" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="deposit-expiration">${T('mydeposits.expiration')}</label>
              <input type="number" name="expiration_days" id="deposit-expiration" min="${cfg.minExpirationDays}" max="${cfg.maxExpirationDays}" value="${cfg.defaultExpirationDays}" class="form-control" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <button class="btn btn-accent rounded-pill w-100">${T('common.create')}</button>
            </div>
          </form>
          <div id="deposit-result" class="mt-3" style="display:none;"></div>
        </div>
      </div>

      <div id="deposits-list"></div>`;

    const renderList = (deposits) => {
      const listEl = container.querySelector('#deposits-list');
      const statusBadge = (d) => {
        const expired = new Date(d.expires_at) < new Date();
        if (expired) return `<span class="badge badge-danger">${T('status.expired')}</span>`;
        return d.active ? `<span class="badge badge-success">${T('status.active')}</span>` : `<span class="badge badge-secondary">${T('status.disabled')}</span>`;
      };

      listEl.innerHTML = deposits.length === 0 ? `
        <div class="card"><div class="card-body">
          <div class="empty-state">
            <i class="bi bi-inbox"></i>
            ${T('mydeposits.empty')}
          </div>
        </div></div>` : deposits.map((d) => `
        <div class="card mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div style="min-width:0;">
                <h2 class="h6 mb-1">${E(d.label)}
                  ${d.has_password ? `<span class="badge badge-info">${T('status.protected')}</span>` : ''}
                  ${statusBadge(d)}
                </h2>
                <p class="small text-secondary mb-2">${T('mydeposits.expires_on', { date: U().formatDate(d.expires_at) })}</p>
                ${(!new Date(d.expires_at) < new Date() && d.active) ? `
                  <div class="d-flex align-items-center gap-2">
                    <code class="share-link small flex-grow-1">${E(d.link)}</code>
                    <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm" data-copy="${E(d.link)}">
                      <i class="bi bi-clipboard me-1"></i>${T('common.copy')}
                    </button>
                  </div>` : ''}
              </div>
              <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm" data-toggle="${d.id}">
                ${d.active ? T('action.disable') : T('action.enable')}
              </button>
            </div>

            <hr style="border-color:#2a2a2a;">
            <h3 class="h6 mb-2">${T('mydeposits.files_received', { count: d.files.length })}</h3>
            ${d.files.length === 0 ? `
              <p class="text-secondary small mb-0">${T('mydeposits.no_files')}</p>` : `
              <div class="table-responsive-dark">
                <table class="table table-sm align-middle mb-0">
                  <thead><tr><th>${T('common.name')}</th><th>${T('common.size')}</th><th>${T('common.depositor')}</th><th>${T('common.received_at')}</th><th class="text-end">${T('common.actions')}</th></tr></thead>
                  <tbody>
                    ${d.files.map((f) => `
                      <tr>
                        <td class="file-name">${E(f.name)}</td>
                        <td>${U().formatBytes(f.size)}</td>
                        <td>${E(f.uploader_name || '-')}</td>
                        <td>${U().formatDate(f.created_at)}</td>
                        <td class="text-end text-nowrap">
                          <a class="btn-icon success" title="${T('common.download')}" href="?action=download_deposit_file&id=${f.id}">
                            <i class="bi bi-download"></i>
                          </a>
                          <button type="button" class="btn-icon danger" title="${T('common.delete')}" data-delete-file="${f.id}">
                            <i class="bi bi-trash"></i>
                          </button>
                        </td>
                      </tr>`).join('')}
                  </tbody>
                </table>
              </div>`}
          </div>
        </div>`).join('');

      listEl.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', () => U().copyToClipboard(btn, btn.dataset.copy));
      });

      listEl.querySelectorAll('[data-toggle]').forEach((btn) => {
        btn.addEventListener('click', async () => {
          try {
            const res = await U().postAction('toggle_deposit', { id: parseInt(btn.dataset.toggle, 10) });
            U().showToast(res.message);
            refreshList();
          } catch (err) {
            U().showToast(err.message, 'error');
          }
        });
      });

      listEl.querySelectorAll('[data-delete-file]').forEach((btn) => {
        btn.addEventListener('click', async () => {
          const ok = await U().confirm({
            title: T('confirm.delete_deposit_file.title'),
            message: T('confirm.delete_deposit_file.message'),
            confirmLabel: T('common.delete'),
            danger: true,
          });
          if (!ok) return;
          try {
            const res = await U().postAction('delete_deposit_file', { id: parseInt(btn.dataset.deleteFile, 10) });
            U().showToast(res.message);
            refreshList();
          } catch (err) {
            U().showToast(err.message, 'error');
          }
        });
      });
    };

    const refreshList = async () => {
      try {
        const data = await U().fetchAction('get_my_deposits');
        renderList(data.deposits);
      } catch (err) {
        U().showToast(err.message, 'error');
      }
    };

    const createForm = container.querySelector('#create-deposit-form');
    createForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const res = await U().postAction('create_deposit', {
          label: createForm.label.value,
          password: createForm.password.value,
          expiration_days: parseInt(createForm.expiration_days.value, 10),
        });
        const resultEl = container.querySelector('#deposit-result');
        resultEl.innerHTML = `
          <div class="result-box">
            <div class="result-title"><i class="bi bi-check-circle"></i> ${E(res.message)}</div>
            <div class="d-flex align-items-center flex-wrap gap-2">
              <code class="share-link flex-grow-1">${E(res.link)}</code>
              <button type="button" class="btn btn-outline-secondary rounded-pill" id="copy-deposit-link">
                <i class="bi bi-clipboard me-1"></i>${T('common.copy')}
              </button>
            </div>
          </div>`;
        resultEl.style.display = 'block';
        resultEl.querySelector('#copy-deposit-link').addEventListener('click', (ev) => {
          U().copyToClipboard(ev.currentTarget, res.link);
        });
        createForm.reset();
        refreshList();
      } catch (err) {
        U().showToast(err.message, 'error');
      }
    });

    const data = await U().fetchAction('get_my_deposits');
    renderList(data.deposits);
  },

  /* ---------------- Secret message ---------------- */
  async secret(container) {
    const cfg = Lethe.config;
    container.innerHTML = `
      <div class="card">
        <div class="card-body p-4">
          <h2 class="h5 mb-2">${T('secret.title')}</h2>
          <p class="text-secondary small mb-4">${T('secret.description')}</p>

          <div id="secret-result" class="mb-4" style="display:none;"></div>

          <form id="secret-form">
            <div class="mb-3">
              <label class="form-label" for="secret-message">${T('secret.message')}</label>
              <textarea name="message" id="secret-message" class="form-control" rows="6" maxlength="${cfg.secretMaxLength}" required
                        placeholder="${T('secret.message_placeholder')}"></textarea>
              <div class="form-text">${T('secret.max_chars', { count: cfg.secretMaxLength })}</div>
            </div>
            <div class="mb-4">
              <label class="form-label" for="secret-expiration">${T('secret.validity')}</label>
              <select name="expiration_days" id="secret-expiration" class="form-select" style="max-width: 220px;">
                ${cfg.secretExpirationOptions.map((d) => `
                  <option value="${d}" ${d === cfg.secretDefaultExpirationDays ? 'selected' : ''}>${d === 1 ? T('secret.day_one') : T('secret.day_many', { days: d })}</option>`).join('')}
              </select>
              <div class="form-text">${T('secret.validity_hint')}</div>
            </div>
            <button type="submit" class="btn btn-accent rounded-pill px-4">
              <i class="bi bi-envelope me-2"></i>${T('secret.generate')}
            </button>
          </form>
        </div>
      </div>`;

    const form = container.querySelector('#secret-form');
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const res = await U().postAction('create_secret', {
          message: form.message.value,
          expiration_days: parseInt(form.expiration_days.value, 10),
        });
        const resultEl = container.querySelector('#secret-result');
        resultEl.innerHTML = `
          <div class="result-box">
            <div class="result-title"><i class="bi bi-check-circle"></i> ${E(res.message)}</div>
            <div class="d-flex align-items-center flex-wrap gap-2">
              <code class="share-link flex-grow-1">${E(res.link)}</code>
              <button type="button" class="btn btn-outline-secondary rounded-pill" id="copy-secret-link">
                <i class="bi bi-clipboard me-1"></i>${T('common.copy')}
              </button>
            </div>
            <p class="small mt-2 mb-0" style="color:#777">${T('secret.result_note')}</p>
          </div>`;
        resultEl.style.display = 'block';
        resultEl.querySelector('#copy-secret-link').addEventListener('click', (ev) => {
          U().copyToClipboard(ev.currentTarget, res.link);
        });
        form.reset();
      } catch (err) {
        U().showToast(err.message, 'error');
      }
    });
  },

  /* ---------------- My secrets ---------------- */
  async my_secrets(container) {
    const data = await U().fetchAction('get_my_secrets');
    const secrets = data.secrets;

    const statusBadge = {
      active: `<span class="badge badge-success">${T('status.active')}</span>`,
      consumed: `<span class="badge badge-info">${T('status.consumed')}</span>`,
      expired: `<span class="badge badge-danger">${T('status.expired')}</span>`,
    };

    container.innerHTML = `
      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive-dark">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>${T('common.created')}</th>
                  <th>${T('common.expiration')}</th>
                  <th>${T('common.status')}</th>
                  <th>${T('common.consumed_at')}</th>
                  <th class="text-end">${T('common.actions')}</th>
                </tr>
              </thead>
              <tbody>
                ${secrets.length === 0 ? `
                  <tr><td colspan="5">
                    <div class="empty-state">
                      <i class="bi bi-key"></i>
                      ${T('mysecrets.empty')}
                    </div>
                  </td></tr>` : secrets.map((s) => `
                  <tr>
                    <td>${U().formatDate(s.created_at)}</td>
                    <td>${U().formatDate(s.expires_at)}</td>
                    <td>${statusBadge[s.status]}</td>
                    <td>${s.consumed_at ? U().formatDate(s.consumed_at) : '-'}</td>
                    <td class="text-end">
                      <button type="button" class="btn-icon danger" title="${T('common.delete')}" data-delete="${s.id}">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>`).join('')}
              </tbody>
            </table>
          </div>
        </div>
      </div>`;

    container.querySelectorAll('[data-delete]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const ok = await U().confirm({
          title: T('confirm.delete_secret.title'),
          message: T('confirm.delete_secret.message'),
          confirmLabel: T('common.delete'),
          danger: true,
        });
        if (!ok) return;
        try {
          const res = await U().postAction('delete_secret', { id: parseInt(btn.dataset.delete, 10) });
          U().showToast(res.message);
          Lethe.views.my_secrets(container);
        } catch (err) {
          U().showToast(err.message, 'error');
        }
      });
    });
  },

  /* ---------------- Users (admin) ---------------- */
  async users(container) {
    const data = await U().fetchAction('get_users');
    const users = data.users;
    const me = Lethe.state.currentUser;

    container.innerHTML = `
      <div class="card mb-4">
        <div class="card-body">
          <h2 class="h6 mb-3">${T('users.create_title')}</h2>
          <form id="create-user-form" class="row g-3">
            <div class="col-md-4">
              <input type="text" name="username" class="form-control" placeholder="${T('common.username')}" required>
            </div>
            <div class="col-md-4">
              <input type="password" name="password" class="form-control" placeholder="${T('common.password')}" required>
            </div>
            <div class="col-md-2 d-flex align-items-center">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_admin" id="is_admin">
                <label class="form-check-label" for="is_admin">${T('users.admin')}</label>
              </div>
            </div>
            <div class="col-md-2">
              <button class="btn btn-accent rounded-pill w-100">${T('common.create')}</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive-dark">
            <table class="table align-middle mb-0">
              <thead><tr><th>${T('users.username_col')}</th><th>${T('common.role')}</th><th>${T('common.created')}</th><th class="text-end">${T('common.actions')}</th></tr></thead>
              <tbody>
                ${users.map((u) => {
                  const isOidc = u.oidc_sub;
                  return `
                  <tr>
                    <td class="file-name">${E(u.username)}${parseInt(u.id, 10) === me.id ? ` <span class="badge badge-accent">${T('common.you')}</span>` : ''}${isOidc ? ` <span class="badge badge-info" title="${E(u.oidc_provider)}">OIDC</span>` : ''}</td>
                    <td>${parseInt(u.is_admin, 10) === 1 ? `<span class="badge badge-accent">${T('common.admin')}</span>` : `<span class="badge badge-secondary">${T('common.user')}</span>`}</td>
                    <td>${U().formatDate(u.created_at)}</td>
                    <td class="text-end text-nowrap">
                      ${isOidc ? `
                        <span class="text-secondary small">${T('users.oidc_account')}</span>` : `
                        <form class="d-inline-flex gap-2 align-items-center" data-reset-form="${u.id}">
                          <input type="password" name="new_password" class="form-control form-control-sm" placeholder="${T('users.new_password')}" style="width:150px;">
                          <button class="btn btn-outline-secondary rounded-pill btn-sm">${T('users.reset')}</button>
                        </form>`}
                      ${parseInt(u.id, 10) !== me.id ? `
                        <button type="button" class="btn-icon danger ms-2" title="${T('common.delete')}" data-delete="${u.id}">
                          <i class="bi bi-trash"></i>
                        </button>` : ''}
                    </td>
                  </tr>`;
                }).join('')}
              </tbody>
            </table>
          </div>
        </div>
      </div>`;

    const createForm = container.querySelector('#create-user-form');
    createForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const res = await U().postAction('create_user', {
          username: createForm.username.value,
          password: createForm.password.value,
          is_admin: createForm.is_admin.checked ? 1 : 0,
        });
        U().showToast(res.message);
        Lethe.views.users(container);
      } catch (err) {
        U().showToast(err.message, 'error');
      }
    });

    container.querySelectorAll('[data-reset-form]').forEach((form) => {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
          const res = await U().postAction('reset_user_password', {
            id: parseInt(form.dataset.resetForm, 10),
            new_password: form.new_password.value,
          });
          U().showToast(res.message);
          Lethe.views.users(container);
        } catch (err) {
          U().showToast(err.message, 'error');
        }
      });
    });

    container.querySelectorAll('[data-delete]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const ok = await U().confirm({
          title: T('confirm.delete_user.title'),
          message: T('confirm.delete_user.message'),
          confirmLabel: T('common.delete'),
          danger: true,
        });
        if (!ok) return;
        try {
          const res = await U().postAction('delete_user', { id: parseInt(btn.dataset.delete, 10) });
          U().showToast(res.message);
          Lethe.views.users(container);
        } catch (err) {
          U().showToast(err.message, 'error');
        }
      });
    });
  },
};
