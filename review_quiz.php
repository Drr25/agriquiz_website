<?php
session_start();
$student_email = $_SESSION['email'] ?? 'guest';
$logoUrl = "asset/agriquiz-logo.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Review & Quiz – AGRIQUIZ</title>
    <link rel="icon" href="<?= $logoUrl ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/review_quiz.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <script src="js/reviewer-render.js"></script>

  <style>
  .sidebar-brand .brand-icon { background: transparent !important; overflow: hidden; }
  .sidebar-brand .brand-icon img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; display: block; }
  .reviewer-meta {
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    padding: 8px 0; margin-bottom: 20px;
    font-size: 12px; font-weight: 500; color: #6b7280;
    border-bottom: 1px solid #f0fdf4;
  }
  .reviewer-meta span { display: flex; align-items: center; gap: 4px; }
  .reviewer-meta i { font-size: 11px; color: #22c55e; }
  .reviewer-meta-sep { color: #d1d5db; }
  .rev-progress {
    display: flex; align-items: center; gap: 10px; margin-bottom: 20px;
  }
  .rev-progress-label {
    font-size: 11px; font-weight: 600; color: #9ca3af;
    min-width: 32px; text-align: right;
  }
  .rev-progress-track {
    flex: 1; height: 3px; background: #f3f4f6; border-radius: 2px; overflow: hidden;
  }
  .rev-progress-fill {
    height: 100%; width: 0%; border-radius: 2px;
    background: #22c55e;
    transition: width 0.3s ease;
  }
  .rev-toc {
    background: #fafafa;
    border: 1px solid #f0f0f0;
    border-radius: 10px; padding: 14px 16px; margin-bottom: 24px;
  }
  .rev-toc-header {
    display: flex; align-items: center; gap: 7px;
    font-size: 11px; font-weight: 700; color: #9ca3af;
    text-transform: uppercase; letter-spacing: .08em;
    margin-bottom: 10px;
  }
  .rev-toc-header i { font-size: 10px; }
  .rev-toc-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 1px; }
  .rev-toc-item {
    display: flex; align-items: center; gap: 10px;
    font-size: 13px; font-weight: 400; color: #4b5563;
    padding: 5px 8px; border-radius: 6px;
    cursor: pointer; transition: background .12s, color .12s;
    text-decoration: none;
  }
  .rev-toc-item:hover { background: #f0fdf4; color: #16a34a; }
  .rev-toc-item.active { background: #f0fdf4; color: #15803d; font-weight: 500; }
  .rev-toc-num {
    font-size: 11px; font-weight: 700; color: #22c55e;
    min-width: 20px; text-align: center;
  }
  .rev-section {
    border: 1px solid #f0f0f0;
    border-radius: 12px; margin-bottom: 10px;
    overflow: hidden;
    background: #fff;
    transition: border-color .2s;
  }
  .rev-section:hover { border-color: #d1fae5; }
  .rev-section-head {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 18px; cursor: pointer;
    user-select: none;
    transition: background .12s;
  }
  .rev-section-head:hover { background: #fafafa; }
  .rev-section-head.open { background: #f8fffe; }
  .rev-section-num {
    display: flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: 6px; flex-shrink: 0;
    background: #dcfce7;
    color: #15803d; font-size: 12px; font-weight: 700;
  }
  .rev-section-title-text {
    flex: 1; font-size: 14.5px; font-weight: 600; color: #111827; line-height: 1.4;
  }
  .rev-section-arrow {
    font-size: 11px; color: #d1d5db;
    transition: transform .2s, color .2s;
  }
  .rev-section-head.open .rev-section-arrow { transform: rotate(90deg); color: #22c55e; }
  .rev-section-body {
    padding: 4px 20px 20px;
    border-top: 1px solid #f3f4f6;
    display: block;
  }
  .rev-section-body.collapsed { display: none; }
  .reviewer-card {
    background: transparent;
    border-radius: 0; border: none; padding: 0;
    margin-top: 8px;
  }
  .reviewer-body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 14.5px;
    line-height: 1.9;
    color: #374151;
  }
  .reviewer-body p { margin: 0 0 14px; color: #374151; }
  .reviewer-body .rh2 {
    font-size: 13.5px; font-weight: 700; color: #111827;
    margin: 22px 0 10px;
    padding-bottom: 6px;
    border-bottom: 2px solid #dcfce7;
    display: block;
  }
  .reviewer-body .rh2:first-child { margin-top: 8px; }
  .reviewer-body .rh3 {
    font-size: 13px; font-weight: 600; color: #15803d;
    margin: 16px 0 8px;
    display: block;
  }
  .reviewer-body strong { font-weight: 700; color: #111827; }
  .reviewer-body em { font-style: italic; color: #6b7280; }
  .reviewer-body .rev-img-wrap {
    display: block; margin: 18px auto; text-align: center; max-width: 100%;
  }
  .reviewer-body .rev-img-wrap img {
    max-width: 100%; max-height: 480px; width: auto; height: auto;
    object-fit: contain; border-radius: 8px;
    border: 1px solid #e5e7eb; box-shadow: 0 2px 12px rgba(0,0,0,.07);
    display: block; margin: 0 auto; cursor: zoom-in;
    transition: transform .15s, box-shadow .15s;
  }
  .reviewer-body .rev-img-wrap img:hover { transform: scale(1.01); box-shadow: 0 4px 20px rgba(0,0,0,.13); }
  .reviewer-body .rev-img-caption { display: block; font-size: 12px; color: #9ca3af; margin-top: 6px; font-style: italic; }
  .img-lightbox {
    display: none; position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,.85); align-items: center; justify-content: center; padding: 20px;
  }
  .img-lightbox.open { display: flex; }
  .img-lightbox img { max-width: 92vw; max-height: 88vh; border-radius: 8px; box-shadow: 0 8px 40px rgba(0,0,0,.5); object-fit: contain; }
  .img-lightbox-close {
    position: absolute; top: 16px; right: 20px;
    background: rgba(255,255,255,.15); border: none; cursor: pointer;
    color: #fff; font-size: 22px; width: 40px; height: 40px;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    transition: background .15s;
  }
  .img-lightbox-close:hover { background: rgba(255,255,255,.28); }
  .reviewer-body ul { margin: 6px 0 16px; padding: 0; list-style: none; }
  .reviewer-body ul li {
    position: relative; padding: 6px 10px 6px 22px;
    margin-bottom: 4px; color: #374151; font-size: 14px; line-height: 1.75;
  }
  .reviewer-body ul li::before {
    content: ''; position: absolute; left: 6px; top: 13px;
    width: 6px; height: 6px; border-radius: 50%; background: #86efac;
  }
  .reviewer-body ol { margin: 6px 0 16px; padding: 0; list-style: none; }
  .reviewer-body ol li {
    position: relative; padding: 6px 10px 6px 22px;
    margin-bottom: 4px; color: #374151; font-size: 14px; line-height: 1.75;
  }
  .reviewer-body ol li::before {
    content: ''; position: absolute; left: 6px; top: 13px;
    width: 6px; height: 6px; border-radius: 50%; background: #86efac;
  }
  .reviewer-body blockquote {
    margin: 16px 0; padding: 14px 18px; background: #f0fdf4;
    border-left: 3px solid #22c55e; border-radius: 0 8px 8px 0;
    color: #374151; font-size: 14px; line-height: 1.8;
  }
  .reviewer-body code {
    background: #f3f4f6; padding: 2px 5px; border-radius: 4px;
    font-size: 12.5px; font-family: 'Courier New', monospace; color: #111827;
  }
  .reviewer-body pre {
    background: #1e293b; color: #e2e8f0; padding: 14px 16px;
    border-radius: 8px; overflow-x: auto; font-size: 12.5px; margin: 12px 0;
  }
  .reviewer-body pre code { background: none; padding: 0; color: inherit; }
  .rev-table-wrap { overflow-x: auto; margin: 14px 0; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden; }
  .reviewer-body table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
  .reviewer-body th {
    background: #f0fdf4; color: #15803d; font-weight: 700; padding: 10px 14px; text-align: left;
    font-size: 12px; letter-spacing: .02em; border-bottom: 1px solid #d1fae5;
  }
  .reviewer-body td { padding: 9px 14px; border-bottom: 1px solid #f3f4f6; color: #374151; }
  .reviewer-body tr:last-child td { border-bottom: none; }
  .reviewer-body tr:hover td { background: #fafff8; transition: background .12s; }
  .reviewer-body hr { border: none; height: 1px; margin: 22px 0; background: #f3f4f6; }
  #reviewTitle { font-size: 20px !important; font-weight: 700 !important; color: #111827 !important; }
  .retry-lock-banner {
    display: flex; align-items: flex-start; gap: 14px; padding: 18px 20px;
    background: #fff7ed; border: 1px solid #fb923c; border-radius: 12px; margin-top: 12px;
  }
  .retry-lock-banner .lock-icon { font-size: 24px; line-height: 1; flex-shrink: 0; margin-top: 2px; }
  .retry-lock-banner .lock-body { flex: 1; }
  .retry-lock-banner .lock-title { font-size: 14px; font-weight: 700; color: #c2410c; margin-bottom: 4px; }
  .retry-lock-banner .lock-msg { font-size: 13px; color: #7c2d12; line-height: 1.6; }
  .retry-lock-banner .lock-countdown { font-size: 13px; font-weight: 600; color: #ea580c; margin-top: 6px; display: flex; align-items: center; gap: 6px; }
  .retry-counter-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: #f0fdf4; border: 1px solid #86efac;
    color: #166534; font-size: 12px; font-weight: 600;
    padding: 3px 10px; border-radius: 20px; margin-left: 8px;
  }
  .retry-counter-badge.warn { background: #fff7ed; border-color: #fb923c; color: #c2410c; }
  .exam-readiness-banner {
    display: flex; align-items: flex-start; gap: 16px; padding: 24px;
    background: #dbeafe; border: 1.5px solid #0284c7; border-radius: 14px;
    margin-bottom: 20px; animation: slideInDown 0.5s ease-out;
  }
  @keyframes slideInDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
  .exam-readiness-banner .readiness-icon { font-size: 36px; line-height: 1; flex-shrink: 0; animation: pulse 2s infinite; }
  @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.08); } }
  .exam-readiness-banner .readiness-body { flex: 1; }
  .exam-readiness-banner .readiness-title { font-size: 17px; font-weight: 700; color: #0c4a6e; margin-bottom: 6px; }
  .exam-readiness-banner .readiness-msg { font-size: 13.5px; color: #0369a1; line-height: 1.6; margin-bottom: 12px; }
  .exam-readiness-banner .readiness-stats { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
  .exam-readiness-banner .stat-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,.75); border: 1px solid #93c5fd;
    color: #1e40af; font-size: 12px; font-weight: 600; padding: 5px 11px; border-radius: 20px;
  }
  .exam-readiness-banner .readiness-actions { display: flex; gap: 8px; flex-wrap: wrap; }
  .exam-readiness-banner .btn-take-exam {
    background: #0284c7; color: #fff; font-weight: 700;
    padding: 9px 16px; border: none; border-radius: 8px; cursor: pointer; font-size: 13px;
    transition: background .2s; display: flex; align-items: center; gap: 6px;
  }
  .exam-readiness-banner .btn-take-exam:hover { background: #0369a1; }
  .exam-readiness-banner .btn-dismiss {
    background: rgba(255,255,255,.85); border: 1px solid #93c5fd; color: #1e40af;
    font-weight: 600; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-size: 13px; transition: background .2s;
  }
  .exam-readiness-banner .btn-dismiss:hover { background: #fff; }
  .pdf-viewer-wrap { border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-top: 8px; background: #f9fafb; }
  .pdf-viewer-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 8px; padding: 10px 16px;
    background: #f0fdf4; border-bottom: 1px solid #d1fae5;
  }
  .pdf-toolbar-info { display: flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; color: #374151; }
  .pdf-toolbar-actions { display: flex; gap: 8px; flex-wrap: wrap; }
  .pdf-btn-open, .pdf-btn-download {
    display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600;
    padding: 5px 12px; border-radius: 6px; text-decoration: none;
    transition: background 0.15s, color 0.15s; cursor: pointer;
  }
  .pdf-btn-open { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
  .pdf-btn-open:hover { background: #bbf7d0; }
  .pdf-btn-download { background: #fff; color: #374151; border: 1px solid #e5e7eb; }
  .pdf-btn-download:hover { background: #f3f4f6; }
  .pdf-embed-wrap { position: relative; width: 100%; height: 80vh; min-height: 500px; background: #fff; }
  .pdf-iframe { width: 100%; height: 100%; border: none; display: block; }
  .pdf-fallback {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    height: 100%; gap: 10px; padding: 40px; text-align: center;
  }
  .pdf-fallback-icon { font-size: 48px; color: #ef4444; opacity: 0.5; }
  .pdf-fallback-title { font-size: 16px; font-weight: 700; color: #111827; }
  .pdf-fallback-msg { font-size: 13px; color: #6b7280; }
  @media (max-width: 600px) { .pdf-embed-wrap { height: 60vh; min-height: 350px; } }

  /* ── RESULT COLOR BY SCORE ─────────────────────────────
     tier-high: 80%+   (green)
     tier-mid : 60-79% (amber)
     tier-low : <60%   (red)
  ───────────────────────────────────────────────────── */
  #resultBox {
    --tier:      #16a34a;
    --tier-dark: #15803d;
    --tier-soft: #dcfce7;
    --tier-line: #86efac;
  }
  #resultBox.tier-mid {
    --tier:      #f59e0b;
    --tier-dark: #b45309;
    --tier-soft: #fef3c7;
    --tier-line: #fcd34d;
  }
  #resultBox.tier-low {
    --tier:      #ef4444;
    --tier-dark: #b91c1c;
    --tier-soft: #fee2e2;
    --tier-line: #fca5a5;
  }
  #resultBox .result-score-ring {
    background: conic-gradient(var(--tier) var(--pct, 0%), #e5e7eb 0) !important;
    transition: background .4s ease;
  }
  #resultBox #scoreText { color: var(--tier-dark) !important; }
  #resultBox h3 { color: var(--tier-dark) !important; }
  #resultBox .result-sub {
    display: inline-block;
    background: var(--tier-soft);
    border: 1px solid var(--tier-line);
    color: var(--tier-dark) !important;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 10px;
  }
  #resultBox { text-align: center; }
  #resultBox #retryBtn:not(:disabled) { background: var(--tier) !important; border-color: var(--tier) !important; color: #fff !important; }

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


<!-- Image lightbox -->
<div class="img-lightbox" id="imgLightbox" onclick="closeLightbox()">
  <button class="img-lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
  <img id="lightboxImg" src="" alt="">
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><img src="<?= $logoUrl ?>" alt="AgriQuiz"></div>
    <h4>AGRI<span style="color: yellow;">QUIZ</span></h4>
  </div>
  <nav>
    <a href="student_dashboard.php"><i class="fas fa-home"></i> Home</a>
    <a href="review_quiz.php" class="active"><i class="fas fa-book"></i> Reviewer & Quiz</a>
    <a href="student_analytics.php"><i class="fas fa-chart-line"></i> Performance</a>
  </nav>
</div>

<div class="topbar">
    <div class="topbar-left">
        <button class="btn-hamburger" id="sidebarToggle" aria-label="Toggle sidebar" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-title"><i class="fas fa-home"></i> Reviewer & Quiz</div>
    </div>
    <div class="topbar-right">
        <a href="profile.php" class="btn-profile"><i class="fas fa-user-circle"></i><span class="btn-label"> Profile</span></a>
       
        <a href="#" onclick="showLogoutModal(); return false;" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span class="btn-label"> Logout</span></a>
    </div>
</div>


<div class="field-navbar" id="fieldNavbar">
  <div style="display:flex;align-items:center;padding:0 20px;color:var(--text-muted);font-size:13px;">
    <div class="spinner" style="width:18px;height:18px;border-width:2px;margin:0 10px 0 0"></div>Loading areas…
  </div>
</div>

<div class="content">
  <div id="welcomeState" class="page-box">
    <div id="examReadinessBanner"></div>
    <div class="welcome-state">
      <div class="welcome-icon"><i class="fas fa-seedling"></i></div>
      <div class="welcome-title">Choose a Learning Areas</div>
      <div class="welcome-sub">Click any of the six agricultural Areas in the navigation bar above to view available reviewers and quizzes.</div>
    </div>
  </div>

  <div id="loadingState" class="page-box hidden">
    <div class="spinner-wrap"><div class="spinner"></div><p>Loading content…</p></div>
  </div>

  <div id="fieldHeader" class="hidden"></div>

  <div id="topicSelectorBox" class="page-box topic-selector-box hidden">
    <div class="section-title" id="topicSelectorTitle">Choose a Topic</div>
    <div class="topic-selector-intro"><i class="fas fa-hand-pointer"></i> Select a topic below to start reviewing or take a quiz.</div>
    <div id="topicCardsList"></div>
  </div>

  <div id="reviewBox" class="page-box hidden">
    <button class="btn-back" onclick="backToTopics()"><i class="fas fa-arrow-left"></i> Back to Topics</button>
    <div class="section-title" id="reviewTitle">Reviewer</div>
    <div class="info-bar"><i class="fas fa-book-open"></i> Read through the reviewer before starting the quiz.</div>
    <div class="reviewer-meta" id="reviewerMeta" style="display:none;"></div>
    <div class="rev-progress" id="revProgress" style="display:none;">
      <i class="fas fa-bolt" style="color:#22c55e;font-size:11px;"></i>
      <div class="rev-progress-track"><div class="rev-progress-fill" id="revProgressFill"></div></div>
      <span class="rev-progress-label" id="revProgressPct">0%</span>
    </div>
    <div class="rev-toc" id="revToc" style="display:none;">
      <div class="rev-toc-header"><i class="fas fa-list-ul"></i> Table of Contents</div>
      <ul class="rev-toc-list" id="revTocList"></ul>
    </div>
    <div class="reviewer-card" id="reviewerCard">
      <div class="reviewer-body" id="reviewContent"></div>
    </div>
    <div style="margin-top:16px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
      <button id="startQuizBtn" class="btn-success hidden" onclick="startQuiz()"><i class="fas fa-play"></i> Start Quiz</button>
      <span id="noQuizNote" class="btn-note hidden"><i class="fas fa-info-circle"></i> No quiz available for this topic yet</span>
    </div>
  </div>

  <div id="quizBox" class="page-box hidden quiz-box">
    <button class="btn-back" id="quizBackBtn" onclick="backToTopics()"><i class="fas fa-arrow-left"></i> Back to Topics</button>
    <div class="progress-label" id="progressLabel">Question 1 of 1</div>
    <div class="progress-wrap"><div class="progress-bar" id="progressBar" style="width:0%"></div></div>
    <div id="questionText"></div>
    <div id="options"></div>
    <div id="feedbackArea"></div>
    <button class="btn-next hidden" id="nextBtn" onclick="nextQuestion()">Next <i class="fas fa-arrow-right"></i></button>
  </div>

  <div id="resultBox" class="page-box hidden">
    <div id="resultExamBanner"></div>
    <div class="result-score-ring" id="scoreRing"><div id="scoreText"></div></div>
    <h3 style="font-size:20px;font-weight:800;margin-bottom:6px;text-align:center;">Quiz Complete!</h3>
    <p class="result-sub" id="resultSub"></p>
    <div id="saveStatusWrap" style="display:flex;justify-content:center;margin-top:8px;min-height:28px;"></div>
    <div id="retryLockWrap"></div>
    <div id="resultActions" style="display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin-top:12px;">
      <button id="retryBtn" class="btn-success" onclick="retryQuiz()"><i class="fas fa-redo"></i> Retry Quiz</button>
      <button class="btn-secondary" onclick="backToReviewer()"><i class="fas fa-book"></i> Re-read Reviewer</button>
      <button class="btn-outline" onclick="backToTopics()"><i class="fas fa-list"></i> All Topics</button>
    </div>
  </div>

  <div id="noContentBox" class="page-box hidden">
    <div class="empty-state"><i class="fas fa-inbox"></i><p>No content available for this areas yet.<br>Check back later or ask your instructor.</p></div>
  </div>
</div>

<script>
const API_BASE = (function(){
  const base = window.location.href.replace(/\/[^\/]*(\?.*)?$/, '/');
  return base + 'api/api.php';
})();

const STUDENT_EMAIL = <?= json_encode($student_email) ?>;

const FIELDS = [
  { name: 'Agricultural Economics and Marketing',     short: 'Econ & Marketing', icon: 'fas fa-chart-bar' },
  { name: 'Agricultural Extension and Communication', short: 'Ext & Comm',        icon: 'fas fa-satellite-dish' },
  { name: 'Animal Science',                           short: 'Animal Science',    icon: 'fas fa-horse' },
  { name: 'Crop Science',                             short: 'Crop Science',      icon: 'fas fa-seedling' },
  { name: 'Crop Protection',                          short: 'Crop Protection',   icon: 'fas fa-shield-alt' },
  { name: 'Soil Science',                             short: 'Soil Science',      icon: 'fas fa-layer-group' },
];

const MAX_RETRIES     = 5;
const LOCKOUT_MS      = 24 * 60 * 60 * 1000;
const LOCK_KEY_PREFIX = 'agriquiz_lock_';

let activeField      = null;
let currentQuiz      = null;
let currentQuizId    = null;
let currentField     = null;
let quizIndex        = 0;
let score            = 0;
let savedReviewer    = null;
let quizAnswers      = [];
let fieldReviewers   = [];
let fieldQuizzes     = [];
let selectedReviewer = null;
let countdownTimer   = null;
let revScrollHandler = null;

function openLightbox(src) {
  const lb = document.getElementById('imgLightbox');
  document.getElementById('lightboxImg').src = src;
  lb.classList.add('open');
}
function closeLightbox() {
  document.getElementById('imgLightbox').classList.remove('open');
}
// Shared renderer (reviewer-render.js) calls window.showReviewerImage() on image click —
// point it at this page's own lightbox so behavior matches the admin preview.
window.showReviewerImage = openLightbox;

// This page's markdown engine now lives in reviewer-render.js (shared with the admin
// preview) so both sides always render identical HTML from the same raw content.
// Local names are kept as aliases so the rest of this file needs no other changes.
const inlineMd          = inlineMdShared;
const parseIntoSections = parseIntoSectionsShared;
const renderBodyLines    = renderBodyLinesShared;

function shuffleArray(arr) {
  const s = [...arr];
  for (let i = s.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [s[i], s[j]] = [s[j], s[i]];
  }
  return s;
}

function shuffleQuestionWithAnswers(question) {
  const options = question.a.map((opt, idx) => ({ text: opt, originalIdx: idx }));
  const shuffled = shuffleArray(options);
  question.a = shuffled.map(o => o.text);
  question.c = shuffled.findIndex(o => o.originalIdx === question.c);
  return question;
}

function lockKey(quizId)    { return LOCK_KEY_PREFIX + quizId + '_' + STUDENT_EMAIL; }
function getLockData(quizId){ try{ const r=localStorage.getItem(lockKey(quizId)); return r?JSON.parse(r):null; }catch(e){return null;} }
function setLockData(quizId,data){ try{localStorage.setItem(lockKey(quizId),JSON.stringify(data));}catch(e){} }

function incrementAttempt(quizId){
  const data = getLockData(quizId) || { attempts:0, lockedAt:null };
  data.attempts += 1;
  if(data.attempts >= MAX_RETRIES && !data.lockedAt) data.lockedAt = Date.now();
  setLockData(quizId, data);
  return data;
}

function isLocked(quizId){
  const data = getLockData(quizId);
  if(!data || !data.lockedAt) return false;
  if(Date.now() - data.lockedAt >= LOCKOUT_MS){ setLockData(quizId,{attempts:0,lockedAt:null}); return false; }
  return true;
}

function msUntilUnlock(quizId){
  const data = getLockData(quizId);
  if(!data || !data.lockedAt) return 0;
  return Math.max(0, LOCKOUT_MS - (Date.now() - data.lockedAt));
}

function remainingAttempts(quizId){
  if(isLocked(quizId)) return 0;
  const data = getLockData(quizId);
  return data ? Math.max(0, MAX_RETRIES - data.attempts) : MAX_RETRIES;
}

function formatCountdown(ms){
  const t=Math.ceil(ms/1000), h=Math.floor(t/3600), m=Math.floor((t%3600)/60), s=t%60;
  return [h,m,s].map(v=>String(v).padStart(2,'0')).join(':');
}

function renderLockUI(quizId){
  if(countdownTimer){ clearInterval(countdownTimer); countdownTimer=null; }
  const wrap=document.getElementById('retryLockWrap');
  const retryBtn=document.getElementById('retryBtn');
  if(!wrap) return;
  if(isLocked(quizId)){
    retryBtn.disabled=true; retryBtn.style.opacity='0.4'; retryBtn.style.cursor='not-allowed';
    function update(){
      const ms=msUntilUnlock(quizId);
      if(ms<=0){ clearInterval(countdownTimer); countdownTimer=null; renderLockUI(quizId); return; }
      const cdEl=document.getElementById('lockCountdown');
      if(cdEl) cdEl.textContent=formatCountdown(ms);
    }
    wrap.innerHTML=`<div class="retry-lock-banner"><div class="lock-icon">🔒</div><div class="lock-body"><div class="lock-title">Quiz Locked — Maximum Attempts Reached</div><div class="lock-msg">You have used all <strong>${MAX_RETRIES} attempts</strong> for this quiz. It will automatically unlock after 24 hours.</div><div class="lock-countdown"><i class="fas fa-clock"></i> Unlocks in: <strong id="lockCountdown">${formatCountdown(msUntilUnlock(quizId))}</strong></div></div></div>`;
    countdownTimer=setInterval(update,1000);
  } else {
    retryBtn.disabled=false; retryBtn.style.opacity=''; retryBtn.style.cursor='';
    const left=remainingAttempts(quizId), isWarn=left<=2;
    wrap.innerHTML=left<MAX_RETRIES?`<div style="display:flex;justify-content:center;margin-top:8px;"><span class="retry-counter-badge ${isWarn?'warn':''}"><i class="fas fa-redo"></i> ${left} retry attempt${left!==1?'s':''} remaining</span></div>`:'';
  }
}

async function getStudentAverage(){
  try{
    const res=await apiFetch('get_student_average',{email:STUDENT_EMAIL});
    if(res.success&&res.data) return res.data.average||0;
  }catch(e){ console.warn('Error fetching average:',e); }
  return 0;
}

async function checkAndRenderExamReadiness(){
  const avg=await getStudentAverage();
  if(avg>=100){
    const bannerHTML=`<div class="exam-readiness-banner"><div class="readiness-icon">🎓</div><div class="readiness-body"><div class="readiness-title">🎉 You're Ready for the Actual Exam!</div><div class="readiness-msg">Congratulations! You have achieved a <strong>100% average</strong> across all your quizzes. You have demonstrated mastery of all topics and are now eligible to take the actual exam.</div><div class="readiness-stats"><span class="stat-badge"><i class="fas fa-check-circle"></i> Overall Average: 100%</span><span class="stat-badge"><i class="fas fa-star"></i> Mastered All Topics</span><span class="stat-badge"><i class="fas fa-trophy"></i> Exam Eligible</span></div><div class="readiness-actions"><button class="btn-take-exam" onclick="goToActualExam()"><i class="fas fa-graduation-cap"></i> Take Actual Exam</button><button class="btn-dismiss" onclick="dismissExamBanner()">Dismiss</button></div></div></div>`;
    const bc=document.getElementById('examReadinessBanner');
    if(bc){ bc.innerHTML=bannerHTML; bc.style.display='block'; }
    const rb=document.getElementById('resultExamBanner');
    if(rb){ rb.innerHTML=bannerHTML; rb.style.display='block'; }
  }
}

function goToActualExam(){ window.location.href='actual_exam.php'; }
function dismissExamBanner(){
  const bc=document.getElementById('examReadinessBanner'); if(bc) bc.style.display='none';
  const rb=document.getElementById('resultExamBanner');   if(rb) rb.style.display='none';
}

function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('show'); }
function closeSidebar(){ document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebarOverlay').classList.remove('show'); }
window.addEventListener('resize', ()=>{ if(window.innerWidth>768) closeSidebar(); });
function slug(s){ return s.toLowerCase().replace(/[^a-z0-9]+/g,'-'); }

function isPdfContent(content) {
  if (!content) return false;
  const trimmed = content.trim();
  return (
    /\.pdf(\?.*)?$/i.test(trimmed) ||
    /^(uploads?|files?|documents?|pdf)\//i.test(trimmed) ||
    /^https?:\/\/.+\.pdf/i.test(trimmed)
  );
}

function getPdfUrl(content) {
  const trimmed = content.trim();
  if (/^https?:\/\//i.test(trimmed)) return trimmed;
  // FIX: file_path saved by the admin side can now be root-relative
  // (starts with "/", e.g. "/admin/uploads/reviewers/xxx.pdf") so that it
  // points at the SAME file regardless of which folder is viewing it.
  // A leading "/" must resolve from the site root (origin), not from
  // wherever this student page happens to live — that mismatch was why
  // students weren't seeing the exact PDF the admin uploaded.
  if (trimmed.startsWith('/')) return window.location.origin + trimmed;
  // Backward compatibility for older records saved as a bare relative
  // fragment (no leading slash) — keep resolving those the old way.
  const base = window.location.href.replace(/\/[^\/]*(\?.*)?$/, '/');
  return base + trimmed;
}

function isMobileDevice() {
  return /Android|iPhone|iPad|iPod|Mobile|Windows Phone/i.test(navigator.userAgent) || window.innerWidth <= 768;
}

function renderPdfViewer(pdfUrl) {
  const useGoogleViewer = isMobileDevice();
  const iframeSrc = useGoogleViewer
    ? `https://docs.google.com/viewer?url=${encodeURIComponent(pdfUrl)}&embedded=true`
    : `${pdfUrl}#toolbar=0&navpanes=0&scrollbar=0&view=FitH`;

  return `
    <div class="pdf-viewer-wrap" id="pdfViewerWrap">
      <div class="pdf-viewer-toolbar">
        <span class="pdf-toolbar-info">
          <i class="fas fa-file-pdf" style="color:#ef4444;"></i>
          <span>PDF Reviewer</span>
        </span>
        <div class="pdf-toolbar-actions">
          <a href="${escHtml(pdfUrl)}" target="_blank" class="pdf-btn-open">
            <i class="fas fa-external-link-alt"></i> Open in New Tab
          </a>
          <a href="${escHtml(pdfUrl)}" download class="pdf-btn-download">
            <i class="fas fa-download"></i> Download
          </a>
        </div>
      </div>
      <div class="pdf-embed-wrap">
        <iframe
          src="${escHtml(iframeSrc)}"
          class="pdf-iframe"
          id="pdfIframe"
          title="PDF Reviewer"
          onload="onPdfIframeLoad(this)"
          onerror="onPdfLoadError()"
        ></iframe>
        <div class="pdf-fallback" id="pdfFallback" style="display:none;">
          <div class="pdf-fallback-icon"><i class="fas fa-file-pdf"></i></div>
          <div class="pdf-fallback-title">PDF Preview Not Available</div>
          <div class="pdf-fallback-msg">Your browser may not support inline PDF viewing.</div>
          <a href="${escHtml(pdfUrl)}" target="_blank" class="pdf-btn-open" style="margin-top:12px;">
            <i class="fas fa-external-link-alt"></i> Open PDF in New Tab
          </a>
        </div>
      </div>
    </div>`;
}

function onPdfIframeLoad(iframe) {
  iframe.style.display = 'block';
  const fallback = document.getElementById('pdfFallback');
  if (fallback) fallback.style.display = 'none';
}

function onPdfLoadError() {
  const iframe = document.getElementById('pdfIframe');
  const fallback = document.getElementById('pdfFallback');
  if (iframe) iframe.style.display = 'none';
  if (fallback) fallback.style.display = 'flex';
}

function renderReviewerContent(rawContent, filePath) {
  if (filePath && isPdfContent(filePath)) {
    document.getElementById('revProgress').style.display = 'none';
    document.getElementById('revToc').style.display = 'none';
    const pdfUrl = getPdfUrl(filePath);
    return renderPdfViewer(pdfUrl);
  }

  if (!filePath && rawContent && isPdfContent(rawContent)) {
    document.getElementById('revProgress').style.display = 'none';
    document.getElementById('revToc').style.display = 'none';
    const pdfUrl = getPdfUrl(rawContent.trim());
    return renderPdfViewer(pdfUrl);
  }

  const sections = parseIntoSections(rawContent || '');

  if (!sections.length) {
    document.getElementById('revProgress').style.display = 'none';
    document.getElementById('revToc').style.display = 'none';
    return '<p style="color:#94a3b8;font-style:italic;padding:20px;">No content available.</p>';
  }

  const tocList = document.getElementById('revTocList');
  const namedSections = sections.filter(s => s.title);

  if (namedSections.length > 1) {
    tocList.innerHTML = namedSections.map((s, idx) => {
      const globalIdx = sections.indexOf(s);
      return `<li class="rev-toc-item" id="toc-item-${globalIdx}" onclick="scrollToRevSection(${globalIdx})">
        <span class="rev-toc-num">${idx + 1}</span>
        ${escHtml(s.title)}
      </li>`;
    }).join('');
    document.getElementById('revToc').style.display = 'block';
    document.getElementById('revProgress').style.display = 'flex';
  } else {
    document.getElementById('revToc').style.display = 'none';
    document.getElementById('revProgress').style.display = sections.length > 0 ? 'flex' : 'none';
  }

  let html = '';
  sections.forEach((s, idx) => {
    if (s.title) {
      html += `
        <div class="rev-section" id="rev-sec-${idx}">
          <div class="rev-section-head open" id="rev-sec-head-${idx}" onclick="toggleRevSection(${idx})">
            <div class="rev-section-num">${namedSections.indexOf(s) + 1}</div>
            <div class="rev-section-title-text">${escHtml(s.title)}</div>
            <i class="fas fa-chevron-right rev-section-arrow" id="rev-sec-arrow-${idx}"></i>
          </div>
          <div class="rev-section-body" id="rev-sec-body-${idx}">
            ${s.bodyHtml}
          </div>
        </div>`;
    } else {
      html += `<div id="rev-sec-${idx}" style="padding:12px 4px 4px;">${s.bodyHtml}</div>`;
    }
  });

  return html;
}

function toggleRevSection(idx) {
  const body  = document.getElementById('rev-sec-body-' + idx);
  const head  = document.getElementById('rev-sec-head-' + idx);
  const arrow = document.getElementById('rev-sec-arrow-' + idx);
  if (!body) return;
  body.classList.toggle('collapsed');
  head.classList.toggle('open');
  if (arrow) {
    if (body.classList.contains('collapsed')) arrow.style.transform = 'rotate(0deg)';
    else arrow.style.transform = 'rotate(90deg)';
  }
}

function scrollToRevSection(idx) {
  const el = document.getElementById('rev-sec-' + idx);
  if (el) {
    const body = document.getElementById('rev-sec-body-' + idx);
    const head = document.getElementById('rev-sec-head-' + idx);
    if (body && body.classList.contains('collapsed')) {
      body.classList.remove('collapsed');
      if (head) head.classList.add('open');
      const arrow = document.getElementById('rev-sec-arrow-' + idx);
      if (arrow) arrow.style.transform = 'rotate(90deg)';
    }
    setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
  }
}

function setupRevScrollProgress() {
  if (revScrollHandler) { window.removeEventListener('scroll', revScrollHandler); revScrollHandler = null; }
  const card = document.getElementById('reviewerCard');
  if (!card) return;
  revScrollHandler = function() {
    const rect  = card.getBoundingClientRect();
    const total = card.offsetHeight - window.innerHeight;
    if (total <= 0) { document.getElementById('revProgressFill').style.width = '100%'; document.getElementById('revProgressPct').textContent = '100%'; return; }
    const scrolled = Math.max(0, -rect.top);
    const pct = Math.min(100, Math.round((scrolled / total) * 100));
    document.getElementById('revProgressFill').style.width = pct + '%';
    document.getElementById('revProgressPct').textContent  = pct + '%';

    document.querySelectorAll('.rev-toc-item').forEach(item => item.classList.remove('active'));
    let activeIdx = -1;
    document.querySelectorAll('[id^="rev-sec-"]').forEach(sec => {
      const sr = sec.getBoundingClientRect();
      if (sr.top <= 120) {
        const idxStr = sec.id.replace('rev-sec-', '');
        if (!isNaN(idxStr)) activeIdx = parseInt(idxStr);
      }
    });
    if (activeIdx >= 0) {
      const tocItem = document.getElementById('toc-item-' + activeIdx);
      if (tocItem) tocItem.classList.add('active');
    }
  };
  window.addEventListener('scroll', revScrollHandler, { passive: true });
}

function readingTime(text) {
  const words = text.trim().split(/\s+/).length;
  const mins  = Math.max(1, Math.ceil(words / 200));
  return { words, mins };
}

async function apiFetch(action, params={}){
  const qs = new URLSearchParams({action,...params}).toString();
  const res = await fetch(`${API_BASE}?${qs}`);
  if(!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

async function apiPost(action, body={}){
  const res = await fetch(`${API_BASE}?action=${action}`, {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(body)
  });
  return res.json();
}

async function buildFieldNavbar(){
  let rCounts={}, qCounts={};
  try {
    const [rRes, qRes] = await Promise.all([apiFetch('get_reviewers'), apiFetch('get_quizzes')]);
    if(rRes.success) rRes.data.forEach(r=>{ rCounts[r.field]=(rCounts[r.field]||0)+1; });
    if(qRes.success) qRes.data.forEach(q=>{ qCounts[q.field]=(qCounts[q.field]||0)+1; });
  } catch(e){ console.warn('Field count error:', e); }

  const navbar = document.getElementById('fieldNavbar');
  navbar.innerHTML = FIELDS.map(f=>{
    const rc=rCounts[f.name]||0, qc=qCounts[f.name]||0;
    const badges=[
      rc ? `<span class="fnav-badge rev"><i class="fas fa-file-alt"></i> ${rc}</span>` : '',
      qc ? `<span class="fnav-badge qz"><i class="fas fa-question-circle"></i> ${qc}</span>` : '',
    ].filter(Boolean).join('');
    return `
      <div class="fnav-item" id="fnav-${slug(f.name)}" data-field="${escHtml(f.name)}">
        <div class="fnav-icon"><i class="${f.icon}"></i></div>
        <span>${f.short}</span>
        ${badges ? `<div class="fnav-badges">${badges}</div>` : ''}
      </div>`;
  }).join('');

  document.querySelectorAll('.fnav-item').forEach(el=>{
    el.addEventListener('click', function(){ const fn=this.dataset.field; if(fn) selectField(fn); });
  });

  checkAndRenderExamReadiness();
}

async function selectField(fieldName){
  document.querySelectorAll('.fnav-item').forEach(el=>el.classList.remove('active'));
  const tab = document.getElementById('fnav-'+slug(fieldName));
  if(tab){ tab.classList.add('active'); tab.scrollIntoView({block:'nearest',inline:'center',behavior:'smooth'}); }
  activeField = fieldName;
  showOnly('loadingState');

  try {
    const [rRes, qRes] = await Promise.all([
      apiFetch('get_reviewers', {field: fieldName}),
      apiFetch('get_quizzes',   {field: fieldName})
    ]);
    fieldReviewers = (rRes.success ? rRes.data||[] : []);
    fieldQuizzes   = (qRes.success ? qRes.data||[] : []);

    if(!fieldReviewers.length && !fieldQuizzes.length){
      renderFieldHeader(fieldName, 'No content yet');
      showOnly('noContentBox');
      return;
    }
    const sub = `${fieldReviewers.length} reviewer${fieldReviewers.length!==1?'s':''} · ${fieldQuizzes.length} quiz${fieldQuizzes.length!==1?'zes':''}`;
    renderFieldHeader(fieldName, sub);

    if(fieldReviewers.length === 1 && fieldQuizzes.length === 0){
      await openReviewer(fieldReviewers[0], null, null);
      return;
    }
    renderTopicCards(fieldName);
  } catch(e){
    console.error(e);
    showOnly('noContentBox');
  }
}

// ── TOPIC PAIRING ─────────────────────────────────────────────────────────
function pairReviewersAndQuizzes(reviewers, quizDetails){
  const byId = (a, b) => (a.id||0) - (b.id||0);
  const revsSorted = [...reviewers].sort(byId);
  const qzsSorted  = [...quizDetails].sort(byId);
  const maxLen = Math.max(revsSorted.length, qzsSorted.length);

  const topics = [];
  for (let i = 0; i < maxLen; i++) {
    topics.push({ rev: revsSorted[i] || null, qz: qzsSorted[i] || null });
  }
  return topics;
}

async function renderTopicCards(fieldName){
  const icon = (FIELDS.find(f=>f.name===fieldName)||{}).icon||'fas fa-leaf';
  document.getElementById('topicSelectorTitle').textContent = 'Choose a Topic';
  const container = document.getElementById('topicCardsList');
  container.innerHTML = '<div class="spinner-wrap"><div class="spinner" style="width:24px;height:24px;border-width:2px"></div></div>';
  showOnly('topicSelectorBox');

  const quizDetails = [];
  for(const qz of fieldQuizzes){
    try {
      const dRes = await apiFetch('quiz_detail', {id: qz.id});
      if(dRes.success && dRes.data){
        const qs = normaliseQuestions(dRes.data.questions||[]);
        quizDetails.push({ id: qz.id, title: dRes.data.title, field: dRes.data.field || fieldName, questions: qs });
      }
    } catch(e) { /* skip a quiz that failed to load its detail */ }
  }

  const topics = pairReviewersAndQuizzes(fieldReviewers, quizDetails);

  const grid = document.createElement('div');
  grid.className = 'topic-cards-grid';

  topics.forEach((topic, i) => {
    const rev = topic.rev, qz = topic.qz;
    const title = rev?.title || qz?.title || `Topic ${i+1}`;
    const hasRev = !!rev;
    const hasQz  = !!(qz && qz.questions?.length);
    const locked  = hasQz ? isLocked(qz.id) : false;
    const attLeft = hasQz ? remainingAttempts(qz.id) : MAX_RETRIES;

    const isPdfRev = rev && (rev.file_path && isPdfContent(rev.file_path) || isPdfContent(rev.content || ''));
    let preview = 'Test your knowledge on this topic.';
    if (rev?.content) {
      if (isPdfRev) {
        preview = '📄 PDF Reviewer — click to open and read.';
      } else {
        preview = (rev.content || '').replace(/!\[[^\]]*\]\([^)]+\)/g, '[image]').replace(/[#*_>`~\[\]]/g,'').replace(/\s+/g,' ').trim().substring(0, 100) + (rev.content.length > 100 ? '…' : '');
      }
    }

    const lockBadge = locked
      ? `<span class="topic-pill" style="background:#fff7ed;border-color:#fb923c;color:#c2410c;"><i class="fas fa-lock"></i> Locked 24h</span>`
      : (hasQz && attLeft < MAX_RETRIES
        ? `<span class="topic-pill ${attLeft <= 2 ? 'has-quiz' : 'has-reviewer'}" style="${attLeft <= 2 ? 'background:#fff7ed;border-color:#fb923c;color:#c2410c;' : ''}"><i class="fas fa-redo"></i> ${attLeft} left</span>`
        : '');

    const pills = [
      hasRev ? `<span class="topic-pill has-reviewer"><i class="${isPdfRev ? 'fas fa-file-pdf' : 'fas fa-book-open'}"></i> ${isPdfRev ? 'PDF' : 'Reviewer'}</span>` : '',
      hasQz  ? `<span class="topic-pill has-quiz"><i class="fas fa-pen"></i> Quiz</span>` : '',
      lockBadge,
    ].filter(Boolean).join('');

    let actions = '';
    if(hasRev && hasQz){
      actions = `<div class="topic-card-actions"><button class="topic-action-btn read" data-action="read"><i class="fas fa-book-open"></i> Read Reviewer</button><button class="topic-action-btn quiz" data-action="quiz" ${locked?'disabled style="opacity:.4;cursor:not-allowed"':''}><i class="fas fa-${locked?'lock':'pen-to-square'}"></i> ${locked?'Locked':'Take Quiz'}</button></div>`;
    } else if(hasRev){
      actions = `<div class="topic-card-actions"><button class="topic-action-btn solo read" data-action="read"><i class="fas fa-book-open"></i> Read Reviewer</button></div>`;
    } else if(hasQz){
      actions = `<div class="topic-card-actions"><button class="topic-action-btn solo quiz" data-action="quiz" ${locked?'disabled style="opacity:.4;cursor:not-allowed"':''}><i class="fas fa-${locked?'lock':'pen-to-square'}"></i> ${locked?'Locked':'Take Quiz'}</button></div>`;
    }

    const card = document.createElement('div');
    card.className = 'topic-card';
    card.style.animationDelay = `${i * 0.07}s`;
    card.innerHTML = `
      <div class="topic-card-accent"></div>
      <div class="topic-card-body">
        <div class="topic-card-top">
          <div class="topic-card-icon"><i class="${icon}"></i></div>
          <div class="topic-card-header">
            <div class="topic-card-num">Topic ${i+1}</div>
            <div class="topic-card-title">${escHtml(title)}</div>
          </div>
        </div>
        <div class="topic-card-desc">${escHtml(preview)}</div>
        <div class="topic-card-pills">${pills}</div>
      </div>
      ${actions}`;

    const capturedRev = rev, capturedQz = qz;
    card.querySelectorAll('.topic-action-btn:not([disabled])').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const action = btn.dataset.action;
        if(action === 'read' && hasRev) await openReviewer(capturedRev, hasQz ? capturedQz : null, hasQz ? capturedQz.id : null);
        else if(action === 'quiz' && hasQz){ if(isLocked(capturedQz.id)) return; launchQuizDirectly(capturedQz, capturedQz.id, fieldName); }
      });
    });
    grid.appendChild(card);
  });

  container.innerHTML = '';
  container.appendChild(grid);
}

async function openReviewer(rev, pairedQuiz, pairedQuizId){
  selectedReviewer = rev;
  savedReviewer    = rev;
  currentQuiz      = pairedQuiz;
  currentQuizId    = pairedQuizId;
  currentField     = activeField;

  document.getElementById('reviewTitle').innerHTML =
    `<strong style="font-weight:700;color:#111827;">${escHtml(rev.title || 'Reviewer')}</strong>`;

  const rawContent = (rev.content || '').trim();
  const filePath   = rev.file_path || null;
  const isPdfRev   = (filePath && isPdfContent(filePath)) || (!filePath && isPdfContent(rawContent));

  const metaEl = document.getElementById('reviewerMeta');
  if (isPdfRev) {
    metaEl.style.display = 'flex';
    metaEl.innerHTML = `
      <span><i class="fas fa-file-pdf" style="color:#ef4444;"></i> PDF Reviewer</span>
      <span class="reviewer-meta-sep">·</span>
      <span><i class="fas fa-tag"></i> ${escHtml(rev.field || activeField || '')}</span>`;
  } else if (rawContent) {
    const { words, mins } = readingTime(rawContent);
    const imgCount = (rawContent.match(/!\[[^\]]*\]\([^)]+\)/g) || []).length;
    const imgBadge = imgCount > 0 ? `<span class="reviewer-meta-sep">·</span><span><i class="fas fa-image"></i> ${imgCount} image${imgCount !== 1 ? 's' : ''}</span>` : '';
    metaEl.style.display = 'flex';
    metaEl.innerHTML = `
      <span><i class="fas fa-clock"></i> ~${mins} min read</span>
      <span class="reviewer-meta-sep">·</span>
      <span><i class="fas fa-align-left"></i> ${words.toLocaleString()} words</span>
      ${imgBadge}
      <span class="reviewer-meta-sep">·</span>
      <span><i class="fas fa-tag"></i> ${escHtml(rev.field || activeField || '')}</span>`;
  } else {
    metaEl.style.display = 'none';
  }

  document.getElementById('reviewContent').innerHTML = renderReviewerContent(rawContent, filePath);

  if (!isPdfRev) {
    setupRevScrollProgress();
  } else {
    if (revScrollHandler) { window.removeEventListener('scroll', revScrollHandler); revScrollHandler = null; }
    document.getElementById('revProgress').style.display = 'none';
  }

  const startBtn   = document.getElementById('startQuizBtn');
  const noQuizNote = document.getElementById('noQuizNote');

  if (pairedQuiz && pairedQuiz.questions?.length) {
    if (isLocked(pairedQuizId)) {
      startBtn.classList.add('hidden');
      noQuizNote.classList.remove('hidden');
      noQuizNote.innerHTML = `<i class="fas fa-lock"></i> Quiz locked for 24h — ${formatCountdown(msUntilUnlock(pairedQuizId))} remaining`;
    } else {
      startBtn.classList.remove('hidden');
      noQuizNote.classList.add('hidden');
    }
  } else {
    startBtn.classList.add('hidden');
    noQuizNote.classList.remove('hidden');
    noQuizNote.innerHTML = `<i class="fas fa-info-circle"></i> No quiz available for this topic yet`;
  }

  showOnly('reviewBox');
}

function launchQuizDirectly(quiz, quizId, field){
  if (!quiz) return;
  if (isLocked(quizId)) return;
  quiz.questions = shuffleArray(quiz.questions).map(q => shuffleQuestionWithAnswers({...q}));
  currentQuiz   = quiz;
  currentQuizId = quizId;
  currentField  = field || activeField;
  savedReviewer = null;
  quizIndex = 0; score = 0; quizAnswers = [];
  document.getElementById('quizBackBtn').classList.remove('hidden');
  showOnly('quizBox');
  renderQuestion();
}

function backToTopics(){
  if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
  if (revScrollHandler) { window.removeEventListener('scroll', revScrollHandler); revScrollHandler = null; }
  if(!activeField) return;
  const sub = `${fieldReviewers.length} reviewer${fieldReviewers.length!==1?'s':''} · ${fieldQuizzes.length} quiz${fieldQuizzes.length!==1?'zes':''}`;
  renderFieldHeader(activeField, sub);
  renderTopicCards(activeField);
}

function normaliseQuestions(rows){
  if(!Array.isArray(rows)) return [];
  return rows.map(row=>{
    let opts = row.options_json;
    if(typeof opts==='string'){ try{opts=JSON.parse(opts);}catch{opts=[];} }
    return { q: row.question_text||row.q||'', a: opts||row.a||[], c: Number(row.correct_index??row.c??0), explanation: row.explanation||'' };
  }).filter(q => q.q && q.a && q.a.length);
}

function renderFieldHeader(name, sub){
  const icon = (FIELDS.find(f=>f.name===name)||{}).icon||'fas fa-leaf';
  document.getElementById('fieldHeader').innerHTML = `
    <div class="field-header">
      <div class="field-header-icon"><i class="${icon}"></i></div>
      <div class="field-header-info"><h2>${name}</h2><p>${sub}</p></div>
    </div>`;
  document.getElementById('fieldHeader').classList.remove('hidden');
}

function showOnly(id){
  ['welcomeState','loadingState','topicSelectorBox','reviewBox','quizBox','resultBox','noContentBox']
    .forEach(s => document.getElementById(s).classList.toggle('hidden', s !== id));
  if(id === 'welcomeState') document.getElementById('fieldHeader').classList.add('hidden');
}

function startQuiz(){
  if(!currentQuiz || !currentQuiz.questions.length) return;
  if(isLocked(currentQuizId)) return;
  currentQuiz.questions = shuffleArray(currentQuiz.questions).map(q => shuffleQuestionWithAnswers({...q}));
  quizIndex = 0; score = 0; quizAnswers = [];
  document.getElementById('quizBackBtn').classList.remove('hidden');
  showOnly('quizBox');
  renderQuestion();
}

const LETTERS = ['A','B','C','D','E','F'];

function renderQuestion(){
  const q   = currentQuiz.questions[quizIndex];
  const tot = currentQuiz.questions.length;
  document.getElementById('progressLabel').textContent = `Question ${quizIndex+1} of ${tot}`;
  document.getElementById('progressBar').style.width  = `${(quizIndex/tot)*100}%`;
  document.getElementById('questionText').textContent  = `${quizIndex+1}. ${q.q}`;
  document.getElementById('nextBtn').classList.add('hidden');
  document.getElementById('feedbackArea').innerHTML    = '';
  const od = document.getElementById('options');
  od.innerHTML = '';
  q.a.forEach((ans, i) => {
    const d = document.createElement('div');
    d.className = 'option';
    d.innerHTML = `<div class="option-letter">${LETTERS[i]||i+1}</div><span>${escHtml(ans)}</span>`;
    d.addEventListener('click', () => pickAnswer(i, d));
    od.appendChild(d);
  });
}

function pickAnswer(chosenIdx, el){
  const q = currentQuiz.questions[quizIndex];
  document.querySelectorAll('#options .option').forEach(o => { o.style.pointerEvents='none'; o.style.cursor='default'; });
  const isCorrect = chosenIdx === q.c;
  quizAnswers.push({ question: q.q, chosen: q.a[chosenIdx], correct: q.a[q.c], isCorrect });

  if(isCorrect){
    score++;
    el.classList.add('correct');
    document.getElementById('feedbackArea').innerHTML = `<div class="feedback-correct"><i class="fas fa-check-circle"></i>Correct! Well done.</div>`;
  } else {
    el.classList.add('wrong');
    const correctEl = document.querySelectorAll('#options .option')[q.c];
    if(correctEl) correctEl.classList.add('correct');
    document.getElementById('feedbackArea').innerHTML = `
      <div class="feedback-box">
        <div class="feedback-header"><i class="fas fa-lightbulb"></i> Why is that wrong?</div>
        <div class="feedback-body loading" id="feedbackBody">
          <div class="feedback-spinner"></div>Generating explanation…
        </div>
      </div>`;
    getAIFeedback(q, chosenIdx).then(exp => {
      const b = document.getElementById('feedbackBody');
      if(b){ b.classList.remove('loading'); b.textContent = exp; }
    }).catch(() => {
      const b = document.getElementById('feedbackBody');
      if(b){ b.classList.remove('loading'); b.textContent = `The correct answer is "${q.a[q.c]}". Review the material and try again!`; }
    });
  }
  document.getElementById('nextBtn').classList.remove('hidden');
}

async function getAIFeedback(question, chosenIdx){
  if(question.explanation && question.explanation.trim().length > 10)
    return question.explanation.trim();

  const prompt = `You are a helpful agricultural studies tutor for Filipino students.\nA student answered a quiz question INCORRECTLY. Explain clearly and concisely (2-3 sentences) why their answer is wrong and why the correct answer is right.\nQuestion: "${question.q}"\nStudent's wrong answer: "${question.a[chosenIdx]}"\nCorrect answer: "${question.a[question.c]}"\nAll options: ${question.a.map((a,i)=>`${['A','B','C','D'][i]}) ${a}`).join(', ')}\nRespond with only the explanation — no greetings, no preamble, no markdown.`;

  const response = await fetch('https://api.anthropic.com/v1/messages', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ model: 'claude-sonnet-4-20250514', max_tokens: 1000, messages: [{ role: 'user', content: prompt }] })
  });
  if(!response.ok) throw new Error('API error');
  const data = await response.json();
  const text = (data.content||[]).map(b=>b.type==='text'?b.text:'').join('').trim();
  return text || `The correct answer is "${question.a[question.c]}". Review this topic in the reviewer.`;
}

function nextQuestion(){
  quizIndex++;
  if(quizIndex >= currentQuiz.questions.length) showResult();
  else renderQuestion();
}

// Score tier → color theme (CSS vars live under #resultBox.tier-*)
function getScoreTier(pct){
  if(pct >= 80) return 'tier-high';
  if(pct >= 60) return 'tier-mid';
  return 'tier-low';
}

function showResult(){
  showOnly('resultBox');
  const tot = currentQuiz.questions.length;
  const pct = Math.round((score / tot) * 100);

  // apply color tier based on score
  const resultBox = document.getElementById('resultBox');
  resultBox.classList.remove('tier-high','tier-mid','tier-low');
  resultBox.classList.add(getScoreTier(pct));

  document.getElementById('scoreText').textContent = `${score}/${tot}`;
  document.getElementById('scoreRing').style.setProperty('--pct', pct + '%');
  const msg = pct >= 80
    ? `🎉 ${pct}% — Excellent work! You've mastered this topic.`
    : pct >= 60
    ? `👍 ${pct}% — Good effort! A little more review will get you there.`
    : `📖 ${pct}% — Keep studying and try again!`;
  document.getElementById('resultSub').textContent = msg;
  incrementAttempt(currentQuizId);
  renderLockUI(currentQuizId);
  saveAttempt();
  checkAndRenderExamReadiness();
}

async function saveAttempt(){
  if(!currentQuizId){ showSaveStatus('error','Quiz ID missing — score not saved.'); return; }
  showSaveStatus('saving','Saving your score…');
  try {
    const res = await apiPost('save_attempt', {
      student_email: STUDENT_EMAIL, quiz_id: currentQuizId,
      field: currentField || activeField || '', quiz_title: currentQuiz?.title || 'Quiz',
      score, total: currentQuiz?.questions?.length || 1, answers: quizAnswers
    });
    if(res.success){
      showSaveStatus('saved','✓ Score saved to your profile!');
      try { localStorage.setItem('agriquiz_analytics_refresh', Date.now().toString()); } catch(e){}
    } else { showSaveStatus('error','Could not save score: '+(res.message||'unknown error')); }
  } catch(e){ showSaveStatus('error','Network error — score not saved.'); }
}

function showSaveStatus(type, msg){
  const icons = { saving: '<div class="save-spin"></div>', saved: '✓', error: '⚠' };
  document.getElementById('saveStatusWrap').innerHTML = `<span class="save-indicator ${type}">${icons[type]} ${msg}</span>`;
}

function retryQuiz(){ if(isLocked(currentQuizId)) return; startQuiz(); }

function backToReviewer(){
  if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
  if(savedReviewer) openReviewer(savedReviewer, currentQuiz, currentQuizId);
  else backToTopics();
}

function escHtml(str){
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

buildFieldNavbar();

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