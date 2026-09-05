<?php
// Arquivo: seeder_api.php
require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/src/Filmes.php';

set_time_limit(300); 

// CORREÇÃO 1: Nome da classe ajustado para bater com a instância no final do arquivo
class SeederFilmesAPI
{
    private PDO $db;
    
    // CORREÇÃO 2: Nome da propriedade ajustado para bater com o construtor
    private Filmes $filmeModel; 
    
    private string $apiKey = '390178bbb0fef86f227fa45ee73403e0';

    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
        $this->filmeModel = new Filmes($conexao);
    }

    public function popular(int $metaDvds = 2000): void
    {
        $dvdsGerados = 0;
        $pagina = 1;

        $stmt = $this->db->query("SELECT id FROM generos");
        $generosValidos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($generosValidos)) {
            die("Erro: Cadastre gêneros no banco antes de rodar o seeder.\n");
        }

        echo "Iniciando consumo da API...\n";

        while ($dvdsGerados < $metaDvds) {
            $dadosDaApi = $this->buscarPaginaAPI($pagina);
            
            if (empty($dadosDaApi['results'])) {
                break; 
            }

            foreach ($dadosDaApi['results'] as $item) {
                if ($dvdsGerados >= $metaDvds) break;

                $dadosFilme = [
                    'titulo'    => substr($item['title'], 0, 100),
                    'valor'     => mt_rand(500, 1500) / 100,
                    'id_genero' => $generosValidos[array_rand($generosValidos)]
                ];

                $this->filmeModel->criar($dadosFilme);
                $idFilmeInserido = $this->db->lastInsertId();

                $qtdEstoque = mt_rand(1, 5); 
                $this->inserirDvds((int)$idFilmeInserido, $qtdEstoque);

                $dvdsGerados += $qtdEstoque;
            }
            
            $pagina++;
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $resposta = curl_exec($ch);
        curl_close($ch);

        return json_decode($resposta, true) ?: [];
    }

    private function inserirDvds(int $idFilme, int $quantidade): void
    {
        $stmt = $this->db->prepare("INSERT INTO dvds (id_filme, quantidade) VALUES (:id_filme, :quantidade)");
        $stmt->execute([
            'id_filme'   => $idFilme,
            'quantidade' => $quantidade
        ]);
    }
}

$seeder = new SeederFilmesAPI($conn);
$seeder->popular(2000);
?>