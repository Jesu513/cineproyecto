<?php

namespace App\Repositories;

use App\Models\Payment;
use App\Database\Connection;

class PaymentRepository
{
    protected Payment $payment;
    protected Connection $db;

    public function __construct()
    {
        $this->payment = new Payment();
        $this->db = Connection::getInstance();
    }

    public function create(array $data): array
    {
        return $this->payment->create($data);
    }

    public function find(int $id): ?array
    {
        return $this->payment->find($id);
    }

    public function findByBooking(int $bookingId): ?array
    {
        return $this->payment->findBy('booking_id', $bookingId);
    }

    public function update(int $id, array $data): array
    {
        return $this->payment->update($id, $data);
    }
}
