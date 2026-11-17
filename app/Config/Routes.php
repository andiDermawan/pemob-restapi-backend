<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', static fn () => service('response')->setJSON([
    'success' => true,
    'message' => 'UMKM Lokal API aktif',
    'data' => ['endpoint' => '/api/umkm'],
]));

$routes->group('api', ['namespace' => 'App\\Controllers\\Api'], static function ($routes): void {
    $routes->get('umkm', 'UmkmController::index');
    $routes->get('umkm/(:num)', 'UmkmController::show/$1');
    $routes->post('umkm', 'UmkmController::create');
    $routes->put('umkm/(:num)', 'UmkmController::update/$1');
    $routes->delete('umkm/(:num)', 'UmkmController::delete/$1');
    $routes->options('umkm', 'UmkmController::options');
    $routes->options('umkm/(:num)', 'UmkmController::options');
});
