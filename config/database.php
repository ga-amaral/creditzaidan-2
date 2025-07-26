<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'zaidancredits');
define('DB_PASS', 'V6$Z2Asn95');
define('DB_NAME', 'creditszaidan_db');

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log('Erro na conexão com o banco de dados: ' . $e->getMessage());
    $conn = null;
    $db_error_message = 'Erro ao conectar ao banco de dados: ' . $e->getMessage();
}
?> 
