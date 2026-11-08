<?php
session_set_cookie_params([
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require "db_conn.php";

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (!isset($_SESSION['started'])) {
    header("Location: getstarted.php");
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$logoUrl  = "asset/agriquiz-logo_login.png";
$logoUrl1 = "asset/agriquiz-logo.png";

$error_message = "";
$maxAttempts   = 5;
$lockSeconds   = 300;

if (isset($_POST["login"])) {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $agreed   = isset($_POST['agree_terms']);
    $token    = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error_message = "Session expired. Please refresh the page and try again.";
    } elseif (!empty($_SESSION['lock_until']) && time() < $_SESSION['lock_until']) {
        $mins = ceil(($_SESSION['lock_until'] - time()) / 60);
        $error_message = "Too many failed attempts. Try again in {$mins} minute(s).";
    } elseif (empty($email) || empty($password)) {
        $error_message = "Please fill in all fields.";
    } elseif (!$agreed) {
        $error_message = "You must agree to the Data Privacy Agreement before signing in.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        $passwordMatch = false;

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Accounts created with password_hash()
                $passwordMatch = true;
            } elseif (hash_equals((string)$user['password'], $password)) {
                // Existing plain-text passwords
                $passwordMatch = true;

                // Auto-upgrade plain text to hashed on successful login
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $upd    = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->bind_param("si", $hashed, $user['id']);
                $upd->execute();
                $upd->close();
            }
        }

        if ($passwordMatch) {
            session_regenerate_id(true);
            unset($_SESSION['login_attempts'], $_SESSION['lock_until']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['email']      = $user['email'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['id']         = $user['id'];

            header("Location: " . ($user['role'] === 'admin' ? "admin_dashboard.php" : "student_dashboard.php"));
            exit();
        } else {
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            if ($_SESSION['login_attempts'] >= $maxAttempts) {
                $_SESSION['lock_until']     = time() + $lockSeconds;
                $_SESSION['login_attempts'] = 0;
                $error_message = "Too many failed attempts. Try again in 5 minute(s).";
            } else {
                $error_message = "Invalid email or password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>AGRIQUIZ – Login</title>
  <link rel="icon" href="<?= $logoUrl1 ?>">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <link rel="stylesheet" href="css/index.css">

  <style>
    /* ---------- Data-privacy consent checkbox ---------- */
    .consent-field {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      margin: 14px 0 18px;
      font-size: 13.5px;
      line-height: 1.4;
    }
    .consent-field input[type="checkbox"] {
      margin-top: 3px;
      width: 16px;
      height: 16px;
      flex-shrink: 0;
      cursor: pointer;
    }
    .consent-field label {
      cursor: pointer;
    }
    .agreement-link {
      color: #2f7d3f;
      font-weight: 600;
      text-decoration: underline;
      cursor: pointer;
      background: none;
      border: none;
      padding: 0;
      font: inherit;
    }
    .agreement-link:hover {
      color: #1f5c2b;
    }

    /* ---------- Data Privacy Agreement modal ---------- */
    .dpa-modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .dpa-modal-overlay.active {
      display: flex;
    }
    .dpa-modal-box {
      background: #fff;
      width: 100%;
      max-width: 640px;
      max-height: 85vh;
      border-radius: 12px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
    }
    .dpa-modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      border-bottom: 1px solid #e5e7eb;
      background: #f8faf8;
    }
    .dpa-modal-header h3 {
      margin: 0;
      font-size: 16px;
      font-weight: 700;
      color: #1f2937;
    }
    .dpa-modal-close {
      background: none;
      border: none;
      font-size: 18px;
      cursor: pointer;
      color: #6b7280;
      line-height: 1;
      padding: 4px;
    }
    .dpa-modal-close:hover {
      color: #1f2937;
    }
    .dpa-modal-body {
      flex: 1;
      overflow: hidden;
    }
    .dpa-modal-body iframe {
      width: 100%;
      height: 100%;
      min-height: 60vh;
      border: none;
    }
    .brand-header .brand-icon {
        background: transparent !important;
        overflow: hidden;
        width: 100px !important;
        height: 90px !important;
        margin: 0 auto 12px !important;
    }
    .brand-header .brand-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
        display: block;
    }
  </style>
</head>
<body>

  <div class="login-wrap">
    <div class="brand-header">
      <div class="brand-icon">
        <img src="<?= $logoUrl ?>" alt="AgriQuiz logo">
      </div>
      <div class="brand-sub">Agriculturist Licensure Learning Platform</div>
    </div>

    <!-- ============ Login card ============ -->
    <div class="login-card">
      <div class="card-title">Sign in to your account</div>

      <?php if ($error_message): ?>
        <div class="alert-error">
          <i class="fas fa-circle-exclamation"></i>
          <?= htmlspecialchars($error_message) ?>
        </div>
      <?php endif; ?>

      <form method="POST" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="field">
          <label for="email">Email address</label>
          <div class="input-wrap">
            <i class="fas fa-envelope input-icon"></i>
            <input type="email" id="email" name="email" class="form-control"
              placeholder="you@example.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
              required autocomplete="email">
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" id="password" name="password" class="form-control"
              placeholder="Enter your password" required autocomplete="current-password">
            <button type="button" class="toggle-pw" onclick="togglePassword()">
              <i class="fas fa-eye" id="toggleIcon"></i>
            </button>
          </div>
        </div>

        <div class="consent-field">
          <input type="checkbox" id="agree_terms" name="agree_terms" value="1"
            <?= isset($_POST['agree_terms']) ? 'checked' : '' ?> required>
          <label for="agree_terms">
            I have read and agree to AgriQuiz's
            <button type="button" class="agreement-link" onclick="openDpaModal()">Data Privacy Agreement</button>,
            in accordance with the Data Privacy Act of 2012 (RA 10173).
          </label>
        </div>

        <button type="submit" name="login" class="btn-login">
          <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
        <a href="getstarted.php" class="btn-cancel">
          <i class="fas fa-arrow-left"></i> Back
        </a>

      </form>
    </div>

  </div>

  <!-- ============ Data Privacy Agreement modal ============ -->
  <div class="dpa-modal-overlay" id="dpaModal">
    <div class="dpa-modal-box">
      <div class="dpa-modal-header">
        <h3><i class="fas fa-shield-halved"></i> Data Privacy Agreement</h3>
        <button type="button" class="dpa-modal-close" onclick="closeDpaModal()">&times;</button>
      </div>
      <div class="dpa-modal-body">
        <iframe id="dpaFrame" src="about:blank" sandbox="allow-same-origin" title="AgriQuiz Data Privacy Agreement"></iframe>
      </div>
    </div>
  </div>

  <script>
    function togglePassword() {
      const pw   = document.getElementById('password');
      const icon = document.getElementById('toggleIcon');
      const show = pw.type === 'password';
      pw.type        = show ? 'text' : 'password';
      icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
    }

    function openDpaModal() {
      const modal = document.getElementById('dpaModal');
      const frame = document.getElementById('dpaFrame');
      if (frame.src === 'about:blank' || !frame.src.includes('data_privacy_agreement.php')) {
        frame.src = 'data_privacy_agreement.php';
      }
      modal.classList.add('active');
    }

    function closeDpaModal() {
      document.getElementById('dpaModal').classList.remove('active');
    }

    // Close modal when clicking outside the box
    document.getElementById('dpaModal').addEventListener('click', function (e) {
      if (e.target === this) closeDpaModal();
    });
  </script>

</body>
</html>