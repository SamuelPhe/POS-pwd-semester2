<?php
session_start();
require_once 'database.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); exit('Forbidden'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: beranda_admin.php'); exit(); }
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
if (!$productId || $productId < 1) {
    $_SESSION['message'] = 'ID produk tidak valid.'; $_SESSION['message_type'] = 'error';
    header('Location: beranda_admin.php'); exit();
}
try {
    $conn->begin_transaction();
    $stmtInventory = $conn->prepare('DELETE FROM inventory WHERE product_id = ?');
    if (!$stmtInventory) throw new RuntimeException('Failed to prepare inventory deletion.');
    $stmtInventory->bind_param('i', $productId); $stmtInventory->execute(); $stmtInventory->close();
    $stmtProduct = $conn->prepare('DELETE FROM products WHERE product_id = ?');
    if (!$stmtProduct) throw new RuntimeException('Failed to prepare product deletion.');
    $stmtProduct->bind_param('i', $productId); $stmtProduct->execute(); $deleted = $stmtProduct->affected_rows; $stmtProduct->close();
    if ($deleted !== 1) throw new RuntimeException('Product was not found or is still referenced.');
    $conn->commit(); $_SESSION['message'] = 'Produk berhasil dihapus.'; $_SESSION['message_type'] = 'success';
} catch (Throwable $e) {
    $conn->rollback(); error_log('POS product deletion failed: ' . $e->getMessage());
    $_SESSION['message'] = 'Produk tidak dapat dihapus. Periksa apakah produk masih digunakan dalam transaksi.';
    $_SESSION['message_type'] = 'error';
}
header('Location: beranda_admin.php'); exit();
