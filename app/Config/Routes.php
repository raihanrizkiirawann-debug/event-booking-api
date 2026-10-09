<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->post('api/register', 'AuthController::register');
$routes->post('api/login', 'AuthController::login');

$routes->get(
    'api/test-protected',
    'TestController::protected',
    ['filter' => 'jwt']
);

$routes->get(
    'api/test-admin',
    'TestController::admin',
    ['filter' => ['jwt', 'admin']]
);

$routes->post(
    'api/events',
    'EventController::create',
    ['filter' => ['jwt', 'admin']]
);

$routes->get(
    'api/events',
    'EventController::index'
);

$routes->get(
    'api/events/(:num)',
    'EventController::show/$1'
);

$routes->put(
    'api/events/(:num)',
    'EventController::update/$1',
    ['filter' => ['jwt', 'admin']]
);

$routes->delete(
    'api/events/(:num)',
    'EventController::delete/$1',
    ['filter' => ['jwt', 'admin']]
);

$routes->post(
    'api/bookings',
    'BookingController::create',
    ['filter' => 'jwt']
);

$routes->get(
    'api/bookings',
    'BookingController::index',
    ['filter' => 'jwt']
);

$routes->delete(
    'api/bookings/(:num)',
    'BookingController::cancel/$1',
    ['filter' => 'jwt']
);