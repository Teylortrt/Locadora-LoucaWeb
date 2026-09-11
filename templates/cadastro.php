<?php 
require_once __DIR__ . '/../config/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = $_POST['nome'] ?? '';
    $sobrenome = $_POST['sobrenome'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $endereco = $_POST['endereco'] ?? '';

    $sql = "INSERT INTO clientes (nome, sobrenome, telefone, endereco) VALUES (:nome, :sobrenome, :telefone, :endereco)";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':sobrenome', $sobrenome);
    $stmt->bindParam(':telefone', $telefone);
    $stmt->bindParam(':endereco', $endereco);

    if ($stmt->execute()) {
        echo "Cadastro realizado com sucesso!";
    } else {
        echo "Erro ao cadastrar: " . $stmt->errorInfo()[2];
    exit;
    }

}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de cliente — Locadora LoucaWeb</title>
    <link rel="stylesheet" href="../public/css/normalize.css">
    <link rel="stylesheet" href="../public/css/skeleton.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <main class="container conteudo">
        <div class="row">
            <section class="eight columns offset-by-two painel-card">
                <header class="cabecalho-pagina"><h2>Cadastro de cliente</h2><p>Preencha os dados para adicionar uma pessoa à locadora.</p></header>
                <form method="POST">
                    <div class="row"><div class="six columns"><label for="nome">Nome</label><input class="u-full-width" type="text" id="nome" name="nome" required></div><div class="six columns"><label for="sobrenome">Sobrenome</label><input class="u-full-width" type="text" id="sobrenome" name="sobrenome" required></div></div>
                    <div class="row"><div class="six columns"><label for="telefone">Telefone</label><input class="u-full-width" type="text" id="telefone" name="telefone" required></div><div class="six columns"><label for="endereco">Endereço</label><input class="u-full-width" type="text" id="endereco" name="endereco" required></div></div>
                    <button type="submit" class="button-primary">Cadastrar cliente</button>
                </form>
                <nav class="navegacao"><a href="tabelaclientes.php">Clientes registrados</a> &nbsp;·&nbsp; <a href="painel.php">Voltar ao painel</a></nav>
            </section>
        </div>
    </main>
</body>
</html>


