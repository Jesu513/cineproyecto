<?php

namespace App\Repositories;

use App\Models\Promotion;
use App\Database\Connection;

class PromotionRepository
{
    protected Promotion $promotion;
    protected Connection $db;

    public function __construct()
    {
        $this->promotion = new Promotion();
        $this->db = Connection::getInstance();
    }

    public function create(array $data): array
    {
        return $this->promotion->create($data);
    }

    public function findByCode(string $code): ?array
    {
        return $this->promotion->findBy('code', $code);
    }

    public function find(int $id): ?array
    {
        return $this->promotion->find($id);
    }

    public function updateUsage(int $id)
    {
        $sql = "UPDATE promotions SET uses_count = uses_count + 1 WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    public function getActivePromotions(): array
    {
        $today = date('Y-m-d');
        return $this->promotion
            ->query()
            ->where('is_active', true)
            ->where('valid_from', '<=', $today)
            ->where('valid_until', '>=', $today)
            ->get();
    }
}
