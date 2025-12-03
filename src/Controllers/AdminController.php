<?php

namespace App\Controllers;

use App\Services\ReportService;
use App\Services\PromotionService;
use App\Utils\Response;
// Asumiendo que existen estos servicios:
use App\Services\MovieService;
use App\Services\BookingService;
use App\Services\UserService;
use App\Services\ShowtimeService;
use App\Services\RoomService;

class AdminController extends BaseController
{
    protected ReportService $reports;
    protected PromotionService $promotions;
    protected MovieService $movies;
    protected BookingService $bookings;
    protected UserService $users;
    protected ShowtimeService $showtimes;
    protected RoomService $rooms;

    public function __construct()
    {
        $this->reports = new ReportService();
        $this->promotions = new PromotionService();
        $this->movies = new MovieService();
        $this->bookings = new BookingService();
        $this->users = new UserService();
        $this->showtimes = new ShowtimeService();
        $this->rooms = new RoomService();
    }

    // ==========================
    // DASHBOARD
    // ==========================

    // GET /api/admin/dashboard
    public function dashboard()
    {
        return Response::json(
            $this->reports->getDashboardSummary()
        );
    }

    // ==========================
    // PELÍCULAS
    // ==========================

    // GET /api/admin/movies
    public function moviesIndex()
    {
        $query = $_GET;
        return Response::json(
            $this->movies->adminList($query) // paginado, filtros
        );
    }

    // POST /api/admin/movies
    public function moviesStore()
    {
        $data = $this->getJsonInput();
        return Response::json(
            $this->movies->createFromAdmin($data)
        );
    }

    // PUT /api/admin/movies/{id}
    public function moviesUpdate($params)
    {
        $data = $this->getJsonInput();
        return Response::json(
            $this->movies->updateFromAdmin((int)$params['id'], $data)
        );
    }

    // PUT /api/admin/movies/{id}/status
    public function moviesToggleStatus($params)
    {
        return Response::json(
            $this->movies->toggleStatus((int)$params['id'])
        );
    }

    // ==========================
    // SALAS
    // ==========================

    // GET /api/admin/rooms
    public function roomsIndex()
    {
        return Response::json(
            $this->rooms->getAll()
        );
    }

    // POST /api/admin/rooms
    public function roomsStore()
    {
        $data = $this->getJsonInput();
        return Response::json(
            $this->rooms->create($data)
        );
    }

    // PUT /api/admin/rooms/{id}
    public function roomsUpdate($params)
    {
        $data = $this->getJsonInput();
        return Response::json(
            $this->rooms->update((int)$params['id'], $data)
        );
    }

    // ==========================
    // HORARIOS (SHOWTIMES)
    // ==========================

    // GET /api/admin/showtimes
    public function showtimesIndex()
    {
        $query = $_GET;
        return Response::json(
            $this->showtimes->adminList($query)
        );
    }

    // POST /api/admin/showtimes
    public function showtimesStore()
    {
        $data = $this->getJsonInput();
        return Response::json(
            $this->showtimes->create($data)
        );
    }

    // PUT /api/admin/showtimes/{id}
    public function showtimesUpdate($params)
    {
        $data = $this->getJsonInput();
        return Response::json(
            $this->showtimes->update((int)$params['id'], $data)
        );
    }

    // ==========================
    // RESERVAS
    // ==========================

    // GET /api/admin/bookings
    public function bookingsIndex()
    {
        $query = $_GET;
        return Response::json(
            $this->bookings->adminList($query) // puedes filtrar por fecha, estado, movie, etc
        );
    }

    // ==========================
    // USUARIOS
    // ==========================

    // GET /api/admin/users
    public function usersIndex()
    {
        $query = $_GET;
        return Response::json(
            $this->users->adminList($query)
        );
    }

    // PUT /api/admin/users/{id}/role
    public function usersChangeRole($params)
    {
        $data = $this->getJsonInput();
        return Response::json(
            $this->users->changeRole((int)$params['id'], $data['role'])
        );
    }

    // PUT /api/admin/users/{id}/status
    public function usersToggleActive($params)
    {
        return Response::json(
            $this->users->toggleActive((int)$params['id'])
        );
    }

    // ==========================
    // PROMOCIONES
    // ==========================

    // POST /api/admin/promotions
    public function promotionsStore()
    {
        $data = $this->getJsonInput();
        return Response::json(
            $this->promotions->promotions->create($data) // usando PromotionRepository interno
        );
    }

    // ==========================
    // REPORTES
    // ==========================

    // GET /api/admin/reports/occupancy
    public function occupancyReport()
    {
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;

        return Response::json(
            $this->reports->getOccupancyReport($from, $to)
        );
    }

    // GET /api/admin/reports/revenue
    public function revenueReport()
    {
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;

        return Response::json(
            $this->reports->getRevenueReport($from, $to)
        );
    }
}
