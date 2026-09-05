<?php

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Filmes.php';

//Instanciando conexao BD
$auth = new Auth($conn);
$auth->exigirLogin();

$filmeModel = new Filmes($conn);

//variavel de conta de filmes
$totalRegistros = $filmeModel->contarTotal();

// 2. Configurações da Paginação
$registrosPorPagina = 30; 

// Captura a página atual pela URL (se não existir, o padrão é 1)
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($paginaAtual < 1) {
    $paginaAtual = 1;
}

// 3. Cálculo do Offset (Deslocamento)
// Ex: Página 1 = (1 - 1) * 5 = 0 (Busca a partir do 0)
// Ex: Página 2 = (2 - 1) * 5 = 5 (Busca a partir do 5)
$offset = ($paginaAtual - 1) * $registrosPorPagina;


// 5. Calcular o total de páginas (arredondando sempre para cima com ceil)
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);


$filmes = $filmeModel->listarPorPagina($registrosPorPagina, $offset)
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>catalogo</title>
    <style>
        .paginacao { margin-top: 20px; }
        .paginacao a { padding: 5px 10px; border: 1px solid #ccc; text-decoration: none; color: #333; margin-right: 5px;}
        .paginacao a.ativo { background-color: #007BFF; color: white; border-color: #007BFF; }
    </style>
</head>
<body>

    <h1>catalogo</h1>
    
   <!-- Listagem de Dados em Grid -->
    <div class="catalogo">
        <?php foreach ($filmes as $filme): ?>
            
            <div class="cartao-filme">
                <!-- Retângulo cinza representando onde ficaria a foto -->
                <div class="poster-falso">
                    Sem Imagem
                </div>
                
                <!-- Dados do Banco -->
                <h3><?= htmlspecialchars($filme['titulo']) ?></h3>
                <p>R$ <?= htmlspecialchars($filme['valor']) ?></p>
            </div>

        <?php endforeach; ?>
    </div>

    <!-- Links de Paginação -->
    <div class="paginacao">
        
        <!-- Botão Anterior -->
        <?php if ($paginaAtual > 1): ?>
            <a href="?pagina=<?= $paginaAtual - 1 ?>">Anterior</a>
        <?php endif; ?>

        <!-- Números das Páginas -->
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a href="?pagina=<?= $i ?>" class="<?= ($i == $paginaAtual) ? 'ativo' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <!-- Botão Próximo -->
        <?php if ($paginaAtual < $totalPaginas): ?>
            <a href="?pagina=<?= $paginaAtual + 1 ?>">Próximo</a>
        <?php endif; ?>

    </div>

</body>
</html>