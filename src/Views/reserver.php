<?php

/**
 * Vue / point d'entrée du flux de réservation.
 *
 * Architecture MVC : cette vue ne contient AUCUNE logique métier ni SQL.
 * Elle délègue tout au ReservationController, qui s'appuie sur la couche
 * objet (modèles Reservation/Trajet, repositories, énumération de statut,
 * exceptions métier), puis affiche un message flash et redirige.
 */

session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/_urls.php';

use App\Controllers\ReservationController;
use App\Exceptions\KaayDemException;

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: {$viewsUrl}login.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['trajet_id'])) {
    header("Location: {$viewsUrl}rechercher_trajet.php"); exit;
}

$userId   = (int) $_SESSION['utilisateur_id'];
$trajetId = (int) $_POST['trajet_id'];

try {
    (new ReservationController())->reserver($trajetId, $userId);
    $_SESSION['flash'] = [
        'type' => 'success',
        'msg'  => 'Réservation effectuée ! Elle est en attente de confirmation du conducteur.',
    ];
} catch (KaayDemException $e) {
    // Erreur métier prévue (trajet complet, conflit…) : message propre à l'utilisateur
    $_SESSION['flash'] = ['type' => 'error', 'msg' => $e->messageUtilisateur()];
} catch (\Throwable $e) {
    // Erreur technique inattendue
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Erreur technique : ' . $e->getMessage()];
}

header("Location: {$viewsUrl}mes_reservations.php"); exit;
