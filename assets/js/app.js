// Lethe - SPA bootstrap: session, navigation, login/logout.
'use strict';

(function () {
  const VIEW_TITLES = {
    dashboard: 'nav.dashboard',
    send_file: 'nav.send_file',
    my_files: 'nav.my_files',
    my_deposits: 'nav.my_deposits',
    secret: 'nav.secret',
    my_secrets: 'nav.my_secrets',
    users: 'nav.users',
  };

  const contentArea = document.getElementById('content-area');
  const contentTitle = document.getElementById('content-title');
  const allNavLinks = document.querySelectorAll('.sidebar .nav-link');
  const loginModalEl = document.getElementById('login-modal');
  const loginForm = document.getElementById('login-form');
  const loginErrorEl = document.getElementById('login-error');
  const logoutBtn = document.getElementById('sidebar-logout-btn');

  function setLoggedIn(user) {
    Lethe.state.currentUser = user;
    document.body.classList.toggle('logged-in', !!user);
    document.body.classList.toggle('logged-out', !user);
    const adminLink = document.querySelector('.sidebar .nav-link.admin-only');
    if (adminLink) {
      adminLink.style.display = (user && user.is_admin) ? 'flex' : 'none';
    }
  }

  function renderLoggedOut() {
    contentTitle.textContent = Lethe.i18n.t('login.title');
    contentArea.innerHTML = `
      <div class="d-flex align-items-center justify-content-center" style="min-height:60dvh;">
        <div class="card" style="max-width:440px; width:100%;">
          <div class="card-body p-4 p-md-5 text-center">
            <div style="font-size:3rem; color:var(--lethe-accent); margin-bottom:1rem;"><i class="bi bi-shield-lock"></i></div>
            <h2 class="h5 mb-2">${Lethe.i18n.t('loggedout.welcome', { app: Lethe.utils.escapeHtml(Lethe.config.appName) })}</h2>
            <p class="text-secondary mb-4">${Lethe.i18n.t('loggedout.subtitle')}</p>
            <button type="button" class="btn btn-accent rounded-pill px-4" id="open-login-btn">
              <i class="bi bi-box-arrow-in-right me-2"></i>${Lethe.i18n.t('login.submit')}
            </button>
          </div>
        </div>
      </div>`;
    contentArea.querySelector('#open-login-btn').addEventListener('click', () => {
      bootstrap.Modal.getOrCreateInstance(loginModalEl).show();
    });
  }

  async function loadView(name) {
    if (!VIEW_TITLES[name]) {
      name = 'dashboard';
    }
    Lethe.state.currentView = name;
    contentTitle.textContent = Lethe.i18n.t(VIEW_TITLES[name]);

    allNavLinks.forEach((link) => {
      link.classList.toggle('active', link.dataset.view === name);
    });

    if (!Lethe.state.currentUser) {
      renderLoggedOut();
      return;
    }

    try {
      await Lethe.views[name](contentArea);
    } catch (err) {
      contentArea.innerHTML = `
        <div class="card"><div class="card-body">
          <div class="empty-state">
            <i class="bi bi-exclamation-triangle" style="color:var(--lethe-danger);"></i>
            ${E(err.message)}
          </div>
        </div></div>`;
    }
  }

  // Navigation
  allNavLinks.forEach((link) => {
    if (link.id === 'sidebar-logout-btn' || link.dataset.view === undefined) return;
    link.addEventListener('click', (e) => {
      e.preventDefault();
      loadView(link.dataset.view);

      const offcanvasEl = document.getElementById('main-nav-offcanvas');
      if (window.innerWidth < 768 && offcanvasEl) {
        const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
        if (offcanvas) offcanvas.hide();
      }
    });
  });

  // Login
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    loginErrorEl.style.display = 'none';
    const btn = loginForm.querySelector('button[type=submit]');
    btn.disabled = true;

    try {
      const data = await Lethe.utils.postAction('login', {
        username: document.getElementById('login-username').value,
        password: document.getElementById('login-password').value,
      });
      setLoggedIn(data.user);
      Lethe.state.csrfToken = data.csrf_token;
      loginForm.reset();
      bootstrap.Modal.getInstance(loginModalEl).hide();
      loadView('dashboard');
    } catch (err) {
      loginErrorEl.textContent = err.message;
      loginErrorEl.style.display = 'block';
    } finally {
      btn.disabled = false;
    }
  });

  // Logout
  logoutBtn.addEventListener('click', async (e) => {
    e.preventDefault();
    try {
      await Lethe.utils.postAction('logout');
    } catch (err) {
      // Ignore: the session is cleared client-side anyway.
    }
    setLoggedIn(null);
    loadView('dashboard');
  });

  // OIDC login button
  const oidcLoginBtn = document.getElementById('oidc-login-btn');
  if (oidcLoginBtn) {
    oidcLoginBtn.addEventListener('click', async () => {
      const oidc = Lethe.state.oidc;
      if (!oidc || !oidc.enabled) {
        return;
      }

      try {
        const data = await Lethe.utils.fetchAction('oidc_init');
        window.location.href = data.authorization_url;
      } catch (err) {
        // OIDC not available or error - fall back to regular login
      }
    });
  }

  // Show OIDC button when OIDC is enabled and user is logged out
  function showOidcButton() {
    if (oidcLoginBtn && Lethe.state.oidc && Lethe.state.oidc.enabled && !Lethe.state.currentUser) {
      oidcLoginBtn.style.display = 'block';
    }
  }

  // Init
  Lethe.state.csrfToken = document.querySelector('meta[name="csrf-token"]').content;

  Lethe.loadView = loadView;

  Lethe.utils.fetchAction('get_session')
    .then((data) => {
      setLoggedIn(data.user);
      Lethe.state.oidc = data.oidc || null;
      showOidcButton();
      loadView('dashboard');
    })
    .catch(() => {
      setLoggedIn(null);
      showOidcButton();
      loadView('dashboard');
    });
})();
