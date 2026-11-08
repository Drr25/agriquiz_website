<?php
define('DEBUG', false);

error_reporting(E_ALL);
ini_set('display_errors', 0);

set_exception_handler(function(Throwable $e) {
    error_log('[save_content.php] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    header('Content-Type: application/json');
    $resp = ['success' => false, 'message' => 'Server error'];
    if (DEBUG) { $resp['debug'] = $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine(); }
    echo json_encode($resp);
    exit;
});
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log('[save_content.php] ' . $e['message'] . ' in ' . $e['file'] . ':' . $e['line']);
        http_response_code(500);
        header('Content-Type: application/json');
        $resp = ['success' => false, 'message' => 'Server error'];
        if (DEBUG) { $resp['debug'] = $e['message'] . ' @ ' . basename($e['file']) . ':' . $e['line']; }
        echo json_encode($resp);
    }
});

session_start();
header('Content-Type: application/json');

/* TODO: palitan ng actual frontend origin niyo instead of '*'
   kapag alam niyo na yung production domain. */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('DB_HOST', 'sql306.infinityfree.com');
define('DB_NAME', 'if0_42619600_agriquiz');
define('DB_USER', 'if0_42619600');
define('DB_PASS', 'MnbvcxzQwerty');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
    return $pdo;
}

/*RESPONSE HELPERS*/
function ok(array $d = []): void {
    echo json_encode(array_merge(['success' => true], $d));
    exit;
}
function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

/* =========================================================
   AUTH HELPERS
========================================================= */
function currentUser(): ?array {
    if (!isset($_SESSION['email'])) return null;
    return [
        'email' => $_SESSION['email'],
        'role'  => $_SESSION['role'] ?? 'student',
    ];
}
function requireLogin(): array {
    $u = currentUser();
    if (!$u) fail('Unauthorized. Please log in.', 401);
    return $u;
}
function requireAdmin(): array {
    $u = requireLogin();
    if ($u['role'] !== 'admin') fail('Forbidden: admin access required.', 403);
    return $u;
}

/*AUTO-CREATE / MIGRATE TABLES*/
function ensureTables(): void {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS reviewers (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        title       VARCHAR(255) NOT NULL,
        field       VARCHAR(255) NOT NULL,
        content     LONGTEXT NOT NULL DEFAULT '',
        file_path   VARCHAR(500) DEFAULT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS quizzes (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        title       VARCHAR(255) NOT NULL,
        field       VARCHAR(255) NOT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS quiz_questions (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        quiz_id         INT NOT NULL,
        question_text   TEXT NOT NULL,
        options_json    JSON NOT NULL,
        correct_index   TINYINT NOT NULL DEFAULT 0,
        explanation     TEXT DEFAULT NULL,
        sort_order      INT DEFAULT 0,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* Add explanation column to tables created before this column existed */
    $has = $db->query("SHOW COLUMNS FROM quiz_questions LIKE 'explanation'")->fetchAll();
    if (empty($has)) {
        $db->exec("ALTER TABLE quiz_questions ADD COLUMN explanation TEXT DEFAULT NULL AFTER correct_index");
    }

    /* CREATE quiz_attempts TABLE for analytics */
    $db->exec("CREATE TABLE IF NOT EXISTS quiz_attempts (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        student_email   VARCHAR(255) NOT NULL,
        quiz_id         INT NOT NULL,
        field           VARCHAR(255) NOT NULL,
        quiz_title      VARCHAR(255),
        score           INT NOT NULL,
        total           INT NOT NULL,
        pct             TINYINT DEFAULT 0,
        answers_json    JSON,
        taken_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_student_email (student_email),
        INDEX idx_quiz_id (quiz_id),
        INDEX idx_field (field),
        INDEX idx_taken_at (taken_at),
        INDEX idx_student_taken (student_email, taken_at),
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* CREATE student_performance TABLE for caching */
    $db->exec("CREATE TABLE IF NOT EXISTS student_performance (
        id                  INT AUTO_INCREMENT PRIMARY KEY,
        student_email       VARCHAR(255) NOT NULL UNIQUE,
        total_attempts      INT DEFAULT 0,
        overall_avg         DECIMAL(5,2),
        best_score          DECIMAL(5,2),
        fields_attempted    INT DEFAULT 0,
        last_attempt_date   TIMESTAMP NULL,
        last_updated        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (student_email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* CREATE field_performance TABLE for per-field analytics */
    $db->exec("CREATE TABLE IF NOT EXISTS field_performance (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        student_email   VARCHAR(255) NOT NULL,
        field           VARCHAR(255) NOT NULL,
        attempts        INT DEFAULT 0,
        avg_pct         DECIMAL(5,2),
        best_pct        DECIMAL(5,2),
        total_correct   INT DEFAULT 0,
        total_questions INT DEFAULT 0,
        last_taken      TIMESTAMP NULL,
        last_updated    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_student_field (student_email, field),
        INDEX idx_student (student_email),
        INDEX idx_last_taken (last_taken)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
ensureTables();

/* ROUTING */
$action = trim($_GET['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

/* ---- reviewers | get_reviewers — read-only, requires login ---- */
if (in_array($action, ['reviewers', 'get_reviewers']) && $method === 'GET') {
    requireLogin();
    $db    = getDB();
    $field = trim($_GET['field'] ?? '');
    if ($field) {
        $st = $db->prepare('SELECT id, title, field, content, file_path, created_at, updated_at FROM reviewers WHERE field = ? ORDER BY id DESC');
        $st->execute([$field]);
    } else {
        $st = $db->query('SELECT id, title, field, content, file_path, created_at, updated_at FROM reviewers ORDER BY id DESC');
    }
    ok(['data' => $st->fetchAll()]);
}

/* ---- reviewer_detail — read-only, requires login ---- */
elseif ($action === 'reviewer_detail' && $method === 'GET') {
    requireLogin();
    $db = getDB();
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) fail('Missing id');
    $st = $db->prepare('SELECT id, title, field, content, file_path, created_at, updated_at FROM reviewers WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) fail('Reviewer not found', 404);
    ok(['data' => $row]);
}

/* ---- get_content — read-only, requires login ---- */
elseif ($action === 'get_content' && $method === 'GET') {
    requireLogin();
    $db    = getDB();
    $id    = (int)($_GET['id']    ?? 0);
    $field = trim($_GET['field'] ?? '');

    if ($id) {
        $st = $db->prepare('SELECT id, title, field, content, file_path, created_at, updated_at FROM reviewers WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) fail('Reviewer not found', 404);
        ok(['data' => $row]);
    } elseif ($field) {
        $st = $db->prepare('SELECT id, title, field, content, file_path, created_at FROM reviewers WHERE field = ? ORDER BY id DESC');
        $st->execute([$field]);
        ok(['data' => $st->fetchAll()]);
    } else {
        $st = $db->query('SELECT id, title, field, LEFT(content, 200) AS content_preview, file_path, created_at FROM reviewers ORDER BY id DESC');
        ok(['data' => $st->fetchAll()]);
    }
}

/* ---- save_reviewer — ADMIN ONLY ----
   Handles TWO content types now:
   1) application/json  → text-only reviewer (no file), old behavior.
   2) multipart/form-data → title/field/id/content PLUS an optional PDF file,
      saved and linked in ONE request instead of two separate round-trips.
      This is what removes the "double wait" for large PDFs. */
elseif ($action === 'save_reviewer' && $method === 'POST') {
    requireAdmin();
    $db = getDB();

    // Detect multipart in two ways: the Content-Type header (works on most
    // servers) AND a fallback check on $_POST/$_FILES actually having data.
    // Some shared hosts (InfinityFree included) don't reliably expose
    // $_SERVER['CONTENT_TYPE'], and php://input often comes back EMPTY for
    // multipart requests anyway (PHP already consumed it into $_POST/$_FILES).
    // Relying on the header alone made this fall through to the JSON branch,
    // read an empty php://input, and fail with "title is required" even
    // though the title was sent — the fallback below fixes that.
    $contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
    $isMultipart = stripos($contentType, 'multipart/form-data') === 0
                || !empty($_POST)
                || !empty($_FILES);

    if ($isMultipart) {
        $title   = trim($_POST['title']   ?? '');
        $field   = trim($_POST['field']   ?? '');
        $content = trim($_POST['content'] ?? '');
        $id      = (int)($_POST['id']     ?? 0);
    } else {
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $title   = trim($body['title']     ?? '');
        $field   = trim($body['field']     ?? '');
        $content = trim($body['content']   ?? '');
        $id      = (int)($body['id']       ?? 0);
    }

    if (!$title)   fail('title is required');
    if (!$field)   fail('field is required');

    $file_path = null; // stays null unless a new PDF is uploaded in this same request
    $hasFile   = $isMultipart && !empty($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE;

    if ($hasFile) {
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $phpUploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload_max_filesize.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds form MAX_FILE_SIZE.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded — check your connection and try again.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on server.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'Upload stopped by a PHP extension.',
            ];
            fail($phpUploadErrors[$_FILES['file']['error']] ?? 'Unknown upload error.');
        }

        $file = $_FILES['file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') fail('Only PDF files are allowed.');

        // NOTE: no artificial max-size check here anymore — the app itself
        // doesn't cap file size. The PHP server (upload_max_filesize /
        // post_max_size in php.ini) still enforces its own hard limit; if a
        // file exceeds that, PHP rejects it before this code even runs, and
        // $_FILES['file']['error'] comes back as UPLOAD_ERR_INI_SIZE or
        // UPLOAD_ERR_FORM_SIZE above — that's a server config limit, not
        // something this script can lift.

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if ($mime !== 'application/pdf') fail('Uploaded file is not a valid PDF.');

        $uploadDir = __DIR__ . '/../uploads/reviewers/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            fail('Could not create upload directory on server.', 500);
        }

        // FIX: filename used to be 'reviewer_' . ($id ?: 'new') . '_' . time() . '.pdf'.
        // For every BRAND NEW reviewer $id is 0 (not assigned yet), so the name
        // collapsed to 'reviewer_new_<second>.pdf' — and time() only has
        // ONE-SECOND resolution. Two separate new-reviewer uploads landing in
        // the same second (quick back-to-back saves, a slightly-too-fast
        // double click, a retried request) produced the EXACT SAME filename,
        // so the second upload silently overwrote the first file on disk.
        // Both reviewers still had distinct database rows/titles, but both
        // rows pointed at the same (now-overwritten) physical file — which is
        // exactly the "first one gets replaced by whatever I import next"
        // symptom. uniqid('', true) gives microsecond + random-entropy
        // uniqueness, so this can no longer collide even for rapid uploads.
        $unique   = str_replace('.', '', uniqid('', true));
        $safeName = 'reviewer_' . ($id ?: 'new') . '_' . $unique . '.pdf';
        $destPath = $uploadDir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            fail('Failed to save uploaded file on server.', 500);
        }

        // Store a root-relative URL (leading "/") derived from this script's
        // own request path, so it resolves correctly from ANY page on the
        // site, admin or student side.
        //   SCRIPT_NAME example: /admin/api/save_content.php
        //   -> dirname()       : /admin/api
        //   -> strip "/api"    : /admin              (this script's parent)
        //   -> file_path       : /admin/uploads/reviewers/xxx.pdf
        $scriptDir  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'); // .../api
        $rootRelDir = preg_replace('#/api$#', '', $scriptDir); // strip trailing /api
        $file_path  = $rootRelDir . '/uploads/reviewers/' . $safeName;

        if (!$content) $content = 'PDF Reviewer: ' . $file['name'];
    } elseif ($isMultipart) {
        // Multipart request but no new file — editing an existing reviewer's
        // title/field only, keep whatever file_path it already has.
        if ($id) {
            $keep = $db->prepare('SELECT file_path, content FROM reviewers WHERE id = ?');
            $keep->execute([$id]);
            $row = $keep->fetch();
            if ($row) {
                $file_path = $row['file_path'];
                if (!$content) $content = $row['content'];
            }
        }
    }

    if (!$content) fail('content is required');

    if ($id) {
        $db->prepare('UPDATE reviewers SET title = ?, field = ?, content = ?, file_path = ?, updated_at = NOW() WHERE id = ?')
           ->execute([$title, $field, $content, $file_path, $id]);
        ok(['id' => $id, 'message' => 'Reviewer updated', 'file_path' => $file_path]);
    } else {
        $db->prepare('INSERT INTO reviewers (title, field, content, file_path) VALUES (?, ?, ?, ?)')
           ->execute([$title, $field, $content, $file_path]);
        ok(['id' => (int)$db->lastInsertId(), 'message' => 'Reviewer saved', 'file_path' => $file_path]);
    }
}

/* ---- delete_reviewer — ADMIN ONLY ---- */
elseif ($action === 'delete_reviewer' && $method === 'DELETE') {
    requireAdmin();
    $db = getDB();
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) fail('Missing id');
    $db->prepare('DELETE FROM reviewers WHERE id = ?')->execute([$id]);
    ok(['message' => 'Reviewer deleted']);
}

/* ---- quizzes | get_quizzes — read-only, requires login ---- */
elseif (in_array($action, ['quizzes', 'get_quizzes']) && $method === 'GET') {
    requireLogin();
    $db    = getDB();
    $field = trim($_GET['field'] ?? '');

    $sql = 'SELECT q.*, COUNT(qq.id) AS question_count FROM quizzes q LEFT JOIN quiz_questions qq ON qq.quiz_id = q.id';
    if ($field) { $sql .= ' WHERE q.field = ?'; }
    $sql .= ' GROUP BY q.id ORDER BY q.id DESC';

    $st = $db->prepare($sql);
    $field ? $st->execute([$field]) : $st->execute();
    ok(['data' => $st->fetchAll()]);
}

/* ---- quiz_detail — read-only, requires login ---- */
elseif ($action === 'quiz_detail' && $method === 'GET') {
    requireLogin();
    $db = getDB();
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) fail('Missing id');

    $st = $db->prepare('SELECT * FROM quizzes WHERE id = ?');
    $st->execute([$id]);
    $quiz = $st->fetch();
    if (!$quiz) fail('Quiz not found', 404);

    $st2 = $db->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY sort_order, id');
    $st2->execute([$id]);
    $rows = $st2->fetchAll();

    foreach ($rows as &$q) {
        if (is_string($q['options_json'])) {
            $dec = json_decode($q['options_json'], true);
            $q['options_json'] = is_array($dec) ? $dec : [];
        }
        $q['explanation'] = $q['explanation'] ?? '';
    }
    unset($q);

    $quiz['questions'] = $rows;
    ok(['data' => $quiz]);
}

/* ---- save_quiz — ADMIN ONLY ---- */
elseif ($action === 'save_quiz' && $method === 'POST') {
    requireAdmin();
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!is_array($body)) {
        fail('Invalid JSON body.');
    }

    $title     = trim($body['title']     ?? '');
    $field     = trim($body['field']     ?? '');
    $questions = $body['questions']      ?? [];
    $id        = isset($body['id']) ? (int)$body['id'] : 0;

    if (!$title)            fail('Quiz title is required');
    if (!$field)            fail('Learning field is required');
    if (!count($questions)) fail('At least one question is required');

    // STRICT SERVER-SIDE VALIDATION — bago pa man tumira sa DB, i-verify muna
    // na kumpleto ang bawat tanong: may text, at LAHAT ng choices ay puno
    // (hindi lang >=2 na puno). Kailangan ito kahit naka-validate na rin sa
    // frontend, dahil kayang i-bypass ng direct API call (Postman/curl/etc.)
    // ang mga check na nasa JS lang.
    foreach ($questions as $i => $q) {
        $qNum  = $i + 1;
        $qText = trim($q['q'] ?? '');
        $opts  = $q['a'] ?? [];

        if (!$qText) {
            fail("Question {$qNum}: question text is required.");
        }
        if (!is_array($opts) || count($opts) < 2) {
            fail("Question {$qNum}: needs at least 2 choices.");
        }
        foreach ($opts as $oi => $opt) {
            if (trim((string)$opt) === '') {
                $letter = ['A','B','C','D'][$oi] ?? ($oi + 1);
                fail("Question {$qNum}: Choice {$letter} cannot be empty.");
            }
        }
    }

    $db = getDB();
    $db->beginTransaction();

    try {
        if ($id) {
            $db->prepare('UPDATE quizzes SET title = ?, field = ? WHERE id = ?')
               ->execute([$title, $field, $id]);
            $db->prepare('DELETE FROM quiz_questions WHERE quiz_id = ?')->execute([$id]);
        } else {
            $db->prepare('INSERT INTO quizzes (title, field) VALUES (?, ?)')
               ->execute([$title, $field]);
            $id = (int)$db->lastInsertId();
        }

        $ins = $db->prepare(
            'INSERT INTO quiz_questions
                (quiz_id, question_text, options_json, correct_index, explanation, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $saved = 0;
        foreach ($questions as $i => $q) {
            $qText = trim($q['q'] ?? '');
            $opts  = $q['a']     ?? [];
            $corr  = (int)($q['c'] ?? 0);
            $expl  = trim($q['explanation'] ?? '');

            // Naka-validate na lahat sa itaas (may text, lahat ng choices
            // puno); ito na lang ang huling safety-net guard bago mag-insert.
            if (!$qText || !is_array($opts) || count($opts) < 2) continue;

            $ins->execute([
                $id,
                $qText,
                json_encode($opts, JSON_UNESCAPED_UNICODE),
                $corr,
                $expl ?: null,
                $i
            ]);
            $saved++;
        }

        if ($saved === 0) {
            $db->rollBack();
            fail('No valid questions were saved. Each question needs text and at least 2 options.');
        }

        $db->commit();
        ok(['id' => $id, 'message' => "Quiz saved with {$saved} question(s)"]);

    } catch (PDOException $e) {
        $db->rollBack();
        error_log('[save_content.php save_quiz] ' . $e->getMessage());
        fail('Database error while saving quiz.', 500);
    }
}

/* ---- delete_quiz — ADMIN ONLY ---- */
elseif ($action === 'delete_quiz' && $method === 'DELETE') {
    requireAdmin();
    $db = getDB();
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) fail('Missing id');
    $db->prepare('DELETE FROM quizzes WHERE id = ?')->execute([$id]);
    ok(['message' => 'Quiz deleted']);
}

/* ---- save_attempt — requires login; student_email galing session ---- */
elseif ($action === 'save_attempt' && $method === 'POST') {
    $user = requireLogin();
    $db   = getDB();
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $student_email = $user['email'];
    $quiz_id    = (int)($body['quiz_id']       ?? 0);
    $field      = trim($body['field']          ?? '');
    $quiz_title = trim($body['quiz_title']     ?? 'Quiz');
    $score      = (int)($body['score']         ?? 0);
    $total      = (int)($body['total']         ?? 0);
    $answers    = $body['answers']             ?? [];

    if (!$quiz_id) fail('quiz_id is required');
    if (!$field)   fail('field is required');
    if ($total <= 0) fail('total must be greater than 0');

    $pct = (int)round(($score / $total) * 100);

    try {
        $db->prepare(
            'INSERT INTO quiz_attempts
                (student_email, quiz_id, field, quiz_title, score, total, pct, answers_json)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $student_email, $quiz_id, $field, $quiz_title,
            $score, $total, $pct,
            json_encode($answers, JSON_UNESCAPED_UNICODE)
        ]);

        $db->prepare(
            'INSERT INTO student_performance (student_email, total_attempts, overall_avg, best_score, fields_attempted, last_attempt_date)
             SELECT ?, COUNT(*), ROUND(AVG(pct), 2), ROUND(MAX(pct), 2), COUNT(DISTINCT field), MAX(taken_at)
             FROM quiz_attempts WHERE student_email = ?
             ON DUPLICATE KEY UPDATE
                total_attempts    = (SELECT COUNT(*) FROM quiz_attempts WHERE student_email = ?),
                overall_avg       = (SELECT ROUND(AVG(pct), 2) FROM quiz_attempts WHERE student_email = ?),
                best_score        = (SELECT ROUND(MAX(pct), 2) FROM quiz_attempts WHERE student_email = ?),
                fields_attempted  = (SELECT COUNT(DISTINCT field) FROM quiz_attempts WHERE student_email = ?),
                last_attempt_date = (SELECT MAX(taken_at) FROM quiz_attempts WHERE student_email = ?),
                last_updated      = CURRENT_TIMESTAMP'
        )->execute(array_fill(0, 7, $student_email));

        ok(['message' => 'Attempt saved successfully', 'pct' => $pct, 'score' => $score, 'total' => $total]);

    } catch (PDOException $e) {
        error_log('[save_content.php save_attempt] ' . $e->getMessage());
        fail('Failed to save attempt.', 500);
    }
}

/* ---- analytics — ADMIN ONLY ---- */
elseif ($action === 'analytics' && $method === 'GET') {
    requireAdmin();
    $db    = getDB();
    $field = trim($_GET['field'] ?? '');
    $email = trim($_GET['email'] ?? '');

    $where  = '1=1';
    $params = [];
    if ($field) { $where .= ' AND a.field = ?'; $params[] = $field; }
    if ($email) { $where .= ' AND a.student_email = ?'; $params[] = $email; }

    $st = $db->prepare("
        SELECT COUNT(*) AS total_attempts,
               COUNT(DISTINCT a.student_email) AS unique_students,
               COUNT(DISTINCT a.quiz_id) AS quizzes_taken,
               ROUND(AVG(a.pct), 1) AS avg_pct,
               MAX(a.pct) AS max_pct, MIN(a.pct) AS min_pct,
               SUM(CASE WHEN a.pct >= 80 THEN 1 ELSE 0 END) AS passed,
               SUM(CASE WHEN a.pct <  80 THEN 1 ELSE 0 END) AS failed
        FROM quiz_attempts a WHERE $where");
    $st->execute($params);
    $overview = $st->fetch();

    $st2 = $db->prepare("
        SELECT a.field, COUNT(*) AS attempts,
               COUNT(DISTINCT a.student_email) AS students,
               ROUND(AVG(a.pct), 1) AS avg_pct,
               SUM(CASE WHEN a.pct >= 80 THEN 1 ELSE 0 END) AS passed
        FROM quiz_attempts a GROUP BY a.field ORDER BY attempts DESC");
    $st2->execute();
    $by_field = $st2->fetchAll();

    $st3 = $db->prepare("
        SELECT a.student_email, COUNT(*) AS attempts,
               ROUND(AVG(a.pct), 1) AS avg_pct, MAX(a.pct) AS best_pct, MAX(a.taken_at) AS last_attempt
        FROM quiz_attempts a WHERE $where
        GROUP BY a.student_email ORDER BY avg_pct DESC LIMIT 50");
    $st3->execute($params);
    $leaderboard = $st3->fetchAll();

    $st4 = $db->prepare("
        SELECT a.id, a.student_email, a.field, a.quiz_title,
               a.score, a.total, a.pct, a.taken_at
        FROM quiz_attempts a WHERE $where ORDER BY a.taken_at DESC LIMIT 20");
    $st4->execute($params);
    $recent = $st4->fetchAll();

    $st5 = $db->prepare("
        SELECT DATE(a.taken_at) AS day, COUNT(*) AS attempts, ROUND(AVG(a.pct), 1) AS avg_pct
        FROM quiz_attempts a
        WHERE a.taken_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(a.taken_at) ORDER BY day ASC");
    $st5->execute();
    $trend = $st5->fetchAll();

    ok(['overview' => $overview, 'by_field' => $by_field, 'leaderboard' => $leaderboard, 'recent' => $recent, 'trend' => $trend]);
}

else {
    fail("Unknown action: '{$action}' | method: '{$method}'", 404);
}