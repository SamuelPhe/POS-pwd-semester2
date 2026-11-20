<?php
session_start();
require_once 'database.php';


if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); 
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transaction_id'])) {
    $transactionId = (int)$_POST['transaction_id']; 

    $conn->begin_transaction();

    try {
        $stmtGetItems = $conn->prepare("SELECT product_id, quantity FROM transaction_items WHERE transaction_id = ?");
        if (!$stmtGetItems) {
            throw new Exception("Prepare failed for getting transaction items: " . $conn->error);
        }
        $stmtGetItems->bind_param("i", $transactionId);
        $stmtGetItems->execute();
        $resultItems = $stmtGetItems->get_result();
        $itemsToRestore = $resultItems->fetch_all(MYSQLI_ASSOC);
        $stmtGetItems->close();

        foreach ($itemsToRestore as $item) {
            $stmtUpdateStock = $conn->prepare("UPDATE inventory SET stock = stock + ? WHERE product_id = ?");
            if (!$stmtUpdateStock) {
                throw new Exception("Prepare failed for updating inventory stock: " . $conn->error);
            }
            $stmtUpdateStock->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmtUpdateStock->execute();
            $stmtUpdateStock->close();
        }

        $stmtDeleteItems = $conn->prepare("DELETE FROM transaction_items WHERE transaction_id = ?");
        if (!$stmtDeleteItems) {
            throw new Exception("Prepare failed for deleting transaction items: " . $conn->error);
        }
        $stmtDeleteItems->bind_param("i", $transactionId);
        $stmtDeleteItems->execute();
        $stmtDeleteItems->close();

        $stmtDeleteTransaction = $conn->prepare("DELETE FROM transactions WHERE transaction_id = ?");
        if (!$stmtDeleteTransaction) {
            throw new Exception("Prepare failed for deleting transaction: " . $conn->error);
        }
        $stmtDeleteTransaction->bind_param("i", $transactionId);
        $stmtDeleteTransaction->execute();
        $stmtDeleteTransaction->close();

        $conn->commit();

        $_SESSION['message'] = "Transaksi #{$transactionId} berhasil dihapus dan stok dikembalikan.";
        $_SESSION['message_type'] = "success";
        header("Location: beranda_admin.php");
        exit();

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Error deleting transaction and restoring stock: " . $e->getMessage());
        $_SESSION['message'] = "Error: Gagal menghapus transaksi #{$transactionId} atau mengembalikan stok. " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: beranda_admin.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("General error processing transaction deletion: " . $e->getMessage());
        $_SESSION['message'] = "Terjadi kesalahan tak terduga saat menghapus transaksi: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: beranda_admin.php");
        exit();
    }
} else {
    $_SESSION['message'] = "Akses tidak valid atau ID transaksi tidak ditemukan.";
    $_SESSION['message_type'] = "error";
    header("Location: beranda_admin.php");
    exit();
}
?>