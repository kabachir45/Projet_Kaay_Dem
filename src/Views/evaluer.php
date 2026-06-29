<?php

/**
 * Vue / point d'entrée de l'évaluation d'un conducteur.
 *
 * Architecture MVC : aucune logique métier ni SQL ici. La vue délègue à
 * EvaluationController, qui s'appuie sur le modèle Evaluation (validation de
 * la note) et EvaluationRepository, puis affiche un message flash.
 */

session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/_urls.php';

use App\Controllers\EvaluationController;
use App\Exceptions\KaayDemException;

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: {$viewsUrl}login.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: {$viewsUrl}mes_reservations.php"); exit;
}

$userId        = (int) $_SESSION['utilisateur_id'];
$reservationId = (int) ($_POST['reservation_id'] ?? 0);
$note          = (int) ($_POST['note'] ?? 0);
$commentaire   = trim($_POST['commentaire'] ?? '');

try {
    (new EvaluationController())->evaluer($reservationId, $userId, $note, $commentaire);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Merci pour votre évaluation !'];
} catch (KaayDemException $e) {
    // Erreur métier (note invalide, réservation non évaluable…)
    $_SESSION['flash'] = ['type' => 'error', 'msg' => $e->messageUtilisateur()];
} catch (\Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Erreur : ' . $e->getMessage()];
}

header("Location: {$viewsUrl}mes_reservations.php"); exit;
