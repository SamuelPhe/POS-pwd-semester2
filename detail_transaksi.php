<?php
session_start();
require_once 'database.php'; 

if (!isset($_SESSION['username']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'kasir')) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['transaction_id']) || !is_numeric($_GET['transaction_id'])) {
    header("Location: beranda_admin.php?error=Transaction ID tidak valid.");
    exit();
}

$transactionId = (int)$_GET['transaction_id'];

$transactionDetails = null;
$transactionItems = [];

try {
    $stmt = $conn->prepare("SELECT t.*, u.username as cashier_name 
                            FROM transactions t 
                            LEFT JOIN users u ON t.user_id = u.user_id 
                            WHERE t.transaction_id = ?");
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactionDetails = $result->fetch_assoc();

    if (!$transactionDetails) {
        header("Location: beranda_admin.php?error=Transaksi tidak ditemukan.");
        exit();
    }

    $stmt = $conn->prepare("SELECT ti.*, p.name as product_name 
                            FROM transaction_items ti 
                            JOIN products p ON ti.product_id = p.product_id 
                            WHERE ti.transaction_id = ?");
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactionItems = $result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Error fetching transaction details: " . $e->getMessage());
    header("Location: beranda_admin.php?error=Terjadi kesalahan saat memuat detail transaksi.");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi #<?= htmlspecialchars($transactionDetails['transaction_id']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-4">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md mt-10">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Detail Transaksi #<?= htmlspecialchars($transactionDetails['transaction_id']) ?></h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <p class="text-gray-600"><strong>Tanggal:</strong> <?= htmlspecialchars(date('d F Y H:i:s', strtotime($transactionDetails['created_at']))) ?></p>
                <p class="text-gray-600"><strong>Kasir:</strong> <?= htmlspecialchars($transactionDetails['cashier_name']) ?>
                <p class="text-gray-600"><strong>Tipe Pelanggan:</strong> <?= htmlspecialchars($transactionDetails['customer_type']) ?></p>
            </div>
            <div>
                <p class="text-xl font-bold text-gray-800">Subtotal: Rp <?= number_format($transactionDetails['total_amount'] + ($transactionDetails['total_amount'] * $transactionDetails['discount'] / (100 - $transactionDetails['discount'])), 0, ',', '.') ?></p>
                <p class="text-xl font-bold text-gray-800">Diskon (<?= htmlspecialchars($transactionDetails['discount']) ?>%): Rp <?= number_format($transactionDetails['total_amount'] * $transactionDetails['discount'] / (100 - $transactionDetails['discount']), 0, ',', '.') ?></p>
                <p class="text-xl font-bold text-gray-600">Total Pembelian: Rp <?= number_format($transactionDetails['total_amount'], 0, ',', '.') ?></p><hr>
                <p class="text-xl font-bold text-gray-600 mt-2">Dibayar: Rp <?= number_format($transactionDetails['paid_amount'], 0, ',', '.') ?></p>
                <p class="text-xl font-bold text-gray-700">Kembalian: Rp <?= number_format($transactionDetails['change_amount'], 0, ',', '.') ?></p>
            </div>
        </div>

        <h2 class="text-xl font-semibold mb-4 text-gray-700">Item Transaksi:</h2>
        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Satuan</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal Item</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($transactionItems)): ?>
                        <?php foreach ($transactionItems as $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($item['product_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($item['quantity']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">Rp <?= number_format($item['price_per_item'], 0, ',', '.') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">Rp <?= number_format($item['quantity'] * $item['price_per_item'], 0, ',', '.') ?></td>
                                
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">Tidak ada item untuk transaksi ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-right mt-6">
            <a href="javascript:history.back()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </div>
</body>
</html>