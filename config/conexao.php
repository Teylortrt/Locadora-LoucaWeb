<?php

// =============================
// DADOS DE CONEXÃO
// =============================

// Endereço do servidor do banco de dados
// "localhost" significa que o banco está na mesma máquina
$host = "localhost";

// Porta padrão do PostgreSQL
$port = "3307";

// Nome do banco de dados criado anteriormente
$dbname = "locadora";

// Usuário do PostgreSQL
$user = "root";

// Senha do usuário do banco
$password = "";


// =============================
// CONEXÃO COM O BANCO
// =============================

try {

    // Cria uma nova conexão PDO com o PostgreSQL
    // A string "mysql:..." é chamada de DSN (Data Source Name)
    $conn = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password
    );

    // Configura o modo de erro para lançar exceções
    // Isso facilita o tratamento de erros
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    // Caso ocorra erro na conexão, exibe a mensagem
    echo "Erro na conexão: " . $e->getMessage();

    // Interrompe a execução do script
    exit;
}

?>

