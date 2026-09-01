<?php
// DADOS DA CONEXAO ( PADRAO PARA O XAMPP)


// endereço do servidor
$host = "localhost";

// porta padrao do Mysql 3306
$port = "3312";

// nome do banco dados
$dbname = "locadora";

// usuario que acessa o servidor
$user = "root";

// senha do xampp
$password = "";

// conexao com o banco

// cria o pdo
$conn = new PDO(
    "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
    $user,
    $password
);

// configura o modo de erros
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


?>