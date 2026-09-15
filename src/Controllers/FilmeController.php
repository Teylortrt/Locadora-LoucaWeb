<?php

class FilmesController
{
    private PDO $db;

    // Recebe a conexão pronta pelo construtor
    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
    }

    public function adicionarFilme($dados): void
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $titulo = $dados['titulo'] ?? '';
        $genero = $dados['id_genero'] ?? '';
        $poster = $dados['poster_url'] ?? '';
        $valor = $dados['valor'] ?? '';

        $sql = "INSERT INTO filmes (titulo, id_genero, valor, poster_url) VALUES (:titulo, :id_genero, :valor, :poster_url)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':id_genero', $genero);
        $stmt->bindParam(':valor', $valor);
        $stmt->bindParam(':poster_url', $poster);

        if ($stmt->execute()) {
            echo "Cadastro realizado com sucesso!";
        } else {
            echo "Erro ao cadastrar: " . $stmt->errorInfo()[2];
        exit;
        }

    }
    }
}