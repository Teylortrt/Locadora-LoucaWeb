<?php
class EmprestimoController 
{
    private PDO $db;

    public function __construct(PDO $conexao) {
        $this->db = $conexao;
    }

}