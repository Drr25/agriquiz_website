<?php
    session_start();
    require 'admin_guard.php';
	$logoUrl = "asset/agriquiz-logo.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review & Quiz – AGRIQUIZ</title>
    <link rel="icon" href="<?= $logoUrl ?>">
    <link rel="stylesheet" href="css/review_quiz_adminside.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="js/reviewer-render.js"></script>
    <style>
    /* PDF inline preview (admin side) — pdf.js renders actual pages as canvas
       images instead of relying on the browser's native PDF plugin, which
       mobile browsers don't expose inside iframes. Works for both blob: URLs
       (unsaved pending file) and real server URLs (existing reviewer). */
    .pdf-pages-scroll {
        max-height: 70vh;
        overflow-y: auto;
        background: #525659;
        padding: 14px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        border-radius: 0 0 8px 8px;
    }
    .pdf-pages-scroll canvas {
        max-width: 100%;
        height: auto;
        box-shadow: 0 2px 10px rgba(0,0,0,.3);
        border-radius: 2px;
    }
    .pdf-pages-loading, .pdf-pages-error {
        padding: 30px;
        text-align: center;
        color: #e5e7eb;
        font-size: 13px;
    }
    .pdf-pages-error { color: #fca5a5; }

    /* Bulk-select controls (Manage tab) */
    .manage-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 10px;
    }
    .manage-header-row .section-title { margin: 0; }
    .bulk-actions-wrap {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }
    .select-all-label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: #4b5563;
        cursor: pointer;
        user-select: none;
    }
    .select-all-label input[type="checkbox"] { width: 15px; height: 15px; cursor: pointer; }
    .btn-bulk-delete {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12.5px;
        opacity: .5;
        cursor: not-allowed;
        transition: opacity .15s;
    }
    .btn-bulk-delete.enabled { opacity: 1; cursor: pointer; }
    .ci-checkbox-wrap {
        display: flex;
        align-items: center;
        padding: 0 10px 0 2px;
    }
    .ci-checkbox-wrap input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; }

    /* View Content modal (Manage tab) */
    .view-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.55);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .view-modal-overlay.show { display: flex; }
    .view-modal-box {
        background: #fff;
        width: 100%;
        max-width: 720px;
        max-height: 88vh;
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 50px rgba(0,0,0,.25);
    }
    .view-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #e5e7eb;
        background: #f8faf8;
        gap: 10px;
    }
    .view-modal-header-text { min-width: 0; }
    .view-modal-title {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #1f2937;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .view-modal-subtitle {
        margin: 2px 0 0;
        font-size: 12px;
        color: #6b7280;
    }
    .view-modal-close {
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        color: #6b7280;
        line-height: 1;
        padding: 4px;
        flex-shrink: 0;
    }
    .view-modal-close:hover { color: #1f2937; }
    .view-modal-body {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }
    .btn-sm.view {
        background: #eef2ff;
        color: #4338ca;
    }
    .btn-sm.view:hover { background: #e0e7ff; }
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
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><img src="<?= $logoUrl ?>" alt="AgriQuiz"></div>
         <h4>AGRI<span style="color: yellow;">QUIZ</span></h4>
    </div>
    <nav>
        <a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a>
        <a href="admin_student_list.php"><i class="fas fa-users"></i> Student List</a>
        <a href="review_quiz_adminside.php" class="active"><i class="fas fa-book"></i> Reviewers &amp; Quizzes</a>
        <a href="admin_preboard_records.php"><i class="fas fa-clipboard-check"></i> Preboard Records</a>
        <a href="admin_analytics.php"><i class="fas fa-chart-line"></i> Performance</a>
    </nav>
</div>

<div class="topbar">
    <div class="topbar-left">
        <button class="btn-hamburger" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-title"><i class="fas fa-home"></i> Reviewers and Quizzes</div>
    </div>
    <div class="topbar-right">
        <a href="profile.php" class="btn-profile"><i class="fas fa-user-circle"></i><span class="btn-label"> Profile</span></a>
    
        <a href="#" onclick="showLogoutModal(); return false;" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span class="btn-label"> Logout</span></a>
    </div>
</div>
    
<div class="content">
    <div class="tab-bar">
        <button class="tab-btn active" onclick="switchTab('import')" id="tabImport"><i class="fas fa-upload"></i> Create Quiz</button>
        <button class="tab-btn" onclick="switchTab('reviewer')" id="tabReviewer"><i class="fas fa-file-alt"></i> Create Reviewer</button>
        <button class="tab-btn" onclick="switchTab('manage')" id="tabManage"><i class="fas fa-layer-group"></i> Manage Content</button>
    </div>

    <!-- CREATE QUIZ TAB -->
    <div id="tabImportPanel">
        <div class="page-box">
            <div class="section-title">Create Quiz</div>
            <div id="quizEditBanner" class="edit-banner hidden">
                <span><i class="fas fa-pencil-alt"></i> Editing existing quiz</span>
                <button onclick="cancelEditQuiz()">✕ Cancel Edit</button>
            </div>
            <input type="hidden" id="quizEditId" value="">

            <div class="import-quiz-section">
                <div class="import-quiz-header">
                    <i class="fas fa-file-alt"></i>
                    <span>Import Questions from File</span>
                    <button class="btn-toggle-import" id="btnToggleImport" onclick="toggleImportSection()">
                        <i class="fas fa-chevron-down" id="importChevron"></i>
                    </button>
                </div>
               <div class="import-format-hint">
                  <div class="import-format-hint">
    <div class="hint-title"><i class="fas fa-info-circle"></i> Expected file format:</div>
    <pre class="hint-pre">1. What is photosynthesis?
A. Process of breathing
B. Making food using sunlight
C. Process of growth
D. Process of decomposition
Answer: B
Explanation: Plants use sunlight to produce food. (optional)

2. Next question here...
A. Option 1
B. Option 2
C. Option 3
D. Option 4
Answer: C</pre>
</div>

                    <div class="import-card" id="quizImportDrop"
                        onclick="document.getElementById('quizImportFile').click()"
                        ondragover="handleDragOver(event,'quizImportDrop')"
                        ondragleave="handleDragLeave('quizImportDrop')"
                        ondrop="handleQuizFileDrop(event)">
                        <input type="file" id="quizImportFile" accept=".txt" onchange="handleQuizFileSelect(event)">
                        <div class="import-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="import-label">Upload Quiz File</div>
                        <div class="import-hint">Drag &amp; drop or <span>click to browse</span><br>Accepts <strong>.txt</strong> only</div>
                        <div id="quizImportFileBadge"></div>
                    </div>

                    <div id="quizImportPreview" class="hidden" style="margin-top:12px">
                        <div class="import-parse-result">
                            <span id="quizImportCount" class="parse-count"></span>
                            <button class="btn-sm edit" id="btnLoadImported" onclick="loadImportedQuestions()">
                                <i class="fas fa-check"></i> Load Questions into Builder
                            </button>
                            <button class="btn-sm delete" onclick="clearQuizImport()">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                        <div class="parsed-questions-wrap" id="parsedQuestionsWrap"></div>
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="form-section">
                <label class="form-label">Learning Areas <span style="color:#dc3545">*</span></label>
                <select class="form-control" id="quizField">
                    <option value="">— Select Areas —</option>
                    <option>Agricultural Economics and Marketing</option>
                    <option>Agricultural Extension and Communication</option>
                    <option>Animal Science</option>
                    <option>Crop Science</option>
                    <option>Crop Protection</option>
                    <option>Soil Science</option>
                </select>
            </div>
            <div class="title-field-wrap">
                <div class="title-field-label"><i class="fas fa-tag"></i> Quiz Title <span style="color:#dc3545">*</span></div>
                <input type="text" class="title-input" id="quizTitle" placeholder="e.g. Crop Science Quiz 1…">
            </div>

            <div class="divider"></div>
            <label class="form-label">Questions</label>
            <div id="qBuilder"></div>
            <button class="btn-outline" style="margin-top:8px" onclick="addQuestion()"><i class="fas fa-plus"></i> Add Question</button>
            <div id="quizSaveStatus" class="upload-status hidden"><div class="spinner"></div><span id="quizSaveMsg">Saving…</span></div>
            <div class="action-row">
                <button class="btn-success" id="btnSaveQuiz" onclick="saveQuiz()"><i class="fas fa-save"></i> Save Quiz</button>
                <button class="btn-outline" onclick="clearQuiz()"><i class="fas fa-times"></i> Clear</button>
            </div>
        </div>
    </div>

    <!-- CREATE REVIEWER TAB -->
    <div id="tabReviewerPanel" class="hidden">
        <div class="page-box">
            <div class="section-title">Create Reviewer</div>
            <div id="reviewerEditBanner" class="edit-banner hidden">
                <span><i class="fas fa-pencil-alt"></i> Editing existing reviewer</span>
                <button onclick="cancelEditReviewer()">✕ Cancel Edit</button>
            </div>
            <input type="hidden" id="reviewerEditId" value="">

            <div class="form-section">
                <label class="form-label">Learning Areas <span style="color:#dc3545">*</span></label>
                <select class="form-control" id="reviewerField">
                    <option value="">— Select Areas —</option>
                    <option>Agricultural Economics and Marketing</option>
                    <option>Agricultural Extension and Communication</option>
                    <option>Animal Science</option>
                    <option>Crop Science</option>
                    <option>Crop Protection</option>
                    <option>Soil Science</option>
                </select>
            </div>
            <div class="title-field-wrap">
                <div class="title-field-label"><i class="fas fa-tag"></i> Reviewer Title <span style="color:#dc3545">*</span></div>
                <input type="text" class="title-input" id="reviewerTitle" placeholder="e.g. Introduction to Crop Science…">
            </div>


            <div class="import-card" id="reviewerDrop"
                onclick="document.getElementById('reviewerFile').click()"
                ondragover="handleDragOver(event,'reviewerDrop')"
                ondragleave="handleDragLeave('reviewerDrop')"
                ondrop="handleDrop(event,'reviewer')">
                <input type="file" id="reviewerFile" accept=".pdf" onchange="handleFileSelect(event,'reviewer')">
                <div class="import-icon"><i class="fas fa-file-pdf"></i></div>
                <div class="import-label">Upload PDF File</div>
                <div class="import-hint">Drag &amp; drop or <span>click to browse</span><br>Accepts <strong>.pdf</strong> only</div>
                <div class="import-hint">file size limit <span><strong>10mb</strong></span></div>
                <div id="reviewerFileBadge"></div>
                <div id="reviewerExtracting" class="hidden pdf-extracting"><div class="spinner"></div> Extracting text from PDF…</div>
            </div>

            <div id="reviewerExtractedWrap" style="margin-top:13px" class="hidden">
                <div id="reviewerSourceTag"></div>
                <div class="text-display" id="reviewerPreviewDisplay"></div>
            </div>
            <div class="divider"></div>
            <div id="reviewerSaveStatus" class="upload-status hidden"><div class="spinner"></div><span id="reviewerSaveMsg">Saving…</span></div>
            <div class="action-row">
                <button class="btn-success" id="btnSaveReviewer" onclick="saveReviewer()"><i class="fas fa-save"></i> Save Reviewer</button>
                <button class="btn-outline" onclick="clearReviewer()"><i class="fas fa-times"></i> Clear</button>
            </div>
        </div>
    </div>

    <!-- MANAGE TAB -->
    <div id="tabManagePanel" class="hidden">
        <div class="page-box">
            <div class="manage-header-row">
                <div class="section-title">Saved Reviewers</div>
                <div class="bulk-actions-wrap">
                    <label class="select-all-label">
                        <input type="checkbox" id="reviewerSelectAll" onchange="toggleSelectAll('reviewer', this.checked)">
                        Select All
                    </label>
                    <button class="btn-sm delete btn-bulk-delete" id="btnBulkDeleteReviewer" onclick="bulkDelete('reviewer')" disabled>
                        <i class="fas fa-trash"></i> Delete Selected (<span id="reviewerSelCount">0</span>)
                    </button>
                </div>
            </div>
            <div class="content-list" id="reviewerList"><div class="loading-row"><div class="spinner"></div> Loading…</div></div>
        </div>
        <div class="page-box">
            <div class="manage-header-row">
                <div class="section-title">Saved Quizzes</div>
                <div class="bulk-actions-wrap">
                    <label class="select-all-label">
                        <input type="checkbox" id="quizSelectAll" onchange="toggleSelectAll('quiz', this.checked)">
                        Select All
                    </label>
                    <button class="btn-sm delete btn-bulk-delete" id="btnBulkDeleteQuiz" onclick="bulkDelete('quiz')" disabled>
                        <i class="fas fa-trash"></i> Delete Selected (<span id="quizSelCount">0</span>)
                    </button>
                </div>
            </div>
            <div class="content-list" id="quizList"><div class="loading-row"><div class="spinner"></div> Loading…</div></div>
        </div>
    </div>
</div>

<div class="toast" id="toast"><i class="fas fa-check-circle" id="toastIcon"></i><span id="toastMsg"></span></div>

<!-- VIEW CONTENT MODAL (Manage tab — read-only preview of a saved reviewer or quiz) -->
<div class="view-modal-overlay" id="viewContentModal" onclick="closeViewContentModal(event)">
    <div class="view-modal-box">
        <div class="view-modal-header">
            <div class="view-modal-header-text">
                <h3 class="view-modal-title" id="viewModalTitle">Content</h3>
                <p class="view-modal-subtitle" id="viewModalSubtitle"></p>
            </div>
            <button class="view-modal-close" onclick="document.getElementById('viewContentModal').classList.remove('show')"><i class="fas fa-times"></i></button>
        </div>
        <div class="view-modal-body" id="viewModalBody"><div class="spinner-wrap"><div class="spinner"></div></div></div>
    </div>
</div>

<!-- Lightbox kept for images if needed -->
<div id="adminImgLightbox" onclick="closeAdminLightbox()" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.85);align-items:center;justify-content:center;padding:20px;">
    <button onclick="closeAdminLightbox()" style="position:absolute;top:16px;right:20px;background:rgba(255,255,255,.15);border:none;cursor:pointer;color:#fff;font-size:22px;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;"><i class="fas fa-times"></i></button>
    <img id="adminLightboxImg" src="" alt="" style="max-width:92vw;max-height:88vh;border-radius:8px;box-shadow:0 8px 40px rgba(0,0,0,.5);object-fit:contain;">
</div>

<script>
const API = 'api/save_content.php';
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

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

let pendingQ = [], reviewerRawText = '', pendingFile = null;
let parsedImportedQ = [];
let importSectionOpen = true;

// ── PDF-AS-FILE STATE ──────
let pendingFileIsPdf       = false;
let editingExistingFilePath = null;
let editingOriginalContent  = '';   

// ── MANAGE TAB — BULK SELECT STATE ────
let selectedReviewers = new Set();
let selectedQuizzes   = new Set();

// ── ADMIN LIGHTBOX (image) ────
function openAdminLightbox(src) {
    const lb = document.getElementById('adminImgLightbox');
    const img = document.getElementById('adminLightboxImg');
    img.style.display = '';
    img.src = src;
    lb.style.padding = '20px';
    lb.style.display = 'flex';
}
function closeAdminLightbox() {
    const lb = document.getElementById('adminImgLightbox');
    const img = document.getElementById('adminLightboxImg');
    img.style.display = '';
    img.src = '';
    lb.style.padding = '20px';
    lb.style.display = 'none';
}
// Shared renderer (reviewer-render.js) calls window.showReviewerImage() on image click.
window.showReviewerImage = openAdminLightbox;

// ── VIEW CONTENT MODAL (Manage tab) — read-only preview ─────────────────────
function closeViewContentModal(e) {
    if (!e || e.target === document.getElementById('viewContentModal')) {
        document.getElementById('viewContentModal').classList.remove('show');
    }
}

async function viewReviewerContent(id) {
    const modal = document.getElementById('viewContentModal');
    const body  = document.getElementById('viewModalBody');
    document.getElementById('viewModalTitle').textContent = 'Loading…';
    document.getElementById('viewModalSubtitle').textContent = '';
    body.innerHTML = `<div class="spinner-wrap"><div class="spinner"></div></div>`;
    modal.classList.add('show');

    try {
        const res = await phpGet('reviewer_detail', {id});
        const r = res.data;
        document.getElementById('viewModalTitle').textContent = r.title;
        document.getElementById('viewModalSubtitle').textContent = r.field;

        if (r.file_path) {
            body.innerHTML = `<div class="pdf-inline-container"></div>`;
            const container = body.querySelector('.pdf-inline-container');
            renderPdfPagesInline(r.file_path, container, r.title);
        } else {
            body.innerHTML = `<div class="reviewer-body">${renderReviewerPreviewHTML(r.content || '')}</div>`;
        }
    } catch (e) {
        body.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i>Failed to load: ${esc(e.message)}</div>`;
    }
}

async function viewQuizContent(id) {
    const modal = document.getElementById('viewContentModal');
    const body  = document.getElementById('viewModalBody');
    document.getElementById('viewModalTitle').textContent = 'Loading…';
    document.getElementById('viewModalSubtitle').textContent = '';
    body.innerHTML = `<div class="spinner-wrap"><div class="spinner"></div></div>`;
    modal.classList.add('show');

    try {
        const res = await phpGet('quiz_detail', {id});
        const q = res.data;
        document.getElementById('viewModalTitle').textContent = q.title;
        document.getElementById('viewModalSubtitle').textContent = `${q.field} · ${q.questions.length} question${q.questions.length !== 1 ? 's' : ''}`;

        const letters = ['A','B','C','D'];
        body.innerHTML = q.questions.map((row, i) => {
            let opts = row.options_json;
            if (typeof opts === 'string') { try { opts = JSON.parse(opts); } catch { opts = []; } }
            if (!Array.isArray(opts)) opts = Object.values(opts);
            const correctIndex = Number(row.correct_index) || 0;
            return `<div class="parsed-q-card">
                <div class="parsed-q-num">Question ${i + 1}</div>
                <div class="parsed-q-text">${esc(row.question_text || '')}</div>
                <div class="parsed-options">
                    ${opts.map((opt, oi) => opt ? `
                        <div class="parsed-opt ${oi === correctIndex ? 'correct' : ''}">
                            <span class="opt-letter">${letters[oi]}.</span>
                            <span>${esc(opt)}</span>
                            ${oi === correctIndex ? '<i class="fas fa-check" style="margin-left:auto;font-size:10px"></i>' : ''}
                        </div>` : '').join('')}
                </div>
                ${row.explanation ? `<div class="parsed-explanation"><i class="fas fa-lightbulb"></i> ${esc(row.explanation)}</div>` : ''}
            </div>`;
        }).join('') || `<div class="empty-state"><i class="fas fa-question-circle"></i>No questions found.</div>`;
    } catch (e) {
        body.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i>Failed to load: ${esc(e.message)}</div>`;
    }
}

// ── PDF INLINE PREVIEW VIA PDF.JS ───────────────────────────────────────────
async function renderPdfPagesInline(url, containerEl, label) {
    containerEl.innerHTML = `
        <div class="pdf-header"><i class="fas fa-file-pdf"></i> ${esc(label)}</div>
        <div class="pdf-pages-scroll"><div class="pdf-pages-loading"><div class="spinner"></div> Loading PDF preview…</div></div>`;
    const pagesWrap = containerEl.querySelector('.pdf-pages-scroll');

    try {
        const pdf = await pdfjsLib.getDocument(url).promise;
        pagesWrap.innerHTML = '';
        const scale = Math.min(2, Math.max(1, (window.innerWidth || 800) / 700));
        for (let i = 1; i <= pdf.numPages; i++) {
            const page = await pdf.getPage(i);
            const viewport = page.getViewport({ scale });
            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            const ctx = canvas.getContext('2d');
            await page.render({ canvasContext: ctx, viewport }).promise;
            pagesWrap.appendChild(canvas);
        }
    } catch (err) {
        pagesWrap.innerHTML = `<div class="pdf-pages-error"><i class="fas fa-exclamation-triangle"></i> Couldn't render PDF preview. The file will still be saved and shown to students normally.</div>`;
    }
}

// ── IMPORT SECTION ───────────────────────────────────────────────────────────
function toggleImportSection() {
    importSectionOpen = !importSectionOpen;
    document.getElementById('importQuizBody')?.classList.toggle('collapsed', !importSectionOpen);
    document.getElementById('importChevron').className = importSectionOpen ? 'fas fa-chevron-down' : 'fas fa-chevron-right';
}

// ── API HELPERS ──────────────────────────────────────────────────────────────
async function phpGet(action,params={}){const qs=new URLSearchParams({action,...params}).toString();const res=await fetch(`${API}?${qs}`);const data=await res.json().catch(()=>({success:false,message:`HTTP ${res.status}`}));if(!res.ok||!data.success)throw new Error(data.message||`HTTP ${res.status}`);return data;}
async function phpPost(action,body={}){const res=await fetch(`${API}?action=${action}`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});const data=await res.json().catch(()=>({success:false,message:`HTTP ${res.status}`}));if(!res.ok||!data.success)throw new Error(data.message||`HTTP ${res.status}`);return data;}
async function phpDelete(action,id){const res=await fetch(`${API}?action=${action}&id=${id}`,{method:'DELETE'});const data=await res.json().catch(()=>({success:false,message:`HTTP ${res.status}`}));if(!res.ok)throw new Error(data.message||`HTTP ${res.status}`);return data;}

function switchTab(tab){['import','reviewer','manage'].forEach(t=>{const k=t[0].toUpperCase()+t.slice(1);document.getElementById('tab'+k).classList.toggle('active',t===tab);document.getElementById('tab'+k+'Panel').classList.toggle('hidden',t!==tab);});if(tab==='manage')renderManage();}
function handleDragOver(e,id){e.preventDefault();document.getElementById(id).classList.add('drag-over');}
function handleDragLeave(id){document.getElementById(id).classList.remove('drag-over');}

// ── FILE HANDLERS ────────────────────────────────────────────────────────────
function handleDrop(e,type){e.preventDefault();const f=e.dataTransfer.files[0];if(f)processFile(f,type);document.getElementById(type==='reviewer'?'reviewerDrop':'quizDrop')?.classList.remove('drag-over');}
function handleFileSelect(e,type){const f=e.target.files[0];if(f)processFile(f,type);}

function processFile(file, type) {
    const isPdf = file.name.toLowerCase().endsWith('.pdf');

    if (type === 'reviewer' && !isPdf) {
        showToast('Only PDF files are accepted for reviewer upload. Use the text box below instead.', 'error');
        document.getElementById('reviewerFile').value = '';
        return;
    }

    const badgeId = type === 'reviewer' ? 'reviewerFileBadge' : 'quizFileBadge';
    showFileBadge(badgeId, file.name, type);

    if (type === 'reviewer' && isPdf) {
        // PDFs are uploaded as files and shown as inline preview
        pendingFile = file;
        pendingFileIsPdf = true;
        showPdfPendingPreview(file);
        return;
    }

    if (isPdf) {
        extractPdfText(file, type);
    } else {
        const r = new FileReader();
        r.onload = e => {
            if (type === 'reviewer') {
                pendingFile = file;
                pendingFileIsPdf = false;
                const converted = convertPlainTextToMarkdown(e.target.result);
                showReviewerText(converted, 'manual');
            }
        };
        r.readAsText(file);
    }
}
function showPdfPendingPreview(file) {
    const url = URL.createObjectURL(file);
    document.getElementById('reviewerExtractedWrap').classList.remove('hidden');
    document.getElementById('reviewerSourceTag').innerHTML =
        `<span class="source-tag pdf"><i class="fas fa-file-pdf"></i> PDF file — will be shown to students exactly as uploaded</span>`;

    const preview = document.getElementById('reviewerPreviewDisplay');
    preview.classList.remove('reviewer-body');
    preview.innerHTML = `<div class="pdf-inline-container"></div>`;
    const container = preview.querySelector('.pdf-inline-container');
    renderPdfPagesInline(url, container, file.name);

    reviewerRawText = '';
}

function showFileBadge(bid,name,type){document.getElementById(bid).innerHTML=`<div class="file-badge"><i class="fas fa-file-check"></i>${esc(name)}<button class="remove-file" onclick="clearFileInput('${type}')"><i class="fas fa-times"></i></button></div>`;}
function clearFileInput(type){
    if(type==='reviewer'){
        document.getElementById('reviewerFileBadge').innerHTML='';
        document.getElementById('reviewerFile').value='';
        document.getElementById('reviewerExtracting').classList.add('hidden');
        document.getElementById('reviewerExtractedWrap').classList.add('hidden');
        reviewerRawText='';
        pendingFile=null;
        pendingFileIsPdf=false;
    }
}

// ── PDF TEXT EXTRACTION (used only for text extraction fallback) ──
async function extractPdfText(file, type) {
    const eid = type === 'quizImport' ? 'quizImportExtracting' : 'reviewerExtracting';
    const extractingEl = document.getElementById(eid);
    if (extractingEl) extractingEl.classList.remove('hidden');
    try {
        const pdf = await pdfjsLib.getDocument({data: await file.arrayBuffer()}).promise;
        let text = '';
        for (let i = 1; i <= pdf.numPages; i++) {
            const pg = await pdf.getPage(i);
            const c  = await pg.getTextContent();
            const items = c.items;
            let lastY = null;
            let lineText = '';
            for (const item of items) {
                const y = item.transform ? Math.round(item.transform[5]) : null;
                if (lastY !== null && y !== null && Math.abs(y - lastY) > 5) {
                    text += lineText.trim() + '\n';
                    lineText = '';
                }
                lineText += item.str;
                lastY = y;
            }
            if (lineText.trim()) text += lineText.trim() + '\n';
            text += '\n';
        }
        text = text.trim();
        if (extractingEl) extractingEl.classList.add('hidden');
        if (!text) { showToast('PDF appears image-based — no text extracted.', 'error'); return; }
        if (type === 'quizImport') {
            processImportedText(text, pdf.numPages);
        } else {
            pendingFile = file;
            const converted = convertPlainTextToMarkdown(text);
            showReviewerText(converted, 'pdf');
            showToast(`Extracted text from PDF (${pdf.numPages} page${pdf.numPages > 1 ? 's' : ''})`);
        }
    } catch(err) {
        if (extractingEl) extractingEl.classList.add('hidden');
        showToast('Failed to read PDF.', 'error');
    }
}

// ── PLAIN TEXT → MARKDOWN CONVERTER ──
function convertPlainTextToMarkdown(raw) {
    if (!raw) return '';

    const lines = raw.split('\n');
    const out = [];

    const reNumberedList = /^(\d+[\.\)])\s+(.+)/;
    const reBullet       = /^[\-\*\•]\s+(.+)/;
    function looksLikeHeading(line) {
        const t = line.trim();
        if (!t || t.length > 90) return false;
        if (t.endsWith('.') || t.endsWith(',') || t.endsWith(';')) return false;
        if (/^[A-Z][A-Z\s\d\-\/\(\)]{4,}$/.test(t)) return true;
        if (t.endsWith(':') && t.length < 70) return true;
        const words = t.split(/\s+/);
        if (words.length >= 2 && words.length <= 10) {
            const capCount = words.filter(w => /^[A-Z]/.test(w)).length;
            if (capCount / words.length >= 0.7) return true;
        }
        return false;
    }

    let prevBlank = true;
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        const trimmed = line.trim();

        if (!trimmed) { out.push(''); prevBlank = true; continue; }
        if (/^#{1,6}\s/.test(trimmed)) { out.push(trimmed); prevBlank = false; continue; }

        const numMatch = reNumberedList.exec(trimmed);
        if (numMatch) { out.push(`${numMatch[1]} ${numMatch[2]}`); prevBlank = false; continue; }

        const bulletMatch = reBullet.exec(trimmed);
        if (bulletMatch) { out.push(`- ${bulletMatch[1]}`); prevBlank = false; continue; }

        if (looksLikeHeading(trimmed)) {
            const isMajor = trimmed.length < 40 && (/^[A-Z][A-Z\s\d]{3,}$/.test(trimmed) || (trimmed.endsWith(':') && trimmed.length < 35));
            if (out[out.length - 1] !== '') out.push('');
            out.push(isMajor ? `# ${trimmed.replace(/:$/, '')}` : `## ${trimmed.replace(/:$/, '')}`);
            out.push('');
            prevBlank = true;
            continue;
        }

        out.push(trimmed);
        prevBlank = false;
    }

    const cleaned = [];
    let blankCount = 0;
    for (const line of out) {
        if (line === '') { blankCount++; if (blankCount <= 1) cleaned.push(line); }
        else { blankCount = 0; cleaned.push(line); }
    }

    return cleaned.join('\n').trim();
}

// ── REVIEWER PREVIEW — 
function showReviewerText(text, source) {
    reviewerRawText = text;
    document.getElementById('reviewerExtractedWrap').classList.remove('hidden');
    document.getElementById('reviewerSourceTag').innerHTML = source === 'pdf'
        ? `<span class="source-tag pdf"><i class="fas fa-file-pdf"></i> Extracted from PDF</span>`
        : `<span class="source-tag manual"><i class="fas fa-keyboard"></i> Manually entered</span>`;
    const preview = document.getElementById('reviewerPreviewDisplay');
    preview.classList.add('reviewer-body');
    preview.innerHTML = renderReviewerPreviewHTML(text);
}

function handleQuizFileDrop(e) {
    e.preventDefault();
    document.getElementById('quizImportDrop').classList.remove('drag-over');
    const f = e.dataTransfer.files[0];
    if (f) handleQuizFile(f);
}
function handleQuizFileSelect(e) {
    const f = e.target.files[0];
    if (f) handleQuizFile(f);
}
function handleQuizFile(file) {
    if (!file.name.toLowerCase().endsWith('.txt')) {
        showToast('Only .txt files are accepted for quiz import.', 'error');
        return;
    }
    document.getElementById('quizImportFileBadge').innerHTML =
        `<div class="file-badge"><i class="fas fa-file-check"></i>${esc(file.name)}<button class="remove-file" onclick="clearQuizImport()"><i class="fas fa-times"></i></button></div>`;
    const r = new FileReader();
    r.onload = e => processImportedText(e.target.result, null);
    r.readAsText(file);
}

function processImportedText(text, pages) {
    parsedImportedQ = parseQuizText(text);
    if (!parsedImportedQ.length) {
        showToast('No questions found. Please check the file format.', 'error');
        return;
    }
    const suffix = pages ? ` (${pages} page${pages > 1 ? 's' : ''})` : '';
    showToast(`Found ${parsedImportedQ.length} question${parsedImportedQ.length > 1 ? 's' : ''}${suffix}`);
    renderParsedPreview();
}

function parseQuizText(raw) {
    const questions = [];
    const lines = raw.split(/\r?\n/).map(l => l.trim()).filter(l => l !== '');
    let current = null;
    const flushCurrent = () => {
        if (current && current.q && current.options.length >= 2) {
            while (current.options.length < 4) current.options.push('');
            questions.push(current);
        }
        current = null;
    };
    const reQuestion = /^(\d+)[.)]\s+(.+)$/;
    const reOption   = /^([A-Da-d])[.)]\s+(.+)$/;
    const reAnswer   = /^(?:correct\s+)?answer\s*[:\-]\s*([A-Da-d])[.).]?\s*.*/i;
    const reExplain  = /^explanation\s*[:\-]\s*(.+)/i;
    for (const line of lines) {
        const qMatch = reQuestion.exec(line);
        if (qMatch) { flushCurrent(); current = { q: qMatch[2].trim(), options: [], correctIndex: 0, explanation: '' }; continue; }
        if (!current) continue;
        const optMatch = reOption.exec(line);
        if (optMatch) { current.options.push(optMatch[2].trim()); continue; }
        const ansMatch = reAnswer.exec(line);
        if (ansMatch) { const letter = ansMatch[1].toUpperCase(); current.correctIndex = ['A','B','C','D'].indexOf(letter); if (current.correctIndex < 0) current.correctIndex = 0; continue; }
        const explMatch = reExplain.exec(line);
        if (explMatch) { current.explanation = explMatch[1].trim(); continue; }
        if (current && !current.options.length) current.q += ' ' + line;
    }
    flushCurrent();
    return questions;
}

function renderParsedPreview() {
    const wrap = document.getElementById('parsedQuestionsWrap');
    const letters = ['A','B','C','D'];
    wrap.innerHTML = parsedImportedQ.map((q, i) => `
        <div class="parsed-q-card">
            <div class="parsed-q-num">Question ${i + 1}</div>
            <div class="parsed-q-text">${esc(q.q)}</div>
            <div class="parsed-options">
                ${q.options.map((opt, oi) => opt ? `
                    <div class="parsed-opt ${oi === q.correctIndex ? 'correct' : ''}">
                        <span class="opt-letter">${letters[oi]}.</span>
                        <span>${esc(opt)}</span>
                        ${oi === q.correctIndex ? '<i class="fas fa-check" style="margin-left:auto;font-size:10px"></i>' : ''}
                    </div>` : '').join('')}
            </div>
            ${q.explanation ? `<div class="parsed-explanation"><i class="fas fa-lightbulb"></i> ${esc(q.explanation)}</div>` : ''}
        </div>
    `).join('');
    document.getElementById('quizImportCount').textContent = `${parsedImportedQ.length} question${parsedImportedQ.length !== 1 ? 's' : ''} detected`;
    document.getElementById('quizImportPreview').classList.remove('hidden');
}

function loadImportedQuestions() {
    if (!parsedImportedQ.length) return;
    const mapped = parsedImportedQ.map(q => ({ q: q.q, a: q.options, c: q.correctIndex, explanation: q.explanation || '' }));
    const builderEmpty = pendingQ.length === 0 || (pendingQ.length === 1 && !pendingQ[0].q);
    if (builderEmpty) pendingQ = mapped;
    else pendingQ = [...pendingQ, ...mapped];
    renderQBuilder();
    if (importSectionOpen) toggleImportSection();
    showToast(`${mapped.length} question${mapped.length !== 1 ? 's' : ''} loaded into builder!`);
    document.getElementById('qBuilder').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function clearQuizImport() {
    parsedImportedQ = [];
    document.getElementById('quizImportFileBadge').innerHTML = '';
    document.getElementById('quizImportFile').value = '';
    document.getElementById('quizImportPreview').classList.add('hidden');
    document.getElementById('parsedQuestionsWrap').innerHTML = '';
}

// ── QUIZ BUILDER ─────────────────────────────────────────────────────────────
function addQuestion(){pendingQ.push({q:'',a:['','','',''],c:0,explanation:''});renderQBuilder();}
function removeQuestion(i){pendingQ.splice(i,1);renderQBuilder();}
function setCorrect(qi,oi){pendingQ[qi].c=oi;renderQBuilder();}
function renderQBuilder(){document.getElementById('qBuilder').innerHTML=pendingQ.map((q,qi)=>`<div class="q-card"><div class="q-card-header"><span class="q-num">Question ${qi+1}</span><button class="btn-icon" onclick="removeQuestion(${qi})"><i class="fas fa-trash"></i></button></div><input class="form-control" placeholder="Enter question text…" value="${esc(q.q)}" oninput="pendingQ[${qi}].q=this.value" style="margin-bottom:10px"><div class="options-row">${q.a.map((a,oi)=>`<div class="option-input-wrap ${q.c===oi?'correct-opt':''}"><input type="radio" name="corr-${qi}" ${q.c===oi?'checked':''} onchange="setCorrect(${qi},${oi})"><input type="text" placeholder="Option ${oi+1}" value="${esc(a)}" oninput="pendingQ[${qi}].a[${oi}]=this.value"></div>`).join('')}</div><div class="explanation-wrap"><div class="explanation-label"><i class="fas fa-lightbulb"></i> Explanation <span style="font-weight:400;color:#9ca3af;font-size:10px;text-transform:none">(why the answer is correct)</span></div><textarea class="explanation-input" placeholder="Explain why the correct answer is right…" oninput="pendingQ[${qi}].explanation=this.value">${esc(q.explanation||'')}</textarea></div></div>`).join('');}
function esc(s){return(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

// ── SAVE QUIZ & REVIEWER FUNCTIONS ──────────────────────────────────────────
async function saveQuiz(){
    const title=document.getElementById('quizTitle').value.trim();
    const field=document.getElementById('quizField').value;
    const editId=document.getElementById('quizEditId').value;

    if(!title){showToast('Please enter a quiz title.','error');return;}
    if(!field){showToast('Please select a learning field.','error');return;}
    if(!pendingQ.length){showToast('Add at least one question.','error');return;}

    document.querySelectorAll('#qBuilder .q-card').forEach((card,qi)=>{
        if(!pendingQ[qi])return;
        const qInput=card.querySelector('input.form-control');
        if(qInput)pendingQ[qi].q=qInput.value.trim();
        card.querySelectorAll('.option-input-wrap input[type="text"]').forEach((inp,oi)=>{
            if(pendingQ[qi].a)pendingQ[qi].a[oi]=inp.value.trim();
        });
        const expl=card.querySelector('textarea');
        if(expl)pendingQ[qi].explanation=expl.value.trim();
    });

    // STRICT VALIDATION: bawat question kailangan may text AT lahat ng choices
    // ay puno. (Dati: .filter() lang ang gamit, kaya tahimik na na-a-allow
    // ang mga may blangkong choice basta may >=2 na filled. Ngayon,
    // mag-e-error agad at ise-stop ang save kung may makikitang kulang.)
    for (let i = 0; i < pendingQ.length; i++) {
        const q = pendingQ[i];
        if (!q.q) {
            showToast(`Question ${i+1}: please enter the question text.`, 'error');
            return;
        }
        if (!Array.isArray(q.a) || q.a.length < 2) {
            showToast(`Question ${i+1}: needs at least 2 choices.`, 'error');
            return;
        }
        const emptyIndex = q.a.findIndex(opt => !opt || !opt.trim());
        if (emptyIndex !== -1) {
            const letters = ['A','B','C','D'];
            showToast(`Question ${i+1}: Choice ${letters[emptyIndex] || (emptyIndex+1)} is empty. Fill in all choices before saving.`, 'error');
            return;
        }
    }

    const questions = pendingQ.map(q => ({ q: q.q, a: q.a, c: q.c, explanation: q.explanation || '' }));

    setFormBusy('btnSaveQuiz','quizSaveStatus','quizSaveMsg',true,'Saving…');
    try{
        const payload={title,field,questions};
        if(editId)payload.id=parseInt(editId);
        const result=await phpPost('save_quiz',payload);
        showToast(result.message||'Quiz saved!');
        clearQuiz();
        renderManage();
    }catch(e){
        showToast('Error: '+e.message,'error');
    }finally{
        setFormBusy('btnSaveQuiz','quizSaveStatus','quizSaveMsg',false,'','Save Quiz');
    }
}

async function saveReviewer() {
    const title  = document.getElementById('reviewerTitle').value.trim();
    const field  = document.getElementById('reviewerField').value;
    const editId = document.getElementById('reviewerEditId').value;

    if (!title) { showToast('Please enter a reviewer title.', 'error'); return; }
    if (!field) { showToast('Please select a learning field.', 'error'); return; }

    const isPdfSave = pendingFileIsPdf && pendingFile;
    const keepingExistingFile = !!editId && !!editingExistingFilePath && !isPdfSave;

    if (!isPdfSave && !keepingExistingFile) {
        showToast('Please upload a PDF file for the reviewer.', 'error');
        return;
    }

    const btn = document.getElementById('btnSaveReviewer');
    const statusEl = document.getElementById('reviewerSaveStatus');
    const msgEl = document.getElementById('reviewerSaveMsg');
    btn.disabled = true;
    statusEl.classList.remove('hidden');
    btn.innerHTML = `<div class="spinner" style="width:14px;height:14px;border-width:2px;border-color:rgba(255,255,255,.3);border-top-color:#fff"></div> Saving…`;
    msgEl.textContent = isPdfSave ? 'Uploading…' : 'Saving…';

    try {
        const fd = new FormData();
        fd.append('title', title);
        fd.append('field', field);
        if (editId) fd.append('id', editId);
        if (isPdfSave) {
            fd.append('file', pendingFile);
        } else {
            fd.append('content', editingOriginalContent);
        }

        await new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', `${API}?action=save_reviewer`);

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const pct = Math.round((e.loaded / e.total) * 100);
                    msgEl.textContent = isPdfSave ? `Uploading… ${pct}%` : 'Saving…';
                }
            };
            xhr.onload = () => {
                let data;
                try { data = JSON.parse(xhr.responseText); }
                catch { reject(new Error(`HTTP ${xhr.status}`)); return; }
                if (xhr.status >= 200 && xhr.status < 300 && data.success) resolve(data);
                else reject(new Error(data.message || `HTTP ${xhr.status}`));
            };
            xhr.onerror = () => reject(new Error('Network error during upload.'));
            xhr.send(fd);
        });

        showToast(`Reviewer "${title}" saved!`);
        clearReviewer();
        renderManage();
    } catch (e) {
        showToast('Error: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        statusEl.classList.add('hidden');
        btn.innerHTML = `<i class="fas fa-save"></i> Save Reviewer`;
    }
}

function clearQuiz(){
    document.getElementById('quizEditId').value='';
    document.getElementById('quizTitle').value='';
    document.getElementById('quizField').value='';
    document.getElementById('quizEditBanner').classList.add('hidden');
    pendingQ=[];
    renderQBuilder();
    addQuestion();
    clearQuizImport();
}

function clearReviewer() {
    document.getElementById('reviewerEditId').value = '';
    document.getElementById('reviewerTitle').value  = '';
    document.getElementById('reviewerField').value  = '';
    document.getElementById('reviewerFileBadge').innerHTML = '';
    document.getElementById('reviewerFile').value   = '';
    document.getElementById('reviewerExtracting').classList.add('hidden');
    document.getElementById('reviewerExtractedWrap').classList.add('hidden');
    document.getElementById('reviewerEditBanner').classList.add('hidden');
    reviewerRawText          = '';
    pendingFile              = null;
    pendingFileIsPdf         = false;
    editingExistingFilePath  = null;
    editingOriginalContent   = '';
}

function cancelEditReviewer(){ clearReviewer(); }

// ── MANAGE TAB — BULK SELECT HELPERS ─────────
function onItemCheck(type, id, checked) {
    const set = type === 'reviewer' ? selectedReviewers : selectedQuizzes;
    if (checked) set.add(id); else set.delete(id);
    updateBulkUI(type);
}

function toggleSelectAll(type, checked) {
    const cls = '.' + type + '-check';
    const set = type === 'reviewer' ? selectedReviewers : selectedQuizzes;
    document.querySelectorAll(cls).forEach(cb => {
        cb.checked = checked;
        const id = parseInt(cb.value, 10);
        if (checked) set.add(id); else set.delete(id);
    });
    updateBulkUI(type);
}

function updateBulkUI(type) {
    const set = type === 'reviewer' ? selectedReviewers : selectedQuizzes;
    const countEl = document.getElementById(type + 'SelCount');
    const btn = document.getElementById('btnBulkDelete' + (type === 'reviewer' ? 'Reviewer' : 'Quiz'));
    if (!countEl || !btn) return;

    countEl.textContent = set.size;
    btn.disabled = set.size === 0;
    btn.classList.toggle('enabled', set.size > 0);

    const selectAllCb = document.getElementById(type + 'SelectAll');
    const totalCbs = document.querySelectorAll('.' + type + '-check').length;
    if (selectAllCb) {
        selectAllCb.checked = totalCbs > 0 && set.size === totalCbs;
        selectAllCb.indeterminate = set.size > 0 && set.size < totalCbs;
    }
}

async function bulkDelete(type) {
    const set = type === 'reviewer' ? selectedReviewers : selectedQuizzes;
    if (!set.size) return;
    const label = type === 'reviewer' ? 'reviewer' : 'quiz';
    const ids = [...set];
    if (!confirm(`Delete ${ids.length} selected ${label}${ids.length > 1 ? 's' : ''}? This cannot be undone.`)) return;

    const btn = document.getElementById('btnBulkDelete' + (type === 'reviewer' ? 'Reviewer' : 'Quiz'));
    btn.disabled = true;

    let failCount = 0;
    for (const id of ids) {
        try {
            await phpDelete(type === 'reviewer' ? 'delete_reviewer' : 'delete_quiz', id);
        } catch (e) {
            failCount++;
        }
    }
    set.clear();

    if (failCount) showToast(`${ids.length - failCount} deleted, ${failCount} failed.`, 'error');
    else showToast(`${ids.length} ${label}${ids.length > 1 ? 's' : ''} deleted.`);

    renderManage();
}

// ── MANAGE TAB ───────────────────────────────────────────────────────────────
async function renderManage() {
    // Fresh load — reset any previous selection so counts/checkboxes start clean.
    selectedReviewers.clear();
    selectedQuizzes.clear();

    const rl = document.getElementById('reviewerList');
    rl.innerHTML = '<div class="loading-row"><div class="spinner"></div> Loading reviewers…</div>';
    try {
        const res = await phpGet('reviewers');
        rl.innerHTML = res.data.length
            ? res.data.map(r => {
                return `<div class="content-item">
                    <label class="ci-checkbox-wrap">
                        <input type="checkbox" class="reviewer-check" value="${r.id}" onchange="onItemCheck('reviewer', ${r.id}, this.checked)">
                    </label>
                    <div class="ci-icon reviewer"><i class="fas fa-file-alt"></i></div>
                    <div class="ci-info">
                        <div class="ci-name">${esc(r.field)}</div>
                        <div class="ci-title-row">${esc(r.title)}${r.file_path ? ' <i class="fas fa-file-pdf" style="color:#ef4444;font-size:11px;" title="Has a PDF file"></i>' : ''}</div>
                        <div class="ci-meta">${esc((r.content||'').substring(0,80))}…</div>
                    </div>
                    <div class="ci-actions">
                        <button class="btn-sm view" onclick="viewReviewerContent(${r.id})"><i class="fas fa-eye"></i> View</button>
                        <button class="btn-sm edit" onclick="editReviewer(${r.id})"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn-sm delete" onclick="deleteReviewer(${r.id},'${esc(r.title)}')"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`;
            }).join('')
            : '<div class="empty-state"><i class="fas fa-file-alt"></i>No reviewers yet.</div>';
    } catch {
        rl.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i>Failed to load.</div>';
    }
    updateBulkUI('reviewer');

    const ql = document.getElementById('quizList');
    ql.innerHTML = '<div class="loading-row"><div class="spinner"></div> Loading quizzes…</div>';
    try {
        const res = await phpGet('quizzes');
        ql.innerHTML = res.data.length
            ? res.data.map(q => {
                return `<div class="content-item">
                    <label class="ci-checkbox-wrap">
                        <input type="checkbox" class="quiz-check" value="${q.id}" onchange="onItemCheck('quiz', ${q.id}, this.checked)">
                    </label>
                    <div class="ci-icon quiz"><i class="fas fa-question-circle"></i></div>
                    <div class="ci-info">
                        <div class="ci-name">${esc(q.field)}</div>
                        <div class="ci-title-row">${esc(q.title)}</div>
                        <div class="ci-meta">${q.question_count} question${q.question_count!=1?'s':''}</div>
                    </div>
                    <div class="ci-actions">
                        <button class="btn-sm view" onclick="viewQuizContent(${q.id})"><i class="fas fa-eye"></i> View</button>
                        <button class="btn-sm edit" onclick="editQuiz(${q.id})"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn-sm delete" onclick="deleteQuiz(${q.id},'${esc(q.title)}')"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`;
            }).join('')
            : '<div class="empty-state"><i class="fas fa-question-circle"></i>No quizzes yet.</div>';
    } catch {
        ql.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i>Failed to load.</div>';
    }
    updateBulkUI('quiz');
}

async function editReviewer(id) {
    switchTab('reviewer');
    showToast('Loading reviewer…');
    try {
        const res = await phpGet('reviewer_detail', {id});
        const r   = res.data;
        document.getElementById('reviewerEditId').value    = r.id;
        document.getElementById('reviewerTitle').value     = r.title;
        document.getElementById('reviewerField').value     = r.field;

        editingExistingFilePath = r.file_path || null;
        editingOriginalContent  = r.content || '';
        pendingFile             = null;
        pendingFileIsPdf        = false;

        if (r.file_path) {
            document.getElementById('reviewerExtractedWrap').classList.remove('hidden');
            document.getElementById('reviewerSourceTag').innerHTML =
                `<span class="source-tag pdf"><i class="fas fa-file-pdf"></i> Existing PDF — upload a new file above to replace it</span>`;
            const preview = document.getElementById('reviewerPreviewDisplay');
            preview.classList.remove('reviewer-body');
            preview.innerHTML = `<div class="pdf-inline-container"></div>`;
            const container = preview.querySelector('.pdf-inline-container');
            renderPdfPagesInline(r.file_path, container, 'Current PDF file');
            reviewerRawText = '';
        } else {
            showReviewerText(r.content, 'manual');
        }

        document.getElementById('reviewerEditBanner').classList.remove('hidden');
        window.scrollTo({top:0, behavior:'smooth'});
        showToast('Reviewer loaded — make edits and save.');
    } catch(e) {
        showToast('Could not load reviewer: ' + e.message, 'error');
    }
}

async function deleteReviewer(id,title){if(!confirm(`Delete reviewer "${title}"?`))return;try{await phpDelete('delete_reviewer',id);showToast('Reviewer deleted.');renderManage();}catch(e){showToast('Delete failed: '+e.message,'error');}}

async function editQuiz(id){
    switchTab('import');
    showToast('Loading quiz…');
    try{
        const res=await phpGet('quiz_detail',{id});
        const q=res.data;
        document.getElementById('quizEditId').value=q.id;
        document.getElementById('quizTitle').value=q.title;
        document.getElementById('quizField').value=q.field;
        pendingQ=q.questions.map(row=>{
            let opts=row.options_json;
            if(typeof opts==='string'){try{opts=JSON.parse(opts);}catch{opts=[];}}
            if(!Array.isArray(opts))opts=Object.values(opts);
            return{q:row.question_text||'',a:opts,c:Number(row.correct_index)||0,explanation:row.explanation||''};
        });
        renderQBuilder();
        document.getElementById('quizEditBanner').classList.remove('hidden');
        window.scrollTo({top:0,behavior:'smooth'});
        showToast('Quiz loaded — make edits and save.');
    }catch(e){
        showToast('Could not load quiz: '+e.message,'error');
    }
}

async function deleteQuiz(id,title){if(!confirm(`Delete quiz "${title}"?`))return;try{await phpDelete('delete_quiz',id);showToast('Quiz deleted.');renderManage();}catch(e){showToast('Delete failed: '+e.message,'error');}}

// ── UTILITIES ────
function setFormBusy(btnId,statusId,msgId,busy,msg='',label=''){const btn=document.getElementById(btnId);btn.disabled=busy;document.getElementById(statusId).classList.toggle('hidden',!busy);if(busy){btn.innerHTML=`<div class="spinner" style="width:14px;height:14px;border-width:2px;border-color:rgba(255,255,255,.3);border-top-color:#fff"></div> Saving…`;document.getElementById(msgId).textContent=msg;}else{btn.innerHTML=`<i class="fas fa-save"></i> ${label}`;}}
function showToast(msg,type='success'){const t=document.getElementById('toast');t.style.background=type==='error'?'#dc3545':'var(--green-dark)';document.getElementById('toastIcon').className=type==='error'?'fas fa-exclamation-circle':'fas fa-check-circle';document.getElementById('toastMsg').innerText=msg;t.classList.add('show');clearTimeout(t._t);t._t=setTimeout(()=>t.classList.remove('show'),3200);}

addQuestion();


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