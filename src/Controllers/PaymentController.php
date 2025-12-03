<?php

namespace App\Controllers;

use App\Services\PaymentService;
use App\Utils\Response;

class PaymentController extends BaseController
{
    protected PaymentService $service;

    public function __construct()
    {
        $this->service = new PaymentService();
    }

    // POST /api/payments/create-intent
    public function createIntent()
    {
        $data = $this->getJsonInput();
        $userId = $_REQUEST['auth_user_id'];

        return Response::json(
            $this->service->createIntent($data['booking_id'], $userId)
        );
    }

    // POST /api/payments/{booking_id}/confirm
    public function confirm($params)
    {
        $data = $this->getJsonInput();

        return Response::json(
            $this->service->confirmPayment(
                (int)$params['booking_id'],
                $data['payment_intent_id']
            )
        );
    }

    // POST /api/payments/{id}/refund
    public function refund($params)
    {
        $data = $this->getJsonInput();

        return Response::json(
            $this->service->refund(
                (int)$params['id'],
                $data['reason'] ?? 'No reason provided'
            )
        );
    }
}
