<?php
session_start();
require_once 'database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: login.php'); exit(); }
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
if ($username === '' || $password === '') {
    header('Location: login.php?error=Username%20dan%20password%20harus%20diisi'); exit();
}
$stmt = $conn->prepare('SELECT user_id, username, password, role FROM users WHERE username = ? LIMIT 1');
if (!$stmt) {
    error_log('POS login: failed to prepare user lookup.');
    header('Location: login.php?error=Terjadi%20kesalahan.%20Silakan%20coba%20lagi'); exit();
}
$stmt->bind_param('s', $username);
if (!$stmt->execute()) {
    error_log('POS login: user lookup failed.'); $stmt->close();
    header('Location: login.php?error=Terjadi%20kesalahan.%20Silakan%20coba%20lagi'); exit();
}
$result = $stmt->get_result(); $user = $result ? $result->fetch_assoc() : null; $stmt->close();
if (!$user || !password_verify($password, $user['password'])) {
    header('Location: login.php?error=Username%20atau%20password%20salah'); exit();
}
if (!in_array($user['role'], ['admin', 'kasir'], true)) {
    header('Location: login.php?error=Role%20akun%20tidak%20valid'); exit();
}
session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
header('Location: ' . ($user['role'] === 'admin' ? 'beranda_admin.php' : 'beranda_kasir.php'));
exit();
