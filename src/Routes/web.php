<?php

use App\Routes\Router;

$router = new Router("/SisCine");

// Ruta raíz básica para verificar el backend
$router->get("/", function () {
    echo "<h1>Sistema de Reservas de Cine — Backend Activo ✔</h1>";
});

// Ruta de salud (health check)
$router->get("/health", function () {
    header("Content-Type: application/json");
    echo json_encode([
        "status" => "ok",
        "timestamp" => date('Y-m-d H:i:s')
    ]);
});

// Resolver rutas
$router->resolve();
