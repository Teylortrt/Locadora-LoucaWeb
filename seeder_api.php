<?php
/**
 * Popula o catálogo de filmes e o estoque de DVDs usando a API do TMDB.
 *
 * Este script deve ser executado em um ambiente de desenvolvimento ou em uma
 * operação controlada de carga inicial, pois cria registros no banco de dados.
 */
require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/src/Models/Filmes.php';

// Permite que a carga tenha tempo suficiente para processar várias páginas.
set_time_limit(300);

class SeederFilmesAPI
{
    private PDO $db;

    private Filmes $filmeModel;

    // Em produção, a chave deve vir de uma variável de ambiente ou de um cofre
    // de segredos, nunca ficar exposta diretamente no código-fonte.
    private string $apiKey = 'SUA_CHAVE_API';

    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
        $this->filmeModel = new Filmes($conexao);
    }

    public function popular(int $metaDvds = 2000): void
    {
        $dvdsGerados = 0;
        $pagina = 1;

        // Importa os gêneros do TMDB e monta o mapa entre o ID do TMDB e o
        // ID local, garantindo a integridade referencial da tabela generos.
        $mapaGeneros = $this->popularGeneros();
        $generosLocais = array_values($mapaGeneros);

        echo "Iniciando consumo da API...\n";

        while ($dvdsGerados < $metaDvds) {
            $dadosDaApi = $this->buscarPaginaAPI($pagina);

            // A ausência de resultados indica que não há mais páginas válidas.
            if (empty($dadosDaApi['results'])) {
                break;
            }

            foreach ($dadosDaApi['results'] as $item) {
                if ($dvdsGerados >= $metaDvds) break;

                $elenco = $this->buscarElencoAPI((int) $item['id']);

                // Converte o formato da API para os campos esperados pelo
                // modelo, usando o gênero correto do filme via mapeamento.
                $tmdbGenreId = $item['genre_ids'][0] ?? null;
                $dadosFilme = [
                    'titulo'     => substr($item['title'], 0, 100),
                    'valor'      => mt_rand(500, 1500) / 100,
                    'id_genero'  => isset($mapaGeneros[$tmdbGenreId])
                        ? $mapaGeneros[$tmdbGenreId]
                        : $generosLocais[array_rand($generosLocais)],
                    'poster_url' => isset($item['poster_path']) && $item['poster_path'] !== ''
                        ? 'https://image.tmdb.org/t/p/w500' . $item['poster_path']
                        : null
                ];

                $this->db->beginTransaction();

                try {
                    $this->filmeModel->criar($dadosFilme);
                    $idFilmeInserido = (int) $this->db->lastInsertId();

                    $this->inserirAtores((int) $idFilmeInserido, $elenco);

                    // Cada filme recebe uma quantidade aleatória de cópias físicas.
                    $qtdEstoque = mt_rand(1, 5);
                    $this->inserirDvds((int) $idFilmeInserido, $qtdEstoque);
                    $this->db->commit();
                } catch (Throwable $erro) {
                    $this->db->rollBack();
                    throw $erro;
                }

                $dvdsGerados += $qtdEstoque;
            }

            $pagina++;

            // Evita realizar requisições em sequência e reduz o risco de
            // atingir o limite de requisições da API.
            usleep(250000);
        }

        echo "Carga finalizada. Total de DVDs físicos gerados: {$dvdsGerados}\n";
    }

    private function buscarPaginaAPI(int $pagina): array
    {
        $url = "https://api.themoviedb.org/3/movie/popular?api_key={$this->apiKey}&language=pt-BR&page={$pagina}";

        return $this->fazerRequisicaoAPI($url);
    }

    private function buscarElencoAPI(int $idFilmeAPI): array
    {
        $url = "https://api.themoviedb.org/3/movie/{$idFilmeAPI}/credits?api_key={$this->apiKey}&language=pt-BR";
        $dados = $this->fazerRequisicaoAPI($url);

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

    private function fazerRequisicaoAPI(string $url): array
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // A validação SSL deve permanecer habilitada em ambientes reais.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $resposta = curl_exec($ch);
        if ($resposta === false) {
            $erro = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Erro ao consultar a API do TMDB: {$erro}");
        }
        curl_close($ch);

        // Retorna uma lista vazia para que o chamador trate respostas inválidas
        // ou sem resultados sem tentar acessar índices inexistentes.
        return json_decode($resposta, true) ?: [];
    }

    private function inserirAtores(int $idFilme, array $elenco): void
    {
        $buscarAtor = $this->db->prepare('SELECT id FROM atores WHERE nome = :nome LIMIT 1');
        $criarAtor = $this->db->prepare('INSERT INTO atores (nome) VALUES (:nome)');
        $vincularAtor = $this->db->prepare(
            'INSERT INTO atores_filme (id_filme, id_ator, personagem)
             VALUES (:id_filme, :id_ator, :personagem)'
        );

        foreach ($elenco as $ator) {
            $nome = trim((string) ($ator['name'] ?? ''));
            if ($nome === '') {
                continue;
            }

            $buscarAtor->execute(['nome' => substr($nome, 0, 100)]);
            $idAtor = $buscarAtor->fetchColumn();

            if ($idAtor === false) {
                $criarAtor->execute(['nome' => substr($nome, 0, 100)]);
                $idAtor = $this->db->lastInsertId();
            }

            $vincularAtor->execute([
                'id_filme' => $idFilme,
                'id_ator' => (int) $idAtor,
                'personagem' => substr(trim((string) ($ator['character'] ?? '')), 0, 100) ?: 'Não informado'
            ]);
        }
    }

    private function inserirDvds(int $idFilme, int $quantidade): void
    {
        // Usa uma instrução preparada para separar os dados da instrução SQL.
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