<?php
require_once __DIR__ . '/../Models/DevolucaoModel.php';
require_once __DIR__ . '/../../config/conexao.php';

class DevolucaoController 
{
    private DevolucaoModel $devolucaoModel;

<<<<<<< HEAD
    // Cria o modelo de devolução usando a conexão compartilhada do sistema.
=======
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
    public function __construct() {
        global $conn;
        $this->devolucaoModel = new DevolucaoModel($conn);
    }
    
<<<<<<< HEAD
    // Lê os DVDs devolvidos, delega o registro ao modelo e responde à tela ou à API.
=======
    // US26 - Registrar devolução (vai calcular e já efetivar)
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
    public function registrarDevolucao()
    {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);
            if (empty($dados)) {
                $dados = $_POST;
            }

            if (!isset($dados['id_emprestimo']) || !isset($dados['dvds_ids'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'Dados inválidos. Necessário id_emprestimo e dvds_ids.']);
                return;
            }

            $dvdsIds = is_array($dados['dvds_ids']) ? $dados['dvds_ids'] : explode(',', $dados['dvds_ids']);

            $resultado = $this->devolucaoModel->registrarDevolucao(
                (int)$dados['id_emprestimo'],
                $dvdsIds
            );

            if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== false) {
                header('Location: ../templates/emprestimos/emprestimos.php?sucesso=Devolucao+realizada');
                exit;
            }

            http_response_code(201);
            echo json_encode([
                'mensagem' => 'Devolução registrada com sucesso.',
                'dados' => $resultado
            ]);
        } catch (Exception $e) {
            if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== false) {
                header('Location: ../templates/emprestimos/emprestimos.php?erro=' . urlencode($e->getMessage()));
                exit;
            }
            http_response_code(500);
            echo json_encode(['erro' => $e->getMessage()]);
        }
    }

<<<<<<< HEAD
    // Calcula valores e atraso sem gravar; usado antes da confirmação da devolução.
=======
    // US27 e US28 - Apenas calcular atraso e valor sem registrar
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
    public function calcularValor()
    {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);

            if (!isset($dados['id_emprestimo']) || !isset($dados['dvds_ids']) || !is_array($dados['dvds_ids'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'Dados inválidos. Necessário id_emprestimo e dvds_ids (array).']);
                return;
            }

            $resultado = $this->devolucaoModel->calcularValorDevolucao(
                (int)$dados['id_emprestimo'],
                $dados['dvds_ids']
            );

            // Removendo itens_validos do output para manter a API limpa
            unset($resultado['itens_validos']);

            http_response_code(200);
            echo json_encode($resultado);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => $e->getMessage()]);
        }
    }
}
