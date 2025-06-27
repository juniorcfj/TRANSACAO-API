<?php
function getConnection() {
    $host = 'localhost';
    $db = 'transacoes';  
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return $pdo;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro na conexão com o banco de dados']);
        exit;
    }
}
