<?php

namespace App\Controllers;

use PDO;
use Exception;
// Como EmprestimoModel ainda não tem namespace declarado, vamos incluí-lo se necessário,
// mas o ideal seria usar require_once no index.php ou adicionar o namespace nele.
// Para garantir, vamos fazer require_once aqui caso não tenha sido carregado.
require_once __DIR__ . '/../Models/EmprestimoModel.php';
require_once __DIR__ . '/../../config/conexao.php'; // Para pegar $conn caso precise, mas ideal é injetar

class EmprestimoController 
{
    private \EmprestimoModel $emprestimoModel;

    public function __construct() {
        global $conn; // Pegando a variável $conn do config/conexao.php
        $this->emprestimoModel = new \EmprestimoModel($conn);
    }
    
    public function criarEmprestimo()
    {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);

            if (!isset($dados['id_cliente']) || !isset($dados['dvds_ids']) || !is_array($dados['dvds_ids'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'Dados inválidos. Necessário id_cliente e dvds_ids (array).']);
                return;
            }

            $resultado = $this->emprestimoModel->criarEmprestimo(
                (int)$dados['id_cliente'],
                $dados['dvds_ids']
            );

            http_response_code(201);
            echo json_encode([
                'mensagem' => 'Empréstimo realizado com sucesso.',
                'dados' => $resultado
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => $e->getMessage()]);
        }
    }

    public function consultarEmprestimos()
    {
        try {
            $emprestimos = $this->emprestimoModel->visualizarEmprestimos();
            http_response_code(200);
            echo json_encode($emprestimos);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao buscar empréstimos: ' . $e->getMessage()]);
        }
    }

    public function consultarEmprestimosCliente($idCliente)
    {
        try {
            $emprestimos = $this->emprestimoModel->consultarEmprestimosCliente((int)$idCliente);
            http_response_code(200);
            echo json_encode($emprestimos);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao buscar empréstimos do cliente: ' . $e->getMessage()]);
        }
    }

    public function consultarEmprestimosAtrasados()
    {
        try {
            $atrasados = $this->emprestimoModel->calcularAtraso();
            http_response_code(200);
            echo json_encode($atrasados);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao buscar atrasos: ' . $e->getMessage()]);
        }
    }

    // Métodos extras vazios para futura implementação (se necessário)
    public function excluirEmprestimo()
    {
        http_response_code(501);
        echo json_encode(['erro' => 'Não implementado']);
    }

    public function editarEmprestimo()
    {
        http_response_code(501);
        echo json_encode(['erro' => 'Não implementado']);
    }
}
