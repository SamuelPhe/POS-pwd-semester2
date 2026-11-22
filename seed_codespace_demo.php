<?php
/*
 * Codespaces-only demo seeder.
 * Run from the workspace terminal with:
 *   curl -X POST http://web/seed_codespace_demo.php
 *
 * This endpoint is intentionally restricted to the repository's Codespaces
 * MariaDB service. It must not be used to seed a production database.
 */
header('Content-Type: application/json; charset=utf-8');

function seedResponse(bool $success, string $message, array $details = [], int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $details), JSON_UNESCAPED_UNICODE);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    seedResponse(false, 'Gunakan HTTP POST dari terminal Codespaces.', [], 405);
}

if (PHP_SAPI !== 'cli' && ($_SERVER['HTTP_HOST'] ?? '') !== 'web') {
    http_response_code(404);
    exit();
}

if (getenv('DB_HOST') !== 'database' ||
    getenv('DB_USERNAME') !== 'pos_local' ||
    getenv('DB_DATABASE') !== 'pos') {
    http_response_code(404);
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../database.php';

$accounts = [
    ['admin', 'admin', '123456'],
    ['kasir', 'kasir', '123456']
];

$demoProducts = [
    ['Air Mineral 600ml', 'drink', '4000.00', 'Air mineral kemasan 600ml.', 30],
    ['Teh Botol 350ml', 'drink', '6000.00', 'Teh siap minum kemasan 350ml.', 24],
    ['Kopi Susu 240ml', 'drink', '8500.00', 'Kopi susu siap minum.', 18],
    ['Mi Instan Goreng', 'food', '3500.00', 'Mi instan rasa goreng.', 40],
    ['Roti Cokelat', 'food', '7000.00', 'Roti isi cokelat.', 15],
    ['Keripik Kentang', 'snack', '9500.00', 'Keripik kentang kemasan.', 12],
    ['Biskuit Cokelat', 'snack', '6500.00', 'Biskuit rasa cokelat.', 20],
    ['Gula Pasir 1kg', 'other', '16000.00', 'Gula pasir kemasan 1 kilogram.', 10]
];

$createdProducts = 0;
$createdInventory = 0;

try {
    $conn->begin_transaction();

    $accountQuery = $conn->prepare(
        "INSERT INTO users (username, password, role)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role)"
    );

    foreach ($accounts as [$username, $role, $plainPassword]) {
        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $accountQuery->bind_param('sss', $username, $passwordHash, $role);
        $accountQuery->execute();
    }
    $accountQuery->close();

    $findProduct = $conn->prepare(
        "SELECT product_id FROM products WHERE name = ? ORDER BY product_id ASC LIMIT 1"
    );
    $insertProduct = $conn->prepare(
        "INSERT INTO products (name, category, price, description, date_added)
         VALUES (?, ?, ?, ?, CURDATE())"
    );
    $findInventory = $conn->prepare(
        "SELECT inventory_id FROM inventory WHERE product_id = ? LIMIT 1"
    );
    $insertInventory = $conn->prepare(
        "INSERT INTO inventory (product_id, stock, min_stock, location)
         VALUES (?, ?, 5, 'Demo Store')"
    );

    foreach ($demoProducts as [$name, $category, $price, $description, $stock]) {
        $findProduct->bind_param('s', $name);
        $findProduct->execute();
        $existingProduct = $findProduct->get_result()->fetch_assoc();

        if ($existingProduct) {
            $productId = (int) $existingProduct['product_id'];
        } else {
            $insertProduct->bind_param('ssss', $name, $category, $price, $description);
            $insertProduct->execute();
            $productId = (int) $conn->insert_id;
            $createdProducts++;
        }

        $findInventory->bind_param('i', $productId);
        $findInventory->execute();
        $existingInventory = $findInventory->get_result()->fetch_assoc();

        if (!$existingInventory) {
            $insertInventory->bind_param('ii', $productId, $stock);
            $insertInventory->execute();
            $createdInventory++;
        }
    }

    $findProduct->close();
    $insertProduct->close();
    $findInventory->close();
    $insertInventory->close();

    $conn->commit();

    seedResponse(true, 'Data demo POS berhasil disiapkan.', [
        'accounts' => [
            ['username' => 'admin', 'role' => 'admin', 'password' => '123456'],
            ['username' => 'kasir', 'role' => 'kasir', 'password' => '123456']
        ],
        'products_created' => $createdProducts,
        'inventory_created' => $createdInventory
    ]);
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    error_log('POS Codespaces demo seed failed: ' . $e->getMessage());
    seedResponse(false, 'Seeder demo gagal. Periksa log server.', [], 500);
}
?>