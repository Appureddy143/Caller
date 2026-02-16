<?php
// pages/telecaller.php
session_start();
require_once __DIR__ . '/../includes/config.php';
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['telecaller','office'])) {
    header('Location: ' . BASE_URL . '/'); exit;
}
$userName = $_SESSION['name'];
$userId   = $_SESSION['user_id'];
$role     = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Telecaller — AdmissionConnect</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0a0e1a;--card:#111827;--border:#1e2d45;
  --accent:#3b82f6;--accent2:#06b6d4;--success:#10b981;--danger:#ef4444;
  --warn:#f59e0b;--purple:#8b5cf6;
  --text:#e2e8f0;--muted:#64748b;--radius:12px;
}
html,body{min-height:100vh;background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;font-size:15px}

/* Header */
.header{
  position:sticky;top:0;background:rgba(10,14,26,.95);backdrop-filter:blur(10px);
  border-bottom:1px solid var(--border);padding:.85rem 1.25rem;
  display:flex;align-items:center;justify-content:space-between;z-index:100;
}
.brand{display:flex;align-items:center;gap:.6rem}
.brand .icon{
  width:34px;height:34px;background:linear-gradient(135deg,var(--accent),var(--accent2));
  border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1rem;
}
.brand span{font-family:'Syne',sans-serif;font-weight:700;font-size:.95rem}

/* Profile btn */
.profile-btn{
  display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.35rem .7rem;
  border-radius:8px;background:var(--card);border:1px solid var(--border);
  position:relative;transition:.15s;
}
.profile-btn:hover{border-color:var(--accent)}
.avatar{
  width:30px;height:30px;background:linear-gradient(135deg,var(--accent),var(--accent2));
  border-radius:7px;display:flex;align-items:center;justify-content:center;
  font-size:.8rem;font-weight:700;
}
.profile-popup{
  position:absolute;top:calc(100% + 8px);right:0;background:var(--card);
  border:1px solid var(--border);border-radius:10px;min-width:140px;
  box-shadow:0 10px 30px rgba(0,0,0,.4);display:none;z-index:200;
}
.profile-popup.show{display:block}
.popup-item{padding:.6rem .9rem;cursor:pointer;display:flex;align-items:center;gap:.5rem;font-size:.85rem;transition:.15s}
.popup-item:hover{background:rgba(255,255,255,.05)}
.popup-item.logout{color:var(--danger)}
.popup-divider{border-top:1px solid var(--border);margin:.2rem 0}

/* Reminder banner */
.reminder-banner{
  background:rgba(245,158,11,.1);border-bottom:1px solid rgba(245,158,11,.2);
  padding:.6rem 1.25rem;display:none;align-items:center;gap:.5rem;font-size:.875rem;
}
.reminder-banner.show{display:flex}

/* Tab nav */
.tab-nav{
  display:flex;background:var(--card);border-bottom:1px solid var(--border);
  padding:0 1rem;position:sticky;top:57px;z-index:90;
}
.tab{
  padding:.75rem 1rem;cursor:pointer;font-size:.875rem;color:var(--muted);
  border-bottom:2px solid transparent;margin-bottom:-1px;transition:.15s;white-space:nowrap;
}
.tab.active{color:var(--accent);border-bottom-color:var(--accent)}
.tab-badge{
  display:inline-flex;align-items:center;justify-content:center;
  background:var(--danger);color:#fff;border-radius:20px;
  font-size:.7rem;padding:.1rem .4rem;margin-left:.3rem;font-weight:700;
}

/* Content */
.content{padding:1rem 1.25rem;max-width:900px;margin:0 auto}

/* Student card */
.student-card{
  background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
  margin-bottom:.75rem;transition:.2s;overflow:hidden;
}
.student-card:hover{border-color:rgba(59,130,246,.3)}
.student-card.reminder-today{border-color:rgba(245,158,11,.4);background:rgba(245,158,11,.03)}
.card-head{padding:.9rem 1rem;display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap}
.card-info{flex:1;min-width:0}
.card-name{font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;margin-bottom:.15rem;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.card-sub{font-size:.8rem;color:var(--muted);display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
.card-actions{display:flex;gap:.5rem;align-items:center;flex-shrink:0}

/* Buttons */
.btn{
  padding:.5rem .9rem;border:none;border-radius:8px;cursor:pointer;
  font-family:'DM Sans',sans-serif;font-size:.85rem;font-weight:500;transition:.15s;
  display:inline-flex;align-items:center;gap:.35rem;white-space:nowrap;
}
.btn-call{background:linear-gradient(135deg,var(--success),#059669);color:#fff;font-weight:700}
.btn-call:hover{opacity:.9}
.btn-feedback{background:var(--card);border:1px solid var(--border);color:var(--text)}
.btn-feedback:hover{border-color:var(--accent);color:var(--accent)}
.btn-primary{background:var(--accent);color:#fff}
.btn-primary:hover{background:#2563eb}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--text)}
.btn-outline:hover{border-color:var(--accent);color:var(--accent)}
.btn-danger{background:var(--danger);color:#fff}
.btn:disabled{opacity:.5;cursor:not-allowed}

/* Badge */
.badge{display:inline-flex;align-items:center;padding:.15rem .5rem;border-radius:20px;font-size:.72rem;font-weight:600}
.badge-green{background:rgba(16,185,129,.15);color:#6ee7b7}
.badge-red{background:rgba(239,68,68,.15);color:#fca5a5}
.badge-yellow{background:rgba(245,158,11,.15);color:#fcd34d}
.badge-gray{background:rgba(100,116,139,.15);color:#94a3b8}
.badge-blue{background:rgba(59,130,246,.15);color:#93c5fd}

/* Collapsible details */
.card-details{
  border-top:1px solid var(--border);padding:.75rem 1rem;
  background:#0d1525;font-size:.82rem;display:none;
}
.card-details.open{display:block}
.detail-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.4rem .75rem}
.detail-item .lbl{color:var(--muted);font-size:.72rem;margin-bottom:.1rem}

/* Feedback history */
.feedback-item{background:var(--card);border-radius:8px;padding:.6rem .75rem;margin-bottom:.4rem}

/* MODAL */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(4px);z-index:500;display:none;align-items:flex-end;justify-content:center}
.modal-bg.show{display:flex}
.modal{
  background:var(--card);border:1px solid var(--border);
  border-radius:var(--radius) var(--radius) 0 0;
  width:100%;max-width:560px;max-height:90vh;overflow-y:auto;
  animation:slideUp .3s ease;
}
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
.modal-hdr{
  padding:1rem 1.25rem;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;background:var(--card);z-index:10;
}
.modal-hdr h3{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700}
.modal-close{background:none;border:none;color:var(--muted);font-size:1.2rem;cursor:pointer;padding:.2rem .4rem}
.modal-body{padding:1.25rem}
.modal-footer{padding:.9rem 1.25rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:.6rem;position:sticky;bottom:0;background:var(--card)}

/* Form */
.form-group{margin-bottom:1rem}
.form-label{display:block;font-size:.8rem;color:var(--muted);margin-bottom:.35rem;font-weight:500}
.form-input{
  width:100%;padding:.65rem .9rem;background:#0d1525;border:1px solid var(--border);
  border-radius:8px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:.9rem;outline:none;transition:.2s;
}
.form-input:focus{border-color:var(--accent)}
textarea.form-input{resize:vertical;min-height:80px}
select.form-input option{background:#0d1525}

/* Status options */
.status-opts{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;margin-bottom:1rem}
.status-opt{
  border:2px solid var(--border);border-radius:8px;padding:.6rem;text-align:center;
  cursor:pointer;transition:.15s;font-size:.82rem;
}
.status-opt:hover{border-color:var(--accent)}
.status-opt.selected-accepted{border-color:var(--success);background:rgba(16,185,129,.1);color:var(--success)}
.status-opt.selected-rejected{border-color:var(--danger);background:rgba(239,68,68,.1);color:var(--danger)}
.status-opt.selected-other{border-color:var(--warn);background:rgba(245,158,11,.1);color:var(--warn)}

/* Meta info */
.meta-row{display:flex;gap:.5rem;align-items:center;background:#0d1525;border-radius:8px;padding:.6rem .9rem;margin-bottom:1rem;font-size:.8rem;color:var(--muted)}

/* Empty state */
.empty{text-align:center;padding:3rem 1rem;color:var(--muted)}
.empty .ico{font-size:2.5rem;margin-bottom:.75rem}

/* Alert */
.alert{padding:.65rem .9rem;border-radius:8px;margin-bottom:.75rem;font-size:.85rem;display:none}
.alert.show{display:block}
.alert-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#fca5a5}
.alert-ok{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#6ee7b7}

/* Spinner */
.spin{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:rot .6s linear infinite;vertical-align:middle;margin-right:.3rem}
@keyframes rot{to{transform:rotate(360deg)}}

/* Search */
.search-wrap{margin-bottom:.75rem}
.search-input{
  width:100%;padding:.6rem .9rem;background:var(--card);border:1px solid var(--border);
  border-radius:8px;color:var(--text);font-size:.875rem;outline:none;transition:.2s;
}
.search-input:focus{border-color:var(--accent)}

/* Responsive */
@media(max-width:480px){
  .card-actions{width:100%;justify-content:flex-end}
  .status-opts{grid-template-columns:1fr}
  .brand span{display:none}
}
</style>
</head>
<body>

<div class="header">
  <div class="brand">
    <div class="icon">📞</div>
    <span>AdmissionConnect</span>
  </div>
  <div class="profile-btn" onclick="toggleProfile()">
    <div class="avatar"><?= strtoupper(substr($userName,0,1)) ?></div>
    <span style="font-size:.85rem;font-weight:500"><?= htmlspecialchars($userName) ?></span>
    <span style="font-size:.65rem;color:var(--muted)">▼</span>
    <div class="profile-popup" id="profile-popup">
      <div class="popup-item" style="pointer-events:none">
        <div>
          <div style="font-weight:600"><?= htmlspecialchars($userName) ?></div>
          <div style="font-size:.72rem;color:var(--muted)"><?= ucfirst($role) ?></div>
        </div>
      </div>
      <div class="popup-divider"></div>
      <div class="popup-item logout" onclick="doLogout()">🚪 Logout</div>
    </div>
  </div>
</div>

<div class="reminder-banner" id="reminder-banner">
  <span>🔔</span>
  <span id="reminder-text">You have callback reminders today!</span>
</div>

<div class="tab-nav">
  <div class="tab active" onclick="switchTab('new',this)">
    New List <span class="tab-badge" id="new-count">0</span>
  </div>
  <div class="tab" onclick="switchTab('previous',this)">
    Previous Updates
  </div>
</div>

<div class="content">
  <div class="search-wrap">
    <input class="search-input" id="search-input" placeholder="🔍 Search student name or mobile..." oninput="filterStudents()">
  </div>

  <!-- NEW LIST tab -->
  <div id="tab-new">
    <div id="new-list">
      <div class="empty"><div class="ico">⏳</div><p>Loading students...</p></div>
    </div>
  </div>

  <!-- PREVIOUS tab -->
  <div id="tab-previous" style="display:none">
    <div id="prev-list">
      <div class="empty"><div class="ico">📋</div><p>Loading history...</p></div>
    </div>
  </div>
</div>

<!-- FEEDBACK MODAL -->
<div class="modal-bg" id="feedback-modal">
  <div class="modal">
    <div class="modal-hdr">
      <h3 id="fb-title">📝 Feedback</h3>
      <button class="modal-close" onclick="closeFeedbackModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="alert alert-err" id="fb-err"></div>

      <!-- Auto date/time -->
      <div class="meta-row">
        <span>📅</span>
        <span id="fb-datetime"></span>
        <span style="color:var(--accent);font-size:.72rem;margin-left:auto">(auto)</span>
      </div>

      <input type="hidden" id="fb-student-id">
      <input type="hidden" id="fb-student-name">

      <!-- Status selection -->
      <div class="form-label" style="margin-bottom:.5rem">Call Outcome *</div>
      <div class="status-opts">
        <div class="status-opt" id="opt-accepted" onclick="selectStatus('accepted')">
          ✅<br>Accepted
        </div>
        <div class="status-opt" id="opt-rejected" onclick="selectStatus('rejected')">
          ❌<br>Rejected
        </div>
        <div class="status-opt" id="opt-other" onclick="selectStatus('other')">
          📋<br>Other
        </div>
      </div>

      <!-- Other reason (shown for 'other') -->
      <div id="other-fields" style="display:none">
        <div class="form-group">
          <label class="form-label">Reason * <span style="font-size:.72rem;color:var(--muted)">(e.g. Switch off, Not reachable, Call tomorrow)</span></label>
          <input class="form-input" id="fb-reason" placeholder="Enter reason...">
        </div>
        <div class="form-group">
          <label class="form-label">📅 Schedule Callback Date</label>
          <input class="form-input" type="date" id="fb-callback-date">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Notes (Optional)</label>
        <textarea class="form-input" id="fb-notes" placeholder="Any additional notes..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeFeedbackModal()">Cancel</button>
      <button class="btn btn-primary" id="fb-submit-btn" onclick="submitFeedback()">✓ Submit Feedback</button>
    </div>
  </div>
</div>

<script>
const BASE = '<?php echo rtrim(BASE_URL, "/"); ?>';
let allStudents = [];
let currentStatus = '';
const today = new Date().toISOString().split('T')[0];

// ─── PROFILE ──────────────────────────────────────────────
function toggleProfile() {
  document.getElementById('profile-popup').classList.toggle('show');
}
document.addEventListener('click', e => {
  if (!e.target.closest('.profile-btn')) {
    document.getElementById('profile-popup').classList.remove('show');
  }
});

async function doLogout() {
  await fetch(BASE + '/api/auth.php?action=logout',{method:'POST'});
  window.location.href = BASE + '/';
}

// ─── TABS ──────────────────────────────────────────────────
let activeTab = 'new';
function switchTab(tab, el) {
  activeTab = tab;
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('tab-new').style.display      = tab==='new' ? '' : 'none';
  document.getElementById('tab-previous').style.display = tab==='previous' ? '' : 'none';
  if (tab === 'previous') renderPrevious();
}

// ─── LOAD STUDENTS ─────────────────────────────────────────
async function loadStudents() {
  const res  = await fetch(BASE + '/api/students.php?action=my_list');
  const data = await res.json();
  allStudents = data.students || [];

  // Reminder banner
  if (data.reminder_count > 0) {
    document.getElementById('reminder-banner').classList.add('show');
    document.getElementById('reminder-text').textContent =
      `🔔 You have ${data.reminder_count} callback reminder${data.reminder_count>1?'s':''} due today!`;
  }

  // Update count badge
  const newOnes = allStudents.filter(s=>!s.last_feedback);
  document.getElementById('new-count').textContent = newOnes.length;

  renderStudents();
}

function filterStudents() {
  renderStudents();
  if (activeTab === 'previous') renderPrevious();
}

function renderStudents() {
  const q = document.getElementById('search-input').value.toLowerCase();
  // "New List" = pending/no feedback
  const newStudents = allStudents.filter(s =>
    (!s.last_feedback || s.has_reminder_today) &&
    (!q || s.name.toLowerCase().includes(q) || s.mobile.includes(q))
  );

  const container = document.getElementById('new-list');
  if (!newStudents.length) {
    container.innerHTML = '<div class="empty"><div class="ico">🎉</div><p>All caught up! No new students.</p></div>';
    return;
  }
  container.innerHTML = newStudents.map(s => studentCard(s)).join('');
}

function renderPrevious() {
  const q = document.getElementById('search-input').value.toLowerCase();
  const prevStudents = allStudents.filter(s =>
    s.last_feedback &&
    (!q || s.name.toLowerCase().includes(q) || s.mobile.includes(q))
  );
  const container = document.getElementById('prev-list');
  if (!prevStudents.length) {
    container.innerHTML = '<div class="empty"><div class="ico">📋</div><p>No previous calls yet.</p></div>';
    return;
  }
  container.innerHTML = prevStudents.map(s => studentCard(s)).join('');
}

function studentCard(s) {
  const isReminder = s.has_reminder_today;
  return `
  <div class="student-card${isReminder?' reminder-today':''}" id="card-${s.id}">
    <div class="card-head">
      <div class="card-info">
        <div class="card-name">${esc(s.name)} ${isReminder?'<span class="badge badge-yellow">🔔 Callback Today</span>':''}</div>
        <div class="card-sub">
          <span>📱 ${esc(s.mobile)}</span>
          ${s.present_college?`<span>🏫 ${esc(s.present_college)}</span>`:''}
          ${s.college_type?`<span class="badge badge-blue">${s.college_type}</span>`:''}
          ${s.last_feedback ? statusBadge(s.last_feedback) : '<span class="badge badge-gray">New</span>'}
        </div>
      </div>
      <div class="card-actions">
        <button class="btn btn-call" onclick="callStudent('${esc(s.mobile)}',${s.id})">
          📞 Call Now
        </button>
        <button class="btn btn-feedback" onclick="openFeedback(${s.id},'${esc(s.name)}')">
          📝 Feedback
        </button>
        <button class="btn btn-outline" style="padding:.5rem .6rem" onclick="toggleDetails(${s.id})" title="More info">
          ⋯
        </button>
      </div>
    </div>
    <div class="card-details" id="details-${s.id}">
      <div class="detail-grid">
        <div class="detail-item"><div class="lbl">Address</div><div>${esc(s.address||'—')}</div></div>
        <div class="detail-item"><div class="lbl">Last Call</div><div>${s.last_call ? new Date(s.last_call).toLocaleDateString() : '—'}</div></div>
        ${s.reminder_date?`<div class="detail-item"><div class="lbl">Reminder</div><div style="color:var(--warn)">${s.reminder_date}</div></div>`:''}
      </div>
    </div>
  </div>`;
}

function toggleDetails(id) {
  const el = document.getElementById('details-' + id);
  el.classList.toggle('open');
}

// ─── CALL NOW ──────────────────────────────────────────────
function callStudent(mobile, studentId) {
  // Open dialer
  window.location.href = 'tel:' + mobile;
  // After dial, open feedback after short delay
  setTimeout(()=>{
    const s = allStudents.find(x=>x.id===studentId);
    if (s) openFeedback(studentId, s.name);
  }, 1500);
}

// ─── FEEDBACK MODAL ────────────────────────────────────────
function openFeedback(studentId, studentName) {
  document.getElementById('fb-student-id').value   = studentId;
  document.getElementById('fb-student-name').value = studentName;
  document.getElementById('fb-title').textContent  = `📝 Feedback — ${studentName}`;
  document.getElementById('fb-err').classList.remove('show');
  document.getElementById('fb-reason').value        = '';
  document.getElementById('fb-callback-date').value = '';
  document.getElementById('fb-notes').value         = '';
  currentStatus = '';
  ['accepted','rejected','other'].forEach(s=>{
    document.getElementById('opt-'+s).className = 'status-opt';
  });
  document.getElementById('other-fields').style.display = 'none';

  // Set min date for callback
  document.getElementById('fb-callback-date').min = today;

  // Auto date/time
  updateFeedbackTime();

  document.getElementById('feedback-modal').classList.add('show');
}

function updateFeedbackTime() {
  const now = new Date();
  document.getElementById('fb-datetime').textContent =
    now.toLocaleDateString('en-IN', {weekday:'short',day:'numeric',month:'short',year:'numeric'}) +
    ' ' + now.toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit'});
}

function closeFeedbackModal() {
  document.getElementById('feedback-modal').classList.remove('show');
}

function selectStatus(status) {
  currentStatus = status;
  ['accepted','rejected','other'].forEach(s=>{
    const el = document.getElementById('opt-'+s);
    el.className = s===status ? `status-opt selected-${s}` : 'status-opt';
  });
  document.getElementById('other-fields').style.display = status==='other' ? '' : 'none';
}

async function submitFeedback() {
  const student_id    = parseInt(document.getElementById('fb-student-id').value);
  const reason        = document.getElementById('fb-reason').value.trim();
  const callback_date = document.getElementById('fb-callback-date').value;
  const notes         = document.getElementById('fb-notes').value.trim();
  const errEl         = document.getElementById('fb-err');
  errEl.classList.remove('show');

  if (!currentStatus) { showFbErr('Please select a call outcome'); return; }
  if (currentStatus==='other' && !reason) { showFbErr('Please enter a reason'); return; }

  const btn = document.getElementById('fb-submit-btn');
  btn.disabled=true; btn.innerHTML='<span class="spin"></span>Saving...';

  try {
    const res = await fetch(BASE + '/api/feedback.php?action=submit',{
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        student_id,
        call_status: currentStatus,
        other_reason: reason||null,
        callback_date: callback_date||null,
        notes: notes||null
      })
    });
    const data = await res.json();
    if (data.success) {
      closeFeedbackModal();
      await loadStudents();
    } else { showFbErr(data.error||'Failed to submit'); }
  } catch(e){ showFbErr('Network error'); }
  finally{ btn.disabled=false; btn.innerHTML='✓ Submit Feedback'; }
}

function showFbErr(msg) {
  const el = document.getElementById('fb-err');
  el.className='alert alert-err show';
  el.textContent=msg;
}

// ─── HELPERS ──────────────────────────────────────────────
function statusBadge(s) {
  const map={
    accepted:'<span class="badge badge-green">✓ Accepted</span>',
    rejected:'<span class="badge badge-red">✗ Rejected</span>',
    pending:'<span class="badge badge-gray">⏳ Pending</span>',
    callback:'<span class="badge badge-yellow">📅 Callback</span>',
    in_progress:'<span class="badge badge-blue">📞 In Progress</span>',
  };
  return map[s]||`<span class="badge badge-gray">${s}</span>`;
}
function esc(s){return String(s||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]))}

// Prevent body scroll when modal open
document.getElementById('feedback-modal').addEventListener('click', function(e){
  if (e.target === this) closeFeedbackModal();
});

// Init
loadStudents();
setInterval(updateFeedbackTime, 30000);
</script>
</body>
</html>
