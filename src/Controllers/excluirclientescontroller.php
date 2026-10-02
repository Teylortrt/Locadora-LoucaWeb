<?php

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../Models/Auth.php';
require_once __DIR__ . '/../Models/Cliente.php';
//verifica se o usuário está logado
$auth = new Auth();
$auth->exigirLogin();
//volta para a página de clientes após a exclusão
$urlRetorno = '../../templates/clientes/tabelaclientes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $urlRetorno . '?erro=' . urlencode('Requisição inválida.'));
    exit;
}

$idCliente = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$idCliente || $idCliente < 1) {
    header('Location: ' . $urlRetorno . '?erro=' . urlencode('Cliente inválido.'));
    exit;
}

try {
    $clienteModel = new \App\Models\Cliente($conn);
    if (!$clienteModel->deletar($idCliente)) {
        header('Location: ' . $urlRetorno . '?erro=' . urlencode('Cliente não encontrado.'));
        exit;
    }

    header('Location: ' . $urlRetorno . '?sucesso=' . urlencode('Cliente excluído com sucesso.'));
} catch (\RuntimeException $e) {
    header('Location: ' . $urlRetorno . '?erro=' . urlencode($e->getMessage()));
} catch (\Throwable $e) {
    error_log('Falha ao excluir cliente: ' . $e->getMessage());
    header('Location: ' . $urlRetorno . '?erro=' . urlencode('Não foi possível excluir o cliente.'));
}

exit;
