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
$routes->get('dossiers/(:num)/acces', 'Dossiers::access/$1');
$routes->post('dossiers/(:num)/acces', 'Dossiers::access/$1');
$routes->get('dossiers/(:num)/suivi', 'Dossiers::suivi/$1');
$routes->get('dossiers/(:num)/pieces', 'Dossiers::pieces/$1');
$routes->post('dossiers/(:num)/pieces', 'Dossiers::enregistrerPieces/$1');
$routes->get('dossiers/(:num)/documents', 'Dossiers::documents/$1');
$routes->get('dossiers/(:num)/phase-b', 'Dossiers::phaseB/$1');
$routes->get('dossiers/(:num)/sarlu/decision', 'Dossiers::sarluDecision/$1');
$routes->get('dossiers/(:num)/phase-b/planifier', 'Dossiers::phaseBPlanifier/$1');
$routes->get('dossiers/(:num)/phase-b/jour-j', 'Dossiers::phaseBJourJ/$1');
$routes->get('dossiers/(:num)/phase-b/jour-j/(:num)', 'Dossiers::phaseBJourJ/$1/$2');
$routes->get('dossiers/(:num)/phase-b/votes', 'Dossiers::phaseBVotes/$1');
$routes->get('dossiers/(:num)/phase-b/votes/(:num)', 'Dossiers::phaseBVotes/$1/$2');
$routes->get('dossiers/(:num)/phase-b/registre', 'Dossiers::phaseBRegistre/$1');
$routes->post('dossiers/(:num)/phase-b/planifier', 'Dossiers::planifierPhaseB/$1');
$routes->post('dossiers/(:num)/phase-b/tenir', 'Dossiers::tenirPhaseB/$1');
$routes->post('dossiers/(:num)/phase-b/interventions', 'Dossiers::ajouterIntervention/$1');
$routes->post('dossiers/(:num)/phase-b/voter', 'Dossiers::voterPhaseB/$1');
$routes->post('dossiers/(:num)/statut', 'Dossiers::status/$1');
$routes->get('pdf/constitutifs/(:num)/(:segment)', 'Constituants::generate/$1/$2');
$routes->get('societes/type', 'Societes::type');
$routes->get('societes/nouveau', 'Societes::new');
$routes->post('societes', 'Societes::create');