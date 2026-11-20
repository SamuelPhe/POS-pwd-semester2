<?php
session_start();
require_once 'database.php'; 


if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'kasir') {
    $_SESSION['message'] = "Akses tidak sah.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php"); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $amount = (int)($_POST['amount'] ?? 0);
    $notes = $_POST['notes'] ?? '';
    

    if (empty($product_id) || empty($action)) {
        $_SESSION['message'] = "Permintaan tidak valid: ID produk atau tindakan tidak ditemukan.";
        $_SESSION['message_type'] = "error";
        header("Location: beranda_kasir.php");
        exit();
    }

    $conn->begin_transaction();

    try {
        $stmt_get_stock = $conn->prepare("SELECT stock FROM inventory WHERE product_id = ?");
        if (!$stmt_get_stock) {
            throw new Exception("Gagal menyiapkan query ambil stok: " . $conn->error);
        }
        $stmt_get_stock->bind_param("i", $product_id);
        $stmt_get_stock->execute();
        $result_get_stock = $stmt_get_stock->get_result();
        $stock_row = $result_get_stock->fetch_assoc();
        $stmt_get_stock->close();

        if (!$stock_row) {
            $conn->rollback();
            $_SESSION['message'] = "Error: Produk (ID: {$product_id}) tidak ditemukan di inventaris. Harap pastikan produk terdaftar.";
            $_SESSION['message_type'] = "error";
            header("Location: beranda_kasir.php"); 
            exit();
        }

        $current_stock = $stock_row['stock'];
        $new_stock = $current_stock; 

        switch ($action) {
            case 'add':
                if ($amount <= 0) {
                    $conn->rollback();
                    $_SESSION['message'] = "Error: Jumlah untuk penambahan stok harus lebih besar dari nol.";
                    $_SESSION['message_type'] = "error";
                    header("Location: beranda_kasir.php");
                    exit();
                }
                $new_stock = $current_stock + $amount;
                break;
            case 'reduce':
                if (($current_stock - $amount) < 0) {
                    $conn->rollback();
                    $_SESSION['message'] = "Error: Pengurangan stok akan menghasilkan stok negatif. Stok saat ini: {$current_stock}, Pengurangan: {$amount}.";
                    $_SESSION['message_type'] = "error";
                    header("Location: beranda_kasir.php");
                    exit();
                }
                $new_stock = $current_stock - $amount;
                break;
            case 'set':
                if ($amount < 0) {
                    $conn->rollback();
                    $_SESSION['message'] = "Error: Tidak dapat mengatur stok ke nilai negatif ({$amount}).";
                    $_SESSION['message_type'] = "error";
                    header("Location: beranda_kasir.php");
                    exit();
                }
                $new_stock = $amount;
                break;
            default:
                $conn->rollback();
                $_SESSION['message'] = "Error: Tindakan stok tidak valid.";
                $_SESSION['message_type'] = "error";
                header("Location: beranda_kasir.php"); 
                exit();
        }
        
       
        $stmt_update_stock = $conn->prepare("UPDATE inventory SET stock=? WHERE product_id=?");
        if (!$stmt_update_stock) {
            throw new Exception("Gagal menyiapkan query update stok: " . $conn->error);
        }
        $stmt_update_stock->bind_param("ii", $new_stock, $product_id);
        $stmt_update_stock->execute();
        $stmt_update_stock->close();
        
        $conn->commit();
        
        $_SESSION['message'] = "Stok berhasil diperbarui (Stok baru: {$new_stock}).";
        $_SESSION['message_type'] = "success";
        header("Location: beranda_kasir.php"); 
        exit();

    } catch (Exception $e) {
        $conn->rollback(); 
        error_log("Error in process_stock.php: " . $e->getMessage()); 
        $_SESSION['message'] = "Terjadi kesalahan: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: beranda_kasir.php"); 
        exit();
    }
}
?>