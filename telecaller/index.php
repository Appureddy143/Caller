<?php
// index.php
session_start();
if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') header('Location: /pages/admin.php');
    else header('Location: /pages/telecaller.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AdmissionConnect — Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0a0e1a;
  --card:#111827;
  --border:#1e2d45;
  --accent:#3b82f6;
  --accent2:#06b6d4;
  --success:#10b981;
  --danger:#ef4444;
  --warn:#f59e0b;
  --text:#e2e8f0;
  --muted:#64748b;
  --radius:14px;
}
html,body{min-height:100vh;background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif}
body{display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}

/* Background mesh */
body::before{
  content:'';position:fixed;inset:0;
  background:radial-gradient(ellipse 80% 60% at 20% 30%,rgba(59,130,246,.12) 0%,transparent 60%),
             radial-gradient(ellipse 60% 80% at 80% 70%,rgba(6,182,212,.08) 0%,transparent 60%);
  pointer-events:none;
}

.login-wrap{
  width:100%;max-width:420px;padding:1rem;
  animation:fadeUp .5s ease both;
}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

.brand{text-align:center;margin-bottom:2rem}
.brand-icon{
  width:64px;height:64px;background:linear-gradient(135deg,var(--accent),var(--accent2));
  border-radius:18px;display:inline-flex;align-items:center;justify-content:center;
  font-size:1.8rem;margin-bottom:.8rem;
  box-shadow:0 0 40px rgba(59,130,246,.3);
}
.brand h1{font-family:'Syne',sans-serif;font-size:1.7rem;font-weight:800;
  background:linear-gradient(90deg,#fff,var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.brand p{color:var(--muted);font-size:.9rem;margin-top:.3rem}

.card{
  background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
  padding:2rem;box-shadow:0 25px 50px rgba(0,0,0,.5);
}

.form-tabs{display:flex;border-bottom:1px solid var(--border);margin-bottom:1.5rem;gap:.5rem}
.tab-btn{
  flex:1;padding:.6rem;background:transparent;border:none;color:var(--muted);
  font-family:'DM Sans',sans-serif;font-size:.9rem;cursor:pointer;
  border-bottom:2px solid transparent;margin-bottom:-1px;transition:.2s;
}
.tab-btn.active{color:var(--accent);border-bottom-color:var(--accent)}

.panel{display:none}.panel.active{display:block}

label{display:block;font-size:.82rem;color:var(--muted);margin-bottom:.4rem;font-weight:500}
input,select{
  width:100%;padding:.75rem 1rem;background:#0d1525;border:1px solid var(--border);
  border-radius:8px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:.95rem;
  transition:border-color .2s;outline:none;margin-bottom:1.1rem;
}
input:focus,select:focus{border-color:var(--accent)}
input::placeholder{color:#374151}
select option{background:#0d1525}

.btn{
  width:100%;padding:.85rem;background:linear-gradient(135deg,var(--accent),var(--accent2));
  border:none;border-radius:8px;color:#fff;font-family:'Syne',sans-serif;
  font-size:1rem;font-weight:700;cursor:pointer;letter-spacing:.03em;
  transition:opacity .2s,transform .1s;
}
.btn:hover{opacity:.9}.btn:active{transform:scale(.98)}
.btn:disabled{opacity:.5;cursor:not-allowed}

.alert{
  padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.875rem;display:none;
}
.alert.error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#fca5a5}
.alert.success{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#6ee7b7}
.alert.show{display:block}

.spinner{
  display:inline-block;width:18px;height:18px;border:2px solid rgba(255,255,255,.3);
  border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;vertical-align:middle;margin-right:.4rem;
}
@keyframes spin{to{transform:rotate(360deg)}}

/* Set Password Modal */
.modal-overlay{
  position:fixed;inset:0;background:rgba(0,0,0,.7);display:flex;
  align-items:center;justify-content:center;z-index:9999;display:none;
  backdrop-filter:blur(4px);
}
.modal-overlay.show{display:flex}
.modal{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
  padding:2rem;width:90%;max-width:380px;animation:fadeUp .3s ease}
.modal h3{font-family:'Syne',sans-serif;font-size:1.2rem;margin-bottom:.4rem}
.modal p{color:var(--muted);font-size:.875rem;margin-bottom:1.5rem}
</style>
</head>
<body>

<div class="login-wrap">
  <div class="brand">
    <div class="brand-icon">📞</div>
    <h1>AdmissionConnect</h1>
    <p>College Admission Telecalling System</p>
  </div>

  <div class="card">
    <div class="form-tabs">
      <button class="tab-btn active" onclick="switchTab('login',this)">Sign In</button>
      <button class="tab-btn" onclick="switchTab('forgot',this)">Forgot Password</button>
    </div>

    <!-- LOGIN PANEL -->
    <div id="panel-login" class="panel active">
      <div class="alert error" id="login-err"></div>
      <label>Email Address</label>
      <input type="email" id="login-email" placeholder="your@email.com" autocomplete="email">
      <label>Password</label>
      <input type="password" id="login-pass" placeholder="Enter password" autocomplete="current-password">
      <button class="btn" id="login-btn" onclick="doLogin()">Sign In</button>
    </div>

    <!-- FORGOT PANEL -->
    <div id="panel-forgot" class="panel">
      <div class="alert error" id="forgot-err"></div>
      <div class="alert success" id="forgot-ok"></div>
      <label>Email Address</label>
      <input type="email" id="f-email" placeholder="your@email.com">
      <label>Date of Birth</label>
      <input type="date" id="f-dob">
      <label>New Password</label>
      <input type="password" id="f-pass" placeholder="New password">
      <label>Confirm Password</label>
      <input type="password" id="f-confirm" placeholder="Confirm password">
      <button class="btn" id="forgot-btn" onclick="doForgot()">Reset Password</button>
    </div>
  </div>
</div>

<!-- Set New Password Modal -->
<div class="modal-overlay" id="set-pass-modal">
  <div class="modal">
    <h3>🔐 Set Your Password</h3>
    <p>This is your first login. Please create a new secure password to continue.</p>
    <div class="alert error" id="sp-err"></div>
    <label>New Password</label>
    <input type="password" id="sp-pass" placeholder="Create password (min 6 chars)">
    <label>Confirm Password</label>
    <input type="password" id="sp-confirm" placeholder="Confirm password">
    <button class="btn" onclick="doSetPassword()">Set Password & Continue</button>
  </div>
</div>

<script>
let tempUserId = null;

function switchTab(tab, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('panel-' + tab).classList.add('active');
}

function showAlert(id, msg, type='error') {
  const el = document.getElementById(id);
  el.className = 'alert ' + type + ' show';
  el.textContent = msg;
}
function hideAlert(id) {
  document.getElementById(id).className = 'alert';
}

async function doLogin() {
  const email = document.getElementById('login-email').value.trim();
  const pass  = document.getElementById('login-pass').value;
  hideAlert('login-err');

  if (!email || !pass) { showAlert('login-err','Email and password are required'); return; }

  const btn = document.getElementById('login-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span>Signing in...';

  try {
    const res = await fetch('/api/auth.php?action=login', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({email, password: pass})
    });
    const data = await res.json();

    if (data.require_set_password) {
      tempUserId = data.user_id;
      document.getElementById('set-pass-modal').classList.add('show');
    } else if (data.success) {
      const role = data.user.role;
      window.location.href = role === 'admin' ? '/pages/admin.php' : '/pages/telecaller.php';
    } else {
      showAlert('login-err', data.error || 'Login failed');
    }
  } catch(e) {
    showAlert('login-err','Network error. Please try again.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Sign In';
  }
}

async function doSetPassword() {
  const pass    = document.getElementById('sp-pass').value;
  const confirm = document.getElementById('sp-confirm').value;
  hideAlert('sp-err');

  if (!pass || !confirm) { showAlert('sp-err','Both fields required'); return; }
  if (pass !== confirm)  { showAlert('sp-err','Passwords do not match'); return; }
  if (pass.length < 6)   { showAlert('sp-err','Password must be at least 6 characters'); return; }

  const res = await fetch('/api/auth.php?action=set_password', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({user_id: tempUserId, password: pass, confirm_password: confirm})
  });
  const data = await res.json();
  if (data.success) {
    window.location.href = data.role === 'admin' ? '/pages/admin.php' : '/pages/telecaller.php';
  } else {
    showAlert('sp-err', data.error || 'Failed to set password');
  }
}

async function doForgot() {
  const email   = document.getElementById('f-email').value.trim();
  const dob     = document.getElementById('f-dob').value;
  const pass    = document.getElementById('f-pass').value;
  const confirm = document.getElementById('f-confirm').value;
  hideAlert('forgot-err'); hideAlert('forgot-ok');

  if (!email||!dob||!pass||!confirm) { showAlert('forgot-err','All fields required'); return; }
  if (pass !== confirm) { showAlert('forgot-err','Passwords do not match'); return; }

  const btn = document.getElementById('forgot-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span>Resetting...';

  try {
    const res = await fetch('/api/auth.php?action=forgot_password', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({email, dob, password:pass, confirm_password:confirm})
    });
    const data = await res.json();
    if (data.success) {
      showAlert('forgot-ok','Password reset! You can now sign in.','success');
      setTimeout(()=>{
        switchTab('login', document.querySelector('.tab-btn'));
      }, 2000);
    } else {
      showAlert('forgot-err', data.error || 'Reset failed');
    }
  } catch(e) {
    showAlert('forgot-err','Network error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Reset Password';
  }
}

// Enter key
document.addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    const active = document.querySelector('.panel.active').id;
    if (active === 'panel-login') doLogin();
    else if (active === 'panel-forgot') doForgot();
  }
});
</script>
</body>
</html>
