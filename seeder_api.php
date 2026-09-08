<?php
/**
 * Popula o catálogo de filmes e o estoque de DVDs usando a API do TMDB.
 *
 * Este script deve ser executado em um ambiente de desenvolvimento ou em uma
 * operação controlada de carga inicial, pois cria registros no banco de dados.
 */
require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/src/Filmes.php';

// Permite que a carga tenha tempo suficiente para processar várias páginas.
set_time_limit(300);

class SeederFilmesAPI
{
    private PDO $db;

    private Filmes $filmeModel;

    // Em produção, a chave deve vir de uma variável de ambiente ou de um cofre
    // de segredos, nunca ficar exposta diretamente no código-fonte.
    private string $apiKey = 'SUA_CHAVE_TMDB';

    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
        $this->filmeModel = new Filmes($conexao);
    }

    public function popular(int $metaDvds = 2000): void
    {
        $dvdsGerados = 0;
        $pagina = 1;

        // Os filmes precisam receber um gênero existente para respeitar a
        // integridade referencial do banco de dados.
        $stmt = $this->db->query("SELECT id FROM generos");
        $generosValidos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($generosValidos)) {
            die("Erro: Cadastre gêneros no banco antes de rodar o seeder.\n");
        }

        echo "Iniciando consumo da API...\n";

        while ($dvdsGerados < $metaDvds) {
            $dadosDaApi = $this->buscarPaginaAPI($pagina);

            // A ausência de resultados indica que não há mais páginas válidas.
            if (empty($dadosDaApi['results'])) {
                break;
            }

            foreach ($dadosDaApi['results'] as $item) {
                if ($dvdsGerados >= $metaDvds) break;

                // Converte o formato da API para os campos esperados pelo
                // modelo e gera valores locais para preço e gênero.
                $dadosFilme = [
                    'titulo'    => substr($item['title'], 0, 100),
                    'valor'     => mt_rand(500, 1500) / 100,
                    'id_genero' => $generosValidos[array_rand($generosValidos)]
                ];

                $this->filmeModel->criar($dadosFilme);
                $idFilmeInserido = $this->db->lastInsertId();

                // Cada filme recebe uma quantidade aleatória de cópias físicas.
                $qtdEstoque = mt_rand(1, 5);
                $this->inserirDvds((int)$idFilmeInserido, $qtdEstoque);

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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // A validação SSL deve permanecer habilitada em ambientes reais.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $resposta = curl_exec($ch);
        curl_close($ch);

        // Retorna uma lista vazia para que o chamador trate respostas inválidas
        // ou sem resultados sem tentar acessar índices inexistentes.
        return json_decode($resposta, true) ?: [];
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