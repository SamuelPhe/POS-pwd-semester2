<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function posTransactionResponse(bool $success, string $message, array $extra = [], int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit();
}

function posMoneyToCents($value): int {
    if (!is_numeric($value)) {
        throw new DomainException('Nominal pembayaran tidak valid.');
    }

    $amount = (float) $value;
    if (!is_finite($amount) || $amount < 0) {
        throw new DomainException('Nominal pembayaran tidak boleh negatif.');
    }

    return (int) round($amount * 100);
}

function posCentsToDecimal(int $cents): string {
    return number_format($cents / 100, 2, '.', '');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    posTransactionResponse(false, 'Metode request tidak valid.', [], 405);
}

if (!isset($_SESSION['user_id'], $_SESSION['username']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'kasir'], true)) {
    posTransactionResponse(false, 'Sesi login tidak valid. Silakan masuk kembali.', [], 403);
}

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
    posTransactionResponse(false, 'Data transaksi tidak valid.', [], 400);
}

$customerType = $data['customer_type'] ?? 'walkin';
if (!in_array($customerType, ['walkin', 'member'], true)) {
    posTransactionResponse(false, 'Tipe pelanggan tidak valid.', [], 422);
}

$discount = filter_var($data['discount'] ?? 0, FILTER_VALIDATE_INT);
if ($discount === false || $discount < 0 || $discount > 100) {
    posTransactionResponse(false, 'Diskon harus berada pada rentang 0 sampai 100 persen.', [], 422);
}

$rawItems = $data['items'] ?? null;
if (!is_array($rawItems) || count($rawItems) === 0) {
    posTransactionResponse(false, 'Tidak ada produk dalam transaksi.', [], 422);
}

// Gabungkan product ID yang sama dan jangan mempercayai harga dari browser.
$quantities = [];
foreach ($rawItems as $item) {
    if (!is_array($item)) {
        posTransactionResponse(false, 'Format item transaksi tidak valid.', [], 422);
    }

    $productId = filter_var($item['productId'] ?? null, FILTER_VALIDATE_INT);
    $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);

    if ($productId === false || $productId < 1 || $quantity === false || $quantity < 1) {
        posTransactionResponse(false, 'Produk dan jumlah item harus valid.', [], 422);
    }

    $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;
}

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    require_once 'database.php';

    $paidCents = posMoneyToCents($data['paid_amount'] ?? null);
    $userId = (int) $_SESSION['user_id'];

    $conn->begin_transaction();

    $productQuery = $conn->prepare(
        "SELECT p.product_id, p.name, p.price, i.stock
         FROM products p
         INNER JOIN inventory i ON i.product_id = p.product_id
         WHERE p.product_id = ?
         FOR UPDATE"
    );

    $products = [];
    $subtotalCents = 0;

    foreach ($quantities as $productId => $quantity) {
        $productQuery->bind_param('i', $productId);
        $productQuery->execute();
        $product = $productQuery->get_result()->fetch_assoc();

        if (!$product) {
            throw new DomainException("Produk ID $productId tidak ditemukan atau belum memiliki data stok.");
        }

        $availableStock = (int) $product['stock'];
        if ($availableStock < $quantity) {
            throw new DomainException(
                "Stok {$product['name']} tidak cukup. Tersedia: $availableStock, diminta: $quantity."
            );
        }

        $unitPriceCents = posMoneyToCents($product['price']);
        $lineTotalCents = $unitPriceCents * $quantity;

        $products[] = [
            'product_id' => (int) $product['product_id'],
            'name' => $product['name'],
            'quantity' => $quantity,
            'unit_price_cents' => $unitPriceCents,
            'line_total_cents' => $lineTotalCents
        ];

        $subtotalCents += $lineTotalCents;
    }

    $productQuery->close();

    $discountCents = (int) round($subtotalCents * $discount / 100);
    $totalCents = max(0, $subtotalCents - $discountCents);

    if ($paidCents < $totalCents) {
        throw new DomainException(
            'Uang yang diberikan belum mencukupi. Total transaksi Rp ' .
            number_format($totalCents / 100, 0, ',', '.') . '.'
        );
    }

    $changeCents = $paidCents - $totalCents;

    $totalAmount = posCentsToDecimal($totalCents);
    $paidAmount = posCentsToDecimal($paidCents);
    $changeAmount = posCentsToDecimal($changeCents);

    $insertTransaction = $conn->prepare(
        "INSERT INTO transactions
            (user_id, customer_type, total_amount, paid_amount, change_amount, discount, created_at, status)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), 'completed')"
    );
    $insertTransaction->bind_param(
        'issssi',
        $userId,
        $customerType,
        $totalAmount,
        $paidAmount,
        $changeAmount,
        $discount
    );
    $insertTransaction->execute();
    $transactionId = (int) $conn->insert_id;
    $insertTransaction->close();

    $insertItem = $conn->prepare(
        "INSERT INTO transaction_items
            (transaction_id, product_id, quantity, price_per_item, price)
         VALUES (?, ?, ?, ?, ?)"
    );

    $updateStock = $conn->prepare(
        "UPDATE inventory
         SET stock = stock - ?
         WHERE product_id = ? AND stock >= ?"
    );

    foreach ($products as $product) {
        $productId = $product['product_id'];
        $quantity = $product['quantity'];
        $unitPrice = posCentsToDecimal($product['unit_price_cents']);
        $lineTotal = posCentsToDecimal($product['line_total_cents']);

        // price menyimpan subtotal baris; price_per_item menyimpan harga satuan.
        $insertItem->bind_param(
            'iiiss',
            $transactionId,
            $productId,
            $quantity,
            $unitPrice,
            $lineTotal
        );
        $insertItem->execute();

        $updateStock->bind_param('iii', $quantity, $productId, $quantity);
        $updateStock->execute();

        if ($updateStock->affected_rows !== 1) {
            throw new DomainException("Stok {$product['name']} berubah. Silakan ulangi transaksi.");
        }
    }

    $insertItem->close();
    $updateStock->close();

    $conn->commit();

    posTransactionResponse(true, 'Transaksi berhasil.', [
        'transaction_id' => $transactionId,
        'subtotal_amount' => posCentsToDecimal($subtotalCents),
        'discount_amount' => posCentsToDecimal($discountCents),
        'total_amount' => $totalAmount,
        'change_amount' => $changeAmount
    ]);
} catch (DomainException $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
    }
    posTransactionResponse(false, $e->getMessage(), [], 422);
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
    }
    error_log('POS transaction processing failed: ' . $e->getMessage());
    posTransactionResponse(false, 'Terjadi kesalahan saat memproses transaksi. Periksa log server.', [], 500);
}
?>