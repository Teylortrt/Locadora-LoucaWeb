<?php

namespace App\Controllers;

use App\Models\Usuario;

class AuthController
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    public function login(): void
    {
        $json = json_decode(file_get_contents('php://input'), true);

        if (empty($json['email']) || empty($json['senha'])) {
            http_response_code(400);
            echo json_encode(["erro" => "Email e senha são obrigatórios"]);
            return;
        }

        $usuario = $this->usuarioModel->buscarPorEmail($json['email']);

        if (!$usuario) {
            http_response_code(401);
            echo json_encode(["erro" => "Email ou senha inválidos"]);
            return;
        }

        if (!$this->usuarioModel->verificarSenha($json['senha'], $usuario['senha'])) {
            http_response_code(401);
            echo json_encode(["erro" => "Email ou senha inválidos"]);
            return;
        }

        // Remove a senha da resposta
        unset($usuario['senha']);

        http_response_code(200);
        echo json_encode([
            "mensagem" => "Login realizado com sucesso",
            "usuario"  => $usuario
        ]);
    }

    public function cadastrar(): void
    {
        $json = json_decode(file_get_contents('php://input'), true);

        if (empty($json['nome']) || empty($json['email']) || empty($json['senha'])) {
            http_response_code(400);
            echo json_encode(["erro" => "Nome, email e senha são obrigatórios"]);
            return;
        }

        // Verifica se o email já existe
        $existente = $this->usuarioModel->buscarPorEmail($json['email']);
        if ($existente) {
            http_response_code(409);
            echo json_encode(["erro" => "Email já cadastrado"]);
            return;
        }

        $id = $this->usuarioModel->criar($json);

        http_response_code(201);
        echo json_encode([
            "mensagem" => "Usuário cadastrado com sucesso",
            "id"       => $id
        ]);
    }
}
