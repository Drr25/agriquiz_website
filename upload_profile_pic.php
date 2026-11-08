<?php
session_start();
require 'db_conn.php';

// ── Auth guard ──
if (empty($_SESSION['id'])) {
    header('Location: profile.php');
    exit;
}

$userId = (int)$_SESSION['id'];

function back_with_error(string $msg): void {
    $_SESSION['profile_flash'] = 'error:' . $msg;
    header('Location: profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['profile_pic'])) {
    back_with_error('No file received.');
}

$file = $_FILES['profile_pic'];

// ── Basic upload error check ──
if ($file['error'] !== UPLOAD_ERR_OK) {
    back_with_error('Upload failed. Please try again.');
}

// ── Size limit: 3MB ──
$maxBytes = 3 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    back_with_error('Image is too large. Max size is 3MB.');
}

// ── Validate real image type (don't trust the extension) ──
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
$mime = mime_content_type($file['tmp_name']);
if (!isset($allowed[$mime])) {
    back_with_error('Only JPG, PNG, or WEBP images are allowed.');
}
$ext = $allowed[$mime];

// ── Make sure upload directory exists ──
$uploadDir = __DIR__ . '/uploads/profile_pics/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ── Remove any previous picture(s) for this user (any extension) ──
foreach (glob($uploadDir . $userId . '.*') as $oldFile) {
    @unlink($oldFile);
}

// ── Save new file ──
$filename = $userId . '_' . time() . '.' . $ext;
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    back_with_error('Could not save the image. Please try again.');
}

// ── Update DB with relative path ──
$relativePath = 'uploads/profile_pics/' . $filename;
$stmt = mysqli_prepare($conn, "UPDATE users SET profile_pic = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'si', $relativePath, $userId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($conn);

$_SESSION['profile_flash'] = 'Profile picture updated successfully.';
header('Location: profile.php');
exit;