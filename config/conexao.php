<?php
// DADOS DA CONEXAO (PADRAO PARA O XAMPP)

$host = "127.0.0.1"; 
$port = "3306";      
$dbname = "locadora";
$user = "root";
$password = "";

// cria o pdo utilizando as variaveis criadas acima
$conn = new PDO(
    "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
    $user,
    $password,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);
?>