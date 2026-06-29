<?php
session_start();
include __DIR__ . '/_urls.php';
if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php"); exit;
}
// L'admin a son propre espace
if (!empty($_SESSION['est_admin'])) {
    header("Location: admin.php"); exit;
}
$activePage = 'dashboard';
$userNom = $_SESSION['nom'] ?? 'Utilisateur';
$initiale = mb_strtoupper(mb_substr($userNom, 0, 1));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mon espace — Kaay Dem !</title>
<?php include __DIR__ . '/_style.php'; ?>
<style>
.dash-header{background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:white;padding:40px 24px}
.dash-header-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;gap:20px}
.dash-avatar{width:64px;height:64px;background:var(--green);border-radius:50%;display:grid;place-items:center;font-size:26px;font-weight:800;color:white;flex-shrink:0}
.dash-welcome h1{font-family:var(--font-head);font-size:26px;font-weight:800}
.dash-welcome p{color:rgba(255,255,255,.7);font-size:15px;margin-top:4px}
.dash-grid{max-width:1100px;margin:0 auto;padding:36px 24px;display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.dash-card{background:white;border:1px solid var(--border);border-radius:var(--radius);padding:28px;display:flex;flex-direction:column;align-items:flex-start;transition:.25s;text-decoration:none;color:var(--navy)}
.dash-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg);border-color:var(--green)}
.dash-card-icon{font-size:32px;margin-bottom:16px}
.dash-card h3{font-family:var(--font-head);font-size:17px;font-weight:700;margin-bottom:6px}
.dash-card p{font-size:14px;color:var(--muted);line-height:1.5}
.dash-card .card-arrow{margin-top:auto;padding-top:16px;font-size:13px;font-weight:600;color:var(--green)}
.dash-card-red:hover{border-color:var(--red)}
.dash-card-red .card-arrow{color:var(--red)}
@media(max-width:768px){.dash-grid{grid-template-columns:1fr 1fr}.dash-grid .dash-card:last-child{grid-column:span 2}}
@media(max-width:480px){.dash-grid{grid-template-columns:1fr}.dash-grid .dash-card:last-child{grid-column:span 1}}
</style>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="dash-header">
  <div class="dash-header-inner">
    <div class="dash-avatar"><?= htmlspecialchars($initiale) ?></div>
    <div class="dash-welcome">
      <h1>Bonjour, <?= htmlspecialchars($userNom) ?> 👋</h1>
      <p>Que souhaitez-vous faire aujourd'hui ?</p>
    </div>
  </div>
</div>

<div class="dash-grid">
  <a href="rechercher_trajet.php" class="dash-card">
    <div class="dash-card-icon">🔍</div>
    <h3>Rechercher un trajet</h3>
    <p>Trouvez un covoiturage disponible entre vos villes habituelles.</p>
    <div class="card-arrow">Rechercher →</div>
  </a>

  <a href="publier_trajet.php" class="dash-card">
    <div class="dash-card-icon">🚘</div>
    <h3>Publier un trajet</h3>
    <p>Proposez vos places disponibles et rentabilisez votre trajet.</p>
    <div class="card-arrow">Publier →</div>
  </a>

  <a href="mes_trajets.php" class="dash-card">
    <div class="dash-card-icon">📋</div>
    <h3>Mes trajets</h3>
    <p>Consultez et gérez les trajets que vous avez publiés en tant que conducteur.</p>
    <div class="card-arrow">Voir mes trajets →</div>
  </a>

  <a href="mes_reservations.php" class="dash-card">
    <div class="dash-card-icon">📅</div>
    <h3>Mes réservations</h3>
    <p>Suivez le statut de vos réservations en cours et passées.</p>
    <div class="card-arrow">Voir mes réservations →</div>
  </a>

  <a href="profil.php" class="dash-card">
    <div class="dash-card-icon">👤</div>
    <h3>Mon profil</h3>
    <p>Consultez et mettez à jour vos informations personnelles.</p>
    <div class="card-arrow">Voir mon profil →</div>
  </a>

  <a href="<?= $baseUrl ?>logout.php" class="dash-card dash-card-red">
    <div class="dash-card-icon">🚪</div>
    <h3>Déconnexion</h3>
    <p>Terminer votre session et revenir à la page d'accueil.</p>
    <div class="card-arrow">Se déconnecter →</div>
  </a>
</div>

<footer>
  <div class="footer-stripe"></div>
  <strong>Kaay Dem !</strong> — Plateforme de covoiturage étudiant · ISM Campus Baobab
</footer>
</body>
</html>
