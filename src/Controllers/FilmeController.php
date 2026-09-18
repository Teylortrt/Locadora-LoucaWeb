<?php

class FilmesController
{
    private PDO $db;

    // Recebe a conexão pronta pelo construtor
    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
    }

    public function salvar(array $dados): int 
    {
        $sql = "INSERT INTO filmes (titulo, id_genero, valor, poster_url) 
                VALUES (:titulo, :id_genero, :valor, :poster_url)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':titulo'     => $dados['titulo'] ?? '',
            ':id_genero'  => $dados['id_genero'] ?? null,
            ':valor'      => $dados['valor'] ?? 0,
            ':poster_url' => $dados['poster_url'] ?? ''
        ]);

        return (int) $this->db->lastInsertId();
    }
}