<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'kasir'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden.']);
    exit();
}
$transactionId = filter_input(INPUT_GET, 'transaction_id', FILTER_VALIDATE_INT);
if (!$transactionId || $transactionId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid transaction ID is required.']);
    exit();
}

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
        echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
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

    $response = [
        'success' => true,
        'transaction' => $transactionDetails,
        'items' => $transactionItems
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    error_log('POS transaction detail lookup failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to fetch transaction details.']);
}