<?php
session_start();
require "db_conn.php";

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

$logoUrl = "asset/agriquiz-logo.png";
$add_success = '';
$add_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $username = trim($_POST['new_username']);
    $email    = trim($_POST['new_email']);
    $password = $_POST['new_password'];
    if (empty($username) || empty($email) || empty($password)) {
        $add_error = "All fields are required.";
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@wvsu\.edu\.ph$/i', $email)) {
        $add_error = "Only @wvsu.edu.ph email addresses are allowed.";
    } else {
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        $check_res = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_res) > 0) {
            $add_error = "A user with that email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';
            $ins_sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            $ins_stmt = mysqli_prepare($conn, $ins_sql);
            mysqli_stmt_bind_param($ins_stmt, "ssss", $username, $email, $hashed, $role);

            if (mysqli_stmt_execute($ins_stmt)) {
                $add_success = "Student account created successfully!";
            } else {
                $add_error = "Database error: " . htmlspecialchars(mysqli_error($conn));
            }
            mysqli_stmt_close($ins_stmt);
        }
        mysqli_stmt_close($check_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List — AGRIQUIZ</title>
    <link rel="icon" href="<?= $logoUrl ?>">
    <link rel="stylesheet" href="asset/css/bootstrap.min.css"
          onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css';">
    <link rel="stylesheet" href="css/admin_student_list.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
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

<div class="app-overlay" id="logoutModal">
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
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><img src="<?= $logoUrl ?>" alt="AgriQuiz"></div>
        <h4>AGRI<span style="color: yellow;">QUIZ</span></h4>
    </div>
    <nav>
        <a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a>
        <a href="admin_student_list.php" class="active"><i class="fas fa-users"></i> Student List</a>
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
        <div class="topbar-title"><i class="fas fa-home"></i> Student List</div>
    </div>
    <div class="topbar-right">
        <a href="profile.php" class="btn-profile"><i class="fas fa-user-circle"></i><span class="btn-label"> Profile</span></a>
       
        <a href="#" onclick="showLogoutModal(); return false;" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span class="btn-label"> Logout</span></a>
    </div>
</div>


<div class="content">
    <div class="page-header">
        <h2><i class="fas fa-users" style="margin-right:8px"></i>Students List</h2>
        <p>Manage and add new students to the AgriQuiz platform.</p>
    </div>

    <?php if ($add_success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($add_success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($add_error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($add_error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-header-left">
                <i class="fas fa-table"></i>
                <span>All Students</span>
                <?php
                $count_sql = "SELECT COUNT(*) AS total FROM users WHERE role='user'";
                $count_res = mysqli_query($conn, $count_sql);
                $count_row = mysqli_fetch_assoc($count_res);
                $total = (int)($count_row['total'] ?? 0);
                ?>
                <span class="record-count" id="recordCount"><?= $total ?> record<?= $total !== 1 ? "s" : "" ?></span>
            </div>
            <div class="table-card-header-right">
                <button class="btn-add-student" id="openAddModal">
                    <i class="fas fa-user-plus"></i><span class="btn-label"> Add Student</span>
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <?php
            $sql = "SELECT * FROM users WHERE role='user'";
            if ($result = mysqli_query($conn, $sql)) {
                if (mysqli_num_rows($result) > 0) {
            ?>
            <table class="students-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Email Address</th>
                        <th>Password</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td>
                            <div class="student-cell">
                                <div class="avatar"><?= strtoupper(substr($row['username'] ?? 'U', 0, 1)) ?></div>
                                <span><?= htmlspecialchars($row['username'] ?? '') ?></span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                        <td><span style="color:var(--text-muted);font-size:12px">••••••••</span></td>
                        <td>
                            <div class="action-buttons">
                                <a href="admin_edit.php?id=<?= (int)$row['id'] ?>" class="btn-icon-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="admin_delete.php?id=<?= (int)$row['id'] ?>" class="btn-icon-delete" onclick="return confirm('Are you sure?')" title="Delete"><i class="fas fa-trash-alt"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php
                    mysqli_free_result($result);
                } else {
                    echo '<div class="empty-state"><i class="fas fa-users-slash"></i><p>No student records found.</p></div>';
                }
            } else {
                echo '<div class="error-message">Oops! ' . htmlspecialchars(mysqli_error($conn)) . '</div>';
            }
            mysqli_close($conn);
            ?>
        </div>
    </div>
</div>

<!-- ADD STUDENT MODAL — self-contained overlay, no Bootstrap dependency -->
<div class="app-overlay" id="addStudentModal">
    <div class="form-card">
        <div class="form-card-header">
            <h5><i class="fas fa-user-plus me-2"></i>Add New Student</h5>
            <button type="button" onclick="closeAddModal()" aria-label="Close">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="add_student" value="1">

            <?php if ($add_error): ?>
            <div class="alert-sm alert-danger">
                <i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($add_error) ?>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label" for="new_username"><i class="fas fa-user me-1" style="color:var(--green-primary)"></i>Username</label>
                <input type="text" class="form-control" id="new_username" name="new_username" placeholder="e.g. juan_dela_cruz" value="<?= htmlspecialchars($_POST['new_username'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="new_email"><i class="fas fa-envelope me-1" style="color:var(--green-primary)"></i>Email Address</label>
                <input type="email" class="form-control" id="new_email" name="new_email"
                    placeholder="e.g. juan.delacruz@wvsu.edu.ph"
                    value="<?= htmlspecialchars($_POST['new_email'] ?? '') ?>"
                    pattern="^[a-zA-Z0-9._%+\-]+@wvsu\.edu\.ph$"
                    title="Only @wvsu.edu.ph email addresses are allowed."
                    required>
                <div class="form-text" style="font-size:12px;color:var(--text-muted);margin-top:4px">Must be a valid @wvsu.edu.ph email address.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="new_password"><i class="fas fa-lock me-1" style="color:var(--green-primary)"></i>Password</label>
                <div class="password-wrap">
                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter a strong password" required minlength="6">
                    <button type="button" class="toggle-pw" onclick="togglePassword()" title="Show/hide password">
                        <i class="fas fa-eye" id="pwIcon"></i>
                    </button>
                </div>
                <div class="form-text" style="font-size:12px;color:var(--text-muted);margin-top:4px">Minimum 6 characters.</div>
            </div>

            <div class="form-card-footer">
                <button type="button" class="btn-cancel" onclick="closeAddModal()"><i class="fas fa-times me-1"></i>Cancel</button>
                <button type="submit" class="btn-confirm" style="background:var(--green-primary,#16a34a)"><i class="fas fa-user-plus me-1"></i>Create Account</button>
            </div>
        </form>
    </div>
</div>

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
sidebar.querySelectorAll('nav a').forEach(link => {
    link.addEventListener('click', () => { if (window.innerWidth < 768) closeSidebar(); });
});

// ── Add Student Modal (vanilla JS, no Bootstrap needed) ──
const addModalEl = document.getElementById('addStudentModal');
const openBtn = document.getElementById('openAddModal');

function openAddModal()  { addModalEl.classList.add('active'); }
function closeAddModal() { addModalEl.classList.remove('active'); }

if (openBtn) openBtn.addEventListener('click', openAddModal);
addModalEl.addEventListener('click', function(e) {
    if (e.target === this) closeAddModal();
});
<?php if ($add_error): ?>openAddModal();<?php endif; ?>

function togglePassword() {
    const pw   = document.getElementById('new_password');
    const icon = document.getElementById('pwIcon');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pw.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

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