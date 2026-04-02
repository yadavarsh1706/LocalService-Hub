// frontend/js/app.js
// ── API base URL — change if your backend is at a different path ──
const API = '../backend/api';

// ── API Helpers ───────────────────────────────────────────────
async function apiFetch(endpoint, options = {}) {
  try {
    const res  = await fetch(endpoint, {
      credentials: 'include',   // send session cookie
      headers: { 'Content-Type': 'application/json' },
      ...options,
    });
    return await res.json();
  } catch (err) {
    console.error('API error:', err);
    return { success: false, message: 'Network error. Is the server running?' };
  }
}

function apiGet(file, params = {}) {
  const qs = new URLSearchParams(params).toString();
  return apiFetch(`${API}/${file}${qs ? '?' + qs : ''}`);
}

function apiPost(file, action, body = {}) {
  return apiFetch(`${API}/${file}?action=${action}`, {
    method: 'POST',
    body: JSON.stringify(body),
  });
}

// ── Session ───────────────────────────────────────────────────
let _user = null;

async function getUser() {
  if (_user !== undefined) return _user;
  const res = await apiGet('auth.php', { action: 'me' });
  _user = res.success ? res.user : null;
  return _user;
}

async function logout() {
  await apiGet('auth.php', { action: 'logout' });
  _user = null;
  location.href = 'login.html';
}

// ── Navbar ────────────────────────────────────────────────────
async function renderNav() {
  const el = document.getElementById('nav');
  if (!el) return;
  const u = await getUser();

  const right = u
    ? `<span style="color:#bfdbfe;font-size:.8rem">Hi, ${u.name.split(' ')[0]}</span>
       ${u.type === 'customer' ? '<a href="bookings.html">My Bookings</a>' : ''}
       ${u.type === 'provider' ? '<a href="provider.html">Dashboard</a>' : ''}
       ${u.type === 'admin'    ? '<a href="admin.html">Admin</a>' : ''}
       <button class="btn btn-sm" style="background:#ef4444;color:#fff;margin-left:.5rem" onclick="logout()">Logout</button>`
    : `<a href="login.html">Login</a>
       <a href="register.html" class="btn btn-sm btn-accent" style="margin-left:.5rem">Register</a>`;

  el.innerHTML = `
  <nav class="navbar">
    <a href="index.html" class="brand"><i class="fas fa-tools"></i> LocalService <span>Hub</span></a>
    <div style="display:flex;align-items:center">
      <a href="browse.html" style="color:#bfdbfe;font-size:.875rem">Browse</a>
      ${right}
    </div>
  </nav>`;
}

// ── Toast ─────────────────────────────────────────────────────
function toast(msg, type = 's') {
  const c = document.getElementById('toast') || (() => {
    const d = document.createElement('div'); d.id = 'toast'; document.body.appendChild(d); return d;
  })();
  const icons = { s: 'check-circle', e: 'times-circle', i: 'info-circle' };
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'}"></i> ${msg}`;
  c.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; t.style.transition = '.4s'; setTimeout(() => t.remove(), 400); }, 3000);
}

// ── Display Helpers ───────────────────────────────────────────
function stars(r) {
  return Array.from({ length: 5 }, (_, i) =>
    `<i class="${i < Math.round(r) ? 'fas' : 'far'} fa-star" style="color:#f59e0b;font-size:.75rem"></i>`
  ).join('');
}

function badge(s) {
  return `<span class="badge badge-${s}">${s}</span>`;
}

function fmtDate(d) {
  return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

function fmtPrice(p) {
  return '₹' + Number(p || 0).toLocaleString('en-IN');
}

// ── Init nav on every page ────────────────────────────────────
document.addEventListener('DOMContentLoaded', renderNav);
