// assets/js/app.js
'use strict';

const API = './api';
let authToken = localStorage.getItem('tc_token');
let currentUser = JSON.parse(localStorage.getItem('tc_user') || 'null');
let currentStudentForFeedback = null;

// ─── API HELPER ───────────────────────────────────────────
async function apiFetch(path, method = 'GET', body = null) {
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json' }
  };
  if (authToken) opts.headers['Authorization'] = `Bearer ${authToken}`;
  if (body) opts.body = JSON.stringify(body);
  const res = await fetch(path, opts);
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || 'Request failed');
  return data;
}

// ─── TOAST ────────────────────────────────────────────────
function showToast(msg, type = 'default') {
  const tc = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  const icons = { success: '✓', error: '✕', default: 'ℹ' };
  t.innerHTML = `<span>${icons[type] || 'ℹ'}</span><span>${msg}</span>`;
  tc.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity 0.3s'; setTimeout(() => t.remove(), 300); }, 3000);
}

// ─── SCREEN ROUTER ────────────────────────────────────────
function showScreen(id) {
  document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
  const el = document.getElementById(id);
  if (el) el.classList.add('active');
  // Close sidebar on mobile
  closeSidebar();
}

// ─── SIDEBAR ─────────────────────────────────────────────
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('open');
  document.querySelector('.sidebar-overlay').classList.toggle('open');
}
function closeSidebar() {
  document.querySelector('.sidebar')?.classList.remove('open');
  document.querySelector('.sidebar-overlay')?.classList.remove('open');
}
document.querySelector('.sidebar-overlay')?.addEventListener('click', closeSidebar);

// ─── PROFILE DROPDOWN ────────────────────────────────────
function toggleProfileDropdown(e) {
  e.stopPropagation();
  document.querySelectorAll('.profile-dropdown').forEach(d => d.classList.toggle('open'));
}
document.addEventListener('click', () => {
  document.querySelectorAll('.profile-dropdown').forEach(d => d.classList.remove('open'));
});

// ─── AUTH STATE ──────────────────────────────────────────
function setAuth(token, user) {
  authToken = token;
  currentUser = user;
  localStorage.setItem('tc_token', token);
  localStorage.setItem('tc_user', JSON.stringify(user));
}
function logout() {
  authToken = null;
  currentUser = null;
  localStorage.removeItem('tc_token');
  localStorage.removeItem('tc_user');
  showScreen('screen-login');
}
function isLoggedIn() { return !!authToken && !!currentUser; }

// ─── INITIAL ROUTE ───────────────────────────────────────
function routeInitial() {
  if (!isLoggedIn()) { showScreen('screen-login'); return; }
  if (currentUser.role === 'admin') showAdminDashboard();
  else if (currentUser.role === 'office') showOfficeDashboard();
  else showTelecallerDashboard();
}

// ─── UPDATE ALL PROFILE UI ───────────────────────────────
function updateProfileUI() {
  if (!currentUser) return;
  const initials = currentUser.name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2);
  document.querySelectorAll('.profile-initial').forEach(el => el.textContent = initials);
  document.querySelectorAll('.profile-name-display').forEach(el => el.textContent = currentUser.name);
  document.querySelectorAll('.profile-role-display').forEach(el => el.textContent = currentUser.role);
}

// ═══════════════════════════════════════════════════════════
// LOGIN SCREEN
// ═══════════════════════════════════════════════════════════
async function handleLogin(e) {
  e.preventDefault();
  const email = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value.trim();
  const btn = document.getElementById('btn-login');
  const errEl = document.getElementById('login-error');
  errEl.style.display = 'none';

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Signing in...';
  try {
    const data = await apiFetch(`${API}/auth.php?action=login`, 'POST', { email, password });
    if (data.require_setup) {
      // First time login — set new password
      document.getElementById('setup-user-id').value = data.user_id;
      showScreen('screen-setup-password');
      return;
    }
    if (data.require_forgot) {
      // Using temp password again — forgot password flow
      document.getElementById('forgot-user-id').value = data.user_id;
      showScreen('screen-forgot-password');
      return;
    }
    setAuth(data.token, data.user);
    updateProfileUI();
    routeInitial();
  } catch (err) {
    errEl.textContent = err.message;
    errEl.style.display = 'flex';
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Sign In →';
  }
}

// ═══════════════════════════════════════════════════════════
// SETUP PASSWORD SCREEN
// ═══════════════════════════════════════════════════════════
async function handleSetupPassword(e) {
  e.preventDefault();
  const userId = document.getElementById('setup-user-id').value;
  const password = document.getElementById('setup-password').value;
  const confirm = document.getElementById('setup-confirm').value;
  const errEl = document.getElementById('setup-error');
  errEl.style.display = 'none';
  if (password !== confirm) { errEl.textContent = 'Passwords do not match!'; errEl.style.display = 'flex'; return; }
  const btn = document.getElementById('btn-setup');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';
  try {
    const data = await apiFetch(`${API}/auth.php?action=setup_password`, 'POST', { user_id: userId, password, confirm_password: confirm });
    setAuth(data.token, data.user);
    updateProfileUI();
    showToast('Password set successfully!', 'success');
    routeInitial();
  } catch (err) {
    errEl.textContent = err.message; errEl.style.display = 'flex';
  } finally {
    btn.disabled = false; btn.innerHTML = 'Set Password →';
  }
}

// ═══════════════════════════════════════════════════════════
// FORGOT PASSWORD SCREEN
// ═══════════════════════════════════════════════════════════
async function handleForgotPassword(e) {
  e.preventDefault();
  const userId = document.getElementById('forgot-user-id').value;
  const dob = document.getElementById('forgot-dob').value;
  const password = document.getElementById('forgot-password').value;
  const confirm = document.getElementById('forgot-confirm').value;
  const errEl = document.getElementById('forgot-error');
  errEl.style.display = 'none';
  if (password !== confirm) { errEl.textContent = 'Passwords do not match!'; errEl.style.display = 'flex'; return; }
  const btn = document.getElementById('btn-forgot');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';
  try {
    const data = await apiFetch(`${API}/auth.php?action=forgot_password`, 'POST', { user_id: userId, dob, password, confirm_password: confirm });
    setAuth(data.token, data.user);
    updateProfileUI();
    showToast('Password reset successfully!', 'success');
    routeInitial();
  } catch (err) {
    errEl.textContent = err.message; errEl.style.display = 'flex';
  } finally {
    btn.disabled = false; btn.innerHTML = 'Reset Password →';
  }
}

// ═══════════════════════════════════════════════════════════
// TELECALLER DASHBOARD
// ═══════════════════════════════════════════════════════════
async function showTelecallerDashboard() {
  showScreen('screen-telecaller');
  updateProfileUI();
  await loadTelecallerStudents();
  await loadReminders();
}

async function loadReminders() {
  try {
    const reminders = await apiFetch(`${API}/feedback.php?action=reminders`);
    const container = document.getElementById('reminder-container');
    if (!reminders.length) { container.style.display = 'none'; return; }
    container.style.display = 'block';
    const list = document.getElementById('reminder-list');
    list.innerHTML = reminders.map(r => `
      <li onclick="openFeedbackModal(${r.student_id}, '${escHtml(r.name)}', '${escHtml(r.mobile)}')">
        📞 <strong>${escHtml(r.name)}</strong> (${escHtml(r.mobile)}) — ${escHtml(r.reason || 'Follow-up')}
      </li>`).join('');
  } catch(e) { /* ignore */ }
}

async function loadTelecallerStudents() {
  const container = document.getElementById('tc-student-list');
  container.innerHTML = `<div class="loading-overlay"><span class="spinner spinner-dark"></span></div>`;
  try {
    const students = await apiFetch(`${API}/students.php?action=my_list`);
    if (!students.length) {
      container.innerHTML = `<div class="empty-state"><div class="empty-icon">📋</div><p>No students assigned yet. Check back later!</p></div>`;
      return;
    }
    // Separate previous updates and new list
    const withFeedback = students.filter(s => s.last_status);
    const newList = students.filter(s => !s.last_status);

    let html = '';
    if (withFeedback.length) {
      html += `<div class="student-section">
        <div class="student-section-title">📌 Previous Updates (${withFeedback.length})</div>
        <div class="student-list">${withFeedback.map(renderStudentCard).join('')}</div>
      </div>`;
    }
    if (newList.length) {
      html += `<div class="student-section">
        <div class="student-section-title">🆕 New Leads (${newList.length})</div>
        <div class="student-list">${newList.map(renderStudentCard).join('')}</div>
      </div>`;
    }
    container.innerHTML = html;
  } catch (err) {
    container.innerHTML = `<div class="alert alert-error">⚠ ${err.message}</div>`;
  }
}

function renderStudentCard(s) {
  const initials = s.name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
  const statusBadge = s.last_status ? `<span class="badge badge-${s.last_status === 'accepted' ? 'success' : s.last_status === 'rejected' ? 'danger' : 'warning'}">${s.last_status}</span>` : '';
  const followUp = s.follow_up_date ? `<span class="badge badge-info">📅 Follow-up: ${s.follow_up_date}</span>` : '';
  const isReminder = s.follow_up_date === new Date().toISOString().split('T')[0];
  return `
  <div class="student-card ${isReminder ? 'reminder' : ''} ${s.last_status ? 'status-' + s.last_status : ''}">
    <div class="student-avatar">${initials}</div>
    <div class="student-info">
      <div class="student-name">${escHtml(s.name)} ${statusBadge} ${followUp}</div>
      <div class="student-meta">📱 ${escHtml(s.mobile)} ${s.present_college ? '· 🏫 ' + escHtml(s.present_college) : ''}</div>
      ${s.address ? `<div class="student-meta">📍 ${escHtml(s.address)}</div>` : ''}
      ${s.last_reason ? `<div class="student-meta">💬 ${escHtml(s.last_reason)}</div>` : ''}
    </div>
    <div class="student-actions">
      <a href="tel:${escHtml(s.mobile)}" onclick="handleCallNow(${s.id}, '${escHtml(s.name)}', '${escHtml(s.mobile)}')" class="btn btn-accent btn-sm">📞 Call Now</a>
      <button onclick="openFeedbackModal(${s.id}, '${escHtml(s.name)}', '${escHtml(s.mobile)}')" class="btn btn-primary btn-sm">📝 Feedback</button>
    </div>
  </div>`;
}

function handleCallNow(id, name, mobile) {
  // tel: link handled by <a href="tel:...">
  // After short delay, open feedback modal
  setTimeout(() => {
    if (confirm(`Called ${name}? Fill in the feedback now?`)) {
      openFeedbackModal(id, name, mobile);
    }
  }, 1000);
}

// ═══════════════════════════════════════════════════════════
// FEEDBACK MODAL
// ═══════════════════════════════════════════════════════════
function openFeedbackModal(studentId, name, mobile) {
  currentStudentForFeedback = { id: studentId, name, mobile };
  document.getElementById('fb-student-name').textContent = name;
  document.getElementById('fb-student-mobile').textContent = mobile;
  document.getElementById('fb-datetime').textContent = new Date().toLocaleString('en-IN', { dateStyle: 'long', timeStyle: 'short' });

  // Reset form
  document.querySelectorAll('.status-option').forEach(r => r.checked = false);
  document.getElementById('fb-reason').value = '';
  document.getElementById('fb-follow-date').value = '';
  document.getElementById('fb-reason-group').style.display = 'none';
  document.getElementById('fb-follow-group').style.display = 'none';
  document.getElementById('fb-follow-group-accepted').style.display = 'none';
  document.getElementById('fb-error').style.display = 'none';

  openModal('modal-feedback');
}

function onStatusChange(val) {
  const reasonGroup = document.getElementById('fb-reason-group');
  const followGroup = document.getElementById('fb-follow-group');
  const followGroupA = document.getElementById('fb-follow-group-accepted');
  reasonGroup.style.display = val === 'other' ? 'block' : 'none';
  followGroup.style.display = val === 'other' ? 'block' : 'none';
  followGroupA.style.display = val === 'accepted' ? 'block' : 'none';
}

async function submitFeedback() {
  if (!currentStudentForFeedback) return;
  const status = document.querySelector('.status-option:checked')?.value;
  const reason = document.getElementById('fb-reason').value.trim();
  const followDate = document.getElementById('fb-follow-date').value || document.getElementById('fb-follow-date-accepted').value;
  const errEl = document.getElementById('fb-error');
  errEl.style.display = 'none';

  if (!status) { errEl.textContent = 'Please select a status'; errEl.style.display = 'flex'; return; }
  if (status === 'other' && !reason) { errEl.textContent = 'Please enter a reason'; errEl.style.display = 'flex'; return; }

  const btn = document.getElementById('btn-submit-feedback');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Submitting...';
  try {
    await apiFetch(`${API}/feedback.php?action=submit`, 'POST', {
      student_id: currentStudentForFeedback.id, status, reason, follow_up_date: followDate
    });
    showToast('Feedback submitted!', 'success');
    closeModal('modal-feedback');
    await loadTelecallerStudents();
    await loadReminders();
  } catch (err) {
    errEl.textContent = err.message; errEl.style.display = 'flex';
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Submit Feedback';
  }
}

// ═══════════════════════════════════════════════════════════
// OFFICE DASHBOARD
// ═══════════════════════════════════════════════════════════
async function showOfficeDashboard() {
  showScreen('screen-office');
  updateProfileUI();
}

async function handleAddStudentOffice(e) {
  e.preventDefault();
  await doAddStudent('office-student-form', 'btn-add-student-office');
}

// ═══════════════════════════════════════════════════════════
// ADMIN SCREENS
// ═══════════════════════════════════════════════════════════
async function showAdminDashboard() {
  showScreen('screen-admin');
  updateProfileUI();
  await loadAdminStats();
}

function adminNav(page) {
  document.querySelectorAll('.admin-page').forEach(p => p.style.display = 'none');
  document.getElementById('admin-' + page).style.display = 'block';
  document.querySelectorAll('#screen-admin .nav-item').forEach(n => n.classList.remove('active'));
  document.querySelector(`[data-admin-nav="${page}"]`)?.classList.add('active');
  document.getElementById('admin-page-title').textContent = {
    dashboard: 'Dashboard', adduser: 'Add User', viewusers: 'View Users', addstudent: 'Add Student'
  }[page] || page;
  if (page === 'viewusers') loadViewUsers();
  if (page === 'dashboard') loadAdminStats();
}

async function loadAdminStats() {
  const container = document.getElementById('admin-stats-list');
  container.innerHTML = `<div class="loading-overlay"><span class="spinner spinner-dark"></span></div>`;
  try {
    const stats = await apiFetch(`${API}/users.php?action=dashboard_stats`);
    const totalAssigned = stats.reduce((a,b) => a + parseInt(b.total_assigned), 0);
    const totalCompleted = stats.reduce((a,b) => a + parseInt(b.completed), 0);

    document.getElementById('admin-stat-telecallers').textContent = stats.length;
    document.getElementById('admin-stat-total').textContent = totalAssigned;
    document.getElementById('admin-stat-completed').textContent = totalCompleted;
    document.getElementById('admin-stat-pending').textContent = totalAssigned - totalCompleted;

    if (!stats.length) {
      container.innerHTML = `<div class="empty-state"><div class="empty-icon">👥</div><p>No telecallers yet. Add users first.</p></div>`;
      return;
    }
    container.innerHTML = `
      <div class="table-wrap">
        <table>
          <thead><tr><th>Telecaller</th><th>Assigned</th><th>Completed</th><th>Pending</th><th>Progress</th><th></th></tr></thead>
          <tbody>
            ${stats.map(u => {
              const pct = u.total_assigned > 0 ? Math.round((u.completed / u.total_assigned) * 100) : 0;
              const pending = u.total_assigned - u.completed;
              return `<tr class="clickable" onclick="openUserDetail(${u.id})">
                <td><strong>${escHtml(u.name)}</strong></td>
                <td>${u.total_assigned}</td>
                <td><span class="badge badge-success">${u.completed}</span></td>
                <td><span class="badge badge-pending">${pending}</span></td>
                <td>
                  <div style="background:var(--bg2);border-radius:100px;height:8px;min-width:80px;">
                    <div style="background:var(--primary-light);height:8px;border-radius:100px;width:${pct}%;transition:width 1s"></div>
                  </div>
                  <span style="font-size:0.75rem;color:var(--text-muted)">${pct}%</span>
                </td>
                <td><button class="btn btn-outline btn-sm">View →</button></td>
              </tr>`;
            }).join('')}
          </tbody>
        </table>
      </div>`;
  } catch (err) {
    container.innerHTML = `<div class="alert alert-error">⚠ ${err.message}</div>`;
  }
}

async function loadViewUsers() {
  const container = document.getElementById('users-table-body');
  container.innerHTML = `<tr><td colspan="7" class="loading-overlay"><span class="spinner spinner-dark"></span></td></tr>`;
  try {
    const users = await apiFetch(`${API}/users.php?action=list`);
    if (!users.length) {
      container.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted)">No users yet</td></tr>`;
      return;
    }
    container.innerHTML = users.map(u => `
      <tr>
        <td><strong>${escHtml(u.name)}</strong></td>
        <td>${escHtml(u.email)}</td>
        <td>${escHtml(u.phone)}</td>
        <td><span class="badge ${u.role === 'admin' ? 'badge-info' : u.role === 'telecaller' ? 'badge-pending' : 'badge-warning'}">${u.role}</span></td>
        <td><span class="badge ${u.is_active ? 'badge-success' : 'badge-danger'}">${u.is_active ? 'Active' : 'Inactive'}</span></td>
        <td><span class="badge ${u.password_set ? 'badge-success' : 'badge-warning'}">${u.password_set ? 'Set' : 'Temp'}</span></td>
        <td>
          <button onclick="toggleUserStatus(${u.id}, this)" class="btn btn-outline btn-sm">${u.is_active ? 'Disable' : 'Enable'}</button>
          <button onclick="openUserDetail(${u.id})" class="btn btn-primary btn-sm">Details</button>
        </td>
      </tr>`).join('');
  } catch (err) {
    container.innerHTML = `<tr><td colspan="7"><div class="alert alert-error">⚠ ${err.message}</div></td></tr>`;
  }
}

async function handleAddUser(e) {
  e.preventDefault();
  const form = document.getElementById('add-user-form');
  const btn = document.getElementById('btn-add-user');
  const errEl = document.getElementById('add-user-error');
  errEl.style.display = 'none';
  document.getElementById('password-reveal-box').style.display = 'none';

  const data = {
    name: document.getElementById('au-name').value.trim(),
    email: document.getElementById('au-email').value.trim(),
    phone: document.getElementById('au-phone').value.trim(),
    gender: document.getElementById('au-gender').value,
    dob: document.getElementById('au-dob').value,
    role: document.getElementById('au-role').value
  };

  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Creating...';
  try {
    const res = await apiFetch(`${API}/users.php?action=create`, 'POST', data);
    document.getElementById('generated-password').textContent = res.temp_password;
    document.getElementById('password-reveal-box').style.display = 'block';
    showToast('User created successfully!', 'success');
    form.reset();
  } catch (err) {
    errEl.textContent = err.message; errEl.style.display = 'flex';
  } finally {
    btn.disabled = false; btn.innerHTML = '+ Create User';
  }
}

async function toggleUserStatus(id, btn) {
  try {
    await apiFetch(`${API}/users.php?action=toggle`, 'POST', { id });
    showToast('User status updated', 'success');
    loadViewUsers();
  } catch (err) { showToast(err.message, 'error'); }
}

async function openUserDetail(userId) {
  document.getElementById('user-detail-body').innerHTML = `<div class="loading-overlay"><span class="spinner spinner-dark"></span></div>`;
  openModal('modal-user-detail');
  try {
    const data = await apiFetch(`${API}/users.php?action=details&id=${userId}`);
    const u = data.user;
    const initials = u.name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
    document.getElementById('user-detail-body').innerHTML = `
      <div class="detail-user-header">
        <div class="detail-avatar">${initials}</div>
        <div>
          <h3>${escHtml(u.name)}</h3>
          <p style="opacity:0.8;font-size:0.85rem">${escHtml(u.email)} · ${escHtml(u.phone)}</p>
          <span class="badge" style="background:rgba(255,255,255,0.2);color:#fff;margin-top:0.4rem">${u.role}</span>
        </div>
      </div>
      <div style="padding:1.25rem">
        <div class="stats-grid" style="margin-bottom:1.25rem">
          <div class="stat-card"><div class="stat-label">Total</div><div class="stat-value">${data.stats.total}</div></div>
          <div class="stat-card success"><div class="stat-label">Completed</div><div class="stat-value">${data.stats.completed}</div></div>
          <div class="stat-card warning"><div class="stat-label">Pending</div><div class="stat-value">${data.stats.pending}</div></div>
        </div>
        <h4 style="font-family:'Syne',sans-serif;font-size:0.9rem;color:var(--text-muted);margin-bottom:0.75rem">STUDENT LIST</h4>
        ${data.students.length ? `
        <div class="table-wrap"><table>
          <thead><tr><th>Name</th><th>Mobile</th><th>College</th><th>Status</th><th>Last Call</th></tr></thead>
          <tbody>${data.students.map(s => `<tr>
            <td><strong>${escHtml(s.name)}</strong></td>
            <td>${escHtml(s.mobile)}</td>
            <td>${escHtml(s.present_college || '—')}</td>
            <td>${s.status ? `<span class="badge badge-${s.status === 'accepted' ? 'success' : s.status === 'rejected' ? 'danger' : 'warning'}">${s.status}</span>` : '<span class="badge badge-pending">Pending</span>'}</td>
            <td style="font-size:0.8rem;color:var(--text-muted)">${s.call_datetime ? new Date(s.call_datetime).toLocaleDateString('en-IN') : '—'}</td>
          </tr>`).join('')}</tbody>
        </table></div>` : `<div class="empty-state"><p>No students assigned yet</p></div>`}
      </div>`;
  } catch (err) {
    document.getElementById('user-detail-body').innerHTML = `<div class="alert alert-error" style="margin:1rem">⚠ ${err.message}</div>`;
  }
}

async function handleAddStudentAdmin(e) {
  e.preventDefault();
  await doAddStudent('admin-student-form', 'btn-add-student-admin');
}

async function doAddStudent(formId, btnId) {
  const btn = document.getElementById(btnId);
  const errId = formId + '-error';
  let errEl = document.getElementById(errId);
  if (!errEl) { errEl = document.createElement('div'); errEl.id = errId; errEl.className = 'alert alert-error'; document.getElementById(formId).prepend(errEl); }
  errEl.style.display = 'none';
  const data = {
    name: document.getElementById(formId.replace('-form','') + '-name') ? document.getElementById(formId.replace('-form','') + '-name').value.trim() : '',
    mobile: document.getElementById(formId.replace('-form','') + '-mobile')?.value.trim(),
    college: document.getElementById(formId.replace('-form','') + '-college')?.value.trim(),
    address: document.getElementById(formId.replace('-form','') + '-address')?.value.trim()
  };
  // fallback: get from data attributes
  const form = document.getElementById(formId);
  const inputs = form.querySelectorAll('input, textarea, select');
  inputs.forEach(inp => { if (inp.name) data[inp.name] = inp.value.trim(); });

  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Adding...';
  try {
    await apiFetch(`${API}/students.php?action=add`, 'POST', data);
    showToast('Student added & assigned!', 'success');
    form.reset();
  } catch (err) {
    errEl.textContent = err.message; errEl.style.display = 'flex';
  } finally {
    btn.disabled = false; btn.innerHTML = '+ Add Student';
  }
}

// Export Excel via CSV download
async function exportExcel() {
  try {
    const users = await apiFetch(`${API}/users.php?action=export`);
    if (!users.length) { showToast('No data to export', 'error'); return; }
    const headers = ['Name', 'Email', 'Phone', 'Gender', 'DOB', 'Role', 'Total Students', 'Completed'];
    const rows = users.map(u => [u.name, u.email, u.phone, u.gender, u.dob, u.role, u.total_students, u.completed]);
    const csv = [headers, ...rows].map(r => r.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url;
    a.download = `telecaller_report_${new Date().toISOString().split('T')[0]}.csv`;
    a.click(); URL.revokeObjectURL(url);
    showToast('Report exported!', 'success');
  } catch (err) { showToast(err.message, 'error'); }
}

// ─── MODAL HELPERS ───────────────────────────────────────
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
  });
});

// ─── UTILS ───────────────────────────────────────────────
function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function copyPassword() {
  const p = document.getElementById('generated-password').textContent;
  navigator.clipboard.writeText(p).then(() => showToast('Password copied!', 'success'));
}

// ─── INIT ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('btn-menu')?.addEventListener('click', toggleSidebar);
  routeInitial();
});
