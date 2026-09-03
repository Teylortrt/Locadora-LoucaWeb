<?php
require_once __DIR__ . '/../src/Models/Auth.php';
require_once __DIR__ . '/../src/Models/cliente.php';
require_once __DIR__ . '/../src/Controllers/ClienteController.php';
// 3. Instancia as classes passando a variável $conn
$auth = new Auth($conn);
$auth->exigirLogin();

$clienteModel = new Cliente($conn);

// 4. Armazena o retorno do banco em uma variável para o HTML
$listaDeClientes = $clienteModel->listarTodos();
?>
<h2>Lista de Clientes</h2>

<br>
<!-- tabela de clientes -->
 <ul>
    <?php foreach ($listaDeClientes as $cliente) : ?>
     <li>
                <?= htmlspecialchars($cliente['nome']) ?> <?= htmlspecialchars($cliente['sobrenome']) ?>
    </li>
    <?php endforeach; ?>
</ul>
<br>
<!-- Link para voltar ao cadastro -->
<a href="cadastro.php">Voltar ao Cadastro</a>