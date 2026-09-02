<?php

class FilmesController
{
    private PDO $db;

    // Recebe a conexão pronta pelo construtor
    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
    }

}