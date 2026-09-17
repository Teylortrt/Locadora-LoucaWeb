<?php

class TmdbClient
{
    // A informação nasce e fica retida na classe
    private string $apiKey;
    private string $baseUrl = 'https://api.themoviedb.org/3';

    public function __construct()
    {
        // A chave da API é lida do arquivo .env
        $this->apiKey = $_ENV['TMDB_API_KEY'] ?? '';
        
        if (empty($this->apiKey)) {
            throw new Exception("Chave da API do TMDb não encontrada. Verifique o arquivo .env.");
        }
    }

    public function buscarFilmePorTitulo(string $titulo): ?array
    {
        $url = "{$this->baseUrl}/search/movie?api_key={$this->apiKey}&language=pt-BR&query=" . urlencode($titulo);
        $response = file_get_contents($url);
        
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['results'] ?? null;
    }

    public function buscarSinopse(string $titulo): ?string
    {
        $filmes = $this->buscarFilmePorTitulo($titulo);
        
        if (empty($filmes)) {
            return null;
        }

        // Retorna a sinopse do primeiro filme encontrado
        return $filmes[0]['overview'] ?? null;
    }
}