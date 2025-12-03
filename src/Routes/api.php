<?php

use App\Routes\Router;

// Controllers
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\MovieController;
use App\Controllers\BookingController;
use App\Controllers\SeatController;
use App\Controllers\PaymentController;
use App\Controllers\PromotionController;
use App\Controllers\RecommendationController;
use App\Controllers\AdminController;
use App\Controllers\StaffController;

$router = new Router("/SisCine");

// ===============================
//           AUTH
// ===============================

$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/logout', [AuthController::class, 'logout'], ['auth']);
$router->get('/api/auth/me', [AuthController::class, 'me'], ['auth']);

// ===============================
//          USER PROFILE
// ===============================

$router->get('/api/users/me', [UserController::class, 'me'], ['auth']);
$router->put('/api/users/me', [UserController::class, 'updateProfile'], ['auth']);
$router->put('/api/users/change-password', [UserController::class, 'changePassword'], ['auth']);

// ===============================
//          MOVIES (PUBLIC)
// ===============================

$router->get('/api/movies', [MovieController::class, 'index']);
$router->get('/api/movies/{id}', [MovieController::class, 'show']);
$router->get('/api/movies/{id}/showtimes', [MovieController::class, 'getShowtimes']);

// ===============================
//          SEATS
// ===============================

$router->get('/api/showtimes/{id}/seats', [SeatController::class, 'getSeatMap']);
$router->get('/api/showtimes/{id}/occupied', [SeatController::class, 'getOccupiedSeats']);

// ===============================
//          BOOKINGS
// ===============================

$router->post('/api/bookings/reserve', [BookingController::class, 'reserve'], ['auth']);
$router->post('/api/bookings/{id}/confirm', [BookingController::class, 'confirm'], ['auth']);
$router->delete('/api/bookings/{id}/cancel', [BookingController::class, 'cancel'], ['auth']);
$router->get('/api/bookings/my-bookings', [BookingController::class, 'myBookings'], ['auth']);

// ===============================
//          PAYMENTS
// ===============================

$router->post('/api/payments/create-intent', [PaymentController::class, 'createIntent'], ['auth']);
$router->post('/api/payments/{id}/confirm', [PaymentController::class, 'confirm'], ['auth']);
$router->post('/api/payments/{id}/refund', [PaymentController::class, 'refund'], ['auth', 'role:admin,staff']);

// ===============================
//          PROMOTIONS
// ===============================

$router->post('/api/promotions/validate', [PromotionController::class, 'validateCoupon'], ['auth']);
$router->get('/api/promotions/active', [PromotionController::class, 'active']);

// ===============================
//      RECOMMENDATIONS & RATINGS
// ===============================

$router->get('/api/recommendations', [RecommendationController::class, 'getForUser'], ['auth']);
$router->post('/api/ratings', [RecommendationController::class, 'rateMovie'], ['auth']);

// ===========================================================
//                      ADMIN PANEL
// ===========================================================

// Dashboard
$router->get('/api/admin/dashboard', [AdminController::class, 'dashboard'], ['auth', 'role:admin']);

// Películas
$router->get('/api/admin/movies', [AdminController::class, 'moviesIndex'], ['auth', 'role:admin']);
$router->post('/api/admin/movies', [AdminController::class, 'moviesStore'], ['auth', 'role:admin']);
$router->put('/api/admin/movies/{id}', [AdminController::class, 'moviesUpdate'], ['auth', 'role:admin']);
$router->put('/api/admin/movies/{id}/status', [AdminController::class, 'moviesToggleStatus'], ['auth', 'role:admin']);

// Salas
$router->get('/api/admin/rooms', [AdminController::class, 'roomsIndex'], ['auth', 'role:admin']);
$router->post('/api/admin/rooms', [AdminController::class, 'roomsStore'], ['auth', 'role:admin']);
$router->put('/api/admin/rooms/{id}', [AdminController::class, 'roomsUpdate'], ['auth', 'role:admin']);

// Horarios
$router->get('/api/admin/showtimes', [AdminController::class, 'showtimesIndex'], ['auth', 'role:admin']);
$router->post('/api/admin/showtimes', [AdminController::class, 'showtimesStore'], ['auth', 'role:admin']);
$router->put('/api/admin/showtimes/{id}', [AdminController::class, 'showtimesUpdate'], ['auth', 'role:admin']);

// Reservas & Usuarios
$router->get('/api/admin/bookings', [AdminController::class, 'bookingsIndex'], ['auth', 'role:admin']);
$router->get('/api/admin/users', [AdminController::class, 'usersIndex'], ['auth', 'role:admin']);
$router->put('/api/admin/users/{id}/role', [AdminController::class, 'usersChangeRole'], ['auth', 'role:admin']);
$router->put('/api/admin/users/{id}/status', [AdminController::class, 'usersToggleActive'], ['auth', 'role:admin']);

// Promociones
$router->post('/api/admin/promotions', [AdminController::class, 'promotionsStore'], ['auth', 'role:admin']);

// Reportes
$router->get('/api/admin/reports/occupancy', [AdminController::class, 'occupancyReport'], ['auth', 'role:admin']);
$router->get('/api/admin/reports/revenue', [AdminController::class, 'revenueReport'], ['auth', 'role:admin']);

// ===========================================================
//                      STAFF PANEL
// ===========================================================

$router->get('/api/staff/bookings/validate', [StaffController::class, 'validateBooking'], ['auth', 'role:staff,admin']);
$router->post('/api/staff/bookings/{id}/confirm-cash', [StaffController::class, 'confirmCash'], ['auth', 'role:staff,admin']);
$router->post('/api/staff/bookings/{id}/change-seats', [StaffController::class, 'changeSeats'], ['auth', 'role:staff,admin']);
$router->post('/api/staff/payments/{id}/refund', [StaffController::class, 'refund'], ['auth', 'role:staff,admin']);
$router->get('/api/staff/showtimes/{id}/seats', [StaffController::class, 'showtimeSeats'], ['auth', 'role:staff,admin']);

// ===========================================================
// Resolver rutas
// ===========================================================

$router->resolve();
// ============================
// RUTAS PÚBLICAS DE PELÍCULAS
// ============================

$router->get('/api/movies', [MovieController::class, 'index']);

$router->get('/api/movies/{id}', [MovieController::class, 'show']);

$router->get('/api/movies/{id}/showtimes', [MovieController::class, 'showtimes']);

$router->get('/api/genres', [MovieController::class, 'genres']);

$router->get('/api/movies/top-rated', [MovieController::class, 'getTopRated']);

$router->get('/api/movies/recent', [MovieController::class, 'getRecent']);

$router->get('/api/movies/upcoming', [MovieController::class, 'getUpcoming']);

