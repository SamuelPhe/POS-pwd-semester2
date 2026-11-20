<?php
session_start();
require_once 'database.php'; 

if (isset($_SESSION['user_id'])) {
    error_log("beranda_kasir.php: User ID found in session: " . $_SESSION['user_id'], 0);
} else {
    error_log("beranda_kasir.php: User ID NOT found in session.", 0);
}

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'kasir') {
    header("Location: login.php");
    exit();
}


$query_products = "SELECT p.*, i.stock, DATE_FORMAT(p.date_added, '%d %b %Y') as formatted_date 
                  FROM products p 
                  LEFT JOIN inventory i ON p.product_id = i.product_id";
$products = $conn->query($query_products)->fetch_all(MYSQLI_ASSOC);

$query_transactions = "SELECT t.*, u.username as cashier_name 
                      FROM transactions t 
                      LEFT JOIN users u ON t.user_id = u.user_id 
                      ORDER BY t.created_at DESC LIMIT 10";
$transactions = $conn->query($query_transactions)->fetch_all(MYSQLI_ASSOC);

$total_products = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$total_transactions = $conn->query("SELECT COUNT(*) as total FROM transactions")->fetch_assoc()['total'];
$low_stock = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE stock < min_stock")->fetch_assoc()['total'];
$today_revenue = $conn->query("SELECT SUM(total_amount) as total FROM transactions WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        #view-transaction-modal {
            display: flex !important;
        }
    </style>
    <style>
        .active-tab {
            border-bottom: 3px solid #3b82f6;
            color: #3b82f6;
            font-weight: 600;
        }
        .sidebar-item.active {
            background-color: #EFF6FF;
            border-left: 4px solid #3B82F6;
            color: #3B82F6;
        }
        .sidebar-item:hover:not(.active) {
            background-color: #F3F4F6;
        }
        .modal {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-white w-64 min-h-screen shadow-lg">
            <div class="p-4 flex items-center justify-center border-b">
                <img src="logo.png" alt="Company Logo" class="h-10">
            </div>
            <div class="p-4">
                <div class="flex items-center space-x-3 mb-6">
                    
                    <div>
                        <p class="font-semibold"><?= htmlspecialchars($_SESSION['username']) ?></p>
                        <p class="text-xs text-gray-500"><?= ucfirst($_SESSION['role']) ?></p>
                    </div>
                </div>
                
                <nav class="space-y-1">
                    <a href="#" data-tab="dashboard" class="sidebar-item active flex items-center space-x-3 p-3 rounded-lg">
                        <i class="fas fa-chart-line w-4"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="#" data-tab="products" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                        <i class="fas fa-box-open w-4"></i>
                        <span>Products</span>
                    </a>
                    <a href="#" data-tab="inventory" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                        <i class="fas fa-warehouse w-4"></i>
                        <span>Inventory</span>
                    </a>
                    <a href="#" data-tab="transactions" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                        <i class="fas fa-receipt w-4"></i>
                        <span>Transactions</span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="flex justify-between items-center p-4">
                    <h1 class="text-xl font-semibold text-gray-800" id="page-title">Dashboard</h1>
                    
                    <div class="flex items-center space-x-4">
                        <button id="logout-btn" class="flex items-center space-x-2 bg-red-50 px-3 py-2 rounded-lg text-red-600 hover:bg-red-100 transition">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="p-6">
                <!-- Tab Content -->
                <div id="dashboard-content" class="tab-content">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500">Total Products</p>
                                    <h3 class="text-2xl font-bold"><?= $total_products ?></h3>
                                </div>
                                <div class="bg-blue-100 p-3 rounded-full">
                                    <i class="fas fa-box-open text-blue-600"></i>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500">Total Transactions</p>
                                    <h3 class="text-2xl font-bold"><?= $total_transactions ?></h3>
                                </div>
                                <div class="bg-green-100 p-3 rounded-full">
                                    <i class="fas fa-receipt text-green-600"></i>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500">Low Stock Items</p>
                                    <h3 class="text-2xl font-bold"><?= $low_stock ?></h3>
                                </div>
                                <div class="bg-yellow-100 p-3 rounded-full">
                                    <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500">Today's Revenue</p>
                                    <h3 class="text-2xl font-bold">Rp <?= number_format($today_revenue, 0, ',', '.') ?></h3>
                                </div>
                                <div class="bg-purple-100 p-3 rounded-full">
                                    <i class="fas fa-money-bill-wave text-purple-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold">Transactions</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Transaction ID</th>
                    <th scope="col" class="px-6 py-3">Date</th>
                    <th scope="col" class="px-6 py-3">Cashier</th>
                    <th scope="col" class="px-6 py-3">Amount</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                <tr class="bg-white border-b">
                    <td class="px-6 py-4 font-medium text-gray-900">#<?= $transaction['transaction_id'] ?></td>
                    <td class="px-6 py-4"><?= date('d M Y H:i', strtotime($transaction['created_at'])) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?= htmlspecialchars($transaction['cashier_name']) ?> </td>
                    <td class="px-6 py-4">Rp <?= number_format($transaction['total_amount'], 0, ',', '.') ?></td>
                    <td class="px-6 py-4">
                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-0.5 rounded"><?= ucfirst($transaction['status']) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="font-semibold mb-4">Low Stock Alert</h3>
                            <div class="space-y-3">
                                <?php
                                $low_stock_items = $conn->query("
                                    SELECT p.product_id, p.name, i.stock, i.min_stock 
                                    FROM products p 
                                    JOIN inventory i ON p.product_id = i.product_id 
                                    WHERE i.stock < i.min_stock
                                    LIMIT 3
                                ")->fetch_all(MYSQLI_ASSOC);
                                
                                foreach ($low_stock_items as $item): 
                                ?>
                                <div class="flex items-center p-3 border rounded-lg">
                                    <div class="ml-3 flex-1">
                                        <p class="font-medium"><?= htmlspecialchars($item['name']) ?></p>
                                        <p class="text-sm text-gray-500">Stock: <?= $item['stock'] ?> (min: <?= $item['min_stock'] ?>)</p>
                                    </div>
                                    
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                        </div>
                    </div>
                </div>

                <!-- Products Tab Content -->
                <div id="products-content" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Product Management</h2>
                        
                    </div>

                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-4 border-b flex justify-between">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    
                                </div>
                                Product
                            </div>
                            
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">ID</th>
                                        <th scope="col" class="px-6 py-3">Name</th>
                                        <th scope="col" class="px-6 py-3">Category</th>
                                        <th scope="col" class="px-6 py-3">Price</th>
                                        <th scope="col" class="px-6 py-3">Stock</th>
                                       
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr class="bg-white border-b">
                                        <td class="px-6 py-4 font-medium text-gray-900"><?= $product['product_id'] ?></td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($product['name']) ?></td>
                                        <td class="px-6 py-4"><?= ucfirst($product['category']) ?></td>
                                        <td class="px-6 py-4">Rp <?= number_format($product['price'], 0, ',', '.') ?></td>
                                        <td class="px-6 py-4"><?= $product['stock'] ?? 0 ?></td>
                                        
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Inventory Tab Content -->
                 
                <div id="inventory-content" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Inventory Management</h2>
                    </div>

                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-4 border-b flex justify-between">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    
                                </div>
                                Inventory
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">ID</th>
                                        <th scope="col" class="px-6 py-3">Product</th>
                                        <th scope="col" class="px-6 py-3">Current</th>
                                        <th scope="col" class="px-6 py-3">Min</th>
                                        <th scope="col" class="px-6 py-3">Status</th>
                                        <th scope="col" class="px-6 py-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $inventory_items = $conn->query("
                                        SELECT p.product_id, p.name, i.stock, i.min_stock 
                                        FROM products p 
                                        JOIN inventory i ON p.product_id = i.product_id
                                    ")->fetch_all(MYSQLI_ASSOC);
                                    
                                    foreach ($inventory_items as $item): 
                                        $status = $item['stock'] < 5 ? 'Critical' : ($item['stock'] < $item['min_stock'] ? 'Low' : 'Adequate');
                                        $status_class = $status === 'Critical' ? 'bg-red-100 text-red-800' : 
                                                      ($status === 'Low' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800');
                                    ?>
                                    <tr class="bg-white border-b">
                                        <td class="px-6 py-4 font-medium text-gray-900"><?= $item['product_id'] ?></td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($item['name']) ?></td>
                                        <td class="px-6 py-4"><?= $item['stock'] ?></td>
                                        <td class="px-6 py-4"><?= $item['min_stock'] ?></td>
                                        <td class="px-6 py-4">
                                            <span class="text-xs font-medium px-2 py-0.5 rounded <?= $status_class ?>"><?= $status ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <button class="text-blue-600 hover:text-blue-800 transition" onclick="showUpdateStockModal(<?= $item['product_id'] ?>, '<?= addslashes($item['name']) ?>')">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                </div>
            <!-- Transactions Tab Content -->
                    <div id="transactions-content" class="tab-content hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-semibold">Transaction History</h2>
                             <button onclick="startNewTransaction()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center">
                              <i class="fas fa-cash-register mr-2"></i>
                               New Transaction
                            </button>
                        </div>

                    <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-4 border-b">
                    <div class="relative">
                     <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                          
                        </div>
                     History
                    </div>
             </div>

             <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                          <tr>
                             <th scope="col" class="px-6 py-3">ID</th>
                             <th scope="col" class="px-6 py-3">Date</th>
                             <th scope="col" class="px-6 py-3">Cashier</th>
                              <th scope="col" class="px-6 py-3">Amount</th>
                             <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                    <tr class="bg-white border-b">
                        <td class="px-6 py-4 font-medium text-gray-900">#<?= $transaction['transaction_id'] ?></td>
                        <td class="px-6 py-4"><?= date('d M Y H:i', strtotime($transaction['created_at'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?= htmlspecialchars($transaction['cashier_name']) ?> </td>
                        <td class="px-6 py-4">Rp <?= number_format($transaction['total_amount'], 0, ',', '.') ?></td>
                        <td class="px-6 py-4">
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-0.5 rounded"><?= ucfirst($transaction['status']) ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-middle text-sm font-medium">
                            <a href="detail_transaksi.php?transaction_id=<?= $transaction['transaction_id'] ?>" class="text-indigo-600 hover:text-indigo-900">View</a>
                             <form action="delete_transaction_kasir.php" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi #<?= $transaction['transaction_id'] ?>? Tindakan ini tidak dapat dibatalkan.');">
                             <input type="hidden" name="transaction_id" value="<?= $transaction['transaction_id'] ?>">
                               <button type="submit" class="text-red-600 hover:text-red-800 transition">Delete</button>
                             </form>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>    
            </main>

        </div>
    </div>

    <!-- Product Modal -->
    <div id="product-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center pb-3">
                <h3 class="text-xl font-semibold" id="product-modal-title">Add Product</h3>
                <button onclick="closeModal('product-modal')" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="product-form" action="process_product.php" method="POST">
                <input type="hidden" id="product-id" name="product_id">
                <div class="space-y-4">
                    <div>
                        <label for="product-name" class="block text-sm font-medium text-gray-700">Product Name</label>
                        <input type="text" id="product-name" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                    </div>
                    <div>
                        <label for="product-category" class="block text-sm font-medium text-gray-700">Category</label>
                        <select id="product-category" name="category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                            <option value="food">Food</option>
                            <option value="drink">Drink</option>
                            <option value="snack">Snack</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="product-price" class="block text-sm font-medium text-gray-700">Price</label>
                        <input type="number" id="product-price" name="price" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                    </div>
                    <div>
                        <label for="product-stock" class="block text-sm font-medium text-gray-700">Initial Stock</label>
                        <input type="number" id="product-stock" name="stock" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                    </div>
                    <div>
                        <label for="product-description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="product-description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeModal('product-modal')" class="px-4 py-2 text-gray-700 border rounded-lg hover:bg-gray-100 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Stock Modal -->
    <div id="update-stock-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center pb-3">
                <h3 class="text-xl font-semibold">Update Stock</h3>
                <button onclick="closeModal('update-stock-modal')" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="update-stock-form" action="process_stock_kasir.php" method="POST">
                <input type="hidden" id="stock-product-id" name="product_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Product</label>
                        <p id="stock-product-name" class="mt-1 font-medium"></p>
                    </div>
                    <div>
                        <label for="update-stock-action" class="block text-sm font-medium text-gray-700">Action</label>
                        <select id="update-stock-action" name="action" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                            <option value="add">Add Stock</option>
                            <option value="reduce">Reduce Stock</option>
                            <option value="set">Set Stock</option>
                        </select>
                    </div>
                    <div>
                        <label for="update-stock-amount" class="block text-sm font-medium text-gray-700">Amount</label>
                        <input type="number" id="update-stock-amount" name="amount" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                    </div>
                    <div>
                        <label for="update-stock-notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea id="update-stock-notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeModal('update-stock-modal')" class="px-4 py-2 text-gray-700 border rounded-lg hover:bg-gray-100 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmation-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="flex justify-end">
                <button onclick="closeModal('confirmation-modal')" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="flex flex-col items-center text-center p-4">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation text-red-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2" id="confirmation-title">Are you sure?</h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500" id="confirmation-message"></p>
                </div>
            </div>

            <div class="flex justify-center space-x-4 pt-4">
                <button onclick="closeModal('confirmation-modal')" class="px-4 py-2 text-gray-700 border rounded-lg hover:bg-gray-100 transition">
                    Cancel
                </button>
                <form id="delete-form" method="POST" action="delete_product.php" class="inline">
                    <input type="hidden" id="delete-product-id" name="product_id">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
   
<!-- Transaction Modal -->
<div id="transaction-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-5 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-3">
            <h3 class="text-xl font-semibold">New Transaction</h3>
            <button onclick="closeModal('transaction-modal')" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Product Selection -->
            <div class="md:col-span-1">
                <div class="relative mb-4">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        
                    </div>
                    
                </div>

                <div class="h-96 overflow-y-auto border rounded-lg p-2">
                    <div id="transaction-product-list" class="space-y-2">
                        <?php foreach ($products as $product): ?>
                        <div onclick="addProductToCart(<?= $product['product_id'] ?>, '<?= htmlspecialchars($product['name']) ?>', <?= $product['price'] ?>)" class="p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <div class="flex items-center">
                                <div class="ml-3">
                                    <p class="font-medium"><?= htmlspecialchars($product['name']) ?></p>
                                    <p class="text-sm text-gray-500">Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Cart Items -->
            <div class="md:col-span-2">
                <div class="p-4 bg-gray-50 rounded-lg mb-4">
                    <div class="flex justify-between items-center">
                        <h4 class="font-medium">Transaction #<?= date('YmdHis') ?></h4>
                        <p class="text-gray-500"><?= date('d M Y H:i') ?></p>
                    </div>
                </div>

                <div class="overflow-x-auto mb-4">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3">Product</th>
                                <th scope="col" class="px-6 py-3">Price</th>
                                <th scope="col" class="px-6 py-3">Qty</th>
                                <th scope="col" class="px-6 py-3">Subtotal</th>
                                <th scope="col" class="px-6 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody id="transaction-cart-items">
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="transaction-customer" class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                            <select id="transaction-customer" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                <option value="walkin">Walk-in Customer</option>
                                <option value="member">Member</option>
                            </select>
                        </div>
                        <div>
                            <label for="transaction-discount" class="block text-sm font-medium text-gray-700 mb-1">Discount (%)</label>
                            <input type="number" id="transaction-discount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="0" min="0" max="100">
                        </div>
                    </div>

                    <div class="flex justify-end">
                       <div class="w-64">
    <div class="flex justify-between mb-1">
        <span class="text-gray-600">Subtotal:</span>
        <span id="transaction-subtotal" class="font-medium">Rp 0</span>
    </div>
    <div class="flex justify-between mb-1">
        <span class="text-gray-600">Discount:</span>
        <span id="transaction-discount-amount" class="font-medium">Rp 0</span>
    </div>
    <div class="flex justify-between text-lg font-bold mt-3 pt-2 border-t">
        <span>Total Pembelian:</span>
        <span id="transaction-total">Rp 0</span>
        <input type="hidden" id="transaction-total-numeric" name="total_amount" value="0">
    </div>

    <div class="mt-4">
        <label for="paid_amount" class="block text-sm font-medium text-gray-700 mb-1">Uang Diberikan Pelanggan</label>
        <input type="number" id="paid_amount" name="paid_amount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="0" min="0" step="1" required>
    </div>
    <div class="flex justify-between text-lg font-bold mt-3 pt-2 border-t">
        <span>Kembalian:</span>
        <span id="transaction-change">Rp 0</span>
    </div>
    <button id="complete-transaction-btn" class="mt-4 w-full py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
        Complete Transaction
    </button>
</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
     
   
</div>
</div>
</div>
</div>
    
    <script>

        
function waitForElement(selector, callback) {
    const element = document.querySelector(selector);
    if (element) {
        callback(element);
    } else {
        setTimeout(() => waitForElement(selector, callback), 100);
    }
}
        function viewTransactionDetails(transactionId) {
   
    alert('Viewing transaction details for ID: ' + transactionId);
    window.location.href = 'transaction_details.php?id=' + transactionId;
}
        
        document.addEventListener('DOMContentLoaded', function() {
    function handleTabNavigation() {
        const tabs = document.querySelectorAll('[data-tab]');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (!tab) {
                    console.error('Tab not found');
                    return;
                }
                tabs.forEach(t => {
                    if (t) t.classList.remove('active');
                });
                tab.classList.add('active');
            
                const tabName = tab.getAttribute('data-tab');
                const tabContent = document.getElementById(`${tabName}-content`);
                
                if (tabContent) {
                    document.querySelectorAll('.tab-content').forEach(content => {
                        if (content) content.classList.add('hidden');
                    });
                    tabContent.classList.remove('hidden');
    
                    const pageTitle = document.getElementById('page-title');
                    if (pageTitle) {
                        pageTitle.textContent = tab.textContent.trim();
                    }
                } else {
                    console.error(`Tab content not found for: ${tabName}`);
                }
            });
        });
    }

   
    handleTabNavigation();

    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            window.location.href = 'logout.php';
        });
    } else {
        console.error('Logout button not found');
    }
});
        function showAddProductModal() {
            document.getElementById('product-modal-title').textContent = 'Add Product';
            document.getElementById('product-form').reset();
            document.getElementById('product-id').value = '';
            showModal('product-modal');
        }

        function showEditProductModal(productId) {
            document.getElementById('product-modal-title').textContent = 'Edit Product';
            document.getElementById('product-id').value = productId;
            showModal('product-modal');
        }

        function showUpdateStockModal(productId, productName) {
            document.getElementById('stock-product-id').value = productId;
            document.getElementById('stock-product-name').textContent = productName;
            document.getElementById('update-stock-form').reset();
            showModal('update-stock-modal');
        }

        function confirmDeleteProduct(productId, productName) {
            document.getElementById('confirmation-title').textContent = 'Delete Product';
            document.getElementById('confirmation-message').textContent = `Are you sure you want to delete "${productName}" (ID: ${productId})? This action cannot be undone.`;
            document.getElementById('delete-product-id').value = productId;
            showModal('confirmation-modal');
        }
       
        function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    } else {
        console.error('Modal not found:', modalId);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}
        
let cart = [];

function addProductToCart(productId, productName, productPrice) {
    const existingItem = cart.find(item => item.productId === productId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            productId,
            productName,
            price: productPrice,
            quantity: 1
        });
    }
    
    updateCartDisplay();
}

function updateCartDisplay() {
    const cartItemsContainer = document.getElementById('transaction-cart-items');
    cartItemsContainer.innerHTML = '';
    
    let subtotal = 0;
    
    cart.forEach(item => {
        const row = document.createElement('tr');
        row.className = 'bg-white border-b';
        row.innerHTML = `
            <td class="px-6 py-4 font-medium text-gray-900">${item.productName}</td>
            <td class="px-6 py-4">Rp ${item.price.toLocaleString('id-ID')}</td>
            <td class="px-6 py-4">
                <input type="number" min="1" value="${item.quantity}" 
                       class="w-16 text-center border rounded" 
                       onchange="updateCartItemQuantity(${item.productId}, this.value)">
            </td>
            <td class="px-6 py-4">Rp ${(item.price * item.quantity).toLocaleString('id-ID')}</td>
            <td class="px-6 py-4">
                <button class="text-red-500 hover:text-red-700 transition" 
                        onclick="removeFromCart(${item.productId})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        cartItemsContainer.appendChild(row);
        
        subtotal += item.price * item.quantity;
    });
    
    updateTransactionTotals(subtotal);
}



        function openModal(modalId) {
            console.log('Attempting to open modal:', modalId); 
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex'); 
                document.body.style.overflow = 'hidden'; 
                console.log('Modal opened:', modalId); 
            } else {
                console.error('Modal element not found:', modalId); 
            }
        }

        function closeModal(modalId) {
            console.log('Attempting to close modal:', modalId); 
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = ''; 
                console.log('Modal closed:', modalId); 
            } else {
                console.error('Modal element not found:', modalId); 
            }
        }


        function viewTransactionDetails(transactionId) {
            console.log('viewTransactionDetails called for ID:', transactionId); 

            document.getElementById('transaction-details-content').innerHTML = '<p>Loading details...</p>';

 
            fetch(`get_transaction_details.php?transaction_id=${transactionId}`)
                .then(response => {
                    console.log('Fetch response received for ID:', transactionId, response);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Data received from API:', data); 
                    if (data.success) {
                        const transaction = data.transaction;
                        const items = data.items;

                        let detailsHtml = `
                            <p><strong>Transaction ID:</strong> #${transaction.transaction_id}</p>
                            <p><strong>Date:</strong> ${new Date(transaction.created_at).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
                            <p><strong>Cashier:</strong> ${transaction.cashier_name ? transaction.cashier_name : 'N/A'}</p>
                            <p><strong>Customer Type:</strong> ${transaction.customer_type}</p>
                            <p><strong>Total Amount:</strong> Rp ${parseFloat(transaction.total_amount).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}</p>
                            <p><strong>Discount:</strong> ${parseFloat(transaction.discount).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}%</p>
                            <p><strong>Status:</strong> <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-0.5 rounded">${transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1)}</span></p>
                            <h4 class="font-semibold mt-6 mb-3 text-lg">Items:</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm text-left text-gray-500 border-collapse">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 border border-gray-200">Product Name</th>
                                            <th scope="col" class="px-6 py-3 border border-gray-200">Quantity</th>
                                            <th scope="col" class="px-6 py-3 border border-gray-200">Price</th>
                                            <th scope="col" class="px-6 py-3 border border-gray-200">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        if (items.length > 0) {
                            items.forEach(item => {
                                detailsHtml += `
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4 border border-gray-200">${item.product_name}</td>
                                        <td class="px-6 py-4 border border-gray-200">${item.quantity}</td>
                                        <td class="px-6 py-4 border border-gray-200">Rp ${parseFloat(item.price).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}</td>
                                        <td class="px-6 py-4 border border-gray-200">Rp ${parseFloat(item.quantity * item.price).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}</td>
                                    </tr>
                                `;
                            });
                        } else {
                            detailsHtml += `
                                <tr class="bg-white border-b">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500 border border-gray-200">No items found for this transaction.</td>
                                </tr>
                            `;
                        }

                        detailsHtml += `
                                    </tbody>
                                </table>
                            </div>
                        `;

                        document.getElementById('transaction-details-content').innerHTML = detailsHtml;
                        openModal('view-transaction-modal'); 
                    } else {
                        document.getElementById('transaction-details-content').innerHTML = `<p class="text-red-500">Error: ${data.message}</p>`;
                        openModal('view-transaction-modal');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error); 
                    document.getElementById('transaction-details-content').innerHTML = `<p class="text-red-500">An error occurred while fetching transaction details. Please check your network connection or server logs. <br> Error: ${error.message}</p>`;
                    openModal('view-transaction-modal');
                });
        }

function updateCartItemQuantity(productId, newQuantity) {
    const item = cart.find(item => item.productId === productId);
    if (item) {
        item.quantity = parseInt(newQuantity);
        updateCartDisplay();
    }
}


function removeFromCart(productId) {
    cart = cart.filter(item => item.productId !== productId);
    updateCartDisplay();
}

function updateTransactionTotals(subtotal) {
    const discountPercentage = parseInt(document.getElementById('transaction-discount').value) || 0;
    const discountAmount = subtotal * discountPercentage / 100;
    const total = subtotal - discountAmount;

    document.getElementById('transaction-subtotal').textContent = `Rp ${subtotal.toLocaleString('id-ID')}`;
    document.getElementById('transaction-discount-amount').textContent = `Rp ${discountAmount.toLocaleString('id-ID')}`;
    document.getElementById('transaction-total').textContent = `Rp ${total.toLocaleString('id-ID')}`;
    document.getElementById('transaction-total-numeric').value = total;
    calculateChange();
}


document.getElementById('transaction-discount').addEventListener('input', function() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    updateTransactionTotals(subtotal);
});

document.getElementById('paid_amount').addEventListener('input', calculateChange);

function startNewTransaction() {
    cart = [];
    document.getElementById('transaction-discount').value = '0';
    document.getElementById('paid_amount').value = ''; 
    document.getElementById('transaction-change').textContent = 'Rp 0'; 
    updateCartDisplay(); 
    showModal('transaction-modal');
}

document.getElementById('complete-transaction-btn').addEventListener('click', function() {
    if (cart.length === 0) {
        alert('Please add at least one product to the cart');
        return;
    }
    
    completeTransaction();
});


function calculateChange() {
    const totalAmount = parseFloat(document.getElementById('transaction-total-numeric').value) || 0;
    const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
    let change = paidAmount - totalAmount;

    const completeBtn = document.getElementById('complete-transaction-btn');

    if (paidAmount < totalAmount) {
        document.getElementById('transaction-change').textContent = `Kurang: Rp ${Math.abs(change).toLocaleString('id-ID')}`;
        completeBtn.disabled = true; 
    } else {
        document.getElementById('transaction-change').textContent = `Rp ${change.toLocaleString('id-ID')}`;
        completeBtn.disabled = false; 
    }
}

function completeTransaction() {
    const customerType = document.getElementById('transaction-customer').value;
    const discount = parseInt(document.getElementById('transaction-discount').value) || 0;
    const total = parseFloat(document.getElementById('transaction-total-numeric').value) || 0; 
    const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0; 

    const transactionData = {
        customer_type: customerType,
        discount: discount,
        total_amount: total,
        paid_amount: paidAmount, 
        items: cart
    };

   
    fetch('process_transaction.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(transactionData)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => { throw new Error(err.message || 'Server error'); });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Transaksi berhasil! Kembalian: Rp ' + parseFloat(data.change_amount).toLocaleString('id-ID') + '.'); 
            closeModal('transaction-modal');
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memproses transaksi: ' + error.message);
    });
}

    </script>
</body>
</html>