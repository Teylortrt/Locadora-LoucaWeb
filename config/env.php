<?php
// Arquivo: config/env.php

$caminhoEnv = __DIR__ . '/../.env';

if (file_exists($caminhoEnv)) {
    // A função parse_ini_file converte o arquivo texto em um Array PHP
    // (INI_SCANNER_RAW evita erros de sintaxe com valores contendo caracteres especiais)
    $variaveis = parse_ini_file($caminhoEnv, false, INI_SCANNER_RAW);

    if (is_array($variaveis)) {
        foreach ($variaveis as $chave => $valor) {
            // Injeta a variável na superglobal $_ENV
            $_ENV[$chave] = $valor;
        }
    }
} else {
    die("Erro crítico: Arquivo .env não encontrado.");
}