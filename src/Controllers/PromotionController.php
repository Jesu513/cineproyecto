<?php

namespace App\Controllers;

use App\Services\PromotionService;
use App\Utils\Response;

class PromotionController extends BaseController
{
    protected PromotionService $service;

    public function __construct()
    {
        $this->service = new PromotionService();
    }

    // POST /api/promotions/validate
    public function validate()
    {
        $data = $this->getJsonInput();
        $userId = $_REQUEST['auth_user_id'];

        return Response::json(
            $this->service->validateCoupon(
                $data['code'],
                $userId,
                $data['booking_id']
            )
        );
    }

    // GET /api/promotions/active
    public function active()
    {
        return Response::json(
            $this->service->promotions->getActivePromotions()
        );
    }

    // POST /api/promotions (admin)
    public function create()
    {
        $data = $this->getJsonInput();

        return Response::json(
            $this->service->promotions->create($data)
        );
    }
}
