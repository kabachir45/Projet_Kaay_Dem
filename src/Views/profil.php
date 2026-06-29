<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/_urls.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: {$viewsUrl}login.php"); exit;
}

use App\Core\Database;
use App\Controllers\UtilisateurController;
use App\Exceptions\KaayDemException;

$userId  = (int)$_SESSION['utilisateur_id'];
$activePage = '';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Traitement modification — délégué à UtilisateurController (couche objet)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $controller = new UtilisateurController();
    try {
        if ($_POST['action'] === 'modifier_profil') {
            $utilisateur = $controller->modifierProfil($userId, $_POST);
            $_SESSION['nom'] = $utilisateur->getNomComplet();
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Profil mis à jour.'];
        } elseif ($_POST['action'] === 'changer_mdp') {
            $controller->changerMotDePasse(
                $userId,
                $_POST['ancien_mdp']  ?? '',
                $_POST['nouveau_mdp'] ?? '',
                $_POST['confirm_mdp'] ?? ''
            );
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Mot de passe mis à jour.'];
        }
    } catch(KaayDemException $e) {
        $_SESSION['flash'] = ['type'=>'error','msg'=>$e->messageUtilisateur()];
    } catch(\Throwable $e) {
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Erreur : '.$e->getMessage()];
    }
    header("Location: profil.php"); exit;
}

// Charger données
$user = null;
$stats = ['trajets'=>0,'reservations'=>0,'evaluations'=>0,'note'=>0];
$erreurBd = false;
try {
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id=:id");
    $stmt->execute([':id'=>$userId]);
    $user = $stmt->fetch();

    // Stats conducteur
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM trajets t JOIN profil_conducteur pc ON pc.id=t.conducteur_id WHERE pc.utilisateur_id=:uid AND t.annule=0");
    $stmt->execute([':uid'=>$userId]);
    $stats['trajets'] = (int)$stmt->fetchColumn();

    // Stats passager
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN profil_passager pp ON pp.id=r.passager_id WHERE pp.utilisateur_id=:uid");
    $stmt->execute([':uid'=>$userId]);
    $stats['reservations'] = (int)$stmt->fetchColumn();

    // Note moyenne en tant que conducteur
    $stmt = $pdo->prepare("SELECT COALESCE(AVG(e.note),0), COUNT(e.id) FROM evaluations e JOIN reservations r ON r.id=e.reservation_id JOIN trajets t ON t.id=r.trajet_id JOIN profil_conducteur pc ON pc.id=t.conducteur_id WHERE pc.utilisateur_id=:uid");
    $stmt->execute([':uid'=>$userId]);
    [$stats['note'], $stats['evaluations']] = $stmt->fetch(\PDO::FETCH_NUM);

    // Profil conducteur
    $stmt = $pdo->prepare("SELECT * FROM profil_conducteur WHERE utilisateur_id=:uid");
    $stmt->execute([':uid'=>$userId]);
    $profilConducteur = $stmt->fetch();
} catch(\Exception $e) { $erreurBd = true; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mon profil — Kaay Dem !</title>
<?php include __DIR__ . '/_style.php'; ?>
<style>
.page-header{background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:white;padding:40px 24px}
.page-header-inner{max-width:960px;margin:0 auto;display:flex;align-items:center;gap:20px}
.profile-avatar-lg{width:72px;height:72px;background:var(--green);border-radius:50%;display:grid;place-items:center;font-size:28px;font-weight:800;color:white;flex-shrink:0}
.content{max-width:960px;margin:32px auto;padding:0 24px;display:grid;grid-template-columns:1fr 1fr;gap:24px}
.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px;grid-column:span 2}
.stat-box{background:white;border:1px solid var(--border);border-radius:var(--radius);padding:20px;text-align:center}
.stat-box-num{font-family:var(--font-head);font-size:28px;font-weight:800;color:var(--green)}
.stat-box-label{font-size:13px;color:var(--muted);margin-top:4px}
@media(max-width:700px){.content{grid-template-columns:1fr}.stats-grid{grid-template-columns:repeat(2,1fr);grid-column:span 1}}
</style>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="page-header">
  <div class="page-header-inner">
    <?php if($user): ?>
    <div class="profile-avatar-lg"><?= mb_strtoupper(mb_substr($user['prenom'],0,1)) ?></div>
    <div>
      <h1 style="font-family:var(--font-head);font-size:24px;font-weight:800;margin-bottom:4px">
        <?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?>
      </h1>
      <div style="color:rgba(255,255,255,.7);font-size:15px"><?= htmlspecialchars($user['email']) ?></div>
      <?php if(!empty($profilConducteur)): ?>
      <span class="badge <?= $profilConducteur['statut']==='VALIDE'?'badge-green':($profilConducteur['statut']==='EN_ATTENTE'?'badge-yellow':'badge-red') ?>" style="margin-top:8px;display:inline-block">
        Conducteur : <?= $profilConducteur['statut'] ?>
      </span>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if($flash): ?>
<div style="max-width:960px;margin:16px auto;padding:0 24px">
  <div class="alert alert-<?= $flash['type']==='success'?'success':'error' ?>"><?= htmlspecialchars($flash['msg']) ?></div>
</div>
<?php endif; ?>

<div class="content">

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-box">
      <div class="stat-box-num"><?= $stats['trajets'] ?></div>
      <div class="stat-box-label">Trajets publiés</div>
    </div>
    <div class="stat-box">
      <div class="stat-box-num"><?= $stats['reservations'] ?></div>
      <div class="stat-box-label">Réservations</div>
    </div>
    <div class="stat-box">
      <div class="stat-box-num"><?= $stats['note']>0 ? number_format((float)$stats['note'],1).' ★' : '—' ?></div>
      <div class="stat-box-label">Note conducteur (<?= $stats['evaluations'] ?> avis)</div>
    </div>
  </div>

  <!-- Modifier profil -->
  <?php if($user): ?>
  <div class="card">
    <div class="card-body">
      <h2 style="font-family:var(--font-head);font-size:18px;font-weight:800;margin-bottom:20px">✏️ Modifier mes informations</h2>
      <form method="POST">
        <input type="hidden" name="action" value="modifier_profil">
        <div class="form-group">
          <label class="form-label">Prénom</label>
          <input class="form-control" type="text" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Nom</label>
          <input class="form-control" type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Téléphone</label>
          <input class="form-control" type="tel" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:var(--bg);color:var(--muted)">
        </div>
        <button type="submit" class="btn btn-green" style="width:100%;justify-content:center">Enregistrer</button>
      </form>
    </div>
  </div>

  <!-- Changer mot de passe -->
  <div class="card">
    <div class="card-body">
      <h2 style="font-family:var(--font-head);font-size:18px;font-weight:800;margin-bottom:20px">🔒 Changer de mot de passe</h2>
      <form method="POST">
        <input type="hidden" name="action" value="changer_mdp">
        <div class="form-group">
          <label class="form-label">Ancien mot de passe</label>
          <input class="form-control" type="password" name="ancien_mdp" required>
        </div>
        <div class="form-group">
          <label class="form-label">Nouveau mot de passe</label>
          <input class="form-control" type="password" name="nouveau_mdp" required minlength="8">
        </div>
        <div class="form-group">
          <label class="form-label">Confirmer le nouveau mot de passe</label>
          <input class="form-control" type="password" name="confirm_mdp" required>
        </div>
        <button type="submit" class="btn btn-navy" style="width:100%;justify-content:center">Changer le mot de passe</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

</div>

<footer><div class="footer-stripe"></div><strong>Kaay Dem !</strong> — ISM Campus Baobab</footer>
</body>
</html>
