<?php

namespace App\Services;

use App\Repositories\RatingRepository;
use App\Repositories\MovieRepository;

class RecommendationEngine
{
    protected RatingRepository $ratings;
    protected MovieRepository $movies;

    public function __construct()
    {
        $this->ratings = new RatingRepository();
        $this->movies = new MovieRepository();
    }

    /**
     * Generar recomendaciones para un usuario
     */
    public function getRecommendations(int $userId, int $limit = 10): array
    {
        $userRatings = $this->ratings->getUserRatings($userId);

        // Si no tiene historial → películas populares
        if (empty($userRatings)) {
            return $this->movies->getPopularMovies($limit);
        }

        // Filtrado colaborativo
        $collab = $this->collaborativeFiltering($userId);

        // Géneros favoritos
        $genreScore = $this->genreSimilarity($userId);

        // Ranking sumado
        $final = [];

        foreach ($collab as $movieId => $score) {
            $final[$movieId] = ($final[$movieId] ?? 0) + $score;
        }

        foreach ($genreScore as $movieId => $score) {
            $final[$movieId] = ($final[$movieId] ?? 0) + $score;
        }

        // Ordenar por puntaje total
        arsort($final);

        // Devolver películas en orden
        $recommendedIds = array_slice(array_keys($final), 0, $limit);

        return $this->movies->getMoviesByIds($recommendedIds);
    }

    /**
     * FILTRADO COLABORATIVO
     */
    private function collaborativeFiltering(int $userId): array
    {
        $allRatings = $this->ratings->getRatings();
        $matrix = [];

        // Construir matriz usuario → movie → rating
        foreach ($allRatings as $r) {
            $matrix[$r['user_id']][$r['movie_id']] = $r['rating'];
        }

        if (!isset($matrix[$userId])) return [];

        $scores = [];

        foreach ($matrix as $otherUser => $ratings) {
            if ($otherUser === $userId) continue;

            $similarity = $this->cosineSimilarity($matrix[$userId], $ratings);
            if ($similarity <= 0) continue;

            foreach ($ratings as $movieId => $rating) {
                if (!isset($matrix[$userId][$movieId])) {
                    if (!isset($scores[$movieId])) $scores[$movieId] = 0;
                    $scores[$movieId] += $similarity * $rating;
                }
            }
        }

        return $scores;
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0;
        $normA = 0;
        $normB = 0;

        foreach ($a as $key => $value) {
            if (isset($b[$key])) {
                $dot += $value * $b[$key];
            }
        }

        foreach ($a as $value) $normA += $value * $value;
        foreach ($b as $value) $normB += $value * $value;

        $den = (sqrt($normA) * sqrt($normB));
        return $den ? $dot / $den : 0;
    }

    /**
     * SIMILITUD POR GÉNEROS
     */
    private function genreSimilarity(int $userId): array
    {
        $rated = $this->ratings->getUserRatings($userId);
        $favoriteGenres = [];

        foreach ($rated as $r) {
            $movie = $this->movies->find($r['movie_id']);
            if ($movie && is_array($movie['genres'])) {
                foreach ($movie['genres'] as $g) {
                    if (!isset($favoriteGenres[$g])) $favoriteGenres[$g] = 0;
                    $favoriteGenres[$g] += $r['rating'];
                }
            }
        }

        // Puntuación
        $scores = [];
        $allMovies = $this->movies->getAll();

        foreach ($allMovies as $movie) {
            if (!is_array($movie['genres'])) continue;

            $score = 0;
            foreach ($movie['genres'] as $g) {
                if (isset($favoriteGenres[$g])) {
                    $score += $favoriteGenres[$g];
                }
            }

            if ($score > 0) {
                $scores[$movie['id']] = $score;
            }
        }

        return $scores;
    }
}
