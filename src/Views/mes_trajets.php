<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/_urls.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: {$viewsUrl}login.php"); exit;
}

use App\Core\Database;
use App\Controllers\ReservationController;
use App\Controllers\TrajetController;
use App\Exceptions\KaayDemException;

$userId = (int)$_SESSION['utilisateur_id'];
$activePage = '';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Gérer actions conducteur — déléguées à la couche objet (contrôleurs)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rid    = (int)($_POST['reservation_id'] ?? 0);
    try {
        $resaCtrl = new ReservationController();
        switch ($action) {
            case 'confirmer':
                $resaCtrl->confirmerParConducteur($rid, $userId);
                $flash = ['type'=>'success','msg'=>'Réservation confirmée.'];
                break;
            case 'terminer':
                $resaCtrl->terminerParConducteur($rid, $userId);
                $flash = ['type'=>'success','msg'=>'Trajet marqué comme terminé.'];
                break;
            case 'paiement':
                $resaCtrl->confirmerPaiement($rid, $userId);
                $flash = ['type'=>'success','msg'=>'Paiement confirmé.'];
                break;
            case 'annuler_trajet':
                (new TrajetController())->annulerParConducteur((int)($_POST['trajet_id'] ?? 0), $userId);
                $flash = ['type'=>'success','msg'=>'Trajet annulé. Les réservations actives ont été annulées automatiquement.'];
                break;
        }
    } catch(KaayDemException $e) {
        $flash = ['type'=>'error','msg'=>$e->messageUtilisateur()];
    } catch(\Throwable $e) {
        $flash = ['type'=>'error','msg'=>'Erreur : '.$e->getMessage()];
    }
    $_SESSION['flash'] = $flash;
    header("Location: mes_trajets.php"); exit;
}

// Charger trajets + réservations
$trajets = [];
$erreurBd = false;
try {
    $pdo = Database::getInstance()->getConnection();
    // Vérifier si conducteur
    $stmt = $pdo->prepare("SELECT pc.id FROM profil_conducteur pc WHERE pc.utilisateur_id = :uid");
    $stmt->execute([':uid' => $userId]);
    $conducteur = $stmt->fetch();
    $conducteurId = $conducteur ? (int)$conducteur['id'] : null;

    if ($conducteurId) {
        $stmt = $pdo->prepare("
            SELECT t.*,
                   COUNT(r.id) AS nb_reservations,
                   SUM(CASE WHEN r.statut='EN_ATTENTE' THEN 1 ELSE 0 END) AS nb_attente,
                   SUM(CASE WHEN r.statut='CONFIRMEE'  THEN 1 ELSE 0 END) AS nb_confirmees,
                   SUM(CASE WHEN r.statut='TERMINEE'   THEN 1 ELSE 0 END) AS nb_terminees
            FROM trajets t
            LEFT JOIN reservations r ON r.trajet_id = t.id
            WHERE t.conducteur_id = :cid
            GROUP BY t.id
            ORDER BY t.date_depart DESC
        ");
        $stmt->execute([':cid' => $conducteurId]);
        $trajets = $stmt->fetchAll();

        // Charger réservations pour chaque trajet
        foreach ($trajets as &$trajet) {
            $stmt2 = $pdo->prepare("
                SELECT r.*, u.nom, u.prenom, u.telephone
                FROM reservations r
                JOIN profil_passager pp ON pp.id = r.passager_id
                JOIN utilisateurs u ON u.id = pp.utilisateur_id
                WHERE r.trajet_id = :tid
                ORDER BY r.created_at ASC
            ");
            $stmt2->execute([':tid' => $trajet['id']]);
            $trajet['reservations'] = $stmt2->fetchAll();
        }
    }
} catch(\Exception $e) { $erreurBd = true; }

$statutBadge = ['EN_ATTENTE'=>'badge-yellow','CONFIRMEE'=>'badge-green','ANNULEE'=>'badge-red','TERMINEE'=>'badge-blue'];
$statutLabel = ['EN_ATTENTE'=>'En attente','CONFIRMEE'=>'Confirmée','ANNULEE'=>'Annulée','TERMINEE'=>'Terminée'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mes trajets — Kaay Dem !</title>
<?php include __DIR__ . '/_style.php'; ?>
<style>
.page-header{background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:white;padding:32px 24px}
.page-header-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between}
.page-header h1{font-family:var(--font-head);font-size:24px;font-weight:800}
.content{max-width:1100px;margin:32px auto;padding:0 24px}
.trajet-bloc{background:white;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:20px;overflow:hidden}
.trajet-bloc-header{padding:20px 24px;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;border-bottom:1px solid var(--border)}
.trajet-bloc-title{font-family:var(--font-head);font-size:18px;font-weight:800;color:var(--navy);margin-bottom:6px}
.trajet-bloc-meta{display:flex;gap:8px;flex-wrap:wrap}
.trajet-bloc-actions{display:flex;gap:8px;flex-shrink:0}
.reservations-table{width:100%}
.reservations-table th{background:var(--bg);padding:11px 16px;text-align:left;font-size:13px;font-weight:600;color:var(--slate)}
.reservations-table td{padding:11px 16px;border-top:1px solid var(--border);font-size:14px;color:var(--slate)}
.no-resa{padding:20px 24px;color:var(--muted);font-size:14px;text-align:center}
.stat-pills{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
</style>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="page-header">
  <div class="page-header-inner">
    <h1>🚘 Mes trajets</h1>
    <a href="publier_trajet.php" class="btn btn-green">+ Publier un trajet</a>
  </div>
</div>

<div class="content">
  <a href="dashboard.php" style="color:var(--muted);font-size:14px;font-weight:500;display:inline-block;margin-bottom:20px">← Mon espace</a>

  <?php if($flash): ?>
  <div class="alert alert-<?= $flash['type']==='success'?'success':'error' ?>"><?= htmlspecialchars($flash['msg']) ?></div>
  <?php endif; ?>

  <?php if(!$conducteurId): ?>
  <div class="card"><div class="card-body" style="text-align:center;padding:50px">
    <div style="font-size:48px;margin-bottom:14px">🚘</div>
    <h3 style="font-family:var(--font-head);font-size:18px;font-weight:700;margin-bottom:8px">Vous n'êtes pas encore conducteur</h3>
    <p style="color:var(--muted);margin-bottom:20px">Créez un profil conducteur pour publier des trajets.</p>
    <a href="devenir_conducteur.php" class="btn btn-green">Devenir conducteur</a>
  </div></div>

  <?php elseif($erreurBd): ?>
  <div class="alert alert-error">⚠️ Impossible de charger vos trajets.</div>

  <?php elseif(empty($trajets)): ?>
  <div class="card"><div class="card-body" style="text-align:center;padding:50px">
    <div style="font-size:48px;margin-bottom:14px">🗺️</div>
    <h3 style="font-family:var(--font-head);font-size:18px;font-weight:700;margin-bottom:8px">Aucun trajet publié</h3>
    <p style="color:var(--muted);margin-bottom:20px">Publiez votre premier trajet pour commencer.</p>
    <a href="publier_trajet.php" class="btn btn-green">Publier un trajet</a>
  </div></div>

  <?php else: ?>
    <?php foreach($trajets as $t): ?>
    <?php $dateDepart = new DateTime($t['date_depart']); $isPast = $dateDepart < new DateTime(); ?>
    <div class="trajet-bloc">
      <div class="trajet-bloc-header">
        <div>
          <div class="trajet-bloc-title">
            <?= htmlspecialchars($t['ville_depart']) ?> → <?= htmlspecialchars($t['ville_arrivee']) ?>
            <?php if($t['annule']): ?><span class="badge badge-red" style="margin-left:8px">Annulé</span><?php endif; ?>
          </div>
          <div class="trajet-bloc-meta">
            <span class="meta-chip">📅 <?= $dateDepart->format('d/m/Y H:i') ?></span>
            <span class="meta-chip">💺 <?= $t['places_disponibles'] ?> places restantes</span>
            <span class="meta-chip">💰 <?= number_format((float)$t['prix'],0,',',' ') ?> FCFA</span>
          </div>
          <div class="stat-pills" style="margin-top:8px">
            <?php if($t['nb_attente']>0): ?><span class="badge badge-yellow"><?= $t['nb_attente'] ?> en attente</span><?php endif; ?>
            <?php if($t['nb_confirmees']>0): ?><span class="badge badge-green"><?= $t['nb_confirmees'] ?> confirmée<?= $t['nb_confirmees']>1?'s':'' ?></span><?php endif; ?>
            <?php if($t['nb_terminees']>0): ?><span class="badge badge-blue"><?= $t['nb_terminees'] ?> terminée<?= $t['nb_terminees']>1?'s':'' ?></span><?php endif; ?>
          </div>
        </div>
        <?php if(!$t['annule'] && !$isPast): ?>
        <div class="trajet-bloc-actions">
          <a href="modifier_trajet.php?id=<?= $t['id'] ?>" class="btn btn-outline" style="padding:9px 16px;font-size:13px">Modifier</a>
          <form method="POST" onsubmit="return confirm('Annuler ce trajet et toutes ses réservations ?')">
            <input type="hidden" name="action" value="annuler_trajet">
            <input type="hidden" name="trajet_id" value="<?= $t['id'] ?>">
            <button type="submit" class="btn btn-red" style="padding:9px 16px;font-size:13px">Annuler le trajet</button>
          </form>
        </div>
        <?php endif; ?>
      </div>

      <?php if(empty($t['reservations'])): ?>
      <div class="no-resa">Aucune réservation pour ce trajet.</div>
      <?php else: ?>
      <table class="reservations-table">
        <thead><tr>
          <th>Passager</th><th>Téléphone</th><th>Statut</th><th>Paiement</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach($t['reservations'] as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['prenom'].' '.$r['nom']) ?></td>
          <td><?= htmlspecialchars($r['telephone'] ?? '—') ?></td>
          <td><span class="badge <?= $statutBadge[$r['statut']] ?>"><?= $statutLabel[$r['statut']] ?></span></td>
          <td>
            <?php if($r['statut']==='TERMINEE'): ?>
              <?php if($r['paiement_confirme']): ?>
                <span class="badge badge-green">✓ Payé</span>
              <?php else: ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                  <input type="hidden" name="action" value="paiement">
                  <button type="submit" class="btn btn-green" style="padding:6px 12px;font-size:12px">Confirmer paiement</button>
                </form>
              <?php endif; ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php if($r['statut']==='EN_ATTENTE'): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
              <input type="hidden" name="action" value="confirmer">
              <button type="submit" class="btn btn-green" style="padding:6px 12px;font-size:12px">Confirmer</button>
            </form>
            <?php elseif($r['statut']==='CONFIRMEE'): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
              <input type="hidden" name="action" value="terminer">
              <button type="submit" class="btn btn-navy" style="padding:6px 12px;font-size:12px">Marquer terminé</button>
            </form>
            <?php else: ?>—<?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<footer><div class="footer-stripe"></div><strong>Kaay Dem !</strong> — ISM Campus Baobab</footer>
</body>
</html>
