<?php
session_start(); 

require_once 'database.php';

if (!isset($_SESSION['username']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'kasir')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Akses tidak sah.']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data received.']);
        exit();
    }

    $customer_type = $data['customer_type'] ?? 'walkin';
    $discount = (int)($data['discount'] ?? 0);
    $total_amount = (float)($data['total_amount'] ?? 0);
    $paid_amount = (float)($data['paid_amount'] ?? 0);   
    $items = $data['items'] ?? [];

    $change_amount = $paid_amount - $total_amount;

    $conn->begin_transaction();

    try {
        if ($paid_amount < $total_amount) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => "Uang yang diberikan pelanggan (Rp " . number_format($paid_amount, 0, ',', '.') . ") tidak mencukupi. Total transaksi Rp " . number_format($total_amount, 0, ',', '.') . "."]);
            exit();
        }

        $stmt_insert_transaction = $conn->prepare("INSERT INTO transactions (user_id, customer_type, total_amount, paid_amount, change_amount, discount, created_at, status) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'completed')");
        
        if (!$stmt_insert_transaction) {
            throw new Exception("Gagal menyiapkan query insert transaksi: " . $conn->error);
        }
        
        $user_id = $_SESSION['user_id'] ?? null; 
        if (is_null($user_id)) {
            throw new Exception("User ID tidak ditemukan dalam sesi. Pastikan user login.");
        }

        $stmt_insert_transaction->bind_param("isdddi", $user_id, $customer_type, $total_amount, $paid_amount, $change_amount, $discount); 
        $stmt_insert_transaction->execute();
        $transaction_id = $conn->insert_id;
        $stmt_insert_transaction->close();

        if (empty($items)) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Tidak ada produk dalam transaksi.']);
            exit();
        }

        foreach ($items as $item) {
            $product_id = (int)$item['productId'];
            $quantity = (int)$item['quantity'];
            $price_per_item = (float)$item['price'];

            if ($quantity <= 0) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => "Kuantitas produk (ID: {$product_id}) harus lebih besar dari nol."]);
                exit();
            }

            $stmt_get_stock = $conn->prepare("SELECT stock FROM inventory WHERE product_id = ?");
            if (!$stmt_get_stock) {
                throw new Exception("Gagal menyiapkan query ambil stok: " . $conn->error);
            }
            $stmt_get_stock->bind_param("i", $product_id);
            $stmt_get_stock->execute();
            $result_get_stock = $stmt_get_stock->get_result();
            $stock_row = $result_get_stock->fetch_assoc();
            $stmt_get_stock->close();

            if (!$stock_row || $stock_row['stock'] < $quantity) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => "Stok tidak cukup untuk produk " . ($item['productName'] ?? "ID {$product_id}") . ". Stok tersedia: " . ($stock_row ? $stock_row['stock'] : 0) . ", Diminta: {$quantity}."]);
                exit();
            }

            $stmt_update_stock = $conn->prepare("UPDATE inventory SET stock = stock - ? WHERE product_id = ?");
            if (!$stmt_update_stock) {
                throw new Exception("Gagal menyiapkan query update stok: " . $conn->error);
            }
            $stmt_update_stock->bind_param("ii", $quantity, $product_id);
            $stmt_update_stock->execute();
            $stmt_update_stock->close();

            $stmt_insert_item = $conn->prepare("INSERT INTO transaction_items (transaction_id, product_id, quantity, price_per_item) VALUES (?, ?, ?, ?)");
            if (!$stmt_insert_item) {
                throw new Exception("Gagal menyiapkan query insert item transaksi: " . $conn->error);
            }
            $stmt_insert_item->bind_param("iiid", $transaction_id, $product_id, $quantity, $price_per_item);
            $stmt_insert_item->execute();
            $stmt_insert_item->close();
        }

        $conn->commit(); 

        echo json_encode(['success' => true, 'message' => "Transaksi berhasil!", 'change_amount' => $change_amount]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error processing transaction: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => "Terjadi kesalahan saat memproses transaksi: " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid.']);
}
?>