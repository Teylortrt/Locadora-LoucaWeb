
//Teste para ver se aparece o poster de um filme
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

//Teste para ver se aparece o poster de um filme
$stmt = $conn->query("SELECT poster_url FROM filmes WHERE id = 1");
$filme = $stmt->fetch();
if ($filme && !empty($filme['poster_url'])) {
    echo '<img src="' . htmlspecialchars($filme['poster_url']) . '" alt="Poster do Filme" loading="lazy">';
} else {
    echo '<div class="poster-falso">Sem Imagem</div>';
}
?>
