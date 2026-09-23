<?php
Class EmprestimoModel
{
    private PDO $db;

    public function __construct(PDO $conexao)
    {
        $this->db = $conexao;
    }

    public function visualizarEmpréstimos()
    {

    }

    public function calcularAtraso()
    {

    }
    
}