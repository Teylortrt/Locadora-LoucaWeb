<?php
require_once __DIR__ . '/../config/conexao.php';
// Cria o comando SQL para buscar todos os registros da tabela pessoas
// ORDER BY id organiza os resultados pelo ID em ordem crescente
$sql = "SELECT * FROM clientes ORDER BY id";

// Executa a consulta diretamente (query é usada quando não há parâmetros)
$stmt = $conn->query($sql);
// Converte o resultado em um array associativo
// Cada linha da tabela vira um item do array
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- front da tabela -->
<h2>Lista de Clientes</h2>

<br>
<!-- tabela de clientes -->
<table border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Sobrenome</th>
        <th>Telefone</th>
        <th>Endereco</th>
    </tr>
    <?php foreach ($clientes as $cliente) : ?>
        <tr>
            <!-- Exibe os dados de cada cliente na tabela -->
            <td><?php echo $cliente['id']; ?></td>
            <td><?php echo $cliente['nome']; ?></td>
            <td><?php echo $cliente['sobrenome']; ?></td>
            <td><?php echo $cliente['telefone']; ?></td>
            <td><?php echo $cliente['endereco']; ?></td>
        
        <!-- Link para editar o cliente, passando o ID como parâmetro na URL -->
        <td><a href="editarclientes.php?id=<?php echo $cliente['id']; ?>">Editar</a></td>
        <!-- Link para excluir o cliente, passando o ID como parâmetro na URL -->
        <td><a href="../src/Controllers/excluirclientescontroller.php?id=<?php echo $cliente['id']; ?>">Excluir</a></td>
        </tr>
     <?php endforeach; ?>
</table>
<br>
<!-- Link para voltar ao cadastro -->
<a href="cadastro.php">Voltar ao Cadastro</a>