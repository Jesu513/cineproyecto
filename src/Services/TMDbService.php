<?php
// ============================================
// src/Services/TMDbService.php
// Servicio de integración con The Movie Database API
// ============================================

namespace App\Services;

use App\Utils\Logger;
use Exception;

class TMDbService
{
    private array $config;
    private Logger $logger;
    private string $baseUrl;
    private string $apiKey;
    private string $imageBaseUrl;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/tmdb.php';
        $this->logger = new Logger();
        $this->baseUrl = $this->config['api_url'];
        $this->apiKey = $this->config['api_key'];
        $this->imageBaseUrl = $this->config['image_base_url'];

        if (empty($this->apiKey)) {
            throw new Exception('TMDb API key no configurada');
        }
    }

    /**
     * Buscar películas por título
     */
    public function searchMovies(string $query, int $page = 1): array
    {
        try {
            $endpoint = '/search/movie';
            $params = [
                'query' => $query,
                'page' => $page,
                'language' => $this->config['language'],
                'include_adult' => $this->config['filters']['include_adult'] ? 'true' : 'false'
            ];

            $response = $this->makeRequest($endpoint, $params);

            $this->logger->info('TMDb search', [
                'query' => $query,
                'results' => $response['total_results'] ?? 0
            ]);

            return $response;

        } catch (Exception $e) {
            $this->logger->exception($e, ['query' => $query]);
            throw $e;
        }
    }

    /**
     * Obtener detalles de una película por ID de TMDb
     */
    public function getMovieDetails(int $tmdbId): array
    {
        try {
            $endpoint = "/movie/{$tmdbId}";
            $params = [
                'language' => $this->config['language'],
                'append_to_response' => 'credits,videos,images'
            ];

            $movie = $this->makeRequest($endpoint, $params);

            // Transformar al formato de nuestra BD
            $transformed = $this->transformMovieData($movie);

            $this->logger->info('TMDb movie details retrieved', [
                'tmdb_id' => $tmdbId,
                'title' => $transformed['title']
            ]);

            return $transformed;

        } catch (Exception $e) {
            $this->logger->exception($e, ['tmdb_id' => $tmdbId]);
            throw $e;
        }
    }

    /**
     * Obtener películas populares
     */
    public function getPopularMovies(int $page = 1): array
    {
        try {
            $endpoint = '/movie/popular';
            $params = [
                'page' => $page,
                'language' => $this->config['language']
            ];

            return $this->makeRequest($endpoint, $params);

        } catch (Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Obtener películas en cartelera
     */
    public function getNowPlaying(int $page = 1): array
    {
        try {
            $endpoint = '/movie/now_playing';
            $params = [
                'page' => $page,
                'language' => $this->config['language'],
                'region' => $this->config['region']
            ];

            return $this->makeRequest($endpoint, $params);

        } catch (Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Obtener próximos estrenos
     */
    public function getUpcoming(int $page = 1): array
    {
        try {
            $endpoint = '/movie/upcoming';
            $params = [
                'page' => $page,
                'language' => $this->config['language'],
                'region' => $this->config['region']
            ];

            return $this->makeRequest($endpoint, $params);

        } catch (Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Obtener películas mejor calificadas
     */
    public function getTopRated(int $page = 1): array
    {
        try {
            $endpoint = '/movie/top_rated';
            $params = [
                'page' => $page,
                'language' => $this->config['language']
            ];

            return $this->makeRequest($endpoint, $params);

        } catch (Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Obtener géneros de películas
     */
    public function getGenres(): array
    {
        try {
            $endpoint = '/genre/movie/list';
            $params = [
                'language' => $this->config['language']
            ];

            $response = $this->makeRequest($endpoint, $params);
            return $response['genres'] ?? [];

        } catch (Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Transformar datos de TMDb a formato de nuestra BD
     */
    private function transformMovieData(array $movie): array
    {
        // Obtener trailer de YouTube
        $trailerUrl = null;
        if (isset($movie['videos']['results'])) {
            foreach ($movie['videos']['results'] as $video) {
                if ($video['type'] === 'Trailer' && $video['site'] === 'YouTube') {
                    $trailerUrl = 'https://www.youtube.com/watch?v=' . $video['key'];
                    break;
                }
            }
        }

        // Extraer géneros
        $genres = [];
        if (isset($movie['genres'])) {
            $genres = array_map(fn($g) => $g['name'], $movie['genres']);
        }

        // Extraer cast (actores principales)
        $cast = [];
        if (isset($movie['credits']['cast'])) {
            $cast = array_slice(
                array_map(fn($actor) => [
                    'name' => $actor['name'],
                    'character' => $actor['character'],
                    'profile_path' => $actor['profile_path']
                ], $movie['credits']['cast']),
                0,
                10
            );
        }

        // Obtener director
        $director = null;
        if (isset($movie['credits']['crew'])) {
            foreach ($movie['credits']['crew'] as $member) {
                if ($member['job'] === 'Director') {
                    $director = $member['name'];
                    break;
                }
            }
        }

        // Construir URLs de imágenes
        $posterUrl = $movie['poster_path'] 
            ? $this->getImageUrl($movie['poster_path'], 'poster')
            : null;

        $backdropUrl = $movie['backdrop_path']
            ? $this->getImageUrl($movie['backdrop_path'], 'backdrop')
            : null;

        return [
            'tmdb_id' => $movie['id'],
            'title' => $movie['title'],
            'original_title' => $movie['original_title'],
            'synopsis' => $movie['overview'],
            'duration' => $movie['runtime'] ?? null,
            'release_date' => $movie['release_date'] ?? null,
            'poster_url' => $posterUrl,
            'backdrop_url' => $backdropUrl,
            'trailer_url' => $trailerUrl,
            'rating' => round($movie['vote_average'], 1),
            'language' => $movie['original_language'],
            'genres' => json_encode($genres),
            'cast' => json_encode($cast),
            'director' => $director,
            'classification' => $movie['adult'] ? 'R' : 'PG-13',
            'is_active' => true
        ];
    }

    /**
     * Construir URL completa de imagen
     */
    public function getImageUrl(string $path, string $type = 'poster'): string
    {
        $size = $this->config['preferred_sizes'][$type] ?? 'original';
        return $this->imageBaseUrl . $size . $path;
    }

    /**
     * Hacer request a la API de TMDb
     */
    private function makeRequest(string $endpoint, array $params = []): array
    {
        // Agregar API key a los parámetros
        $params['api_key'] = $this->apiKey;

        // Construir URL
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);

        // Configurar opciones de cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json'
            ]
        ]);

        // Ejecutar request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Verificar errores de cURL
        if ($error) {
            throw new Exception("TMDb API request failed: {$error}");
        }

        // Decodificar respuesta
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from TMDb API');
        }

        // Verificar código HTTP
        if ($httpCode !== 200) {
            $errorMessage = $data['status_message'] ?? 'Unknown error';
            throw new Exception("TMDb API error: {$errorMessage} (HTTP {$httpCode})");
        }

        return $data;
    }

    /**
     * Buscar películas por género
     */
    public function getMoviesByGenre(int $genreId, int $page = 1): array
    {
        try {
            $endpoint = '/discover/movie';
            $params = [
                'with_genres' => $genreId,
                'page' => $page,
                'language' => $this->config['language'],
                'sort_by' => 'popularity.desc'
            ];

            return $this->makeRequest($endpoint, $params);

        } catch (Exception $e) {
            $this->logger->exception($e, ['genre_id' => $genreId]);
            throw $e;
        }
    }

    /**
     * Obtener películas similares
     */
    public function getSimilarMovies(int $tmdbId, int $page = 1): array
    {
        try {
            $endpoint = "/movie/{$tmdbId}/similar";
            $params = [
                'page' => $page,
                'language' => $this->config['language']
            ];

            return $this->makeRequest($endpoint, $params);

        } catch (Exception $e) {
            $this->logger->exception($e, ['tmdb_id' => $tmdbId]);
            throw $e;
        }
    }

    /**
     * Obtener recomendaciones basadas en una película
     */
    public function getRecommendations(int $tmdbId, int $page = 1): array
    {
        try {
            $endpoint = "/movie/{$tmdbId}/recommendations";
            $params = [
                'page' => $page,
                'language' => $this->config['language']
            ];

            return $this->makeRequest($endpoint, $params);

        } catch (Exception $e) {
            $this->logger->exception($e, ['tmdb_id' => $tmdbId]);
            throw $e;
        }
    }
}