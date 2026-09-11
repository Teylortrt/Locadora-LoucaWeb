<?php
require_once __DIR__ . '/../config/conexao.php';

$id = $_GET['id'];

//parte para editar registro de clientes
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'] ?? '';
    $sobrenome = $_POST['sobrenome'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $endereco = $_POST['endereco'] ?? '';

    $sql = "UPDATE clientes SET nome = :nome, sobrenome = :sobrenome, telefone = :telefone, endereco = :endereco WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':sobrenome', $sobrenome);
    $stmt->bindParam(':telefone', $telefone);
    $stmt->bindParam(':endereco', $endereco);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        echo "Cliente atualizado com sucesso!";
        header("Location: tabelaclientes.php");
        exit;
    } else {
        echo "Erro ao atualizar cliente: " . $stmt->errorInfo()[2];
        exit;
    }
}
// Busca os dados do cliente para preencher o formulário
$sql = "SELECT * FROM clientes WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id);
$stmt->execute();
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar cliente — Locadora LoucaWeb</title>
    <link rel="stylesheet" href="../public/css/normalize.css">
    <link rel="stylesheet" href="../public/css/skeleton.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <main class="container conteudo"><div class="row"><section class="eight columns offset-by-two painel-card">
        <header class="cabecalho-pagina"><h2>Editar cliente</h2><p>Atualize as informações de cadastro.</p></header>
        <form method="POST">
            <div class="row"><div class="six columns"><label for="nome">Nome</label><input class="u-full-width" type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required></div><div class="six columns"><label for="sobrenome">Sobrenome</label><input class="u-full-width" type="text" id="sobrenome" name="sobrenome" value="<?php echo htmlspecialchars($cliente['sobrenome']); ?>" required></div></div>
            <div class="row"><div class="six columns"><label for="telefone">Telefone</label><input class="u-full-width" type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($cliente['telefone']); ?>" required></div><div class="six columns"><label for="endereco">Endereço</label><input class="u-full-width" type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($cliente['endereco']); ?>" required></div></div>
            <button type="submit" class="button-primary">Salvar alterações</button>
        </form>
        <nav class="navegacao"><a href="tabelaclientes.php">Voltar à lista de clientes</a></nav>
    </section></div></main>
</body>
</html>
