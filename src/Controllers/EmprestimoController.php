<?php
require_once __DIR__ . '/../Models/EmprestimoModel.php';
require_once __DIR__ . '/../../config/conexao.php'; // Para pegar $conn caso precise, mas ideal é injetar

class EmprestimoController 
{
    private EmprestimoModel $emprestimoModel;

<<<<<<< HEAD
    // Cria o modelo de empréstimo usando a conexão compartilhada do sistema.
=======
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
    public function __construct() {
        global $conn; // Pegando a variável $conn do config/conexao.php
        $this->emprestimoModel = new EmprestimoModel($conn);
    }
    
<<<<<<< HEAD
    // Valida cliente e DVDs, cria o empréstimo e escolhe resposta HTML ou JSON.
=======
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
    public function criarEmprestimo()
    {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);
            if (empty($dados)) {
                $dados = $_POST;
            }

            if (!isset($dados['id_cliente']) || !isset($dados['dvds_ids']) || !is_array($dados['dvds_ids'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'Dados inválidos. Necessário id_cliente e dvds_ids (array).']);
                return;
            }

            $prazoDias = isset($dados['prazo_dias']) ? (int)$dados['prazo_dias'] : 1;

            $resultado = $this->emprestimoModel->criarEmprestimo(
                (int)$dados['id_cliente'],
                $dados['dvds_ids'],
                $prazoDias
            );

            // Se for requisição tradicional, redirecionar
            if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== false) {
                header('Location: ../templates/painel.php?sucesso=Emprestimo+realizado');
                exit;
            }

            http_response_code(201);
            echo json_encode([
                'mensagem' => 'Empréstimo realizado com sucesso.',
                'dados' => $resultado
            ]);
        } catch (Exception $e) {
            if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== false) {
                header('Location: ../templates/painel.php?erro=' . urlencode($e->getMessage()));
                exit;
            }
            http_response_code(500);
            echo json_encode(['erro' => $e->getMessage()]);
        }
    }

<<<<<<< HEAD
    // Consulta os empréstimos pendentes e responde com uma lista JSON.
=======
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
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

<<<<<<< HEAD
    // Retorna os itens ainda pendentes dos empréstimos do cliente informado.
=======
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
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

<<<<<<< HEAD
    // Retorna em JSON os empréstimos que passaram do prazo de devolução.
=======
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
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

<<<<<<< HEAD
    // Responde HTTP 501 porque a exclusão de empréstimos ainda não foi implementada.
=======
    // Métodos extras vazios para futura implementação (se necessário)
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
    public function excluirEmprestimo()
    {
        http_response_code(501);
        echo json_encode(['erro' => 'Não implementado']);
    }

<<<<<<< HEAD
    // Responde HTTP 501 porque a edição de empréstimos ainda não foi implementada.
=======
>>>>>>> b0a8314cc2d9710fe875e1523ce48ae794c99259
    public function editarEmprestimo()
    {
        http_response_code(501);
        echo json_encode(['erro' => 'Não implementado']);
    }
}
