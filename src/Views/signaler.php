<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/_urls.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: {$viewsUrl}login.php"); exit;
}

use App\Controllers\SignalementController;
use App\Exceptions\KaayDemException;

$userId = (int)$_SESSION['utilisateur_id'];
$activePage = '';
$flash = null;
$signaleId = (int)($_GET['user_id'] ?? 0);

$controller = new SignalementController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $signaleId   = (int)($_POST['signale_id'] ?? 0);
    $motif       = $_POST['motif'] ?? '';
    $description = $_POST['description'] ?? '';
    try {
        $controller->signaler($userId, $signaleId, $motif, $description);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Signalement envoyé. L\'équipe d\'administration va l\'examiner.'];
        header("Location: dashboard.php"); exit;
    } catch(KaayDemException $e) {
        $flash = ['type'=>'error','msg'=>$e->messageUtilisateur()];
    } catch(\Throwable $e) {
        $flash = ['type'=>'error','msg'=>'Erreur : '.$e->getMessage()];
    }
}

// Liste des utilisateurs signalables (sauf soi-même et admins)
$utilisateurs = $controller->utilisateursSignalables($userId);

$motifs = ['Comportement dangereux','Harcèlement','Non-respect des horaires','Véhicule non conforme','Arnaque / Non-paiement','Autre'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Signaler un utilisateur — Kaay Dem !</title>
<?php include __DIR__ . '/_style.php'; ?>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>
<div class="page-sm">
  <a href="dashboard.php" style="color:var(--muted);font-size:14px;font-weight:500;display:inline-block;margin-bottom:24px">← Mon espace</a>
  <h1 style="font-family:var(--font-head);font-size:24px;font-weight:800;color:var(--navy);margin-bottom:6px">🚩 Signaler un utilisateur</h1>
  <p style="color:var(--muted);margin-bottom:28px">Signalez un comportement inapproprié. Votre signalement sera traité sous 48h.</p>

  <?php if($flash): ?>
  <div class="alert alert-<?= $flash['type']==='success'?'success':'error' ?>"><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Utilisateur à signaler *</label>
          <select class="form-control" name="signale_id" required>
            <option value="">— Sélectionner un utilisateur</option>
            <?php foreach($utilisateurs as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $u['id']===$signaleId?'selected':'' ?>>
              <?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Motif *</label>
          <select class="form-control" name="motif" required>
            <option value="">— Choisir un motif</option>
            <?php foreach($motifs as $m): ?>
            <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="4" placeholder="Décrivez les faits avec précision (date, trajet, contexte)..."></textarea>
        </div>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-sm);padding:13px;margin-bottom:20px;font-size:13px;color:#b91c1c">
          ⚠️ Les faux signalements peuvent entraîner la suspension de votre compte.
        </div>
        <button type="submit" class="btn btn-red btn-lg" style="width:100%;justify-content:center">Envoyer le signalement</button>
      </form>
    </div>
  </div>
</div>
<footer><div class="footer-stripe"></div><strong>Kaay Dem !</strong> — ISM Campus Baobab</footer>
</body>
</html>
