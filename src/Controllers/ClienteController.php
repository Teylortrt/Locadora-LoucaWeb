<?php

namespace App\Controllers;

use App\Models\Cliente;

class ClienteController
{
    private Cliente $clienteModel;

    public function __construct()
    {
        $this->clienteModel = new Cliente();
    }

    public function listar(): void
    {
        http_response_code(200);
        echo json_encode($this->clienteModel->listar(), JSON_UNESCAPED_UNICODE);
    }

    public function buscarPorId(int $id): void
    {
        $cliente = $this->clienteModel->buscarPorId($id);

        if (!$cliente) {
            http_response_code(404);
            echo json_encode(["erro" => "Cliente não encontrado"], JSON_UNESCAPED_UNICODE);
            return;
        }

        http_response_code(200);
        echo json_encode($cliente, JSON_UNESCAPED_UNICODE);
    }

    public function cadastrar(): void
    {
        $json = json_decode(file_get_contents('php://input'), true);

        if (empty($json['nome']) || empty($json['sobrenome']) || empty($json['telefone']) || empty($json['endereco'])) {
            http_response_code(400);
            echo json_encode(["erro" => "Nome, sobrenome, telefone e endereço são obrigatórios"], JSON_UNESCAPED_UNICODE);
            return;
        }

        $id = $this->clienteModel->criar($json['nome'], $json['sobrenome'], $json['telefone'], $json['endereco']);

        http_response_code(201);
        echo json_encode([
            "mensagem" => "Cliente cadastrado com sucesso",
            "id"       => $id
        ], JSON_UNESCAPED_UNICODE);
    }

    public function atualizar(int $id): void
    {
        if (!$this->clienteModel->existe($id)) {
            http_response_code(404);
            echo json_encode(["erro" => "Cliente não encontrado"], JSON_UNESCAPED_UNICODE);
            return;
        }

        $json = json_decode(file_get_contents('php://input'), true);

        if (empty($json['nome']) || empty($json['sobrenome']) || empty($json['telefone']) || empty($json['endereco'])) {
            http_response_code(400);
            echo json_encode(["erro" => "Nome, sobrenome, telefone e endereço são obrigatórios"], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->clienteModel->atualizar($id, $json['nome'], $json['sobrenome'], $json['telefone'], $json['endereco']);

        http_response_code(200);
        echo json_encode(["mensagem" => "Cliente atualizado com sucesso"], JSON_UNESCAPED_UNICODE);
    }

    public function deletar(int $id): void
    {
        if (!$this->clienteModel->existe($id)) {
            http_response_code(404);
            echo json_encode(["erro" => "Cliente não encontrado"], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->clienteModel->deletar($id);

        http_response_code(200);
        echo json_encode(["mensagem" => "Cliente removido com sucesso"], JSON_UNESCAPED_UNICODE);
    }
}

