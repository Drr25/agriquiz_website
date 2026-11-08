<?php
session_start();
require 'db_conn.php';
$logoUrl = "asset/agriquiz-logo.png";

$email   = $_SESSION['email'];
$initial = strtoupper(mb_substr($email, 0, 1));
//fetch profile
$profilePic = null;
$username   = '';
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) {
    $profilePic = $row['profile_pic'] ?? null;

    // Kunin ang username (iba't ibang posibleng column names)
    foreach (['username', 'user_name', 'uname'] as $col) {
        if (!empty($row[$col])) {
            $username = trim($row[$col]);
            break;
        }
    }
}
mysqli_stmt_close($stmt);
mysqli_close($conn);

// Fallback kung walang username sa DB: gamitin ang bahagi ng email bago ang "@"
if ($username === '') {
    $username = explode('@', $email)[0];
}

$hasPic = !empty($profilePic) && file_exists(__DIR__ . '/' . $profilePic);
$picUrl = $hasPic ? htmlspecialchars($profilePic) . '?v=' . time() : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – AGRIQUIZ</title>
  <link rel="icon" href="<?= $logoUrl ?>">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
  <link rel="stylesheet" href="css/student_dashboard.css">
  <style>
    .sidebar-brand .brand-icon { background: transparent !important; overflow: hidden; }
    .sidebar-brand .brand-icon img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; display: block; }

    /* logout modal */
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
    .logout-overlay.active { display: flex; }
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
    .btn-cancel { background: #f1f1f1; color: #333; }
    .btn-cancel:hover { opacity: .8; }
    .btn-confirm { background: #e53e3e; color: #fff; }
    .btn-confirm:hover { opacity: .85; }
  </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
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

<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><img src="<?= $logoUrl ?>" alt="AgriQuiz logo"></div>
    <h4>AGRI<span style="color: yellow;">QUIZ</span></h4>
  </div>
  <nav>
    <a href="student_dashboard.php" class="active"><i class="fas fa-home"></i> Home</a>
    <a href="review_quiz.php"><i class="fas fa-book"></i> Review &amp; Quiz</a>
    <a href="student_analytics.php"><i class="fas fa-chart-line"></i> Performance</a>
  </nav>
</div>

<div class="topbar">
  <div class="topbar-left">
    <button class="btn-hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    <div class="topbar-title"><i class="fas fa-home"></i> Dashboard</div>
  </div>
  <div class="topbar-right">
    <a href="profile.php" class="btn-profile"><i class="fas fa-user-circle"></i><span class="btn-label"> Profile</span></a>
    <a href="#" onclick="showLogoutModal(); return false;" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span class="btn-label"> Logout</span></a>
  </div>
</div>

<div class="content">

  <!-- HERO -->
  <div class="hero-banner">
    <div class="hero-left">
      <h1 id="greetingText">Good day! 👋</h1>
      <p><?= htmlspecialchars($username) ?></p>
      <div class="hero-quick-btns">
        <a href="review_quiz.php" class="hero-btn"><i class="fas fa-book-open"></i> Start Reviewing</a>
        <a href="student_analytics.php" class="hero-btn"><i class="fas fa-chart-bar"></i> My Performance</a>
      </div>
    </div>
    <div class="hero-avatar">
      <?php if ($hasPic): ?>
        <img src="<?= $picUrl ?>" alt="Profile picture">
      <?php else: ?>
        <?= htmlspecialchars($initial) ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- STAT CARDS -->
  <div class="stats-row" id="statsRow">
    <div class="stat-card">
      <div class="stat-card-top"><div class="stat-icon green"><i class="fas fa-pen"></i></div></div>
      <div class="stat-value" id="s-attempts"><span class="spinner-sm"></span></div>
      <div class="stat-label">Total Quizzes Taken</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-top"><div class="stat-icon blue"><i class="fas fa-chart-line"></i></div></div>
      <div class="stat-value" id="s-avg"><span class="spinner-sm"></span></div>
      <div class="stat-label">Overall Average</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-top"><div class="stat-icon orange"><i class="fas fa-trophy"></i></div></div>
      <div class="stat-value" id="s-best"><span class="spinner-sm"></span></div>
      <div class="stat-label">Best Score</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-top"><div class="stat-icon purple"><i class="fas fa-layer-group"></i></div></div>
      <div class="stat-value" id="s-fields"><span class="spinner-sm"></span></div>
      <div class="stat-label">Fields Attempted</div>
    </div>
  </div>

  <!-- SECTION GRID -->
  <div class="section-grid">

    <!-- FIELD PERFORMANCE -->
    <div class="page-box">
      <div class="section-title">Performance by Field</div>
      <div id="fieldPerfContainer">
        <div class="spinner-wrap"><div class="spinner"></div></div>
      </div>
      <a href="student_analytics.php" class="view-all-link"><i class="fas fa-chart-bar"></i> View Full Analytics</a>
    </div>

    <!-- RECENT ACTIVITY -->
    <div class="page-box">
      <div class="section-title">Recent Activity</div>
      <div id="recentContainer">
        <div class="spinner-wrap"><div class="spinner"></div></div>
      </div>
      <a href="student_analytics.php" class="view-all-link"><i class="fas fa-history"></i> View All Attempts</a>
    </div>

  </div>
</div>

<script>
const ANALYTICS_API   = 'api/analytics_api.php';
const STUDENT_EMAIL   = <?= json_encode($email) ?>;

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('show');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
}

// ── Greeting — "Good day" para sa lahat ──────────────────────────────────────
document.getElementById('greetingText').textContent = 'Good day! 👋';

// ── Helpers ──────────────────────────────────────────────────────────────────
function scoreColor(pct) {
  return pct < 50 ? 'red' : pct < 80 ? 'orange' : 'green';
}

// ── FIX 2: scoreColorVar — was inline ternary mess ──────────────────────────
function scoreColorVar(pct) {
  const cl = scoreColor(pct);
  return cl === 'red' ? 'var(--red)' : cl === 'orange' ? 'var(--orange)' : 'var(--green-score)';
}

async function apiFetch(action, params = {}) {
  const qs  = new URLSearchParams({ action, ...params }).toString();
  const res = await fetch(`${ANALYTICS_API}?${qs}`);
  if (!res.ok) throw new Error(`API error ${res.status}`);
  return res.json();
}

// ── Load summary stat cards ──────────────────────────────────────────────────
async function loadSummary() {
  try {
    const r = await apiFetch('student_summary', { email: STUDENT_EMAIL });
    if (r.success && r.data) {
      const d   = r.data;
      const avg  = parseFloat(d.overall_avg || 0).toFixed(1);
      const best = parseFloat(d.best_score  || 0).toFixed(1);

      document.getElementById('s-attempts').textContent = d.total_attempts || 0;
      document.getElementById('s-avg').innerHTML  = `<span style="color:${scoreColorVar(avg)}">${avg}%</span>`;
      document.getElementById('s-best').innerHTML = `<span style="color:${scoreColorVar(best)}">${best}%</span>`;
      document.getElementById('s-fields').textContent = d.fields_attempted || 0;
    } else {
      ['s-attempts', 's-avg', 's-best', 's-fields']
        .forEach(id => document.getElementById(id).textContent = '—');
    }
  } catch (e) {
    console.error('loadSummary:', e);
    ['s-attempts', 's-avg', 's-best', 's-fields']
      .forEach(id => document.getElementById(id).textContent = '—');
  }
}

// ── Load field performance bars ──────────────────────────────────────────────
async function loadFieldPerf() {
  const container = document.getElementById('fieldPerfContainer');
  try {
    const r = await apiFetch('student_field_performance', { email: STUDENT_EMAIL });
    if (!r.success || !r.data?.length) {
      container.innerHTML = `
        <div class="empty-sm">
          <i class="fas fa-seedling"></i>
          No quiz attempts yet.<br>Take a quiz to see your performance!
        </div>`;
      return;
    }
    container.innerHTML = r.data.map(row => {
      const pct        = parseFloat(row.avg_pct || 0);
      const cl         = scoreColor(pct);
      const shortField = row.field
        .replace('Agricultural ', 'Agri. ')
        .replace(' and ', ' & ');
      return `
        <div class="field-row">
          <div class="field-row-top">
            <span class="field-name-sm">${shortField}</span>
            <span class="field-pct-label ${cl}">${pct.toFixed(1)}%</span>
          </div>
          <div class="prog-track">
            <div class="prog-fill ${cl}" style="width:0%" data-pct="${Math.min(pct, 100)}"></div>
          </div>
        </div>`;
    }).join('');

    // Animate bars after paint
    setTimeout(() => {
      document.querySelectorAll('.prog-fill')
        .forEach(el => el.style.width = el.dataset.pct + '%');
    }, 80);

  } catch (e) {
    console.error('loadFieldPerf:', e);
    container.innerHTML = `<div class="empty-sm"><i class="fas fa-exclamation-circle"></i>Could not load field data.</div>`;
  }
}

// ── Load recent attempts ─────────────────────────────────────────────────────
async function loadRecent() {
  const container = document.getElementById('recentContainer');
  try {
    const r = await apiFetch('student_recent', { email: STUDENT_EMAIL, limit: 6 });
    if (!r.success || !r.data?.length) {
      container.innerHTML = `
        <div class="empty-sm">
          <i class="fas fa-history"></i>
          No attempts yet.<br>Complete a quiz to see your history.
        </div>`;
      return;
    }
    container.innerHTML = r.data.map(row => {
      // ── FIX 3: guard against division-by-zero ──────────────────────────
      const total  = parseInt(row.total) || 1;
      const score  = parseInt(row.score) || 0;
      const pct    = Math.round(score / total * 100);
      const cl     = scoreColor(pct);

      const dt      = new Date(row.taken_at);
      const timeStr = dt.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
                    + ' '
                    + dt.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });

      const shortField = (row.field || '')
        .replace('Agricultural ', 'Agri. ')
        .replace(' and ', ' & ');

      return `
        <div class="attempt-item">
          <div class="attempt-icon ${cl}"><i class="fas fa-pen"></i></div>
          <div class="attempt-info">
            <div class="attempt-field">${shortField}</div>
            <div class="attempt-quiz">${row.quiz_title || 'Quiz'}</div>
            <div class="attempt-time">${timeStr}</div>
          </div>
          <div class="attempt-score ${cl}">${score}/${total}</div>
        </div>`;
    }).join('');

  } catch (e) {
    console.error('loadRecent:', e);
    container.innerHTML = `<div class="empty-sm"><i class="fas fa-exclamation-circle"></i>Could not load recent activity.</div>`;
  }
}

// ── Boot all three in parallel ────────
Promise.all([loadSummary(), loadFieldPerf(), loadRecent()]);

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
document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) hideLogoutModal();
});
</script>
</body>
</html>