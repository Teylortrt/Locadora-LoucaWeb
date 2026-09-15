<?php
Class Generos
{
    private PDO $db;

    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
    }

    public function listarGeneros(): array
    {
        $sql = "SELECT * FROM generos";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}