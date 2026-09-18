<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/conexao.php';

require_once __DIR__ . '/../../src/Models/Auth.php';
require_once __DIR__ . '/../../src/Models/Filmes.php';
require_once __DIR__ . '/../../src/Models/Generos.php';
require_once __DIR__ . '/../../src/Controllers/FilmeController.php';
require_once __DIR__ . '/../../src/Controllers/DvdController.php';
require_once __DIR__ . '/../../src/Services/TmdbClient.php';

// Exige autenticação
$auth = new Auth();
$auth->exigirLogin();

// Garante que $conn existe antes de instanciar os Models/Controllers
if (!isset($conn) || !($conn instanceof PDO)) {
    die("Erro grave: Conexão com o banco de dados não foi estabelecida.");
}

// Instâncias dos Controllers e Models
$dvdController   = new DVDController($conn);
$generosModel    = new Generos($conn);
$filmeController = new FilmesController($conn);
$tmdbClient      = new TmdbClient();

// Dados para preencher os selects
$listarGeneros = $generosModel->listarGeneros();

// Variáveis de estado da tela
$filmesEncontrados = null;
$mensagemSucesso   = false;
$mensagemErro      = null;

// Processa o formulário via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Ação de PESQUISAR no TMDb
    if (isset($_POST['btn_pesquisar']) && !empty($_POST['titulo_pesquisa'])) {
        $filmesEncontrados = $tmdbClient->buscarFilmePorTitulo($_POST['titulo_pesquisa']);
    }
    
    // 2. Ação de ADICIONAR FILME + DVD
    if (isset($_POST['btn_adicionar'])) {
        try {
            $conn->beginTransaction();

            $idFilme = $filmeController->salvar($_POST);
            $quantidade = (int) ($_POST['dvd'] ?? 0);

            $dvdController->inserirDVD($idFilme, $quantidade);

            $conn->commit();
            $mensagemSucesso = true;

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $mensagemErro = "Erro ao cadastrar: " . $e->getMessage();
        }
    }
}
?>

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Catálogo - Cadastrar Filme</title>
    <link rel="icon" href="../../image/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../public/css/normalize.css">
    <link rel="stylesheet" href="../../public/css/skeleton.css">
    <link rel="stylesheet" href="../../public/css/style.css?v=<?= filemtime(__DIR__ . '/../../public/css/style.css') ?>">
    <style>

    </style>
</head>
<body>

    <header class="topo">
        <div class="container">
            <div class="marca">
                <span>Locadora</span>
                <h1>LoucaWeb</h1>
            </div>

            <form class="form-cadastro" method="GET" action="filmes.php">
                <button type="submit" class="button">Voltar</button>
            </form>
        </div>
    </header>

    <main class="container conteudo cadastrar-filme">
        <div class="row">

            <!-- Formulário para pesquisar filme no TMDb -->
            <div class="eight columns">
                <div class="card">
                    <h2>Pesquisar filme no TMDb</h2>
                    <form method="POST" action="">
                        <label for="titulo_pesquisa">Título</label>
                        <input class="u-full-width" type="text" id="titulo_pesquisa" name="titulo_pesquisa" required>
                        <button class="button-primary" type="submit" name="btn_pesquisar">Pesquisar</button>
                    </form>
                    
                    <!-- LÓGICA DE EXIBIÇÃO DA PESQUISA -->
                    <?php if ($filmesEncontrados !== null): ?>
                        <?php if (empty($filmesEncontrados)): ?>
                            <p>Nenhum resultado encontrado.</p>
                        <?php else: ?>
                            <div class="resultado-pesquisa">
                                <?php foreach ($filmesEncontrados as $filme): ?>
                                    <?php 
                                        $posterPath = $filme['poster_path'] 
                                            ? "https://image.tmdb.org/t/p/w200{$filme['poster_path']}" 
                                            : ''; // Pode colocar uma URL de imagem padrão aqui
                                        
                                        // Escapa os dados para evitar que as aspas do título quebrem o JavaScript
                                        $tituloJs = htmlspecialchars(addslashes($filme['title']));
                                        $posterJs = htmlspecialchars(addslashes($posterPath));
                                    ?>
                                    <div class="resultado-item">
                                        <?php if ($posterPath): ?>
                                            <img src="<?= htmlspecialchars($posterPath) ?>" alt="Poster de <?= htmlspecialchars($filme['title']) ?>" loading="lazy">
                                        <?php else: ?>
                                            <div style="height: 225px; background: #eee; display: flex; align-items:center; justify-content:center;">Sem Foto</div>
                                        <?php endif; ?>
                                        <p><?= htmlspecialchars($filme['title']) ?></p>
                                        
                                        <!-- Preenche o formulário ao lado -->
                                        <button type="button" class="button u-full-width" onclick="preencherForm('<?= $tituloJs ?>', '<?= $posterJs ?>')">Usar este</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Formulário para adicionar filme manualmente -->
            <div class="four columns ">
                <div class="card">
                    <h2>Adicionar Filme</h2>
                    <form method="POST" action="">
                        <div class="row">
                            <div class="six columns">
                                <label for="titulo">Título</label>
                                <input class="u-full-width" type="text" id="titulo" name="titulo" required>
                            </div>
                            <div class="six columns">
                                <label for="id_genero">Gênero</label>
                                <select class="u-full-width" id="id_genero" name="id_genero" required>
                                    <option value="" disabled selected>Selecione...</option>
                                    <?php
                                    foreach ($listarGeneros as $genero) {
                                        echo "<option value=\"{$genero['id']}\">" . htmlspecialchars($genero['genero']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <label for="poster_url">URL do Poster</label>
                        <input class="u-full-width" type="url" id="poster_url" name="poster_url">

                        <label for="valor">Valor (R$)</label>
                        <input class="u-full-width" type="number" step="0.01" id="valor" name="valor" required>

                        <label for="dvd">Quantidade de Cópias</label>
                        <input class="u-full-width" type="number" step="1" min="0" id="dvd" name="dvd" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>

                        <button class="button-primary" type="submit" name="btn_adicionar">Salvar Filme</button>
                        
                        <?php if ($mensagemSucesso): ?>
                            <p class="mensagem sucesso" style="color: green; margin-top:10px;">Filme e estoque gravados com sucesso!</p>
                        <?php endif; ?>

                        <?php if ($mensagemErro): ?>
                            <p class="mensagem erro" style="color: red; margin-top:10px;"><?= htmlspecialchars($mensagemErro) ?></p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Script JS para pegar os dados do filme clicado e jogar nos inputs -->
    <script>
        function preencherForm(titulo, poster) {
            document.getElementById('titulo').value = titulo;
            document.getElementById('poster_url').value = poster;
            
            // Dá um scroll suave até o formulário no celular
            document.getElementById('titulo').scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Destaca o formulário rapidamente para o usuário perceber a ação
            const form = document.getElementById('titulo');
            form.style.boxShadow = "0 0 10px #007BFF";
            setTimeout(() => { form.style.boxShadow = "none"; }, 1000);
        }
    </script>
</body>
</html>