<?php
session_start();
require 'db_conn.php';
$logoUrl = "asset/agriquiz-logo.png";

// ── Auth guard ──
if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['id'];

// ── Fetch user row ──
$stmt = mysqli_prepare($conn, "SELECT id, username, email, role, profile_pic, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// ── Per-role stats--
$totalQuizzes   = 0;
$totalReviewers = 0;
$totalStudents  = 0;
$quizzesTaken   = 0;
$avgScore       = 0;

if ($user['role'] === 'admin') {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM quizzes"));
    $totalQuizzes = (int)($r['c'] ?? 0);

    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM reviewers"));
    $totalReviewers = (int)($r['c'] ?? 0);

    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='user'"));
    $totalStudents = (int)($r['c'] ?? 0);
} else {
    if (mysqli_query($conn, "SHOW TABLES LIKE 'quiz_results'") &&
        mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'quiz_results'")) > 0) {

        $r = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT COUNT(*) AS c, ROUND(AVG(score_pct),1) AS avg
             FROM quiz_results WHERE user_id = $userId"
        ));
        $quizzesTaken = (int)($r['c'] ?? 0);
        $avgScore     = (float)($r['avg'] ?? 0);
    }
}

mysqli_close($conn);

$isAdmin   = $user['role'] === 'admin';
$initials  = strtoupper(substr($user['username'] ?? 'U', 0, 2));
$joinDate  = date('F j, Y', strtotime($user['created_at']));
$roleLabel = $isAdmin ? 'Administrator' : 'Student';
$dashLink  = $isAdmin ? 'admin_dashboard.php' : 'student_dashboard.php';

// ── Profile picture ──
$hasPic  = !empty($user['profile_pic']) && file_exists(__DIR__ . '/' . $user['profile_pic']);
$picUrl  = $hasPic ? htmlspecialchars($user['profile_pic']) . '?v=' . time() : '';

// ── Flash message from update ───────────────────────────────────
$flash = $_SESSION['profile_flash'] ?? '';
unset($_SESSION['profile_flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile — AGRIQUIZ</title>
  <link rel="icon" href="<?= $logoUrl ?>">
  <link rel="stylesheet" href="asset/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --green-primary: #198754;
      --green-dark:    #146c43;
      --green-light:   #82ceab;
      --green-pale:    #d1f0e0;
      --green-ultra:   #f0faf5;
      --bg-body:       #f4f9f6;
      --bg-card:       #ffffff;
      --bg-sidebar:    #198754;
      --bg-topbar:     #82ceab;
      --text-dark:     #1a2e22;
      --text-mid:      #3a5c47;
      --text-muted:    #6b9980;
      --border-light:  #c8e8d6;
      --shadow-sm:     0 1px 4px rgba(25,135,84,.10);
      --shadow-md:     0 4px 16px rgba(25,135,84,.13);
      --sidebar-width: 230px;
      --topbar-height: 60px;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--bg-body);
      color: var(--text-dark);
      overflow-x: hidden;
    }

    /* ── Sidebar ── */
    .sidebar {
      position: fixed; top: 0; left: 0;
      width: var(--sidebar-width); height: 100vh;
      background: var(--bg-sidebar);
      display: flex; flex-direction: column;
      z-index: 1100;
      box-shadow: 2px 0 12px rgba(25,135,84,.18);
      transition: transform .25s ease;
    }
    .sidebar-brand {
      padding: 20px 20px 16px;
      border-bottom: 1px solid rgba(255,255,255,.15);
      display: flex; align-items: center; gap: 10px;
    }
    .brand-icon {
      width: 50px; height: 50px; background: transparent;
      border-radius: 8px; display: grid; place-items: center;
      font-size: 16px; color: #fff; flex-shrink: 0;
      overflow: hidden;
    }
    .brand-icon img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; display: block; }
    .sidebar-brand h4 { font-size: 17px; font-weight: 800; color: #fff; letter-spacing: -.3px; margin: 0; }
    .sidebar nav { padding: 12px 0; flex: 1; }
    .sidebar nav a {
      color: rgba(255,255,255,.82); padding: 13px 20px;
      display: flex; align-items: center; gap: 11px;
      text-decoration: none; font-size: 14px; font-weight: 500;
      border-left: 3px solid transparent; transition: all .18s;
    }
    .sidebar nav a i { width: 16px; text-align: center; font-size: 14px; opacity: .85; }
    .sidebar nav a:hover,
    .sidebar nav a.active { background: var(--green-dark); color: #fff; border-left-color: var(--green-light); }

    /* ── Topbar ── */
    .topbar {
      position: fixed; top: 0;
      left: var(--sidebar-width); right: 0;
      height: var(--topbar-height);
      background: var(--bg-topbar);
      border-bottom: 1px solid rgba(25,135,84,.2);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 24px; z-index: 1000;
      box-shadow: var(--shadow-sm); transition: left .25s ease;
    }
    .topbar-title {
      font-size: 15px; font-weight: 700; color: var(--text-dark);
      display: flex; align-items: center; gap: 8px;
    }
    .topbar-title i { color: var(--green-primary); }
    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .btn-back {
      background: rgba(255,255,255,.35); border: 1px solid rgba(255,255,255,.5);
      color: var(--text-dark); font-family: inherit; font-size: 13px; font-weight: 600;
      padding: 7px 16px; border-radius: 7px; cursor: pointer;
      display: flex; align-items: center; gap: 6px; text-decoration: none; transition: .2s;
    }
    .btn-back:hover { background: rgba(255,255,255,.55); color: var(--text-dark); text-decoration: none; }
    .btn-logout {
      background: #dc3545; border: none; color: #fff;
      font-family: inherit; font-size: 13px; font-weight: 600;
      padding: 7px 18px; border-radius: 7px; cursor: pointer;
      display: flex; align-items: center; gap: 6px;
      transition: .2s; text-decoration: none;
    }
    .btn-logout:hover { background: #b02a37; color: #fff; text-decoration: none; }
    .sidebar-toggle {
      display: none; background: rgba(255,255,255,.25);
      border: 1px solid rgba(255,255,255,.4); color: var(--text-dark);
      font-size: 16px; padding: 6px 11px; border-radius: 7px;
      cursor: pointer; transition: .2s; margin-right: 8px;
    }

    /* ── Content ── */
    .content {
      margin-left: var(--sidebar-width);
      margin-top: var(--topbar-height);
      padding: 30px 28px;
      min-height: calc(100vh - var(--topbar-height));
    }
    .sidebar-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,.35); z-index: 1050;
    }
    .sidebar-overlay.active { display: block; }

    /* ── Flash banner ── */
    .flash {
      padding: 13px 18px; border-radius: 10px; font-size: 14px;
      font-weight: 600; margin-bottom: 20px;
      display: flex; align-items: center; gap: 10px;
      animation: fadeIn .35s ease both;
    }
    .flash.success { background: var(--green-pale); color: var(--green-dark); border: 1px solid var(--green-light); }
    .flash.error   { background: #fde8ec; color: #7a1020; border: 1px solid #f5c2cb; }

    /* ── Profile hero card ── */
    .profile-hero {
      background: linear-gradient(135deg, var(--green-dark) 0%, var(--green-primary) 60%, #22c55e 100%);
      border-radius: 16px; padding: 32px 28px;
      display: flex; align-items: center; gap: 24px;
      position: relative; overflow: hidden;
      margin-bottom: 22px; box-shadow: var(--shadow-md);
      animation: fadeIn .4s ease both;
    }
    .profile-hero::before {
      content: ''; position: absolute; right: -40px; top: -50px;
      width: 220px; height: 220px; border-radius: 50%;
      background: rgba(255,255,255,.06);
    }
    .profile-hero::after {
      content: ''; position: absolute; right: 100px; bottom: -60px;
      width: 140px; height: 140px; border-radius: 50%;
      background: rgba(255,255,255,.04);
    }

    /* ── Avatar (now editable) ── */
    .avatar-wrap {
      position: relative; z-index: 1; flex-shrink: 0;
      width: 90px; height: 90px;
    }
    .avatar-ring {
      width: 90px; height: 90px; border-radius: 50%;
      background: rgba(255,255,255,.2);
      border: 3px solid rgba(255,255,255,.5);
      display: grid; place-items: center;
      font-size: 32px; font-weight: 800; color: #fff;
      letter-spacing: -1px; text-transform: uppercase;
      overflow: hidden;
    }
    .avatar-ring img {
      width: 100%; height: 100%;
      object-fit: cover; border-radius: 50%;
      display: block;
    }
    .avatar-edit-btn {
      position: absolute; bottom: -2px; right: -2px;
      width: 30px; height: 30px; border-radius: 50%;
      background: #fff; color: var(--green-primary);
      border: 2px solid var(--green-dark);
      display: grid; place-items: center;
      font-size: 12px; cursor: pointer;
      box-shadow: 0 2px 6px rgba(0,0,0,.25);
      transition: transform .15s ease;
    }
    .avatar-edit-btn:hover { transform: scale(1.08); background: var(--green-pale); }
    .avatar-wrap.uploading .avatar-ring { opacity: .55; }
    .avatar-wrap.uploading .avatar-edit-btn i { animation: spin .8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    .hero-info { flex: 1; position: relative; z-index: 1; }
    .hero-name { font-size: 24px; font-weight: 800; color: #fff; letter-spacing: -.5px; line-height: 1.2; }
    .hero-email { font-size: 14px; color: rgba(255,255,255,.75); margin-top: 5px; display: flex; align-items: center; gap: 7px; }
    .hero-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
    .hero-badge {
      background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.3);
      color: #fff; font-size: 12px; font-weight: 600;
      padding: 5px 13px; border-radius: 99px;
      display: inline-flex; align-items: center; gap: 6px;
    }

    /* ── Card ── */
    .card {
      background: var(--bg-card); border-radius: 14px;
      padding: 24px; box-shadow: var(--shadow-sm);
      border: 1px solid var(--border-light);
      animation: fadeIn .4s ease both;
      width: 100%;
    }
    .card-title {
      font-size: 14px; font-weight: 800; color: var(--text-dark);
      margin-bottom: 18px; display: flex; align-items: center; gap: 9px;
      padding-bottom: 12px; border-bottom: 1px solid var(--border-light);
    }
    .card-title i { color: var(--green-primary); font-size: 15px; }

    /* ── Info list ── */
    .info-row {
      display: flex; align-items: flex-start; gap: 12px;
      padding: 12px 0; border-bottom: 1px solid #f0f0f0;
    }
    .info-row:last-child { border-bottom: none; padding-bottom: 0; }
    .info-icon {
      width: 34px; height: 34px; border-radius: 9px;
      background: var(--green-pale); color: var(--green-primary);
      display: grid; place-items: center; font-size: 13px; flex-shrink: 0;
    }
    .info-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; }
    .info-value { font-size: 14px; font-weight: 600; color: var(--text-dark); margin-top: 2px; word-break: break-word; }

    /* ── Role badge in info ── */
    .role-pill {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 700;
    }
    .role-pill.admin   { background: #dbeafe; color: #1d4ed8; }
    .role-pill.student { background: var(--green-pale); color: var(--green-primary); }

    /* ── Utilities ── */
    @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.open { transform: translateX(0); }
      .topbar { left: 0 !important; padding: 0 14px; }
      .content { margin-left: 0 !important; padding: 18px 16px; }
      .sidebar-toggle { display: inline-flex; align-items: center; }
      .btn-back span, .btn-logout span { display: none; }
      .btn-back, .btn-logout { padding: 7px 11px; }
      .profile-hero { flex-direction: column; text-align: center; padding: 26px 20px; }
      .hero-meta { justify-content: center; }
    }

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


<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ══ SIDEBAR ══ -->
<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><img src="<?= $logoUrl ?>" alt="AgriQuiz"></div>
    <h4>AGRI<span style="color: yellow;">QUIZ</span></h4>
  </div>
  <nav>
    <?php if ($isAdmin): ?>
      <a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a>
      <a href="admin_student_list.php"><i class="fas fa-users"></i> Student List</a>
      <a href="admin_preboard_records.php"><i class="fas fa-clipboard-check"></i> Preboard Records</a>
      <a href="review_quiz_adminside.php"><i class="fas fa-book"></i> Reviewers &amp; Quizzes</a>
      <a href="admin_analytics.php"><i class="fas fa-chart-line"></i> Performance</a>
    <?php else: ?>
      <a href="student_dashboard.php"><i class="fas fa-home"></i> Home</a>
      <a href="review_quiz.php"><i class="fas fa-book"></i> Review &amp; Quiz</a>
      <a href="student_dashboard.php"><i class="fas fa-chart-line"></i> Performance</a>
    <?php endif; ?>
    <a href="profile.php" class="active"><i class="fas fa-user-circle"></i> My Profile</a>
  </nav>
</div>

<!-- ══ TOPBAR ══ -->
<div class="topbar">
  <div class="topbar-title">
    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <i class="fas fa-user-circle"></i> My Profile
  </div>
  <div class="topbar-right">
    <a href="<?= $dashLink ?>" class="btn-back">
      <i class="fas fa-arrow-left"></i> <span>Dashboard</span>
    </a>
        <a href="#" onclick="showLogoutModal(); return false;" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span class="btn-label"> Logout</span></a>
  </div>
</div>


<!-- ══ CONTENT ══ -->
<div class="content">

  <?php if ($flash): ?>
    <?php $isErr = str_starts_with($flash, 'error:'); ?>
    <div class="flash <?= $isErr ? 'error' : 'success' ?>">
      <i class="fas <?= $isErr ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
      <?= htmlspecialchars($isErr ? substr($flash, 6) : $flash) ?>
    </div>
  <?php endif; ?>

  <!-- ── Hero ── -->
  <div class="profile-hero">

    <div class="avatar-wrap" id="avatarWrap">
      <form id="picForm" action="upload_profile_pic.php" method="POST" enctype="multipart/form-data">
        <div class="avatar-ring" id="avatarRing">
          <?php if ($hasPic): ?>
            <img src="<?= $picUrl ?>" alt="Profile picture" id="avatarImg">
          <?php else: ?>
            <span id="avatarInitials"><?= htmlspecialchars($initials) ?></span>
          <?php endif; ?>
        </div>
        <label class="avatar-edit-btn" for="picInput" title="Change profile picture">
          <i class="fas fa-camera"></i>
        </label>
        <input type="file" name="profile_pic" id="picInput" accept="image/png, image/jpeg, image/webp" hidden>
      </form>
    </div>

    <div class="hero-info">
      <div class="hero-name"><?= htmlspecialchars($user['username']) ?></div>
      <div class="hero-email fw-bold">
        <i class="fas fa-envelope" style="font-size:12px"></i>
        <?= htmlspecialchars($user['email']) ?>
      </div>
      <div class="hero-meta">
        <span class="hero-badge">
          <i class="fas <?= $isAdmin ? 'fa-shield-alt' : 'fa-graduation-cap' ?>"></i>
          <?= $roleLabel ?>
        </span>
        <span class="hero-badge">
          <i class="fas fa-calendar-alt"></i>
          Joined <?= $joinDate ?>
        </span>
        <span class="hero-badge">
          <i class="fas fa-id-badge"></i>
          ID #<?= $user['id'] ?>
        </span>
      </div>
    </div>
  </div>

  <!-- ── Account Information Card ── -->
  <div class="card">
    <div class="card-title"><i class="fas fa-info-circle"></i> Account Information</div>

    <div class="info-row">
      <div class="info-icon"><i class="fas fa-user"></i></div>
      <div>
        <div class="info-label">Username</div>
        <div class="info-value"><?= htmlspecialchars($user['username']) ?></div>
      </div>
    </div>

    <div class="info-row">
      <div class="info-icon"><i class="fas fa-envelope"></i></div>
      <div>
        <div class="info-label">Email Address</div>
        <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
      </div>
    </div>

    <div class="info-row">
      <div class="info-icon"><i class="fas fa-user-tag"></i></div>
      <div>
        <div class="info-label">Role</div>
        <div class="info-value">
          <span class="role-pill <?= $user['role'] ?>">
            <i class="fas <?= $isAdmin ? 'fa-shield-alt' : 'fa-graduation-cap' ?>"></i>
            <?= $roleLabel ?>
          </span>
        </div>
      </div>
    </div>


    <div class="info-row">
      <div class="info-icon"><i class="fas fa-fingerprint"></i></div>
      <div>
        <div class="info-label">User ID</div>
        <div class="info-value">#<?= $user['id'] ?></div>
      </div>
    </div>
  </div>

</div><!-- /content -->

<script>
/* ── Sidebar toggle ── */
const sidebarEl = document.getElementById('sidebar');
const overlayEl = document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click', () => {
  sidebarEl.classList.toggle('open');
  overlayEl.classList.toggle('active');
});
overlayEl.addEventListener('click', () => {
  sidebarEl.classList.remove('open');
  overlayEl.classList.remove('active');
});

/* ── Profile picture upload: pick file -> auto submit ── */
const picInput  = document.getElementById('picInput');
const picForm   = document.getElementById('picForm');
const avatarWrap = document.getElementById('avatarWrap');

const MAX_SIZE = 3 * 1024 * 1024; // 3MB, keep in sync with upload_profile_pic.php

picInput.addEventListener('change', () => {
  const file = picInput.files[0];
  if (!file) return;

  if (file.size > MAX_SIZE) {
    alert('Image is too large. Max size is 3MB.');
    picInput.value = '';
    return;
  }

  avatarWrap.classList.add('uploading');
  picForm.submit();
});

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
document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) hideLogoutModal();
});

</script>

</body>
</html>