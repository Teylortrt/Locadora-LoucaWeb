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
<h2>Cadastro de Pessoa</h2>

<!-- Formulário que envia os dados usando o método POST -->
<form method="POST">

    <!-- Campo para digitar o nome -->
    Nome:<br>
    <!-- required obriga o preenchimento -->
    <input type="text" name="nome" required><br><br>

    <!-- Campo para digitar o sobrenome -->
    Sobrenome:<br>
    <!-- required obriga o preenchimento -->
    <input type="text" name="sobrenome" required><br><br>

    <!-- Campo para digitar o telefone -->
    Telefone:<br>
    <input type="text" name="telefone" required><br><br>

    <!-- Campo para digitar o endereço -->
    Endereço:<br>
    <input type="text" name="endereco" required><br><br>

    <!-- Botão que envia o formulário -->
    <button type="submit">Cadastrar</button>

</form>
<br>
<!-- Link para visualizar a lista de cadastrados -->
<a href="tabelaclientes.php">clientes registrados</a>
<br>
<!-- Link para voltar ao painel -->
<a href="painel.php">Voltar ao Painel</a>


