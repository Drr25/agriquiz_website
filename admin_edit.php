<?php
require 'db_conn.php';
session_start();

$id = $_GET['id'];
$logoUrl = "asset/agriquiz-logo.png";
$result = mysqli_query($conn, "SELECT * FROM users WHERE id=$id");
$row = mysqli_fetch_assoc($result);

if(isset($_POST['update'])){

    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $new_password = $_POST['new_password'];

    if(!empty($new_password)){
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET
                username='$username',
                email='$email',
                password='$hashed'
                WHERE id=$id";
    } else {
        $sql = "UPDATE users SET
                username='$username',
                email='$email'
                WHERE id=$id";
    }

    mysqli_query($conn, $sql);
    header("Location: admin_student_list.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student — AGRIQUIZ</title>
    <link rel="icon" href="<?= $logoUrl ?>">
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin_edit.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .password-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrap input {
            width: 100%;
            padding: 10px 44px 10px 38px;
            box-sizing: border-box;
            border: 1px solid #c8e8d6;
            border-radius: 8px;
            font-size: 14px;
            color: #1a2e22;
            background: #f0faf5;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .password-wrap input:focus {
            border-color: #198754;
            box-shadow: 0 0 0 3px rgba(25,135,84,.12);
            background: #fff;
        }
        .password-wrap .lock-icon {
            position: absolute;
            left: 14px;
            font-size: 14px;
            color: #6b9980;
            pointer-events: none;
            z-index: 1;
        }
        .toggle-pw {
            position: absolute;
            right: 38px;
            left: 385px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6b9980;
            font-size: 14px;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            line-height: 1;
        }
        .toggle-pw:hover {
            color: #198754;
        }
        small{
            font-size:11px; 
            color:#6b9980; 
            margin-top:4px; 
            display:block;
        }
    </style>
</head>
<body>

    <div class="form-card">

        <!-- HEADER -->
        <div class="form-card-header">
            <div class="header-avatar">
                <i class="fas fa-user-edit"></i>
            </div>
            <div>
                <h3>Edit Student</h3>
                <p>Update student information below</p>
            </div>
        </div>

        <div class="form-body">

            <!-- STUDENT ID PILL -->
            <div class="id-pill">
                <i class="fas fa-fingerprint"></i> Student ID: <?= htmlspecialchars($id) ?>
            </div>

            <form method="POST">

                <!-- USERNAME -->
                <div class="field-group">
                    <label><i class="fas fa-user" style="color:#198754; margin-right:5px;"></i>Full Name</label>
                    <div class="input-wrap">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username"
                               value="<?= htmlspecialchars($row['username']) ?>"
                               placeholder="Enter username" required>
                    </div>
                </div>

                <!-- EMAIL -->
                <div class="field-group">
                    <label><i class="fas fa-envelope" style="color:#198754; margin-right:5px;"></i>Email Address</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email"
                               value="<?= htmlspecialchars($row['email']) ?>"
                               placeholder="Enter email address" required>
                    </div>
                </div>

                <!-- NEW PASSWORD -->
                <div class="field-group">
                    <label>
                        <i class="fas fa-lock" style="color:#198754; margin-right:5px;"></i>New Password
                        <span style="font-size:10px; color:#6b9980; font-weight:500; text-transform:none; letter-spacing:0;">(leave blank to keep current)</span>
                    </label>
                    <div class="input-wrap password-wrap">
                        <i class="fas fa-lock lock-icon"></i>
                        <input type="password" name="new_password" id="new_password"
                               placeholder="Enter new password" minlength="6">
                        <button type="button" class="toggle-pw" onclick="togglePassword()" title="Show/hide password">
                            <i class="fas fa-eye" id="pwIcon"></i>
                        </button>
                    </div>
                    <small>Minimum 6 characters.</small>
                </div>

                <div class="form-divider"></div>

                <!-- ACTIONS -->
                <div class="form-actions">
                    <a href="admin_student_list.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <button type="submit" name="update" class="btn-update">
                        <i class="fas fa-floppy-disk"></i> Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>

<script>
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
</script>

</body>
</html>