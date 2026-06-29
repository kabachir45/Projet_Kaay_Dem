<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/_urls.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: {$viewsUrl}login.php"); exit;
}

use App\Controllers\ConducteurController;
use App\Exceptions\KaayDemException;

$userId = (int)$_SESSION['utilisateur_id'];
$activePage = '';
$flash = null;

$controller = new ConducteurController();

// Profil existant (modèle) → tableau compatible avec l'affichage ci-dessous
$profilModel    = $controller->profilDe($userId);
$profilExistant = $profilModel ? ['statut' => $profilModel->getStatut()->value] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$profilExistant) {
    try {
        $controller->devenir($userId, $_POST);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Demande envoyée ! Un administrateur validera votre profil sous 24h.'];
        header("Location: dashboard.php"); exit;
    } catch(KaayDemException $e) {
        $flash = ['type'=>'error','msg'=>$e->messageUtilisateur()];
    } catch(\Throwable $e) {
        $flash = ['type'=>'error','msg'=>'Erreur : '.$e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Devenir conducteur — Kaay Dem !</title>
<?php include __DIR__ . '/_style.php'; ?>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>
<div class="page-md">
  <a href="dashboard.php" style="color:var(--muted);font-size:14px;font-weight:500;display:inline-block;margin-bottom:24px">← Mon espace</a>
  <h1 style="font-family:var(--font-head);font-size:26px;font-weight:800;color:var(--navy);margin-bottom:6px">🚗 Devenir conducteur</h1>
  <p style="color:var(--muted);margin-bottom:28px">Soumettez votre demande — un administrateur la validera sous 24h.</p>

  <?php if($flash): ?>
  <div class="alert alert-<?= $flash['type']==='success'?'success':'error' ?>"><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <?php if($profilExistant): ?>
  <div class="card"><div class="card-body" style="text-align:center;padding:50px">
    <div style="font-size:48px;margin-bottom:16px">
      <?= $profilExistant['statut']==='VALIDE'?'✅':($profilExistant['statut']==='EN_ATTENTE'?'⏳':'❌') ?>
    </div>
    <h3 style="font-family:var(--font-head);font-size:20px;font-weight:800;margin-bottom:8px">
      <?php if($profilExistant['statut']==='VALIDE'): ?>Vous êtes déjà conducteur validé !
      <?php elseif($profilExistant['statut']==='EN_ATTENTE'): ?>Demande en cours de validation
      <?php else: ?>Demande refusée
      <?php endif; ?>
    </h3>
    <p style="color:var(--muted);margin-bottom:24px">
      <?php if($profilExistant['statut']==='EN_ATTENTE'): ?>Un administrateur examinera votre dossier sous 24h.
      <?php elseif($profilExistant['statut']==='VALIDE'): ?>Vous pouvez publier des trajets dès maintenant.
      <?php else: ?>Contactez l'administration pour plus d'informations.
      <?php endif; ?>
    </p>
    <a href="<?= $profilExistant['statut']==='VALIDE'?'publier_trajet.php':'dashboard.php' ?>" class="btn btn-green">
      <?= $profilExistant['statut']==='VALIDE'?'Publier un trajet':'Retour au tableau de bord' ?>
    </a>
  </div></div>

  <?php else: ?>
  <div class="card">
    <div class="card-body">
      <!-- Étapes -->
      <div style="display:flex;gap:0;margin-bottom:32px;border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden">
        <?php foreach(['Votre dossier','Validation admin','Publiez des trajets'] as $i=>$step): ?>
        <div style="flex:1;padding:14px;text-align:center;background:<?= $i===0?'var(--green)':'var(--bg)' ?>;color:<?= $i===0?'white':'var(--muted)' ?>;font-size:13px;font-weight:700;border-right:<?= $i<2?'1px solid var(--border)':'none' ?>">
          <div style="font-size:18px;margin-bottom:4px"><?= ['📝','✅','🚗'][$i] ?></div>
          <?= $step ?>
        </div>
        <?php endforeach; ?>
      </div>

      <form method="POST">
        <h3 style="font-family:var(--font-head);font-size:16px;font-weight:800;margin-bottom:16px;color:var(--navy)">📋 Informations permis</h3>
        <div class="form-group">
          <label class="form-label">Numéro de permis de conduire *</label>
          <input class="form-control" type="text" name="numero_permis" placeholder="ex. SN-2020-001234" required>
        </div>

        <h3 style="font-family:var(--font-head);font-size:16px;font-weight:800;margin-bottom:16px;margin-top:24px;color:var(--navy)">🚙 Votre véhicule (optionnel)</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Marque</label>
            <input class="form-control" type="text" name="marque" placeholder="ex. Peugeot">
          </div>
          <div class="form-group">
            <label class="form-label">Modèle</label>
            <input class="form-control" type="text" name="modele" placeholder="ex. 208">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Immatriculation</label>
            <input class="form-control" type="text" name="immatriculation" placeholder="ex. DK-1234-AB">
          </div>
          <div class="form-group">
            <label class="form-label">Nombre de places passagers</label>
            <input class="form-control" type="number" name="nombre_places" min="1" max="8" placeholder="ex. 4">
          </div>
        </div>

        <div style="background:var(--green-light);border:1px solid rgba(0,133,63,.2);border-radius:var(--radius-sm);padding:14px;margin-bottom:20px;font-size:14px;color:var(--green-dark)">
          ℹ️ Votre demande sera examinée par un administrateur. Vous recevrez une confirmation dès validation.
        </div>
        <button type="submit" class="btn btn-green btn-lg" style="width:100%;justify-content:center">
          Envoyer ma demande →
        </button>
      </form>
    </div>
  </div>
  <?php endif; ?>
</div>
<footer><div class="footer-stripe"></div><strong>Kaay Dem !</strong> — ISM Campus Baobab</footer>
</body>
</html>
