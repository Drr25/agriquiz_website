<?php
session_start();
require 'db_conn.php';

$toast = '';
$logoUrl = "asset/agriquiz-logo.png";

// Handle Bulk Add Records
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_save'])) {
    $saved   = 0;
    $date    = mysqli_real_escape_string($conn, $_POST['exam_date']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject'] ?? '');

    if (!empty($_POST['scores'])) {
        foreach ($_POST['scores'] as $student_id => $score_val) {
            $score_val = trim($score_val);
            if ($score_val === '' || !is_numeric($score_val)) continue;
            $student_id  = (int) $student_id;
            $score       = (float) $score_val;
            $total_items = isset($_POST['totals'][$student_id]) ? (int)$_POST['totals'][$student_id] : 100;
            if ($student_id && $score >= 0 && $total_items > 0 && $date) {
                $result = mysqli_query($conn, "
                    INSERT INTO preboard_manual_records (student_id, score, total_items, subject, recorded_at)
                    VALUES ($student_id, $score, $total_items, '$subject', '$date')
                ");
                if (!$result) {
                    error_log("DB Insert Error: " . mysqli_error($conn));
                } else {
                    $saved++;
                }
            }
        }
    }
    $toast = $saved > 0 ? 'added' : 'invalid';
}

// Handle Delete Record 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_record'])) {
    $del_id = (int) $_POST['record_id'];
    if ($del_id) {
        mysqli_query($conn, "DELETE FROM preboard_manual_records WHERE id = $del_id");
        $toast = 'deleted';
    }
}

// Fetch Students 
$students = mysqli_query($conn, "SELECT id, username, email FROM users WHERE role = 'user' ORDER BY username ASC");
$studentList = [];
while ($row = mysqli_fetch_assoc($students)) $studentList[] = $row;

// ── Stats ──
$statsRow = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*)                                     AS total_records,
        COUNT(DISTINCT student_id)                   AS students_recorded,
        ROUND(AVG((score / total_items) * 100), 1)  AS avg_score
    FROM preboard_manual_records
    WHERE total_items > 0
"));
$totalRecords     = $statsRow['total_records']     ?? 0;
$studentsRecorded = $statsRow['students_recorded'] ?? 0;
$avgScore         = $statsRow['avg_score']         ?? 0;

// ── All Records ──
$records = mysqli_query($conn, "
    SELECT pmr.id, u.username, u.email, pmr.subject, pmr.score, pmr.total_items, pmr.recorded_at
    FROM preboard_manual_records pmr
    JOIN users u ON u.id = pmr.student_id
    ORDER BY pmr.recorded_at DESC
");
$allRecords = [];
while ($row = mysqli_fetch_assoc($records)) $allRecords[] = $row;

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGRIQUIZ — Preboard Records</title>
    <link rel="icon" href="<?= $logoUrl ?>">
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_dashboard.css">
    <style>
        :root {
            --green-main:  #2e7d32; --green-light: #43a047;
            --green-pale:  #e8f5e9; --gold: #f9a825; --gold-pale: #fff8e1;
            --red: #c62828; --red-pale: #ffebee;
            --blue: #1565c0; --blue-pale: #e3f2fd;
            --text-dark: #1a2e1a; --text-muted: #6b7c6b;
            --border: #e0e8e0; --card-bg: #fff; --page-bg: #f4f7f4;
        }
        body { background:var(--page-bg); font-family:'Plus Jakarta Sans',sans-serif; }
        .pb-wrapper { max-width:1200px; margin:0 auto; padding:28px 20px 60px; }

        /* ── Header ── */
        .pb-header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:16px; margin-bottom:28px; }
        .pb-header h1 { font-size:22px; font-weight:800; color:var(--text-dark); margin:0 0 4px; letter-spacing:-.5px; display:flex; align-items:center; gap:10px; }
        .pb-header h1 i { color:var(--green-main); }
        .pb-header p { font-size:13px; color:var(--text-muted); margin:0; }

        /* ── Stat Pills ── */
        .stat-pills { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px; }
        .stat-pill { background:var(--card-bg); border:1.5px solid var(--border); border-radius:12px; padding:12px 20px; display:flex; align-items:center; gap:12px; min-width:150px; flex:1; }
        .spi { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0; }
        .spi.g    { background:var(--green-pale); color:var(--green-main); }
        .spi.gold { background:var(--gold-pale);  color:var(--gold); }
        .spi.sky  { background:var(--blue-pale);  color:var(--blue); }
        .sp-val { font-size:20px; font-weight:800; color:var(--text-dark); line-height:1; }
        .sp-lbl { font-size:11px; color:var(--text-muted); margin-top:2px; }

        /* ── Entry Table Card ── */
        .entry-card { background:var(--card-bg); border-radius:16px; box-shadow:0 2px 14px rgba(0,0,0,.06); margin-bottom:26px; overflow:hidden; }
        .entry-card-hdr { background:linear-gradient(135deg,var(--green-main),var(--green-light)); padding:16px 22px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; cursor:pointer; user-select:none; }
        .entry-card-hdr .hdr-left { display:flex; align-items:center; gap:10px; }
        .entry-card-hdr span { font-size:15px; font-weight:700; color:#fff; }
        .entry-card-hdr .tog { color:rgba(255,255,255,.8); font-size:13px; transition:transform .3s; }
        .entry-card-hdr.collapsed .tog { transform:rotate(-90deg); }

        .entry-top-bar { display:flex; align-items:flex-end; gap:14px; flex-wrap:wrap; padding:18px 22px 0; }
        .fg { flex:1; min-width:160px; }
        .fg label { display:block; font-size:11px; font-weight:700; color:var(--text-muted); margin-bottom:5px; text-transform:uppercase; letter-spacing:.4px; }
        .fg input[type=date], .fg input[type=number], .fg input[type=text] {
            width:100%; padding:9px 13px; border:1.5px solid var(--border); border-radius:9px;
            font-size:13px; font-family:'Plus Jakarta Sans',sans-serif; color:var(--text-dark);
            background:#fafafa; outline:none; transition:border-color .2s,box-shadow .2s;
        }
        .fg input:focus { border-color:var(--green-main); box-shadow:0 0 0 3px rgba(46,125,50,.12); background:#fff; }

        .entry-body { display:none; padding:0 22px 22px; }
        .entry-body.open { display:block; }

        /* ── Entry Table ── */
        .entry-search-bar { margin:14px 0 10px; position:relative; }
        .entry-search-bar input { width:100%; padding:9px 13px 9px 36px; border:1.5px solid var(--border); border-radius:9px; font-size:13px; font-family:'Plus Jakarta Sans',sans-serif; color:var(--text-dark); background:#fafafa; outline:none; }
        .entry-search-bar input:focus { border-color:var(--green-main); box-shadow:0 0 0 3px rgba(46,125,50,.12); background:#fff; }
        .entry-search-bar i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:13px; }

        .entry-table-wrap { overflow-x:auto; border:1.5px solid var(--border); border-radius:12px; }
        .entry-table { width:100%; border-collapse:collapse; font-size:13px; }
        .entry-table thead th { background:#f8fbf8; padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; border-bottom:1.5px solid var(--border); white-space:nowrap; }
        .entry-table tbody tr { border-bottom:1px solid var(--border); transition:background .12s; }
        .entry-table tbody tr:last-child { border-bottom:none; }
        .entry-table tbody tr:hover { background:#f5faf5; }
        .entry-table td { padding:10px 14px; vertical-align:middle; }

        /* Checkbox column */
        .entry-table td.cb-cell { text-align:center; width:44px; }
        .entry-cb { width:17px; height:17px; accent-color:var(--green-main); cursor:pointer; }

        /* Student name cell */
        .s-cell { display:flex; align-items:center; gap:10px; }
        .av { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,var(--green-main),var(--green-light)); color:#fff; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .s-name  { font-weight:600; color:var(--text-dark); }
        .s-email { font-size:11px; color:var(--text-muted); }

        /* Score input inline */
        .score-inline { display:flex; align-items:center; gap:6px; }
        .score-inline input { width:72px; padding:7px 10px; border:1.5px solid var(--border); border-radius:8px; font-size:13px; font-family:'Plus Jakarta Sans',sans-serif; text-align:center; color:var(--text-dark); background:#fafafa; outline:none; transition:border-color .2s,box-shadow .2s; }
        .score-inline input:focus { border-color:var(--green-main); box-shadow:0 0 0 3px rgba(46,125,50,.12); background:#fff; }
        .score-inline input.has-value { border-color:var(--green-main); background:#f0faf0; }
        .score-sep { font-weight:700; color:var(--text-muted); font-size:14px; }
        .score-inline .total-in { width:64px; }
        .pct-live { font-size:12px; font-weight:700; padding:3px 9px; border-radius:20px; min-width:52px; text-align:center; display:inline-block; white-space:nowrap; }
        .pct-live.pass { background:var(--green-pale); color:var(--green-main); }
        .pct-live.fail { background:var(--red-pale); color:var(--red); }
        .pct-live.empty { background:#f0f0f0; color:#aaa; }

        /* Footer bar */
        .entry-footer { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-top:14px; }
        .sel-info { font-size:13px; color:var(--text-muted); }
        .sel-info strong { color:var(--text-dark); }
        .btn-submit { background:linear-gradient(135deg,var(--green-main),var(--green-light)); color:#fff; border:none; border-radius:9px; padding:11px 26px; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:7px; transition:opacity .2s,transform .15s; }
        .btn-submit:hover { opacity:.9; transform:translateY(-1px); }
        .btn-submit:disabled { opacity:.5; cursor:not-allowed; transform:none; }

        /* ── Records Table ── */
        .filter-bar { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-bottom:16px; }
        .filter-bar .fg { margin:0; }
        .btn-clear { background:#fff; color:var(--text-muted); border:1.5px solid var(--border); border-radius:9px; padding:9px 16px; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; align-self:flex-end; }
        .btn-clear:hover { background:#f0f0f0; color:var(--text-dark); }

        .table-card { background:var(--card-bg); border-radius:16px; box-shadow:0 2px 14px rgba(0,0,0,.06); overflow:hidden; }
        .tc-hdr { padding:16px 22px; border-bottom:1.5px solid var(--border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
        .tc-hdr .title { font-size:15px; font-weight:700; color:var(--text-dark); display:flex; align-items:center; gap:8px; }
        .cnt-badge { background:var(--green-pale); color:var(--green-main); font-size:12px; font-weight:700; border-radius:20px; padding:2px 10px; }

        .rec-table { width:100%; border-collapse:collapse; font-size:13px; }
        .rec-table thead th { background:#f8fbf8; padding:11px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; border-bottom:1.5px solid var(--border); white-space:nowrap; }
        .rec-table tbody tr { border-bottom:1px solid var(--border); transition:background .15s; }
        .rec-table tbody tr:hover { background:#f9fbf9; }
        .rec-table tbody tr:last-child { border-bottom:none; }
        .rec-table td { padding:13px 16px; vertical-align:middle; }
        /* ── CHANGE 1: white-space:nowrap added so "Not Ready for ALE" doesn't wrap ── */
        .sc-badge { display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap; }
        .sc-badge.pass { background:var(--green-pale); color:var(--green-main); }
        .sc-badge.fail { background:var(--red-pale); color:var(--red); }
        .btn-del { background:var(--red-pale); color:var(--red); border:none; border-radius:7px; padding:6px 12px; font-size:12px; font-weight:600; cursor:pointer; transition:background .2s; display:flex; align-items:center; gap:5px; }
        .btn-del:hover { background:var(--red); color:#fff; }
        .empty-st { text-align:center; padding:50px 20px; color:var(--text-muted); }
        .empty-st i { font-size:36px; margin-bottom:12px; opacity:.4; display:block; }

        /* ── Toast ── */
        .toast-wrap { position:fixed; bottom:28px; right:24px; z-index:9999; }
        .toast-msg { display:flex; align-items:center; gap:10px; padding:14px 20px; border-radius:12px; font-size:14px; font-weight:600; color:#fff; box-shadow:0 6px 20px rgba(0,0,0,.18); animation:slideUp .35s ease, fadeOut .4s 3.2s forwards; }
        .toast-msg.success { background:var(--green-main); }
        .toast-msg.error   { background:var(--red); }
        .toast-msg.deleted { background:#5d4037; }
        .toast-msg.invalid { background:#e65100; }
        @keyframes slideUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        @keyframes fadeOut { to{opacity:0;transform:translateY(10px)} }

        @media(max-width:640px) {
            .entry-table thead th:nth-child(2), .entry-table td:nth-child(2) { display:none; }
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
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><img src="<?= $logoUrl ?>" alt="AgriQuiz"></div>
         <h4>AGRI<span style="color: yellow;">QUIZ</span></h4>
    </div>
    <nav>
        <a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a>
        <a href="admin_student_list.php"><i class="fas fa-users"></i> Student List</a>
        <a href="review_quiz_adminside.php"><i class="fas fa-book"></i> Reviewers &amp; Quizzes</a>
        <a href="admin_preboard_records.php" class="active"><i class="fas fa-clipboard-check"></i> Preboard Records</a>
        <a href="admin_analytics.php"><i class="fas fa-chart-line"></i> Performance</a>
    </nav>
</div>

<div class="topbar">
    <div class="topbar-left">
        <button class="btn-hamburger" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-title"><i class="fas fa-home"></i> Preboard Records</div>
    </div>
    <div class="topbar-right">
        <a href="profile.php" class="btn-profile"><i class="fas fa-user-circle"></i><span class="btn-label"> Profile</span></a>
       
        <a href="#" onclick="showLogoutModal(); return false;" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span class="btn-label"> Logout</span></a>
    </div>
</div>


<div class="content">
<div class="pb-wrapper">

    <!-- Header -->
    <div class="pb-header">
        <div>
            <h1><i class="fas fa-clipboard-check"></i> Preboard Manual Records</h1>
            <p>Check the student's checkbox and enter the score, then click Save.</p>
        </div>
    </div>

    <!-- Stat Pills -->
    <div class="stat-pills">
        <div class="stat-pill">
            <div class="spi g"><i class="fas fa-clipboard-list"></i></div>
            <div>
                <div class="sp-val"><?= (int)$totalRecords ?></div>
                <div class="sp-lbl">Total Records</div>
            </div>
        </div>
        <div class="stat-pill">
            <div class="spi gold"><i class="fas fa-user-graduate"></i></div>
            <div>
                <div class="sp-val"><?= (int)$studentsRecorded ?></div>
                <div class="sp-lbl">Students Recorded</div>
            </div>
        </div>
        <div class="stat-pill">
            <div class="spi sky"><i class="fas fa-percent"></i></div>
            <div>
                <div class="sp-val"><?= $avgScore > 0 ? $avgScore . '%' : '0' ?></div>
                <div class="sp-lbl">Average Score</div>
            </div>
        </div>
    </div>

    <!-- ENTRY CARD — Student table with inline scoring  -->
    <div class="entry-card">
        <div class="entry-card-hdr" id="entryHdr">
            <div class="hdr-left">
                <i class="fas fa-plus-circle" style="color:#fff;font-size:16px"></i>
                <span>Add Preboard Scores</span>
                <?php if (!empty($studentList)): ?>
                    <span style="background:rgba(255,255,255,.25);color:#fff;font-size:11px;font-weight:700;border-radius:20px;padding:2px 10px;">
                        <?= count($studentList) ?> student<?= count($studentList) != 1 ? 's' : '' ?>
                    </span>
                <?php endif; ?>
            </div>
            <i class="fas fa-chevron-down tog" id="entryTog"></i>
        </div>

        <form method="POST" action="" id="bulkForm">
            <input type="hidden" name="bulk_save" value="1">

            <!-- Date + Areas + default total items -->
            <div class="entry-top-bar">
                <div class="fg" style="max-width:200px">
                    <label><i class="fas fa-calendar"></i> Exam Date</label>
                    <input type="date" name="exam_date" id="examDate" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="fg" style="max-width:220px">
                    <label><i class="fas fa-book"></i> Areas</label>
                    <input type="text" name="subject" id="examSubject" placeholder="e.g. Crop Science" required>
                </div>
                <div class="fg" style="max-width:180px">
                    <label><i class="fas fa-list-ol"></i> Default Total Items</label>
                    <input type="number" id="defaultTotal" value="100" min="1" placeholder="100"
                           oninput="applyDefaultTotal(this.value)">
                </div>
                <div style="align-self:flex-end;padding-bottom:2px">
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">
                        <i class="fas fa-check-square"></i> Quick Select
                    </label>
                    <div style="display:flex;gap:8px;">
                        <button type="button" onclick="selectAll()" style="background:var(--green-pale);color:var(--green-main);border:1.5px solid var(--green-main);border-radius:8px;padding:7px 14px;font-size:12px;font-weight:700;cursor:pointer;">All</button>
                        <button type="button" onclick="deselectAll()" style="background:#fff;color:var(--text-muted);border:1.5px solid var(--border);border-radius:8px;padding:7px 14px;font-size:12px;font-weight:700;cursor:pointer;">None</button>
                    </div>
                </div>
            </div>

            <div class="entry-body open" id="entryBody">

                <!-- Search within student list -->
                <div class="entry-search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="entrySearch" placeholder="Search student by name or email…" oninput="filterEntryTable()">
                </div>

                <?php if (empty($studentList)): ?>
                    <div class="empty-st">
                        <i class="fas fa-users"></i>
                        <p>Walang students na nahanap. Mag-enroll muna ng students.</p>
                    </div>
                <?php else: ?>
                <div class="entry-table-wrap">
                    <table class="entry-table" id="entryTable">
                        <thead>
                            <tr>
                                <th style="text-align:center;width:44px">
                                    <input type="checkbox" class="entry-cb" id="masterCb" onclick="toggleAll(this)" title="Select all">
                                </th>
                                <th>#</th>
                                <th>Student</th>
                                <th>Score / Total Items</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody id="entryTbody">
                            <?php foreach ($studentList as $i => $st): ?>
                            <tr data-search="<?= strtolower(htmlspecialchars($st['username'] . ' ' . $st['email'])) ?>">
                                <td class="cb-cell">
                                    <input type="checkbox" class="entry-cb row-cb"
                                           id="cb_<?= $st['id'] ?>"
                                           onchange="onRowCheck(this, <?= $st['id'] ?>)">
                                </td>
                                <td style="color:var(--text-muted);font-size:12px;white-space:nowrap"><?= $i + 1 ?></td>
                                <td>
                                    <label for="cb_<?= $st['id'] ?>" style="cursor:pointer;display:block">
                                        <div class="s-cell">
                                            <div class="av"><?= strtoupper(substr($st['username'], 0, 1)) ?></div>
                                            <div>
                                                <div class="s-name"><?= htmlspecialchars($st['username']) ?></div>
                                                <div class="s-email"><?= htmlspecialchars($st['email']) ?></div>
                                            </div>
                                        </div>
                                    </label>
                                </td>
                                <td>
                                    <div class="score-inline" id="score_row_<?= $st['id'] ?>">
                                        <input type="number"
                                               name="scores[<?= $st['id'] ?>]"
                                               id="score_<?= $st['id'] ?>"
                                               class="score-input"
                                               placeholder="Score"
                                               min="0" step="0.01"
                                               disabled
                                               oninput="calcPct(<?= $st['id'] ?>)">
                                        <span class="score-sep">/</span>
                                        <input type="number"
                                               name="totals[<?= $st['id'] ?>]"
                                               id="total_<?= $st['id'] ?>"
                                               class="total-in"
                                               value="100"
                                               min="1"
                                               disabled
                                               oninput="calcPct(<?= $st['id'] ?>)">
                                    </div>
                                </td>
                                <td>
                                    <span class="pct-live empty" id="pct_<?= $st['id'] ?>">—</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="entry-footer">
                    <div class="sel-info">
                        <strong id="selCount">0</strong> student<?= count($studentList) != 1 ? 's' : '' ?> selected
                        &nbsp;·&nbsp;
                        <strong id="filledCount">0</strong> with scores
                    </div>
                    <button type="submit" class="btn-submit" id="saveBtn" disabled>
                        <i class="fas fa-save"></i> Save Records
                    </button>
                </div>
                <?php endif; ?>

            </div><!-- /entry-body -->
        </form>
    </div>


    <!-- HISTORY — existing records      -->

    <div class="filter-bar">
        <div class="fg" style="position:relative;min-width:220px;">
            <label><i class="fas fa-search"></i> Search Records</label>
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;pointer-events:none;"></i>
                <input type="text" id="recordSearch" placeholder="Name or email…"
                       style="padding-left:36px;" oninput="filterRecords()">
            </div>
        </div>
        <button type="button" class="btn-clear" onclick="clearSearch()">
            <i class="fas fa-times"></i> Clear
        </button>
    </div>

    <div class="table-card">
        <div class="tc-hdr">
            <div class="title">
                <i class="fas fa-history" style="color:var(--green-main)"></i>
                Preboard Records
                <span class="cnt-badge" id="recordCount">
                    <?= count($allRecords) ?> record<?= count($allRecords) != 1 ? 's' : '' ?>
                </span>
            </div>
        </div>

        <?php if (empty($allRecords)): ?>
            <div class="empty-st">
                <i class="fas fa-clipboard"></i>
                <p>Wala pang records. Mag-record na gamit ang form sa itaas.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table class="rec-table" id="recTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Areas</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Date Taken</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="recTbody">
                        <?php foreach ($allRecords as $i => $rec):
                            $pct  = $rec['total_items'] > 0 ? round(($rec['score'] / $rec['total_items']) * 100, 1) : 0;
                            $pass = $pct >= 75;
                            $dt   = date('M j, Y', strtotime($rec['recorded_at']));
                            $sk   = strtolower($rec['username'] . ' ' . $rec['email']);
                        ?>
                        <tr data-search="<?= htmlspecialchars($sk) ?>">
                            <td style="color:var(--text-muted);font-size:12px"><?= $i + 1 ?></td>
                            <td>
                                <div class="s-cell">
                                    <div class="av"><?= strtoupper(substr($rec['username'], 0, 1)) ?></div>
                                    <div>
                                        <div class="s-name"><?= htmlspecialchars($rec['username']) ?></div>
                                        <div class="s-email"><?= htmlspecialchars($rec['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($rec['subject'] ?? '—') ?></td>
                            <td style="font-weight:700"><?= $rec['score'] ?> / <?= $rec['total_items'] ?></td>
                            <td>
                                <!-- ── CHANGE 2: Ready / Not Ready for ALE label added ── -->
                                <span class="sc-badge <?= $pass ? 'pass' : 'fail' ?>">
                                    <i class="fas <?= $pass ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                    <?= $pct ?>% &mdash; <?= $pass ? 'Ready' : 'Not Ready for ALE' ?>
                                </span>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted);white-space:nowrap"><?= $dt ?></td>
                            <td>
                                <form method="POST" action="" onsubmit="return confirm('Delete this record?');" style="margin:0">
                                    <input type="hidden" name="record_id" value="<?= $rec['id'] ?>">
                                    <button type="submit" name="delete_record" class="btn-del">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>
</div>

<!-- Toast -->
<?php if ($toast): ?>
<div class="toast-wrap" id="toastWrap">
    <?php
        $msgs = [
            'added'   => ['success', 'fa-check-circle',        'Records saved successfully!'],
            'deleted' => ['deleted', 'fa-trash',                'Record deleted.'],
            'error'   => ['error',   'fa-times-circle',         'Database error. Try again.'],
            'invalid' => ['invalid', 'fa-exclamation-triangle', 'Walang score na naipasok. I-check ang form.'],
        ];
        [$cls, $icon, $msg] = $msgs[$toast] ?? ['error', 'fa-times-circle', 'Unknown error.'];
    ?>
    <div class="toast-msg <?= $cls ?>"><i class="fas <?= $icon ?>"></i> <?= $msg ?></div>
</div>
<?php endif; ?>

<script src="asset/js/bootstrap.min.js"></script>
<script>
/* ── Sidebar ── */
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click', () => {
    sidebar.classList.contains('open')
        ? (sidebar.classList.remove('open'), overlay.classList.remove('active'))
        : (sidebar.classList.add('open'),    overlay.classList.add('active'));
});
overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });
sidebar.querySelectorAll('nav a').forEach(l => l.addEventListener('click', () => {
    if (window.innerWidth < 768) { sidebar.classList.remove('open'); overlay.classList.remove('active'); }
}));

/* ── Toggle entry card ── */
const entryHdr  = document.getElementById('entryHdr');
const entryBody = document.getElementById('entryBody');
const entryTog  = document.getElementById('entryTog');
entryHdr.addEventListener('click', (e) => {
    if (e.target.closest('button') || e.target.closest('input')) return;
    const open = entryBody.classList.toggle('open');
    entryHdr.classList.toggle('collapsed', !open);
});

/* ── Enable/disable inputs when row is checked ── */
function onRowCheck(cb, id) {
    const scoreIn = document.getElementById('score_' + id);
    const totalIn = document.getElementById('total_' + id);
    scoreIn.disabled = !cb.checked;
    totalIn.disabled = !cb.checked;
    if (cb.checked) {
        scoreIn.focus();
    } else {
        scoreIn.value = '';
        scoreIn.classList.remove('has-value');
        updatePct(id, null, null);
    }
    updateCounts();
    syncMaster();
}

/* ── Live percentage display ── */
function calcPct(id) {
    const s = parseFloat(document.getElementById('score_' + id).value);
    const t = parseFloat(document.getElementById('total_' + id).value);
    const si = document.getElementById('score_' + id);
    si.classList.toggle('has-value', si.value !== '');
    updatePct(id, s, t);
    updateCounts();
}

function updatePct(id, score, total) {
    const el = document.getElementById('pct_' + id);
    if (score === null || isNaN(score) || isNaN(total) || total <= 0) {
        el.textContent = '—';
        el.className = 'pct-live empty';
        return;
    }
    const pct = Math.round((score / total) * 1000) / 10;
    /* ── CHANGE 3: Show Ready / Not Ready for ALE in live pill ── */
    el.textContent = pct >= 75 ? pct + '% — Ready' : pct + '% — Not Ready for ALE';
    el.className = 'pct-live ' + (pct >= 75 ? 'pass' : 'fail');
}

/* ── Counter update ── */
function updateCounts() {
    const cbs    = document.querySelectorAll('.row-cb:checked');
    const filled = [...cbs].filter(cb => {
        const id = cb.id.replace('cb_', '');
        const v = document.getElementById('score_' + id).value;
        return v !== '' && !isNaN(parseFloat(v));
    });
    document.getElementById('selCount').textContent    = cbs.length;
    document.getElementById('filledCount').textContent = filled.length;
    document.getElementById('saveBtn').disabled        = filled.length === 0;
}

/* ── Select / Deselect all visible rows ── */
function toggleAll(master) {
    document.querySelectorAll('#entryTbody tr:not([style*="display: none"]) .row-cb').forEach(cb => {
        cb.checked = master.checked;
        const id = cb.id.replace('cb_', '');
        const scoreIn = document.getElementById('score_' + id);
        const totalIn = document.getElementById('total_' + id);
        scoreIn.disabled = !master.checked;
        totalIn.disabled = !master.checked;
        if (!master.checked) { scoreIn.value = ''; scoreIn.classList.remove('has-value'); updatePct(id, null, null); }
    });
    updateCounts();
}

function selectAll()   { document.getElementById('masterCb').checked = true;  toggleAll(document.getElementById('masterCb')); }
function deselectAll() { document.getElementById('masterCb').checked = false; toggleAll(document.getElementById('masterCb')); }

function syncMaster() {
    const all = document.querySelectorAll('#entryTbody tr:not([style*="display: none"]) .row-cb');
    const chk = document.querySelectorAll('#entryTbody tr:not([style*="display: none"]) .row-cb:checked');
    const mc  = document.getElementById('masterCb');
    mc.indeterminate = chk.length > 0 && chk.length < all.length;
    mc.checked       = all.length > 0 && chk.length === all.length;
}

/* ── Apply default total to all rows ── */
function applyDefaultTotal(val) {
    const n = parseInt(val);
    if (!n || n < 1) return;
    document.querySelectorAll('[id^="total_"]').forEach(inp => {
        inp.value = n;
        const id = inp.id.replace('total_', '');
        calcPct(id);
    });
}

/* ── Search within entry table ── */
function filterEntryTable() {
    const q = document.getElementById('entrySearch').value.toLowerCase().trim();
    document.querySelectorAll('#entryTbody tr').forEach(row => {
        row.style.display = (!q || row.dataset.search.includes(q)) ? '' : 'none';
    });
    syncMaster();
}

/* ── Search existing records ── */
function filterRecords() {
    const q = document.getElementById('recordSearch').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#recTbody tr[data-search]');
    let vis = 0;
    rows.forEach(row => {
        const m = !q || row.dataset.search.includes(q);
        row.style.display = m ? '' : 'none';
        if (m) vis++;
    });
    const b = document.getElementById('recordCount');
    if (b) b.textContent = vis + ' record' + (vis !== 1 ? 's' : '');
}
function clearSearch() { document.getElementById('recordSearch').value = ''; filterRecords(); }

/* ── Toast dismiss ── */
const tw = document.getElementById('toastWrap');
if (tw) setTimeout(() => tw.style.display = 'none', 3800);

/* ── Auto-open form after save ── */
<?php if ($toast === 'added'): ?>
entryBody.classList.add('open');
entryHdr.classList.remove('collapsed');
<?php endif; ?>

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