<?php
class Ator
{
    private PDO $db;

    // Recebe a conexão pronta pelo construtor
    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
    }

    // Retorna os dados para quem chamou o método
    public function listarTodos(): array
    {
        $sql = "SELECT * FROM atores ORDER BY nome";
        $stmt = $this->db->query($sql);
        
        return $stmt->fetchAll(); // PDO::FETCH_ASSOC já foi definido no arquivo de conexão
    }



    public function listarFilmeAtuado(int $idAtor) : array
    {
        $sql = "SELECT f.id, f.titulo, fa.personagem FROM filmes f
                JOIN atores_filme fa ON f.id = fa.id_filme
                WHERE fa.id_ator = :idAtor
                ORDER BY f.titulo";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':idAtor', $idAtor, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}