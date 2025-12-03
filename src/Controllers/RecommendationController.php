<?php

namespace App\Controllers;

use App\Services\RecommendationEngine;
use App\Repositories\RatingRepository;
use App\Utils\Response;

class RecommendationController extends BaseController
{
    protected RecommendationEngine $engine;
    protected RatingRepository $ratings;

    public function __construct()
    {
        $this->engine = new RecommendationEngine();
        $this->ratings = new RatingRepository();
    }

    // GET /api/recommendations
    public function recommendations()
    {
        $userId = $_REQUEST['auth_user_id'];
        return Response::json(
            $this->engine->getRecommendations($userId)
        );
    }

    // POST /api/ratings
    public function rate()
    {
        $data = $this->getJsonInput();
        $data['user_id'] = $_REQUEST['auth_user_id'];

        return Response::json(
            $this->ratings->rateMovie($data)
        );
    }
}
