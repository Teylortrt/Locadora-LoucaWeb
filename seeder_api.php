<?php
/**
 * ========================================================================
 * SeederFilmesAPI — Script de Carga Inicial (Seeding) do Acervo
 * ========================================================================
 *
 * Este script automatiza a população do banco de dados da Locadora LoucaWeb
 * consumindo a API REST do TMDB (The Movie Database) para simular o acervo
 * real de aproximadamente 2.000 DVDs físicos, conforme exigido na
 * especificação do projeto (Item 3 — "Backend PHP & API Integrator").
 *
 * Fluxo de execução:
 *   1. Importa os gêneros do TMDB → tabela `generos`
 *   2. Percorre páginas de filmes populares → tabela `filmes`
 *   3. Para cada filme, busca o elenco → tabelas `atores` e `atores_filme` (N:M)
 *   4. Gera cópias físicas aleatórias (1–5) → tabela `dvds`
 *   5. Repete até atingir a meta de DVDs (padrão: 2.000)
 *
 * Cada filme é inserido dentro de uma TRANSAÇÃO (BEGIN/COMMIT/ROLLBACK),
 * garantindo as propriedades ACID: ou o filme, seu elenco e seu estoque
 * são inseridos juntos, ou nada é persistido.
 *
 * Requisitos:
 *   - PHP 8.0+ com extensão cURL habilitada
 *   - Variável TMDB_API_KEY definida no arquivo .env
 *   - Banco de dados já criado com as tabelas do DDL (locadora.sql)
 *
 * Uso: php seeder_api.php  (executar apenas UMA vez, na carga inicial)
 *
 * @see https://developer.themoviedb.org/docs  Documentação oficial da API TMDB
 */
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/src/Models/Filmes.php';

// Permite que a carga tenha tempo suficiente para processar várias páginas.
// O padrão do PHP é 30 segundos, insuficiente para ~100 páginas de requisições HTTP.
set_time_limit(300);

class SeederFilmesAPI
{
    // Conexão com o banco de dados (PDO)
    private PDO $db;

    // Modelo responsável pelas operações na tabela de filmes
    private Filmes $filmeModel;

    // Chave de autenticação da API do TMDB.
    // Em produção, a chave deve vir de uma variável de ambiente ou de um cofre
    // de segredos, nunca ficar exposta diretamente no código-fonte.
    private string $apiKey;

    /**
     * Construtor da classe.
     * Inicializa a conexão com o banco, o modelo de filmes e carrega a chave da API.
     *
     * @param PDO $conexao Instância de conexão com o banco de dados.
     * @throws Exception Caso a chave da API não seja encontrada nas variáveis de ambiente.
     */
    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
        $this->filmeModel = new Filmes($conexao);
        
        // Obtém a chave da API do TMDB a partir do arquivo .env
        $this->apiKey = $_ENV['TMDB_API_KEY'] ?? '';

        // Validação crucial: o script não pode funcionar sem a chave da API
        if (empty($this->apiKey)) {
            throw new Exception("Chave da API do TMDb não encontrada. Verifique o arquivo .env.");
        }
    }

    /**
     * Orquestra o processo principal de população do banco de dados (Carga Inicial).
     * Este método gerencia a busca de filmes na API, inserção no banco e controle do estoque.
     * 
     * @param int $metaDvds A quantidade total de DVDs que desejamos ter no estoque.
     */
    public function popular(int $metaDvds = 2000): void
    {
        $dvdsGerados = 0; // Contador de DVDs físicos inseridos no sistema
        $pagina = 1;      // Controle de paginação da API do TMDB

        // Importa os gêneros do TMDB e monta o mapa entre o ID do TMDB e o
        // ID local, garantindo a integridade referencial da tabela generos.
        // Passo essencial para que o filme tenha a referência correta de gênero.
        $mapaGeneros = $this->popularGeneros();
        $generosLocais = array_values($mapaGeneros);

        echo "Iniciando consumo da API...\n";

        // Loop principal: continua buscando filmes até atingirmos a meta de estoque
        while ($dvdsGerados < $metaDvds) {
            // Busca a página atual de filmes populares na API
            $dadosDaApi = $this->buscarPaginaAPI($pagina);

            // A ausência de resultados indica que não há mais páginas válidas,
            // ou seja, a API não tem mais filmes para retornar nesta lista.
            if (empty($dadosDaApi['results'])) {
                break;
            }

            // Itera sobre cada filme retornado na página atual
            foreach ($dadosDaApi['results'] as $item) {
                // Verificação de segurança: interrompe o foreach se a meta já foi batida
                if ($dvdsGerados >= $metaDvds) break;

                // Pula filmes sem título válido (dados incompletos da API)
                $titulo = trim((string) ($item['title'] ?? ''));
                if ($titulo === '') {
                    continue;
                }

                // ---- PROTEÇÃO CONTRA DUPLICATAS ----
                // Verifica se o filme já existe no banco pelo título.
                // Evita registros duplicados caso o seeder seja executado mais de uma vez
                // ou caso a API retorne o mesmo filme em páginas diferentes.
                $stmtVerifica = $this->db->prepare(
                    'SELECT COUNT(*) FROM filmes WHERE titulo = :titulo'
                );
                $stmtVerifica->execute(['titulo' => substr($titulo, 0, 100)]);
                $jaExiste = (int) $stmtVerifica->fetchColumn() > 0;
                $stmtVerifica->closeCursor(); // Libera o buffer do statement

                if ($jaExiste) {
                    continue; // Filme já cadastrado, pula para o próximo
                }

                // Para cada filme, fazemos uma requisição adicional para buscar os atores (elenco).
                // Isso representa mais uma chamada HTTP por filme, justificando o rate limiting.
                $elenco = $this->buscarElencoAPI((int) $item['id']);

                // Converte o formato da API para os campos esperados pelo
                // modelo, usando o gênero correto do filme via mapeamento.
                $tmdbGenreId = $item['genre_ids'][0] ?? null;
                $dadosFilme = [
                    'titulo'     => substr($titulo, 0, 100),         // Usa o título já validado e sanitizado
                    'valor'      => 10.00,                          // Valor fixo de locação (R$ 10,00)
                    'id_genero'  => isset($mapaGeneros[$tmdbGenreId])
                        ? $mapaGeneros[$tmdbGenreId]
                        : $generosLocais[array_rand($generosLocais)], // Fallback: pega um gênero aleatório caso não exista
                    'poster_url' => isset($item['poster_path']) && $item['poster_path'] !== ''
                        ? 'https://image.tmdb.org/t/p/w500' . $item['poster_path'] // Monta a URL completa do pôster
                        : null
                ];

                // Inicia uma transação no banco de dados. 
                // Isso garante o conceito de ACID (Atomicidade, Consistência, Isolamento e Durabilidade).
                // Ou inserimos o Filme, Elenco e Estoque juntos, ou nada é inserido, evitando dados parciais.
                $this->db->beginTransaction();

                try {
                    // 1. Cria o filme
                    $this->filmeModel->criar($dadosFilme);
                    $idFilmeInserido = (int) $this->db->lastInsertId();

                    // 2. Insere e vincula os atores a este filme
                    $this->inserirAtores((int) $idFilmeInserido, $elenco);

                    // 3. Cada filme recebe uma quantidade aleatória de cópias físicas para simular o estoque real.
                    $qtdEstoque = mt_rand(1, 5);
                    $this->inserirDvds((int) $idFilmeInserido, $qtdEstoque);
                    
                    // Se tudo deu certo, efetiva a transação
                    $this->db->commit();
                } catch (Throwable $erro) {
                    // Em caso de qualquer erro, desfaz tudo que foi feito dentro desta iteração
                    $this->db->rollBack();
                    throw $erro;
                }

                // Incrementa o número total de DVDs gerados com o estoque que acabamos de inserir
                $dvdsGerados += $qtdEstoque;
            }

            // Avança para a próxima página da API para o próximo ciclo do while
            $pagina++;

            // Rate Limiting (Controle de Requisições):
            // Evita realizar requisições em sequência rapidamente, reduzindo o risco de
            // atingir o limite de requisições da API e ser bloqueado. Aguarda 0.25s.
            usleep(250000);
        }

        echo "Carga finalizada. Total de DVDs físicos gerados: {$dvdsGerados}\n";
    }

    /**
     * Busca uma página de filmes populares na API do TMDB.
     * 
     * @param int $pagina Número da página a ser buscada.
     * @return array Resposta decodificada em formato de array associativo.
     */
    private function buscarPaginaAPI(int $pagina): array
    {
        // Monta a URL de busca de filmes populares, informando o idioma pt-BR e a página
        $url = "https://api.themoviedb.org/3/movie/popular?api_key={$this->apiKey}&language=pt-BR&page={$pagina}";

        return $this->fazerRequisicaoAPI($url);
    }

    /**
     * Busca os créditos (elenco e equipe) de um filme específico na API.
     * 
     * @param int $idFilmeAPI ID do filme na base de dados do TMDB.
     * @return array Lista contendo os 10 atores principais do filme.
     */
    private function buscarElencoAPI(int $idFilmeAPI): array
    {
        // Monta a URL para pegar os atores do filme
        $url = "https://api.themoviedb.org/3/movie/{$idFilmeAPI}/credits?api_key={$this->apiKey}&language=pt-BR";
        $dados = $this->fazerRequisicaoAPI($url);

        // Retorna apenas os 10 primeiros membros do elenco (cast) para economizar processamento e banco
        return array_slice($dados['cast'] ?? [], 0, 10);
    }

    /**
     * Busca a lista oficial de gêneros de filmes na API do TMDB.
     *
     * O endpoint /genre/movie/list retorna objetos com "id" (o ID do gênero
     * dentro do TMDB) e "name" (o nome localizado de acordo com o idioma).
     */
    private function buscarGenerosAPI(): array
    {
        $url = "https://api.themoviedb.org/3/genre/movie/list?api_key={$this->apiKey}&language=pt-BR";
        $dados = $this->fazerRequisicaoAPI($url);

        return $dados['genres'] ?? [];
    }

    /**
     * Importa os gêneros do TMDB para a tabela local "generos".
     *
     * Cada filme da API possui um campo genre_ids com IDs do TMDB, que não
     * coincidem com os IDs gerados automaticamente pelo banco local. Por isso
     * esta função reutiliza os gêneros já existentes ou cria novos, e retorna
     * um mapa associando o ID do TMDB ao ID local.
     *
     * @return array<int, int> Ex.: [28 => 1, 12 => 7, ...]
     */
    private function popularGeneros(): array
    {
        $generos = $this->buscarGenerosAPI();

        if (empty($generos)) {
            throw new RuntimeException('Nenhum gênero foi retornado pela API do TMDB.');
        }

        $buscarGenero = $this->db->prepare('SELECT id FROM generos WHERE genero = :genero LIMIT 1');
        $criarGenero = $this->db->prepare('INSERT INTO generos (genero) VALUES (:genero)');

        $mapa = [];
        $novos = 0;

        foreach ($generos as $genero) {
            $nome = trim((string) ($genero['name'] ?? ''));
            if ($nome === '' || !isset($genero['id'])) {
                continue;
            }

            $nome = substr($nome, 0, 45);

            $buscarGenero->execute(['genero' => $nome]);
            $idLocal = $buscarGenero->fetchColumn();
            $buscarGenero->closeCursor(); // Libera o buffer para a próxima iteração

            if ($idLocal === false) {
                $criarGenero->execute(['genero' => $nome]);
                $idLocal = (int) $this->db->lastInsertId();
                $novos++;
            }

            $mapa[(int) $genero['id']] = (int) $idLocal;
        }

        if (empty($mapa)) {
            throw new RuntimeException('Não foi possível importar nenhum gênero do TMDB.');
        }

        echo "Gêneros importados: {$novos} novos, " . count($mapa) . " mapeados.\n";

        return $mapa;
    }

    /**
     * Função utilitária centralizada para realizar as chamadas HTTP à API do TMDB.
     * Utiliza a biblioteca cURL nativa do PHP, que é a extensão padrão para
     * requisições HTTP em scripts de linha de comando (CLI).
     *
     * Tratamentos de erro implementados:
     *   - Falha de conexão (sem internet, DNS, timeout) → RuntimeException
     *   - HTTP status != 200 (401 = chave inválida, 429 = rate limit) → RuntimeException
     *   - JSON inválido ou resposta vazia → retorna array vazio (fallback seguro)
     * 
     * @param string $url URL completa do endpoint a ser consumido.
     * @return array Resposta JSON convertida em um array associativo do PHP.
     * @throws RuntimeException Se a conexão falhar ou a API retornar erro HTTP.
     */
    private function fazerRequisicaoAPI(string $url): array
    {
        // Inicializa a sessão do cURL — cada chamada cria uma sessão independente
        $ch = curl_init();
        
        // Define as configurações (options) do cURL
        curl_setopt($ch, CURLOPT_URL, $url);           // URL da requisição
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retornar a resposta como string em vez de imprimir na tela

        // A validação SSL deve permanecer habilitada em ambientes reais
        // para evitar ataques man-in-the-middle (interceptação de tráfego).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        // Executa a requisição HTTP GET
        $resposta = curl_exec($ch);
        
        // Trata falhas na conexão ou comunicação (ex: sem internet, API fora do ar)
        if ($resposta === false) {
            $erro = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Erro ao consultar a API do TMDB: {$erro}");
        }

        // ---- VALIDAÇÃO DO STATUS HTTP ----
        // Verifica se a API retornou sucesso (200 OK).
        // Erros comuns:
        //   401 = chave da API inválida ou expirada
        //   404 = endpoint não encontrado
        //   429 = excedeu o limite de requisições (rate limit)
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch); // Encerra a sessão do cURL para liberar recursos

        if ($httpCode !== 200) {
            throw new RuntimeException(
                "A API do TMDB retornou HTTP {$httpCode}. "
                . "Verifique a chave da API e o limite de requisições."
            );
        }

        // Decodifica o JSON retornado para um array associativo.
        // Retorna uma lista vazia para que o chamador trate respostas inválidas
        // ou sem resultados sem tentar acessar índices inexistentes.
        return json_decode($resposta, true) ?: [];
    }

    /**
     * Relaciona atores a um filme específico, garantindo que o ator exista na tabela atores.
     * Esta é uma modelagem clássica de relacionamento N:M (Muitos para Muitos), 
     * onde temos Filmes, Atores e a tabela pivot atores_filme.
     * 
     * @param int $idFilme ID do filme recém inserido.
     * @param array $elenco Array de atores retornados da API.
     */
    private function inserirAtores(int $idFilme, array $elenco): void
    {
        // Prepara as querys uma única vez para otimizar desempenho ao inseri-las repetidamente num loop
        $buscarAtor = $this->db->prepare('SELECT id FROM atores WHERE nome = :nome LIMIT 1');
        $criarAtor = $this->db->prepare('INSERT INTO atores (nome) VALUES (:nome)');
        $vincularAtor = $this->db->prepare(
            'INSERT INTO atores_filme (id_filme, id_ator, personagem)
             VALUES (:id_filme, :id_ator, :personagem)'
        );

        foreach ($elenco as $ator) {
            $nome = trim((string) ($ator['name'] ?? ''));
            if ($nome === '') {
                continue; // Pula se não houver nome válido
            }

            // Verifica se o ator já está cadastrado
            $buscarAtor->execute(['nome' => substr($nome, 0, 100)]);
            $idAtor = $buscarAtor->fetchColumn();
            $buscarAtor->closeCursor(); // Libera o buffer para reutilizar o statement no próximo ciclo

            // Se o ator não for encontrado no banco (é falso), vamos criá-lo
            if ($idAtor === false) {
                $criarAtor->execute(['nome' => substr($nome, 0, 100)]);
                $idAtor = $this->db->lastInsertId(); // Pega o ID gerado pelo auto_increment
            }

            // Finalmente, cria a relação na tabela pivot
            $vincularAtor->execute([
                'id_filme' => $idFilme,
                'id_ator' => (int) $idAtor,
                'personagem' => substr(trim((string) ($ator['character'] ?? '')), 0, 100) ?: 'Não informado'
            ]);
        }
    }

    /**
     * Cria os registros físicos de DVD na tabela de estoque.
     * Simula a compra de cópias do filme pela locadora.
     * 
     * @param int $idFilme O filme que recebeu o estoque
     * @param int $quantidade O número de cópias deste filme que chegaram
     */
    private function inserirDvds(int $idFilme, int $quantidade): void
    {
        // Usa uma instrução preparada (Prepared Statement) para separar os dados da instrução SQL, 
        // prevenindo ataques de SQL Injection e permitindo que o SGBD cache a query.
        $stmt = $this->db->prepare("INSERT INTO dvds (id_filme, quantidade) VALUES (:id_filme, :quantidade)");
        $stmt->execute([
            'id_filme'   => $idFilme,
            'quantidade' => $quantidade
        ]);
    }
}

// Executa a carga inicial com a meta de DVDs definida para este ambiente.
$seeder = new SeederFilmesAPI($conn);
$seeder->popular(2000);
?>