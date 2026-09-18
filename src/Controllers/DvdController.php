<?php
class DVDController 
{
    private PDO $db;

    public function __construct(PDO $conexao) {
        $this->db = $conexao;
    }

    public function inserirDVD(int $idFilme, int $quantidade): void 
    {
        $sql = "INSERT INTO dvds (id_filme, quantidade) VALUES (:id_filme, :quantidade)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id_filme', $idFilme, PDO::PARAM_INT);
        $stmt->bindParam(':quantidade', $quantidade, PDO::PARAM_INT);
        $stmt->execute();
    }
}