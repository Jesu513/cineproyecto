<?php

namespace App\Repositories;

use App\Models\UserRating;
use App\Models\MovieView;
use App\Database\Connection;

class RatingRepository
{
    protected UserRating $ratings;
    protected MovieView $views;
    protected Connection $db;

    public function __construct()
    {
        $this->ratings = new UserRating();
        $this->views = new MovieView();
        $this->db = Connection::getInstance();
    }

    public function rateMovie(array $data): array
    {
        // Si ya calificó, actualizar
        $existing = $this->ratings->findBy('user_id', $data['user_id']);
        
        if ($existing && $existing['movie_id'] == $data['movie_id']) {
            return $this->ratings->update($existing['id'], $data);
        }

        return $this->ratings->create($data);
    }

    public function trackView(int $userId, int $movieId)
    {
        $sql = "INSERT INTO movie_views (user_id, movie_id, view_count)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE view_count = view_count + 1, last_viewed_at = NOW()";
        $this->db->execute($sql, [$userId, $movieId]);
    }

    public function getUserRatings(int $userId): array
    {
        return $this->ratings->findAllBy('user_id', $userId);
    }

    public function getRatings(): array
    {
        return $this->ratings->query()->get();
    }
}
