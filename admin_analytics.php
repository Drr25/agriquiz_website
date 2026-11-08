<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header('Location: login.php'); 
    exit; 
}
$logoUrl = "asset/agriquiz-logo.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics – AGRIQUIZ Admin</title>
    <link rel="icon" href="<?= $logoUrl ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_analytics.css">
</head>
    <style>
    .sidebar-brand .brand-icon { background: transparent !important; overflow: hidden; }
    .sidebar-brand .brand-icon img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; display: block; }
    </style>
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
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><img src="<?= $logoUrl ?>" alt="AgriQuiz"></div>
        <h4>AGRI<span style="color: yellow;">QUIZ</span></h4>
    </div>
    <nav>
        <a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a>
        <a href="admin_student_list.php"><i class="fas fa-users"></i> Student List</a>
        <a href="review_quiz_adminside.php"><i class="fas fa-book"></i> Reviewers &amp; Quizzes</a>
        <a href="admin_preboard_records.php"><i class="fas fa-clipboard-check"></i> Preboard Records</a>
        <a href="admin_analytics.php" class="active"><i class="fas fa-chart-line"></i> Performance</a>
    </nav>
</div>

<div class="topbar">
    <div class="topbar-left">
        <button class="btn-hamburger" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-title"><i class="fas fa-home"></i> Performarnce</div>
    </div>
    <div class="topbar-right">
        <a href="profile.php" class="btn-profile"><i class="fas fa-user-circle"></i><span class="btn-label"> Profile</span></a>
       
        <a href="#" onclick="showLogoutModal(); return false;" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span class="btn-label"> Logout</span></a>
    </div>
</div>


<div class="content">
    <div class="page-header">
        <h1><i class="fas fa-chart-line" style="margin-right:8px;opacity:.85"></i> Class Performance Analytics</h1>
        <p>Monitor student performance across all agricultural learning areas</p>
    </div>

    <!--PASSING RATE CONTROL PANEL -->
    <div class="passing-rate-card">
        <div class="pr-icon"><i class="fas fa-sliders-h"></i></div>
        <div class="pr-info">
            <h3>ALE Readiness Passing Rate</h3>
            <p>Set the minimum average score (%) a student needs to be marked <em>Ready for Actual ALE</em>. Changes apply instantly to both admin and student views.</p>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px">
            <div class="passing-rate-controls">
                <div class="pr-slider-wrap">
                    <label>Drag to adjust</label>
                    <div class="pr-slider-row">
                        <input type="range" id="prSlider" min="1" max="100" value="75" oninput="syncPR('slider')">
                        <input type="number" id="prNumber" min="1" max="100" value="75" oninput="syncPR('number')">
                        <span style="font-size:13px;font-weight:700;color:#6b7280">%</span>
                    </div>
                </div>
                <button class="btn-save-pr" id="savePrBtn" onclick="savePassingRate()">
                    <i class="fas fa-save"></i> Save
                </button>
                <span class="pr-toast" id="prToast"><i class="fas fa-check"></i> Saved!</span>
            </div>
            <div class="pr-presets">
                <span style="font-size:11px;font-weight:700;color:#6b7280;align-self:center">Quick set:</span>
                <button class="btn-preset" onclick="setPreset(50)">50%</button>
                <button class="btn-preset" onclick="setPreset(60)">60%</button>
                <button class="btn-preset" onclick="setPreset(70)">70%</button>
                <button class="btn-preset active" onclick="setPreset(75)">75%</button>
                <button class="btn-preset" onclick="setPreset(80)">80%</button>
                <button class="btn-preset" onclick="setPreset(90)">90%</button>
                <button class="btn-preset" onclick="setPreset(100)">100%</button>
            </div>
        </div>
    </div>
    <!-- /PASSING RATE CONTROL PANEL -->

    <!-- ── Quiz Analytics Stats ── -->
    <div class="stats-row">
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-users"></i></div><div class="stat-value" id="a-students">0</div><div class="stat-label">Total Students</div></div>
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-pen"></i></div><div class="stat-value" id="a-attempts">0</div><div class="stat-label">Quiz Attempts</div></div>
        <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-chart-bar"></i></div><div class="stat-value" id="a-avg">0</div><div class="stat-label">Class Average</div></div>
        <div class="stat-card"><div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-value" id="a-struggling">0</div><div class="stat-label">Below 50%</div></div>
    </div>

    <div class="page-box">
        <div class="section-title">Performance by Areas</div>
        <div id="fieldOverviewContainer"><div class="spinner-wrap"><div class="spinner"></div></div></div>
    </div>

    <div class="two-col">
        <div class="page-box">
            <div class="section-title">Recent Quiz Activity</div>
            <div id="recentContainer"><div class="spinner-wrap"><div class="spinner"></div></div></div>
        </div>
        <div class="page-box">
            <div class="section-title">Top Performers</div>
            <div id="topStudentsContainer"><div class="spinner-wrap"><div class="spinner"></div></div></div>
        </div>
    </div>

    <div class="page-box">
        <div class="section-title">All Students</div>
        <div class="search-bar">
            <div class="search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" id="studentSearch" placeholder="Search by email…" oninput="filterStudents()">
            </div>
        </div>
        <div style="overflow-x:auto">
            <table class="students-table">
                <thead>
                    <tr>
                        <th style="width:34px">#</th>
                        <th>Student</th>
                        <th>Avg Score</th>
                        <th>Best</th>
                        <th>Attempts</th>
                        <th>Areas</th>
                        <th>Last Active</th>
                        <th>ALE Readiness <span id="prThresholdLabel" style="font-weight:700;color:var(--green-primary)"></span></th>
                    </tr>
                </thead>
                <tbody id="studentsBody">
                    <tr><td colspan="8"><div class="spinner-wrap"><div class="spinner"></div></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- STUDENT DETAIL MODAL -->
<div class="modal-overlay" id="studentModal" onclick="closeModal(event)">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modalTitle">Student Performance</div>
            <button class="modal-close" onclick="document.getElementById('studentModal').classList.remove('show')"><i class="fas fa-times"></i></button>
        </div>
        <div id="modalBody"><div class="spinner-wrap"><div class="spinner"></div></div></div>
    </div>
</div>

<script>
const ANALYTICS_API    = 'api/analytics_api.php';
const PASSING_RATE_API = 'api/passing_rate_api.php';

let allStudents  = [];
let passingRate  = 75; // will be overwritten on load

// ── Sidebar ──
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const toggle  = document.getElementById('sidebarToggle');
function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('active'); }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); }
toggle.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
overlay.addEventListener('click', closeSidebar);
sidebar.querySelectorAll('nav a').forEach(link => {
    link.addEventListener('click', () => { if (window.innerWidth < 768) closeSidebar(); });
});

// ── Helpers ──
function scoreColor(pct){ return pct < 50 ? 'red' : pct < 80 ? 'orange' : 'green'; }

async function apiFetch(action, params={}){
    const qs = new URLSearchParams({action,...params}).toString();
    const res = await fetch(`${ANALYTICS_API}?${qs}`);
    return res.json();
}
async function getAllStudents(){
    try {
        const res = await fetch('get_all_students_analytics.php');
        const data = await res.json();
        return data.success ? data.data : [];
    } catch(e) { return []; }
}
async function getAllFieldsAnalytics(){
    try {
        const res = await fetch('get_all_fields_analytics.php');
        const data = await res.json();
        return data.success ? data.data : [];
    } catch(e) { return []; }
}

// PASSING RATE CONTROLS 
// Load saved passing rate from server
async function loadPassingRate(){
    try {
        const res  = await fetch(PASSING_RATE_API + '?action=get');
        const data = await res.json();
        if(data.success){
            passingRate = data.passing_rate;
            document.getElementById('prSlider').value = passingRate;
            document.getElementById('prNumber').value = passingRate;
            syncPresetHighlight(passingRate);
            updateThresholdLabel();
        }
    } catch(e){ console.warn('Could not load passing rate', e); }
}

// Keep slider + number input in sync
function syncPR(source){
    let v;
    if(source === 'slider'){
        v = parseInt(document.getElementById('prSlider').value);
        document.getElementById('prNumber').value = v;
    } else {
        v = parseInt(document.getElementById('prNumber').value) || 75;
        v = Math.max(1, Math.min(100, v));
        document.getElementById('prSlider').value = v;
    }
    passingRate = v;
    syncPresetHighlight(v);
    updateThresholdLabel();
 
    if(allStudents.length) renderStudentTable(allStudents);
}

function setPreset(val){
    passingRate = val;
    document.getElementById('prSlider').value = val;
    document.getElementById('prNumber').value = val;
    syncPresetHighlight(val);
    updateThresholdLabel();
    if(allStudents.length) renderStudentTable(allStudents);
}

function syncPresetHighlight(val){
    document.querySelectorAll('.btn-preset').forEach(btn => {
        btn.classList.toggle('active', parseInt(btn.textContent) === val);
    });
}

function updateThresholdLabel(){
    const lbl = document.getElementById('prThresholdLabel');
    if(lbl) lbl.textContent = `(≥ ${passingRate}%)`;
}

async function savePassingRate(){
    const btn = document.getElementById('savePrBtn');
    btn.classList.add('saving');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    try {
        const res  = await fetch(PASSING_RATE_API + '?action=save', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ passing_rate: passingRate })
        });
        const data = await res.json();
        if(data.success){
            // Broadcast to student tabs via localStorage
            localStorage.setItem('agriquiz_passing_rate', passingRate);

            const toast = document.getElementById('prToast');
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        } else {
            alert('Failed to save: ' + (data.message || 'Unknown error'));
        }
    } catch(e){ alert('Network error saving passing rate'); }

    btn.classList.remove('saving');
    btn.innerHTML = '<i class="fas fa-save"></i> Save';
    // Re-render table with new threshold
    if(allStudents.length) renderStudentTable(allStudents);
}

// ── Quiz Analytics ──
async function loadFieldOverview(){
    const container = document.getElementById('fieldOverviewContainer');
    const fields = await getAllFieldsAnalytics();
    if (!fields || !fields.length) {
        container.innerHTML = `<div class="empty-state"><i class="fas fa-seedling"></i><p>No quiz data yet.</p></div>`;
        return;
    }

    const sumOfFieldAvgs = fields.reduce((s, d) => s + parseFloat(d.avg_pct || 0), 0);
    const classAverage = sumOfFieldAvgs / 6;
    const totalAttempts = fields.reduce((s,d) => s + parseInt(d.total_attempts||0), 0);

    document.getElementById('a-attempts').textContent = totalAttempts;
    document.getElementById('a-avg').innerHTML = `<span style="color:var(--clr-${scoreColor(classAverage)})">${classAverage.toFixed(1)}%</span>`;

    container.innerHTML = fields.map((row, i) => {
        const avg = parseFloat(row.avg_pct||0), best = parseFloat(row.best_pct||0), cl = scoreColor(avg);
        return `<div class="field-card" style="animation-delay:${i*.05}s" onclick="viewFieldDetail('${row.field.replace(/'/g,"\\'")}')">
            <div class="field-card-header">
                <div class="field-card-name"><i class="fas fa-leaf" style="margin-right:6px;color:var(--green-primary)"></i>${row.field}</div>
                <span class="field-card-meta">${row.student_count} students</span>
            </div>
            <div class="field-card-stats">
                <div class="field-stat"><div class="field-stat-label">Average</div><div class="field-stat-value ${cl}">${avg.toFixed(1)}%</div></div>
                <div class="field-stat"><div class="field-stat-label">Highest</div><div class="field-stat-value green">${best.toFixed(1)}%</div></div>
                <div class="field-stat"><div class="field-stat-label">Attempts</div><div class="field-stat-value">${row.total_attempts}</div></div>
            </div>
            <div class="prog-track"><div class="prog-fill ${cl}" style="width:0%" data-pct="${Math.min(avg,100)}"></div></div>
        </div>`;
    }).join('');
    setTimeout(() => {
        document.querySelectorAll('#fieldOverviewContainer .prog-fill').forEach(el => el.style.width = el.dataset.pct + '%');
    }, 100);
}

async function loadRecentAttempts(){
    const container = document.getElementById('recentContainer');
    const students = await getAllStudents();
    const allAttempts = [];
    for (let student of students) {
        const r = await apiFetch('student_recent', {email: student.student_email, limit: 5});
        if (r.success && r.data) allAttempts.push(...r.data.map(a => ({...a, student_email: student.student_email})));
    }
    allAttempts.sort((a,b) => new Date(b.taken_at) - new Date(a.taken_at));
    const recent = allAttempts.slice(0, 8);
    if (!recent.length) { container.innerHTML = `<div class="empty-state"><i class="fas fa-history"></i><p>No recent activity.</p></div>`; return; }
    container.innerHTML = recent.map(row => {
        const pct = Math.round(row.pct||0), cl = scoreColor(pct);
        const dt = new Date(row.taken_at);
        const timeStr = dt.toLocaleDateString('en-PH',{month:'short',day:'numeric'}) + ' ' + dt.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'});
        const emailShort = row.student_email?.split('@')[0] || 'Unknown';
        return `<div class="recent-item">
            <div class="recent-avatar">${emailShort.charAt(0).toUpperCase()}</div>
            <div class="recent-info">
                <div class="recent-email">${row.student_email || 'Unknown'}</div>
                <div class="recent-field">${row.field}</div>
                <div class="recent-time">${timeStr}</div>
            </div>
            <span class="score-pill ${cl}">${row.score}/${row.total}</span>
        </div>`;
    }).join('');
}

async function loadTopStudents(){
    const container = document.getElementById('topStudentsContainer');
    const students = await getAllStudents();
    if (!students.length) { container.innerHTML = `<div class="empty-state"><i class="fas fa-star"></i><p>No student data yet.</p></div>`; return; }
    container.innerHTML = students.slice(0,5).map((s, i) => {
        const avg = parseFloat(s.overall_avg||0), cl = scoreColor(avg);
        const rankIcon = i===0?'🥇':i===1?'🥈':i===2?'🥉':`#${i+1}`;
        return `<div class="recent-item" onclick="openStudentModal('${s.student_email.replace(/'/g,"\\'")}')">
            <div class="recent-avatar" style="cursor:pointer;background:var(--green-pale);font-weight:800">${rankIcon}</div>
            <div class="recent-info" style="cursor:pointer">
                <div class="recent-email">${s.student_email}</div>
                <div class="recent-field">${s.fields_attempted} field${s.fields_attempted!=1?'s':''} · ${s.total_attempts} attempt${s.total_attempts!=1?'s':''}</div>
            </div>
            <span class="score-pill ${cl}">${avg.toFixed(1)}%</span>
        </div>`;
    }).join('');
}

async function loadStudents(){
    const students = await getAllStudents();
    if (!students.length) {
        document.getElementById('studentsBody').innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-users"></i><p>No student data yet.</p></div></td></tr>`;
        document.getElementById('a-students').textContent = 0;
        document.getElementById('a-struggling').textContent = 0;
        return;
    }
    allStudents = students;
    document.getElementById('a-students').textContent = allStudents.length;
    const struggling = allStudents.filter(s => parseFloat(s.overall_avg||0) < 50).length;
    document.getElementById('a-struggling').innerHTML = `<span style="color:var(--clr-red)">${struggling}</span>`;
    renderStudentTable(allStudents);
}

function renderStudentTable(data){
    if (!data.length) {
        document.getElementById('studentsBody').innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-search"></i><p>No students match your search.</p></div></td></tr>`;
        return;
    }
    document.getElementById('studentsBody').innerHTML = data.map((s, i) => {
        const avg = parseFloat(s.overall_avg||0), best = parseFloat(s.best_score||0), cl = scoreColor(avg);
        const rank = i===0?'gold':i===1?'silver':i===2?'bronze':'normal';
        const rankIcon = i===0?'🥇':i===1?'🥈':i===2?'🥉':`${i+1}`;
        const dt = s.last_active ? new Date(s.last_active).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'2-digit'}) : '—';

        // ── Use the live passingRate variable (not hardcoded 75) ──
        const aleAvg  = parseFloat(s.ale_avg || s.overall_avg || 0);
        const isReady = aleAvg >= passingRate;
        const aleBadge = isReady
            ? `<span class="ale-badge ready"><i class="fas fa-trophy"></i> Ready for Actual ALE</span>`
            : `<span class="ale-badge not-ready"><i class="fas fa-clock"></i> Not Yet Ready</span>`;

        return `<tr onclick="openStudentModal('${s.student_email.replace(/'/g,"\\'")}')">
            <td><div class="rank-badge ${rank}">${rankIcon}</div></td>
            <td><div class="student-email-cell">
                <div class="s-avatar">${s.student_email.charAt(0).toUpperCase()}</div>
                <div><div class="s-email">${s.student_email}</div><div class="s-meta">${s.fields_attempted} field${s.fields_attempted!=1?'s':''} attempted</div></div>
            </div></td>
            <td><span class="score-pill ${cl}">${avg.toFixed(1)}%</span></td>
            <td><span style="font-size:12px;font-weight:700;color:var(--clr-${scoreColor(best)})">${best.toFixed(1)}%</span></td>
            <td>${s.total_attempts}</td>
            <td>${s.fields_attempted}</td>
            <td style="font-size:12px;color:var(--text-muted)">${dt}</td>
            <td>${aleBadge}</td>
        </tr>`;
    }).join('');
}

function filterStudents(){
    const q = document.getElementById('studentSearch').value.toLowerCase();
    renderStudentTable(allStudents.filter(s => s.student_email.toLowerCase().includes(q)));
}

async function openStudentModal(email){
    document.getElementById('modalTitle').textContent = email;
    document.getElementById('modalBody').innerHTML = `<div class="spinner-wrap"><div class="spinner"></div></div>`;
    document.getElementById('studentModal').classList.add('show');
    const r = await apiFetch('student_field_performance', {email});
    if (!r.success || !r.data?.length) {
        document.getElementById('modalBody').innerHTML = `<div class="empty-state"><i class="fas fa-inbox"></i><p>No quiz data for this student.</p></div>`;
        return;
    }
    document.getElementById('modalBody').innerHTML = r.data.map((row, i) => {
        const avg = parseFloat(row.avg_pct||0), best = parseFloat(row.best_pct||0), cl = scoreColor(avg);
        return `<div class="field-card" style="margin-bottom:12px;animation-delay:${i*.08}s">
            <div class="field-card-header">
                <div class="field-card-name">${row.field}</div>
                <span style="font-size:12px;font-weight:800;color:var(--clr-${cl})">${avg.toFixed(1)}%</span>
            </div>
            <div class="field-card-stats">
                <div class="field-stat"><div class="field-stat-label">Best</div><div class="field-stat-value green">${best.toFixed(1)}%</div></div>
                <div class="field-stat"><div class="field-stat-label">Avg</div><div class="field-stat-value ${cl}">${avg.toFixed(1)}%</div></div>
                <div class="field-stat"><div class="field-stat-label">Attempts</div><div class="field-stat-value">${row.attempts}</div></div>
            </div>
            <div class="prog-track"><div class="prog-fill ${cl}" style="width:0%" data-pct="${Math.min(avg,100)}"></div></div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:6px">${row.total_correct}/${row.total_questions} questions correct</div>
        </div>`;
    }).join('');
    setTimeout(() => {
        document.querySelectorAll('#modalBody .prog-fill').forEach(el => el.style.width = el.dataset.pct + '%');
    }, 80);
}
function closeModal(e){ if(e.target === document.getElementById('studentModal')) document.getElementById('studentModal').classList.remove('show'); }

// ── Boot ──
(async function boot(){
    await loadPassingRate();   // must load first so passingRate is set before renderStudentTable
    Promise.all([loadFieldOverview(), loadRecentAttempts(), loadTopStudents(), loadStudents()]);
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
document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) hideLogoutModal();
});
</script>
</body>
</html>