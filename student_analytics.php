<?php
session_start();
if (!isset($_SESSION['email'])) { header('Location: login.php'); exit; }
$student_email = $_SESSION['email'];
$initial = strtoupper(substr($student_email, 0, 1));
$logoUrl = "asset/agriquiz-logo.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Performance – AGRIQUIZ</title>
    <link rel="icon" href="<?= $logoUrl ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/student_analytics.css">
  <style>
    .sidebar-brand .brand-icon { background: transparent !important; overflow: hidden; }
    .sidebar-brand .brand-icon img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; display: block; }
    .ale-banner {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 16px 22px;
      border-radius: 14px;
      margin-bottom: 22px;
      font-size: 14px;
      font-weight: 700;
      animation: fadeSlideIn .5s ease;
    }
    .ale-banner.ready {
      background: #e8f5e9;
      border: 2px solid #a5d6a7;
      color: #1b5e20;
    }
    .ale-banner.not-ready {
      background: #fff8e1;
      border: 2px solid #c60c0c;
      color: #9d3109;
    }
    .ale-banner .ale-icon {
      font-size: 26px;
      flex-shrink: 0;
    }
    .ale-banner .ale-text strong { font-size: 15px; display: block; margin-bottom: 2px; }
    .ale-banner .ale-text span  { font-size: 12px; font-weight: 500; opacity: .85; }

    @keyframes fadeSlideIn {
      from { opacity: 0; transform: translateY(-8px); }
      to   { opacity: 1; transform: translateY(0);    }
    }

     /*logout*/
         .logout-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .logout-overlay.active {
            display: flex;
        }
        .logout-card {
            background: #fff;
            padding: 30px 28px;
            border-radius: 14px;
            text-align: center;
            max-width: 320px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: logoutPop .18s ease-out;
        }
        @keyframes logoutPop {
            from { transform: scale(0.9); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }
        .logout-icon {
            width: 56px; height: 56px;
            margin: 0 auto 14px;
            border-radius: 50%;
            background: #fef2f2;
            color: #e53e3e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .logout-card h3 {
            font-size: 17px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 6px;
        }
        .logout-card p {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 22px;
        }
        .logout-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .logout-actions button {
            flex: 1;
            padding: 10px 0;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .15s;
        }
        .btn-cancel {
            background: #f1f1f1;
            color: #333;
        }
        .btn-cancel:hover { opacity: .8; }
        .btn-confirm {
            background: #e53e3e;
            color: #fff;
        }
        .btn-confirm:hover { opacity: .85; }

    /* Refresh button */
    .btn-refresh {
      background: transparent;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 8px;
      cursor: pointer;
      color: var(--text-dark);
      font-weight: 700;
      font-size: 14px;
    }
    .btn-refresh .fa-sync-alt {
      transition: transform .6s linear;
    }
    .btn-refresh.spinning .fa-sync-alt {
      transform: rotate(360deg);
    }

  </style>
</head>
<body>

<div class="logout-overlay" id="logoutModal">
    <div class="logout-card">
        <div class="logout-icon"><i class="fas fa-sign-out-alt"></i></div>
        <h3>Log out?</h3>
        <p>Are you sure you want to log out?</p>
        <div class="logout-actions">
            <button class="btn-cancel" onclick="hideLogoutModal()">No</button>
            <button class="btn-confirm" onclick="confirmLogout()">Yes, Logout</button>
        </div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><img src="<?= $logoUrl ?>" alt="AgriQuiz"></div>
    <h4>AGRI<span style="color: yellow;">QUIZ</span></h4>
  </div>
  <nav>
    <a href="student_dashboard.php"><i class="fas fa-home"></i> Home</a>
    <a href="review_quiz.php"><i class="fas fa-book"></i> Reviewer &amp; Quiz</a>
    <a href="student_analytics.php" class="active"><i class="fas fa-chart-line"></i> Performance</a>
  </nav>
</div>

<div class="topbar">
    <div class="topbar-left">
        <button class="btn-hamburger" id="sidebarToggle" aria-label="Toggle sidebar" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-title"><i class="fas fa-home"></i> Performance</div>
    </div>
    <div class="topbar-right">
        <!-- Added refresh button -->
        <button id="refreshBtn" class="btn-refresh" title="Refresh performance" onclick="refreshAll(true)">
          <i class="fas fa-sync-alt"></i><span class="btn-label">Refresh</span>
        </button>

        <a href="profile.php" class="btn-profile"><i class="fas fa-user-circle"></i><span class="btn-label"> Profile</span></a>
       
        <a href="#" onclick="showLogoutModal(); return false;" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span class="btn-label"> Logout</span></a>
    </div>
</div>


<div class="content">

  <div class="page-header">
    <div class="page-header-text">
      <h1><i class="fas fa-chart-line" style="margin-right:8px;opacity:.85"></i> Performance Analytics</h1>
      <p>Track your progress across all agricultural learning areas</p>
      <div class="last-updated" id="lastUpdated"></div>
    </div>
  </div>

  <!-- ALE READINESS BANNER (dynamic) -->
  <div id="aleBanner"></div>

  <!-- LEGEND -->
  <div class="legend-row">
    <div class="legend-item"><div class="legend-dot red"></div> Below 50% — Needs improvement</div>
    <div class="legend-item"><div class="legend-dot orange"></div> 50–79% — Keep practicing</div>
    <div class="legend-item"><div class="legend-dot green"></div> 80–100% — Excellent!</div>
  </div>

  <!-- STAT CARDS -->
  <div class="stats-row" id="statsRow">
    <div class="stat-card"><div class="stat-icon green"><i class="fas fa-pen"></i></div><div class="stat-value" id="s-attempts">0</div><div class="stat-label">Quizzes Taken</div></div>
    <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-chart-line"></i></div><div class="stat-value" id="s-avg">0</div><div class="stat-label">Overall Average</div></div>
    <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-trophy"></i></div><div class="stat-value" id="s-best">0</div><div class="stat-label">Best Score</div></div>
    <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-layer-group"></i></div><div class="stat-value" id="s-fields">0</div><div class="stat-label">Areas Attempted</div></div>
  </div>

  <!-- FIELD PERFORMANCE CARDS -->
  <div id="fieldsGrid" class="fields-grid">
    <div style="grid-column:1/-1"><div class="spinner-wrap"><div class="spinner"></div><p>Loading your performance…</p></div></div>
  </div>

  <!-- HISTORY TABLE -->
  <div class="page-box">
    <div class="section-title">Quiz History</div>
    <div id="historyContainer">
      <div class="spinner-wrap"><div class="spinner"></div></div>
    </div>
  </div>

</div>

<script>
const ANALYTICS_API    = 'api/analytics_api.php';
const PASSING_RATE_API = 'api/passing_rate_api.php';
const STUDENT_EMAIL    = <?= json_encode($student_email) ?>;

let latestAttemptId      = 0;
let lastRefreshTimestamp = 0;
let passingRate          = 75;   // default; overwritten on load
let currentOverallAvg    = 0;

// ── Sidebar ──
function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('show'); }
function closeSidebar(){ document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebarOverlay').classList.remove('show'); }

function scoreColor(pct){ return pct < 50 ? 'red' : pct < 80 ? 'orange' : 'green'; }
function scoreLabel(pct){ return pct < 50 ? '⚠ Needs Improvement' : pct < 80 ? '📈 Keep Practicing' : '🎉 Excellent!'; }
function scoreAdvice(pct, field){
  if(pct < 50)  return `Your average in <strong>${field}</strong> is critically low. Re-read the reviewer and retake the quiz to improve.`;
  if(pct < 80)  return `You're making progress in <strong>${field}</strong>! Focus on weak areas and try the quiz again.`;
  return `Great job in <strong>${field}</strong>! Keep it up and aim for a perfect score.`;
}

async function apiFetch(action, params={}){
  const qs = new URLSearchParams({action,...params}).toString();
  const res = await fetch(`${ANALYTICS_API}?${qs}`);
  return res.json();
}

// ALE READINESS BANNER
function renderAleBanner(avg, rate){
  const banner  = document.getElementById('aleBanner');
  const isReady = avg >= rate;
  const need    = (rate - avg).toFixed(1);
  if (!banner) return;
  banner.innerHTML = isReady
    ? `<div class="ale-banner ready">
         <div class="ale-icon">🏆</div>
         <div class="ale-text">
           <strong>You are Ready for the Actual ALE!</strong>
           <span>Your overall average (${avg.toFixed(1)}%) meets the ${rate}% passing requirement. Keep it up!</span>
         </div>
       </div>`
    : `<div class="ale-banner not-ready">
         <div class="ale-icon">📚</div>
         <div class="ale-text">
           <strong>Not Yet Ready for the Actual ALE</strong>
           <span>Your overall average is ${avg.toFixed(1)}%. You need ${need}% more to reach the ${rate}% passing rate.</span>
         </div>
       </div>`;
}

// LOAD PASSING RATE
async function loadPassingRate(){
  // 1. Try server (authoritative)
  try {
    const res  = await fetch(PASSING_RATE_API + '?action=get');
    const data = await res.json();
    if(data.success){
      passingRate = data.passing_rate;
      // Keep localStorage in sync so same-tab detection also works
      localStorage.setItem('agriquiz_passing_rate', passingRate);
      return;
    }
  } catch(e){ /* fall through */ }

  // 2. Fallback: localStorage (set by admin in same browser session)
  const stored = localStorage.getItem('agriquiz_passing_rate');
  if(stored) passingRate = parseInt(stored) || 75;
}

// ── REFRESH ALL ──────
async function refreshAll(showToast = false){
  const btn = document.getElementById('refreshBtn');
  if (btn) btn.classList.add('spinning');
  lastRefreshTimestamp = Date.now();

  // Always re-fetch passing rate so student sees admin's latest setting
  await loadPassingRate();

  await Promise.all([loadSummary(), loadFieldPerf(), loadHistory(showToast)]);

  if (btn) btn.classList.remove('spinning');
  const now = new Date();
  const lastUpdatedEl = document.getElementById('lastUpdated');
  if (lastUpdatedEl) {
    lastUpdatedEl.textContent =
      `Last updated: ${now.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit',second:'2-digit'})}`;
  }

  if (showToast) showLiveToast('✓ Performance data refreshed!');
}

// ── LIVE TOAST ───
function showLiveToast(msg){
  const existing = document.querySelector('.live-toast');
  if(existing) existing.remove();
  const toast = document.createElement('div');
  toast.className = 'live-toast';
  toast.innerHTML = `<i class="fas fa-check-circle"></i> ${msg}`;
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.classList.add('hide');
    setTimeout(() => toast.remove(), 400);
  }, 3000);
}

// ── LOAD SUMMARY ─────────
async function loadSummary(){
  const r = await apiFetch('student_summary', {email: STUDENT_EMAIL});
  if(r.success && r.data){
    const d   = r.data;
    const avg  = parseFloat(d.overall_avg||0);
    const best = parseFloat(d.best_score||0);
    currentOverallAvg = avg;

    animateValue('s-attempts', parseInt(d.total_attempts||0));
    const sAvg = document.getElementById('s-avg');
    if (sAvg) sAvg.innerHTML   = `<span style="color:var(--clr-${scoreColor(avg)})">${avg.toFixed(1)}%</span>`;
    const sBest = document.getElementById('s-best');
    if (sBest) sBest.innerHTML  = `<span style="color:var(--clr-${scoreColor(best)})">${best.toFixed(1)}%</span>`;
    animateValue('s-fields', parseInt(d.fields_attempted||0));

    // Render ALE banner now that we have both avg and passingRate
    renderAleBanner(avg, passingRate);
  }
}

function animateValue(id, newVal){
  const el = document.getElementById(id);
  if (!el) return;
  const oldVal = parseInt(el.textContent) || 0;
  if(oldVal !== newVal){
    const card = el.closest('.stat-card');
    if (card) card.classList.add('updating');
    setTimeout(() => { if (card) card.classList.remove('updating'); }, 800);
  }
  el.textContent = newVal;
}

// ── LOAD FIELD PERFORMANCE ──────
async function loadFieldPerf(){
  const grid = document.getElementById('fieldsGrid');
  const r = await apiFetch('student_field_performance', {email: STUDENT_EMAIL});

  if(!r.success || !r.data?.length){
    if (grid) {
      grid.innerHTML = `
        <div style="grid-column:1/-1">
          <div class="empty-state">
            <i class="fas fa-seedling"></i>
            <p>You haven't taken any quizzes yet.<br>Go to <a href="review_quiz.php" style="color:var(--green-primary);font-weight:700">Review &amp; Quiz</a> to get started!</p>
          </div>
        </div>`;
    }
    return;
  }

  if (!grid) return;

  grid.innerHTML = r.data.map((row, idx) => {
    const avg  = parseFloat(row.avg_pct||0);
    const best = parseFloat(row.best_pct||0);
    const cl   = scoreColor(avg);
    const pct  = Math.min(avg, 100);

    const r56 = 56, cx = 28, cy = 28, rad = 22;
    const circ = 2 * Math.PI * rad;
    const dash = (pct / 100) * circ;
    const clrMap = { red: 'var(--clr-red)', orange: 'var(--clr-orange)', green: 'var(--clr-green)' };
    const ringClr = clrMap[cl];

    return `
      <div class="field-card" style="animation-delay:${idx*0.07}s">
        <div class="field-card-top">
          <div class="score-ring">
            <svg width="56" height="56" viewBox="0 0 56 56">
              <circle cx="${cx}" cy="${cy}" r="${rad}" fill="none" stroke="var(--green-pale)" stroke-width="6"/>
              <circle cx="${cx}" cy="${cy}" r="${rad}" fill="none" stroke="${ringClr}" stroke-width="6"
                stroke-dasharray="${dash.toFixed(2)} ${(circ-dash).toFixed(2)}" stroke-linecap="round"
                class="ring-arc" data-dash="${dash.toFixed(2)}" data-total="${circ.toFixed(2)}" style="stroke-dasharray:0 ${circ.toFixed(2)}"/>
            </svg>
            <span class="score-ring-val ${cl}">${avg.toFixed(0)}%</span>
          </div>
          <div class="field-card-info">
            <div class="field-card-name">${row.field}</div>
            <div class="field-card-meta">${row.attempts} attempt${row.attempts!=1?'s':''} · Last: ${new Date(row.last_taken).toLocaleDateString('en-PH',{month:'short',day:'numeric'})}</div>
            <div class="status-badge ${cl}">${scoreLabel(avg)}</div>
          </div>
        </div>

        <div class="prog-label"><span>Average Score</span><span>${avg.toFixed(1)}%</span></div>
        <div class="prog-track"><div class="prog-fill ${cl}" style="width:0%" data-pct="${pct}"></div></div>

        <div class="field-mini-stats">
          <div class="field-mini-stat"><strong>${avg.toFixed(1)}%</strong>Average</div>
          <div class="field-mini-stat"><strong>${best.toFixed(1)}%</strong>Best</div>
          <div class="field-mini-stat"><strong>${row.attempts}</strong>Attempts</div>
          <div class="field-mini-stat"><strong>${row.total_correct}/${row.total_questions}</strong>Correct</div>
        </div>

        <div class="advice-box ${cl}">
          <i class="fas fa-${cl==='red'?'exclamation-triangle':cl==='orange'?'lightbulb':'check-circle'}"></i>
          <span>${scoreAdvice(avg, row.field)}</span>
        </div>
      </div>`;
  }).join('');

  setTimeout(() => {
    document.querySelectorAll('.prog-fill').forEach(el => el.style.width = el.dataset.pct + '%');
    document.querySelectorAll('.ring-arc').forEach(arc => {
      const d = arc.dataset.dash, t = arc.dataset.total;
      arc.style.transition = 'stroke-dasharray 1.1s cubic-bezier(.4,0,.2,1)';
      arc.style.strokeDasharray = `${d} ${t - d}`;
    });
  }, 100);
}

// ── LOAD HISTORY ────────
async function loadHistory(highlightNew = false){
  const container = document.getElementById('historyContainer');
  const r = await apiFetch('student_recent', {email: STUDENT_EMAIL, limit: 20});

  if(!r.success || !r.data?.length){
    if (container) container.innerHTML = `<div class="empty-state"><i class="fas fa-history"></i><p>No quiz history yet.</p></div>`;
    return;
  }

  const newIds = new Set();
  if(highlightNew && latestAttemptId > 0){
    r.data.forEach(row => { if(parseInt(row.id) > latestAttemptId) newIds.add(row.id); });
  }
  if(r.data.length > 0){
    latestAttemptId = Math.max(...r.data.map(row => parseInt(row.id)||0));
  }

  if (!container) return;

  container.innerHTML = `
    <div style="overflow-x:auto">
    <table class="history-table">
      <thead>
        <tr>
          <th>Field</th>
          <th>Quiz</th>
          <th>Date</th>
          <th>Score</th>
          <th>Result</th>
        </tr>
      </thead>
      <tbody>
        ${r.data.map(row => {
          const pct = Math.round(row.score / row.total * 100);
          const cl  = scoreColor(pct);
          const dt  = new Date(row.taken_at);
          const dateStr = dt.toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'});
          const timeStr = dt.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'});
          const isNew = newIds.has(row.id);
          return `<tr${isNew?' class="new-row"':''}>
            <td><div class="table-field">${row.field}</div></td>
            <td>${row.quiz_title||'Quiz'}</td>
            <td><div class="table-time">${dateStr}<br>${timeStr}</div></td>
            <td>${row.score}/${row.total}</td>
            <td><span class="score-pill ${cl}"><i class="fas fa-${cl==='green'?'check':'times'}"></i> ${pct}%</span></td>
          </tr>`;
        }).join('')}
      </tbody>
    </table>
    </div>`;
}

// ── LISTEN FOR PASSING RATE CHANGE ──
let lastKnownPR = localStorage.getItem('agriquiz_passing_rate') || '75';

window.addEventListener('storage', async function(e){
  if(e.key === 'agriquiz_passing_rate' && e.newValue !== lastKnownPR){
    lastKnownPR = e.newValue;
    passingRate = parseInt(e.newValue) || 75;
    // Re-render banner with the updated threshold
    renderAleBanner(currentOverallAvg, passingRate);
    showLiveToast(`📋 Passing rate updated to ${passingRate}% by admin`);
  }
  // Also handle quiz completion signal
  if(e.key === 'agriquiz_analytics_refresh'){
    setTimeout(() => {
      refreshAll(false);
      showLiveToast('📊 New quiz result detected — data updated!');
    }, 1200);
  }
});

// Also check on focus (handles same-tab navigation)
window.addEventListener('focus', async function(){
  const storedPR = localStorage.getItem('agriquiz_passing_rate') || '75';
  if(storedPR !== lastKnownPR){
    lastKnownPR = storedPR;
    passingRate = parseInt(storedPR) || 75;
    renderAleBanner(currentOverallAvg, passingRate);
    showLiveToast(`📋 Passing rate updated to ${passingRate}% by admin`);
  }
});

// ── BOOT ─────────
(async function init(){
  lastKnownPR = localStorage.getItem('agriquiz_passing_rate') || '75';
  await refreshAll(false);
})();

//logout
function showLogoutModal() {
    document.getElementById('logoutModal').classList.add('active');
}
function hideLogoutModal() {
    document.getElementById('logoutModal').classList.remove('active');
}
function confirmLogout() {
    window.location.href = 'logout.php';
}
// Close modal kapag click sa labas ng card
const logoutModalEl = document.getElementById('logoutModal');
if (logoutModalEl) {
  logoutModalEl.addEventListener('click', function(e) {
      if (e.target === this) hideLogoutModal();
  });
}

</script>
</body>
</html>