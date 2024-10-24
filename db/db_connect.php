<?php
$config = [
    'host'     => 'localhost',
    'username' => 'root',
    'password' => 'eva',
    'dbname'   => 'diary'
];

$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
    PDO::ATTR_EMULATE_PREPARES   => false,                  
];

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
} catch (PDOException $e) {
    // In production, avoid displaying errors directly
    // Consider logging the error instead
    exit('Database connection failed.');
}
?>
