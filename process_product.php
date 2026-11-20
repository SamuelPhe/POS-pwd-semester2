<?php
session_start();
require_once 'database.php';


if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['message'] = "Akses tidak sah.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php"); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $price = (float)($_POST['price'] ?? 0);
    $stock_input = (int)($_POST['stock'] ?? 0); 
    $description = $_POST['description'] ?? '';
    $date_added = $_POST['date_added'] ?? date('Y-m-d'); 

    $conn->begin_transaction();

    try {
        if ($product_id) {
            $stmt_product_update = $conn->prepare("UPDATE products SET name=?, category=?, price=?, description=? WHERE product_id=?");
            if (!$stmt_product_update) {
                throw new Exception("Gagal menyiapkan query update produk: " . $conn->error);
            }
            $stmt_product_update->bind_param("ssdsi", $name, $category, $price, $description, $product_id);
            $stmt_product_update->execute();
            $stmt_product_update->close();
            
            if (isset($_POST['stock'])) {
                if ($stock_input < 0) {
                    $conn->rollback();
                    $_SESSION['message'] = "Error: Stok tidak bisa diatur ke nilai negatif ({$stock_input}).";
                    $_SESSION['message_type'] = "error";
                    header("Location: beranda_admin.php"); 
                    exit();
                }
                
                $stmt_check_inventory = $conn->prepare("SELECT product_id FROM inventory WHERE product_id = ?");
                if (!$stmt_check_inventory) {
                    throw new Exception("Gagal menyiapkan query cek inventaris: " . $conn->error);
                }
                $stmt_check_inventory->bind_param("i", $product_id);
                $stmt_check_inventory->execute();
                $inventory_exists = $stmt_check_inventory->get_result()->fetch_assoc();
                $stmt_check_inventory->close();

                if ($inventory_exists) {
                    $stmt_inventory_update = $conn->prepare("UPDATE inventory SET stock=? WHERE product_id=?");
                    if (!$stmt_inventory_update) {
                        throw new Exception("Gagal menyiapkan query update inventaris: " . $conn->error);
                    }
                    $stmt_inventory_update->bind_param("ii", $stock_input, $product_id);
                    $stmt_inventory_update->execute();
                    $stmt_inventory_update->close();
                } else {
                    $stmt_inventory_insert = $conn->prepare("INSERT INTO inventory (product_id, stock) VALUES (?, ?)");
                    if (!$stmt_inventory_insert) {
                        throw new Exception("Gagal menyiapkan query insert inventaris (kasus update): " . $conn->error);
                    }
                    $stmt_inventory_insert->bind_param("ii", $product_id, $stock_input);
                    $stmt_inventory_insert->execute();
                    $stmt_inventory_insert->close();
                }
            }
            
            $_SESSION['message'] = "Produk berhasil diperbarui.";

        } else {
            if ($stock_input < 0) {
                $conn->rollback();
                $_SESSION['message'] = "Error: Stok awal untuk produk baru tidak bisa negatif ({$stock_input}).";
                $_SESSION['message_type'] = "error";
                header("Location: beranda_admin.php"); 
                exit();
            }

            $stmt_product_insert = $conn->prepare("INSERT INTO products (name, category, price, description, date_added) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt_product_insert) {
                throw new Exception("Gagal menyiapkan query insert produk: " . $conn->error);
            }
            $stmt_product_insert->bind_param("ssdss", $name, $category, $price, $description, $date_added);
            $stmt_product_insert->execute();
            $product_id = $conn->insert_id; 
            $stmt_product_insert->close();
            
            $stmt_inventory_insert = $conn->prepare("INSERT INTO inventory (product_id, stock) VALUES (?, ?)");
            if (!$stmt_inventory_insert) {
                throw new Exception("Gagal menyiapkan query insert inventaris: " . $conn->error);
            }
            $stmt_inventory_insert->bind_param("ii", $product_id, $stock_input);
            $stmt_inventory_insert->execute();
            $stmt_inventory_insert->close();
            
            $_SESSION['message'] = "Produk berhasil ditambahkan.";
        }
        
        $conn->commit(); 
        $_SESSION['message_type'] = "success";
        
    } catch (Exception $e) {
        $conn->rollback(); 
        error_log("Error in process_product.php: " . $e->getMessage()); 
        $_SESSION['message'] = "Terjadi kesalahan: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    header("Location: beranda_admin.php"); 
    exit();
}
?>