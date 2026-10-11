<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('pdf/demo/convocation', 'PdfDemo::convocation');
$routes->get('pdf/statuts/(:num)', 'Statuts::generate/$1');
$routes->get('pdf/convocations/(:num)', 'Convocations::generate/$1');
$routes->get('pdf/proces-verbaux/(:num)', 'ProcesVerbaux::generate/$1');
$routes->get('dossiers/(:num)', 'Dossiers::show/$1');
$routes->post('dossiers/(:num)/pieces/(:num)', 'Dossiers::piece/$1/$2');
$routes->post('dossiers/(:num)/statut', 'Dossiers::status/$1');
$routes->get('pdf/constitutifs/(:num)/(:segment)', 'Constituants::generate/$1/$2');
$routes->get('societes/type', 'Societes::type');
$routes->get('societes/nouveau', 'Societes::new');
$routes->post('societes', 'Societes::create');
