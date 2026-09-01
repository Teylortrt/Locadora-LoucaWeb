<?php
require_once __DIR__ . '/../../config/conexao.php';

$id = $_GET['id'];

//comando para excluir o cliente com o id especificado
$sql = "DELETE FROM clientes WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id);
$stmt->execute();
//redireciona para a página de listagem de clientes após a exclusão
header("Location: ../../templates/tabelaclientes.php");
exit;
?>
