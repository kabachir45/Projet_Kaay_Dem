<?php

/**
 * Point d'entrée unique de l'application Kaay Dem !
 *
 * Toutes les requêtes HTTP passent par ce fichier grâce à la
 * configuration du serveur web (voir .htaccess).
 *
 * Ordre d'exécution :
 *   1. Autoload Composer (PSR-4)
 *   2. Démarrage de la session
 *   3. Déclaration des routes
 *   4. Dispatch → Controller → View
 */

declare(strict_types=1);

// ── 1. Autoload ───────────────────────────────────────────────────────────────
require_once dirname(__DIR__) . '/vendor/autoload.php';

// ── 2. Session ────────────────────────────────────────────────────────────────
session_start();

// ── 3. Routes ─────────────────────────────────────────────────────────────────
use App\Core\Router;

$router = new Router();

// --- Authentification ---------------------------------------------------------
$router->get('/login',       'AuthController@showLogin');
$router->post('/login',      'AuthController@login');
$router->get('/logout',      'AuthController@logout');
$router->get('/inscription', 'AuthController@showInscription');
$router->post('/inscription','AuthController@inscription');

// --- Accueil ------------------------------------------------------------------
$router->get('/',            'HomeController@index');

// --- Trajets ------------------------------------------------------------------
$router->get('/trajets',          'TrajetController@index');    // liste + recherche
$router->get('/trajets/nouveau',  'TrajetController@create');   // formulaire création
$router->post('/trajets/nouveau', 'TrajetController@store');    // enregistrement
$router->get('/trajets',          'TrajetController@show');     // détail d'un trajet (/trajets/42)
$router->get('/trajets/mes',      'TrajetController@mesTrajets'); // trajets du conducteur connecté

// --- Réservations -------------------------------------------------------------
$router->post('/reservations',         'ReservationController@store');   // créer
$router->post('/reservations/annuler', 'ReservationController@annuler'); // annuler (/reservations/annuler/42)
$router->get('/reservations/mes',      'ReservationController@mesReservations');

// --- Évaluations --------------------------------------------------------------
$router->get('/evaluations/nouveau',   'EvaluationController@create');
$router->post('/evaluations/nouveau',  'EvaluationController@store');

// --- Signalements -------------------------------------------------------------
$router->post('/signalements/nouveau', 'SignalementController@store');

// --- Profil utilisateur -------------------------------------------------------
$router->get('/profil',        'ProfilController@index');
$router->post('/profil',       'ProfilController@update');
$router->get('/devenir-conducteur',  'ProfilController@devenirConducteur');
$router->post('/devenir-conducteur', 'ProfilController@soumettrePermis');

// --- Administration -----------------------------------------------------------
$router->get('/admin',                       'AdminController@dashboard');
$router->get('/admin/conducteurs',           'AdminController@conducteurs');
$router->post('/admin/conducteurs/valider',  'AdminController@validerConducteur');
$router->post('/admin/conducteurs/rejeter',  'AdminController@rejeterConducteur');
$router->get('/admin/signalements',          'AdminController@signalements');
$router->post('/admin/signalements/traiter', 'AdminController@traiterSignalement');
$router->post('/admin/bannir',               'AdminController@bannirUtilisateur');

// ── 4. Dispatch ───────────────────────────────────────────────────────────────
try {
    $router->dispatch();
} catch (\RuntimeException $e) {
    http_response_code(500);
    echo "<h1>Erreur interne</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
