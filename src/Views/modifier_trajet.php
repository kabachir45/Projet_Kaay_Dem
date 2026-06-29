<?php

/**
 * Vue d'édition d'un trajet (réservée à son conducteur, tant qu'aucune
 * réservation n'est confirmée). Délègue au TrajetController.
 */

session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/_urls.php';

use App\Controllers\TrajetController;
use App\Exceptions\KaayDemException;

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: {$viewsUrl}login.php"); exit;
}

$userId     = (int) $_SESSION['utilisateur_id'];
$activePage = '';
$flash      = null;

$trajetId   = (int) ($_GET['id'] ?? $_POST['trajet_id'] ?? 0);
$controller = new TrajetController();

// Charger le trajet (et vérifier l'appartenance)
try {
    $trajet = $controller->chargerPourEdition($trajetId, $userId);
} catch (\Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => "Trajet introuvable ou non autorisé."];
    header("Location: mes_trajets.php"); exit;
}

$modifiable = !$trajet->estAnnule() && !$controller->aReservationConfirmee($trajetId);

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $modifiable) {
    try {
        $controller->modifier($trajetId, $userId, $_POST);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Trajet modifié avec succès.'];
        header("Location: mes_trajets.php"); exit;
    } catch (KaayDemException $e) {
        $flash = ['type' => 'error', 'msg' => $e->messageUtilisateur()];
    } catch (\Throwable $e) {
        $flash = ['type' => 'error', 'msg' => 'Erreur : ' . $e->getMessage()];
    }
}

// Valeurs du formulaire (POST prioritaire en cas d'erreur, sinon valeurs du trajet)
$vDepart  = $_POST['ville_depart']   ?? $trajet->getVilleDepart();
$vArrivee = $_POST['ville_arrivee']  ?? $trajet->getVilleArrivee();
$vPoints  = $_POST['points_arret']   ?? ($trajet->getPointsArret() ?? '');
$vDate    = $_POST['date_depart']    ?? $trajet->getDateDepart()->format('Y-m-d');
$vHeure   = $_POST['heure_depart']   ?? $trajet->getDateDepart()->format('H:i');
$vPrix    = $_POST['prix_par_place'] ?? (string) $trajet->getPrix();
$vDesc    = $_POST['description']    ?? ($trajet->getDescription() ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier un trajet — Kaay Dem !</title>
<?php include __DIR__ . '/_style.php'; ?>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>
<div class="page-md">
  <a href="mes_trajets.php" style="color:var(--muted);font-size:14px;font-weight:500;display:inline-block;margin-bottom:24px">← Mes trajets</a>
  <h1 style="font-family:var(--font-head);font-size:26px;font-weight:800;color:var(--navy);margin-bottom:6px">✏️ Modifier le trajet</h1>
  <p style="color:var(--muted);margin-bottom:28px"><?= htmlspecialchars($trajet->getVilleDepart()) ?> → <?= htmlspecialchars($trajet->getVilleArrivee()) ?></p>

  <?php if($flash): ?>
  <div class="alert alert-<?= $flash['type']==='success'?'success':'error' ?>"><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <?php if(!$modifiable): ?>
  <div class="card"><div class="card-body" style="text-align:center;padding:50px">
    <div style="font-size:48px;margin-bottom:14px">🔒</div>
    <h3 style="font-family:var(--font-head);font-size:18px;font-weight:700;margin-bottom:8px">Modification impossible</h3>
    <p style="color:var(--muted);margin-bottom:20px">
      <?= $trajet->estAnnule() ? "Ce trajet est annulé." : "Ce trajet a déjà une réservation confirmée et ne peut plus être modifié." ?>
    </p>
    <a href="mes_trajets.php" class="btn btn-green">Retour à mes trajets</a>
  </div></div>

  <?php else: ?>
  <div class="card">
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="trajet_id" value="<?= $trajetId ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
          <div class="form-group">
            <label class="form-label">Ville de départ *</label>
            <input class="form-control" type="text" name="ville_depart" required value="<?= htmlspecialchars($vDepart) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Ville d'arrivée *</label>
            <input class="form-control" type="text" name="ville_arrivee" required value="<?= htmlspecialchars($vArrivee) ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Points d'arrêt (optionnel)</label>
          <input class="form-control" type="text" name="points_arret" value="<?= htmlspecialchars($vPoints) ?>">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
          <div class="form-group">
            <label class="form-label">Date de départ *</label>
            <input class="form-control" type="date" name="date_depart" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($vDate) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Heure de départ *</label>
            <input class="form-control" type="time" name="heure_depart" required value="<?= htmlspecialchars($vHeure) ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Prix par place (FCFA) *</label>
          <input class="form-control" type="number" name="prix_par_place" min="0" step="50" required value="<?= htmlspecialchars($vPrix) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Description (optionnel)</label>
          <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($vDesc) ?></textarea>
        </div>
        <p style="font-size:13px;color:var(--muted);margin-bottom:16px">ℹ️ Le nombre de places et l'itinéraire sur la carte ne sont pas modifiables ici.</p>
        <button type="submit" class="btn btn-green btn-lg" style="width:100%;justify-content:center">Enregistrer les modifications</button>
      </form>
    </div>
  </div>
  <?php endif; ?>
</div>
<footer><div class="footer-stripe"></div><strong>Kaay Dem !</strong> — ISM Campus Baobab</footer>
</body>
</html>
