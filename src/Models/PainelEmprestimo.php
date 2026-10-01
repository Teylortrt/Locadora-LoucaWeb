<?php

require_once __DIR__ . '/Dvd.php';
require_once __DIR__ . '/Cliente.php';

// Lógica da tela de empréstimo rápido (templates/painel.php).
// O cliente é escolhido por autocomplete e os DVDs são adicionados um a um; o que
// já foi escolhido fica na sessão (`emprestimo_cliente` e `emprestimo_dvds`)
// até a confirmação, que é enviada ao EmprestimoController.
class PainelEmprestimo
{
    private Dvd $dvdModel;

    private \App\Models\Cliente $clienteModel;

    // Inicia a sessão e recebe os modelos usados pelo formulário de empréstimo.
    public function __construct(Dvd $dvdModel, \App\Models\Cliente $clienteModel)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->dvdModel     = $dvdModel;
        $this->clienteModel = $clienteModel;
    }

    // --- Estado em andamento (sessão) ---

    // Filmes com estoque e respectivas cópias livres, para o select.
    public function opcoesDeFilmes(): array
    {
        return $this->dvdModel->listarComDisponibilidade();
    }

    // DVDs já escolhidos: id => ['titulo' => ..., 'disponivel' => ...].
    public function dvds(): array
    {
        if (!isset($_SESSION['emprestimo_dvds']) || !is_array($_SESSION['emprestimo_dvds'])) {
            $_SESSION['emprestimo_dvds'] = [];
        }

        return $_SESSION['emprestimo_dvds'];
    }

    // Retorna o identificador do cliente escolhido no rascunho da sessão.
    public function clienteId(): int
    {
        return isset($_SESSION['emprestimo_cliente']) ? (int) $_SESSION['emprestimo_cliente'] : 0;
    }

    // Nome do cliente escolhido, para repopular o campo do autocomplete ao
    // recarregar a página. Cliente que não existe mais é descartado do rascunho.
    public function clienteLabel(): string
    {
        $clienteId = $this->clienteId();

        if ($clienteId <= 0) {
            return '';
        }

        $cliente = $this->clienteModel->buscarPorId($clienteId);

        if (!$cliente) {
            $this->definirCliente(0);

            return '';
        }

        return $cliente['nome'] . ' ' . $cliente['sobrenome'];
    }

    // --- Ações do formulário (self-POST) ---

    // Adiciona à sessão um DVD existente para compor o empréstimo em andamento.
    public function adicionarDvd(int $idDvd): void
    {
        $dvd = $idDvd > 0 ? $this->dvdModel->buscarComDisponibilidade($idDvd) : null;

        if (!$dvd || (int) $dvd['disponivel'] <= 0) {
            return; // Bloqueia DVDs sem cópias livres
        }

        $this->dvds();
        $_SESSION['emprestimo_dvds'][$dvd['id']] = [
            'titulo'     => $dvd['titulo'],
            'disponivel' => (int) $dvd['disponivel'],
        ];
    }

    // Retira da sessão o DVD selecionado, sem gravar um empréstimo no banco.
    public function removerDvd(int $idDvd): void
    {
        $this->dvds();
        unset($_SESSION['emprestimo_dvds'][$idDvd]);
    }

    // Guarda na sessão o cliente selecionado para o empréstimo em andamento.
    public function definirCliente(int $idCliente): void
    {
        $_SESSION['emprestimo_cliente'] = $idCliente;
    }

    // Empréstimo confirmado: zera o rascunho inteiro.
    public function limpar(): void
    {
        $_SESSION['emprestimo_dvds']    = [];
        $_SESSION['emprestimo_cliente'] = 0;
    }
}
