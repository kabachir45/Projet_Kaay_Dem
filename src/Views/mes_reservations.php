<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/_urls.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: {$viewsUrl}login.php"); exit;
}

use App\Core\Database;
use App\Controllers\ReservationController;
use App\Exceptions\KaayDemException;

$userId  = (int)$_SESSION['utilisateur_id'];
$activePage = '';
$flash   = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Gérer annulation — déléguée à ReservationController (couche objet)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annuler_id'])) {
    try {
        (new ReservationController())->annuler((int)$_POST['annuler_id'], $userId);
        $flash = ['type'=>'success','msg'=>'Réservation annulée.'];
    } catch(KaayDemException $e) {
        $flash = ['type'=>'error','msg'=>$e->messageUtilisateur()];
    } catch(\Throwable $e) {
        $flash = ['type'=>'error','msg'=>'Erreur lors de l\'annulation.'];
    }
}

// Charger réservations
$reservations = [];
$erreurBd = false;
try {
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("
        SELECT r.*,
               t.ville_depart, t.ville_arrivee, t.date_depart, t.prix,
               u.nom AS conducteur_nom, u.prenom AS conducteur_prenom,
               e.note AS eval_note, e.commentaire AS eval_commentaire
        FROM reservations r
        JOIN profil_passager pp ON pp.id = r.passager_id
        JOIN trajets t ON t.id = r.trajet_id
        JOIN profil_conducteur pc ON pc.id = t.conducteur_id
        JOIN utilisateurs u ON u.id = pc.utilisateur_id
        LEFT JOIN evaluations e ON e.reservation_id = r.id
        WHERE pp.utilisateur_id = :uid
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $reservations = $stmt->fetchAll();
} catch(\Exception $e) { $erreurBd = true; }

$statutLabels = ['EN_ATTENTE'=>'En attente','CONFIRMEE'=>'Confirmée','ANNULEE'=>'Annulée','TERMINEE'=>'Terminée'];
$statutBadge  = ['EN_ATTENTE'=>'badge-yellow','CONFIRMEE'=>'badge-green','ANNULEE'=>'badge-red','TERMINEE'=>'badge-blue'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mes réservations — Kaay Dem !</title>
<?php include __DIR__ . '/_style.php'; ?>
<style>
.page-header{background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:white;padding:32px 24px}
.page-header h1{font-family:var(--font-head);font-size:24px;font-weight:800;max-width:960px;margin:0 auto}
.content{max-width:960px;margin:32px auto;padding:0 24px}
.resa-card{background:white;border:1px solid var(--border);border-radius:var(--radius);padding:22px 24px;margin-bottom:14px}
.resa-top{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:14px}
.resa-route{font-family:var(--font-head);font-size:18px;font-weight:800;color:var(--navy)}
.resa-route span{color:var(--green)}
.resa-meta{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.resa-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;padding-top:14px;border-top:1px solid var(--border)}
.eval-form{background:var(--bg);border-radius:var(--radius-sm);padding:16px;margin-top:14px}
.eval-form h4{font-size:14px;font-weight:700;margin-bottom:12px;color:var(--navy)}
.stars-input{display:flex;gap:6px;margin-bottom:12px}
.stars-input input{display:none}
.stars-input label{font-size:28px;cursor:pointer;color:#d1d5db;transition:.15s}
.stars-input label:hover,.stars-input input:checked ~ label,.stars-input label:hover ~ label{color:var(--gold)}
.stars-input{flex-direction:row-reverse;justify-content:flex-end}
.eval-existante{background:var(--green-light);border-radius:var(--radius-sm);padding:14px;margin-top:10px}
</style>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="page-header">
  <h1>📅 Mes réservations</h1>
</div>

<div class="content">
  <a href="dashboard.php" style="color:var(--muted);font-size:14px;font-weight:500;display:inline-block;margin-bottom:20px">← Mon espace</a>

  <?php if($flash): ?>
  <div class="alert alert-<?= $flash['type']==='success'?'success':'error' ?>">
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
  <?php endif; ?>

  <?php if($erreurBd): ?>
  <div class="alert alert-error">⚠️ Impossible de charger vos réservations.</div>
  <?php elseif(empty($reservations)): ?>
  <div class="card"><div class="card-body" style="text-align:center;padding:50px">
    <div style="font-size:48px;margin-bottom:14px">📅</div>
    <h3 style="font-family:var(--font-head);font-size:18px;font-weight:700;margin-bottom:8px">Aucune réservation</h3>
    <p style="color:var(--muted);margin-bottom:20px">Vous n'avez pas encore réservé de trajet.</p>
    <a href="rechercher_trajet.php" class="btn btn-green">Trouver un trajet</a>
  </div></div>
  <?php else: ?>
    <?php foreach($reservations as $r): ?>
    <?php $dateDepart = new DateTime($r['date_depart']); ?>
    <div class="resa-card">
      <div class="resa-top">
        <div>
          <div class="resa-route">
            <?= htmlspecialchars($r['ville_depart']) ?> <span>→</span> <?= htmlspecialchars($r['ville_arrivee']) ?>
          </div>
          <div class="resa-meta">
            <span class="meta-chip">📅 <?= $dateDepart->format('d/m/Y') ?></span>
            <span class="meta-chip">🕐 <?= $dateDepart->format('H:i') ?></span>
            <span class="meta-chip">👤 <?= htmlspecialchars($r['conducteur_prenom'].' '.$r['conducteur_nom']) ?></span>
            <span class="meta-chip">💰 <?= number_format((float)$r['prix'],0,',',' ') ?> FCFA</span>
          </div>
        </div>
        <span class="badge <?= $statutBadge[$r['statut']] ?? 'badge-gray' ?>">
          <?= $statutLabels[$r['statut']] ?? $r['statut'] ?>
        </span>
      </div>

      <!-- Actions -->
      <?php if(in_array($r['statut'], ['EN_ATTENTE','CONFIRMEE'])): ?>
      <div class="resa-actions">
        <form method="POST" onsubmit="return confirm('Confirmer l\'annulation ?')">
          <input type="hidden" name="annuler_id" value="<?= $r['id'] ?>">
          <button type="submit" class="btn btn-red" style="padding:9px 18px;font-size:13px">Annuler la réservation</button>
        </form>
      </div>
      <?php endif; ?>

      <!-- Évaluation si terminée -->
      <?php if($r['statut'] === 'TERMINEE'): ?>
        <?php if($r['eval_note']): ?>
        <div class="eval-existante">
          <div style="font-size:13px;font-weight:700;color:var(--green-dark);margin-bottom:4px">✓ Trajet évalué</div>
          <div style="color:var(--gold);font-size:18px;margin-bottom:4px">
            <?= str_repeat('★', (int)$r['eval_note']) ?><?= str_repeat('☆', 5-(int)$r['eval_note']) ?>
          </div>
          <?php if($r['eval_commentaire']): ?>
          <div style="font-size:14px;color:var(--slate);font-style:italic">"<?= htmlspecialchars($r['eval_commentaire']) ?>"</div>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="eval-form">
          <h4>⭐ Évaluer ce conducteur</h4>
          <form method="POST" action="evaluer.php">
            <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
            <div class="stars-input">
              <?php for($i=5;$i>=1;$i--): ?>
              <input type="radio" name="note" id="star<?= $r['id'].'_'.$i ?>" value="<?= $i ?>" <?= $i===5?'required':'' ?>>
              <label for="star<?= $r['id'].'_'.$i ?>">★</label>
              <?php endfor; ?>
            </div>
            <div class="form-group">
              <textarea class="form-control" name="commentaire" rows="2" placeholder="Commentaire (optionnel)" style="font-size:14px"></textarea>
            </div>
            <button type="submit" class="btn btn-green" style="padding:9px 18px;font-size:13px">Envoyer l'évaluation</button>
          </form>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<footer><div class="footer-stripe"></div><strong>Kaay Dem !</strong> — ISM Campus Baobab</footer>
</body>
</html>
