<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require 'db_conn.php';

$totalStudents  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'user'"))['total'] ?? 0;
$totalQuizzes   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM quizzes"))['total'] ?? 0;
$totalReviewers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM reviewers"))['total'] ?? 0;
$recentStudents = mysqli_query($conn, "SELECT username, email, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");
$recentQuizzes  = mysqli_query($conn, "SELECT title, field, updated_at FROM quizzes ORDER BY updated_at DESC LIMIT 5");
mysqli_close($conn);

$logoUrl = "asset/agriquiz-logo.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGRIQUIZ — Dashboard</title>
    <link rel="icon" href="<?= $logoUrl ?>">
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_dashboard.css">
</head>
    <style>
    .sidebar-brand .brand-icon { background: transparent !important; overflow: hidden; }
    .sidebar-brand .brand-icon img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; display: block; }
        /* ── Fix sidebar nav spacing to match other admin pages ── */
    .sidebar-brand {
        padding: 20px 20px 16px !important;
        margin-bottom: 0 !important;
        border-bottom: 1px solid rgba(255,255,255,.15);
    }
    .sidebar nav {
        padding: 12px 0 !important;
        margin-top: 0 !important;
    }
    .sidebar nav a {
        padding: 13px 20px !important;
    }
    </style>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><img src="<?= $logoUrl ?>" alt="AgriQuiz"></div>
         <h4>AGRI<span style="color: yellow;">QUIZ</span></h4>
    </div>
    <nav>
        <a href="admin_dashboard.php" class="active"><i class="fas fa-home"></i> Home</a>
        <a href="admin_student_list.php"><i class="fas fa-users"></i> Student List</a>
        <a href="review_quiz_adminside.php"><i class="fas fa-book"></i> Reviewers &amp; Quizzes</a>
        <a href="admin_preboard_records.php"><i class="fas fa-clipboard-check"></i> Preboard Records</a>
        <a href="admin_analytics.php"><i class="fas fa-chart-line"></i> Performance</a>
    </nav>
</div>

<div class="topbar">
    <div class="topbar-left">
        <button class="btn-hamburger" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-title"><i class="fas fa-home"></i> Dashboard</div>
    </div>
    <div class="topbar-right">
        <a href="profile.php" class="btn-profile"><i class="fas fa-user-circle"></i><span class="btn-label"> Profile</span></a>
       
        <a href="logout.php" onclick="showLogoutModal(); return false;" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span class="btn-label"> Logout</span></a>
    </div>
</div>

<div class="content">
    <div class="welcome-banner">
        <div class="welcome-text">
            <h3>Welcome back, Admin! 👋</h3>
            <p>Here's what's happening in AgriQuiz today.</p>
        </div>
        <div class="welcome-icon"><i class="fas fa-seedling"></i></div>
    </div>

    <div style="margin-bottom:18px">
        <h2 style="font-size:17px;font-weight:800;color:var(--text-dark);letter-spacing:-.3px">Overview</h2>
        <p style="font-size:13px;color:var(--text-muted);margin-top:3px">A quick summary of your platform stats and recent activity.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
            <div>
                <div class="stat-value" data-target="<?= (int)$totalStudents ?>">0</div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="stat-card gold">
            <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
            <div>
                <div class="stat-value" data-target="<?= (int)$totalQuizzes ?>">0</div>
                <div class="stat-label">Total Quizzes</div>
            </div>
        </div>
        <div class="stat-card sky">
            <div class="stat-icon"><i class="fas fa-book-open"></i></div>
            <div>
                <div class="stat-value" data-target="<?= (int)$totalReviewers ?>">0</div>
                <div class="stat-label">Total Reviewers</div>
            </div>
        </div>
    </div>

    <div class="bottom-grid">
        <div class="table-card">
            <div class="table-card-header">
                <span class="title"><i class="fas fa-users"></i> Recent Students</span>
                <a href="admin_student_list.php" class="view-all">View All →</a>
            </div>
            <table class="mini-table">
                <thead><tr><th>Student</th><th>Enrolled</th></tr></thead>
                <tbody>
                    <?php if ($recentStudents && mysqli_num_rows($recentStudents) > 0): ?>
                        <?php while ($s = mysqli_fetch_assoc($recentStudents)): ?>
                        <tr>
                            <td>
                                <div class="student-cell">
                                    <div class="avatar-sm"><?= strtoupper(substr($s['username'] ?? 'U', 0, 1)) ?></div>
                                    <div>
                                        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($s['username'] ?? '') ?></div>
                                        <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($s['email'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="color:var(--text-muted);font-size:12px"><?= date('M j, Y', strtotime($s['created_at'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="empty-row"><td colspan="2"><i class="fas fa-users-slash" style="margin-right:6px"></i>No students yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-card">
            <div class="table-card-header">
                <span class="title"><i class="fas fa-clipboard-list"></i> Recent Quizzes</span>
                <a href="review_quiz_adminside.php" class="view-all">View All →</a>
            </div>
            <table class="mini-table">
                <thead><tr><th>Title</th><th>Field</th></tr></thead>
                <tbody>
                    <?php if ($recentQuizzes && mysqli_num_rows($recentQuizzes) > 0): ?>
                        <?php while ($q = mysqli_fetch_assoc($recentQuizzes)): ?>
                        <tr>
                            <td style="font-weight:600"><?= htmlspecialchars($q['title'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($q['field'])): ?>
                                    <span class="badge-field"><?= htmlspecialchars($q['field']) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:12px">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="empty-row"><td colspan="2"><i class="fas fa-file-circle-xmark" style="margin-right:6px"></i>No quizzes yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


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


<script src="asset/js/bootstrap.min.js"></script>
<script>
// ── Sidebar toggle ──
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const toggle  = document.getElementById('sidebarToggle');

function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('active'); }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); }
function toggleSidebar(){ sidebar.classList.contains('open') ? closeSidebar() : openSidebar(); }

toggle.addEventListener('click', toggleSidebar);
overlay.addEventListener('click', closeSidebar);

// Close sidebar when a nav link is clicked on mobile
sidebar.querySelectorAll('nav a').forEach(link => {
    link.addEventListener('click', () => { if (window.innerWidth < 768) closeSidebar(); });
});

// ── Count-up animation ──
document.querySelectorAll('.stat-value[data-target]').forEach(el => {
    const target = parseInt(el.dataset.target, 10);
    if (target === 0) { el.textContent = '0'; return; }
    let current = 0;
    const step = Math.ceil(target / 40);
    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current.toLocaleString();
        if (current >= target) clearInterval(timer);
    }, Math.max(16, Math.ceil(900 / target)));
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