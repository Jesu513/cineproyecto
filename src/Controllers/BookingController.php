<?php

namespace App\Controllers;

use App\Services\BookingService;
use App\Utils\Response;

class BookingController extends BaseController
{
    protected BookingService $service;

    public function __construct()
    {
        $this->service = new BookingService();
    }

    public function showtimeSeats($params)
    {
        return Response::json(
            $this->service->getSeatMap((int)$params['id'])
        );
    }

    public function reserve()
    {
        $data = $this->getJsonInput();
        $userId = $_REQUEST['auth_user_id'];

        return Response::json(
            $this->service->createTemporaryReservation(
                $userId,
                $data['showtime_id'],
                $data['seat_ids']
            )
        );
    }

    public function confirm($params)
    {
        $userId = $_REQUEST['auth_user_id'];
        return Response::json(
            $this->service->confirmBooking((int)$params['id'], $userId)
        );
    }

    public function cancel($params)
    {
        $userId = $_REQUEST['auth_user_id'];
        $this->service->cancelBooking((int)$params['id'], $userId);

        return Response::json(['message' => 'Reserva cancelada.']);
    }

    public function myBookings()
    {
        $userId = $_REQUEST['auth_user_id'];
        return Response::json(
            $this->service->getUserBookings($userId)
        );
    }
}
