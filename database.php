<?php
/* Configure these environment variables in the PHP/Apache environment. */
$host = getenv('DB_HOST') ?: '127.0.0.1';
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');
$database = getenv('DB_DATABASE') ?: 'pos';
if ($username === false || $password === false) {
    http_response_code(500);
    error_log('POS database configuration is incomplete. Set DB_USERNAME and DB_PASSWORD.');
    exit('Database configuration is missing. Please configure the database environment variables.');
}
$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    http_response_code(500);
    error_log('POS database connection failed: ' . mysqli_connect_error());
    exit('Database connection failed. Please check the server configuration.');
}
mysqli_set_charset($conn, 'utf8mb4');
