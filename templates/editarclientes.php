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
<!-- Front para editar cliente -->
 <h2>Editar Cliente</h2>

 <form method="POST">
     Nome:<br>
     <input type="text" name="nome" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required><br><br>

     Sobrenome:<br>
     <input type="text" name="sobrenome" value="<?php echo htmlspecialchars($cliente['sobrenome']); ?>" required><br><br>

     Telefone:<br>
     <input type="text" name="telefone" value="<?php echo htmlspecialchars($cliente['telefone']); ?>" required><br><br>

     Endereço:<br>
     <input type="text" name="endereco" value="<?php echo htmlspecialchars($cliente['endereco']); ?>" required><br><br>

     <button type="submit">Atualizar</button>
    </form>
    <br>
    <!-- Link para voltar à lista de clientes -->
     <a href="tabelaclientes.php">Voltar à Lista de Clientes</a>