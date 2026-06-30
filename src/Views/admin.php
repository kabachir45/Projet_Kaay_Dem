<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/_urls.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: {$viewsUrl}login.php"); exit;
}

use App\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("SELECT est_admin FROM utilisateurs WHERE id=:id");
    $stmt->execute([':id' => $_SESSION['utilisateur_id']]);
    $row = $stmt->fetch();
    if (!$row || !$row['est_admin']) {
        header("Location: {$viewsUrl}dashboard.php"); exit;
    }
} catch(\Exception $e) {
    header("Location: {$viewsUrl}dashboard.php"); exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$section = $_GET['section'] ?? 'dashboard';

// ── Actions POST ──────────────────────────────────────────────
// Les mutations sont déléguées à AdminController (couche objet : modèle
// Administrateur + modèles métier + repositories). La vue ne fait qu'appeler
// l'action et présenter le résultat.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action']    ?? '';
    $targetId = (int)($_POST['target_id'] ?? 0);
    try {
        $admin = new \App\Controllers\AdminController($_SESSION['utilisateur_id']);
        switch($action) {
            case 'valider_conducteur':
                $admin->validerConducteur($targetId);
                $flash = ['type'=>'success','msg'=>'Conducteur validé avec succès.'];
                break;
            case 'rejeter_conducteur':
                $admin->rejeterConducteur($targetId);
                $flash = ['type'=>'success','msg'=>'Conducteur refusé.'];
                break;
            case 'bannir':
                $admin->bannir($targetId);
                $flash = ['type'=>'success','msg'=>'Utilisateur banni et supprimé.'];
                break;
            case 'traiter_signalement':
                $admin->traiterSignalement($targetId);
                $flash = ['type'=>'success','msg'=>'Signalement marqué comme traité.'];
                break;
            case 'annuler_trajet':
                $admin->annulerTrajet($targetId);
                $flash = ['type'=>'success','msg'=>'Trajet annulé.'];
                break;
        }
    } catch(\App\Exceptions\KaayDemException $e) {
        $flash = ['type'=>'error','msg'=>$e->messageUtilisateur()];
    } catch(\Throwable $e) {
        $flash = ['type'=>'error','msg'=>'Erreur : '.$e->getMessage()];
    }
    $_SESSION['flash'] = $flash;
    header("Location: admin.php?section=$section"); exit;
}

// ── Données selon section ─────────────────────────────────────
$data = [];
try {
    // Stats globales toujours chargées
    $data['stats'] = [
        'utilisateurs'  => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE est_admin=0")->fetchColumn(),
        'conducteurs'   => $pdo->query("SELECT COUNT(*) FROM profil_conducteur WHERE statut='VALIDE'")->fetchColumn(),
        'trajets'       => $pdo->query("SELECT COUNT(*) FROM trajets WHERE annule=0")->fetchColumn(),
        'reservations'  => $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn(),
        'en_attente'    => $pdo->query("SELECT COUNT(*) FROM profil_conducteur WHERE statut='EN_ATTENTE'")->fetchColumn(),
        'signalements'  => $pdo->query("SELECT COUNT(*) FROM signalements WHERE traite=0")->fetchColumn(),
        'note_moy'      => $pdo->query("SELECT COALESCE(ROUND(AVG(note),1),0) FROM evaluations")->fetchColumn(),
        'paiements_att' => $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='TERMINEE' AND paiement_confirme=0")->fetchColumn(),
    ];

    // Taux d'occupation = places réservées / places offertes (sur les trajets non annulés)
    $placesReservees = (int) $pdo->query("SELECT COUNT(*) FROM reservations r JOIN trajets t ON t.id=r.trajet_id WHERE t.annule=0 AND r.statut <> 'ANNULEE'")->fetchColumn();
    $placesRestantes = (int) $pdo->query("SELECT COALESCE(SUM(places_disponibles),0) FROM trajets WHERE annule=0")->fetchColumn();
    $placesOffertes  = $placesReservees + $placesRestantes;
    $data['stats']['taux_occupation'] = $placesOffertes > 0 ? round($placesReservees * 100 / $placesOffertes) : 0;

    if ($section === 'conducteurs' || $section === 'dashboard') {
        $data['conducteurs_pending'] = $pdo->query("
            SELECT pc.*, u.nom, u.prenom, u.email, u.telephone
            FROM profil_conducteur pc
            JOIN utilisateurs u ON u.id=pc.utilisateur_id
            WHERE pc.statut='EN_ATTENTE'
            ORDER BY pc.created_at ASC
        ")->fetchAll();
    }
    if ($section === 'conducteurs') {
        $data['conducteurs_all'] = $pdo->query("
            SELECT pc.*, u.nom, u.prenom, u.email,
                   COUNT(t.id) AS nb_trajets,
                   COALESCE(ROUND(AVG(e.note),1),0) AS note
            FROM profil_conducteur pc
            JOIN utilisateurs u ON u.id=pc.utilisateur_id
            LEFT JOIN trajets t ON t.conducteur_id=pc.id AND t.annule=0
            LEFT JOIN reservations r ON r.trajet_id=t.id AND r.statut='TERMINEE'
            LEFT JOIN evaluations e ON e.reservation_id=r.id
            GROUP BY pc.id ORDER BY pc.created_at DESC
        ")->fetchAll();
    }
    if ($section === 'utilisateurs') {
        $data['utilisateurs'] = $pdo->query("
            SELECT u.*,
                   (SELECT COUNT(*) FROM trajets t JOIN profil_conducteur pc ON pc.id=t.conducteur_id WHERE pc.utilisateur_id=u.id AND t.annule=0) AS nb_trajets,
                   (SELECT COUNT(*) FROM reservations r JOIN profil_passager pp ON pp.id=r.passager_id WHERE pp.utilisateur_id=u.id) AS nb_reservations,
                   (SELECT statut FROM profil_conducteur WHERE utilisateur_id=u.id LIMIT 1) AS statut_conducteur
            FROM utilisateurs u WHERE u.est_admin=0
            ORDER BY u.created_at DESC
        ")->fetchAll();
    }
    if ($section === 'trajets') {
        $data['trajets'] = $pdo->query("
            SELECT t.*, u.nom, u.prenom,
                   COUNT(r.id) AS nb_reservations,
                   SUM(CASE WHEN r.statut='EN_ATTENTE' THEN 1 ELSE 0 END) AS nb_attente
            FROM trajets t
            JOIN profil_conducteur pc ON pc.id=t.conducteur_id
            JOIN utilisateurs u ON u.id=pc.utilisateur_id
            LEFT JOIN reservations r ON r.trajet_id=t.id
            GROUP BY t.id
            ORDER BY t.created_at DESC
            LIMIT 50
        ")->fetchAll();
    }
    if ($section === 'signalements') {
        $data['signalements_open'] = $pdo->query("
            SELECT s.*, u1.nom AS rnom, u1.prenom AS rprenom, u2.nom AS snom, u2.prenom AS sprenom, u2.id AS signale_uid
            FROM signalements s
            JOIN utilisateurs u1 ON u1.id=s.rapporteur_id
            JOIN utilisateurs u2 ON u2.id=s.signale_id
            WHERE s.traite=0 ORDER BY s.created_at DESC
        ")->fetchAll();
        $data['signalements_done'] = $pdo->query("
            SELECT s.*, u1.nom AS rnom, u1.prenom AS rprenom, u2.nom AS snom, u2.prenom AS sprenom
            FROM signalements s
            JOIN utilisateurs u1 ON u1.id=s.rapporteur_id
            JOIN utilisateurs u2 ON u2.id=s.signale_id
            WHERE s.traite=1 ORDER BY s.created_at DESC LIMIT 20
        ")->fetchAll();
    }
    if ($section === 'evaluations') {
        $data['evaluations'] = $pdo->query("
            SELECT e.*, r.statut AS r_statut,
                   t.ville_depart, t.ville_arrivee, t.date_depart,
                   uc.nom AS c_nom, uc.prenom AS c_prenom,
                   up.nom AS p_nom, up.prenom AS p_prenom
            FROM evaluations e
            JOIN reservations r ON r.id=e.reservation_id
            JOIN trajets t ON t.id=r.trajet_id
            JOIN profil_conducteur pc ON pc.id=t.conducteur_id
            JOIN utilisateurs uc ON uc.id=pc.utilisateur_id
            JOIN profil_passager pp ON pp.id=r.passager_id
            JOIN utilisateurs up ON up.id=pp.utilisateur_id
            ORDER BY e.created_at DESC LIMIT 50
        ")->fetchAll();
        $data['stats_eval'] = $pdo->query("
            SELECT note, COUNT(*) AS nb FROM evaluations GROUP BY note ORDER BY note DESC
        ")->fetchAll();
    }
    if ($section === 'dashboard') {
        $data['trajets_mois'] = $pdo->query("
            SELECT DATE_FORMAT(date_depart,'%Y-%m') AS mois, COUNT(*) AS nb
            FROM trajets WHERE annule=0
            GROUP BY mois ORDER BY mois DESC LIMIT 6
        ")->fetchAll();
        $data['top_conducteurs'] = $pdo->query("
            SELECT u.nom, u.prenom, COUNT(t.id) AS nb_trajets,
                   COALESCE(ROUND(AVG(e.note),1),0) AS note,
                   COUNT(DISTINCT e.id) AS nb_avis
            FROM utilisateurs u
            JOIN profil_conducteur pc ON pc.id=(SELECT id FROM profil_conducteur WHERE utilisateur_id=u.id LIMIT 1)
            LEFT JOIN trajets t ON t.conducteur_id=pc.id AND t.annule=0
            LEFT JOIN reservations r ON r.trajet_id=t.id AND r.statut='TERMINEE'
            LEFT JOIN evaluations e ON e.reservation_id=r.id
            GROUP BY u.id HAVING nb_trajets>0
            ORDER BY note DESC, nb_trajets DESC LIMIT 5
        ")->fetchAll();
    }
} catch(\Exception $e) { $data['error'] = $e->getMessage(); }

$sections = [
    'dashboard'    => ['icon'=>'📊','label'=>'Tableau de bord'],
    'conducteurs'  => ['icon'=>'🚗','label'=>'Conducteurs'],
    'utilisateurs' => ['icon'=>'👥','label'=>'Utilisateurs'],
    'trajets'      => ['icon'=>'🗺️','label'=>'Trajets'],
    'signalements' => ['icon'=>'🚩','label'=>'Signalements'],
    'evaluations'  => ['icon'=>'⭐','label'=>'Évaluations'],
];
$adminNom = $_SESSION['nom'] ?? 'Administrateur';
$initiale = mb_strtoupper(mb_substr($adminNom, 0, 1));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Administration — Kaay Dem !</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --green:#00853F;--green-light:#e8f7ee;--green-dark:#006830;
  --gold:#FDEF42;--red:#E31E24;
  --navy:#0B1F3A;--navy-mid:#1a3557;
  --slate:#3D566E;--muted:#6B8299;--border:#DDE4EC;
  --bg:#F4F7FB;--white:#fff;
  --radius:12px;--radius-sm:8px;
  --shadow:0 4px 24px rgba(11,31,58,.09);
  --font-head:'Plus Jakarta Sans',system-ui,sans-serif;
  --font-body:'Inter',system-ui,sans-serif;
  --sidebar-w:240px;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{font-family:var(--font-body);background:var(--bg);color:var(--navy);display:flex;min-height:100vh}

/* ── Sidebar ─────────────────────────────────────── */
.sidebar{
  width:var(--sidebar-w);flex-shrink:0;
  background:var(--navy);min-height:100vh;
  display:flex;flex-direction:column;
  position:fixed;top:0;left:0;bottom:0;z-index:50;
}
.sidebar-logo{
  padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.1);
  font-family:var(--font-head);font-size:20px;font-weight:800;color:white;
  display:flex;align-items:center;gap:10px;
}
.sidebar-logo .logo-icon{width:36px;height:36px;background:var(--green);border-radius:9px;display:grid;place-items:center;font-size:16px;flex-shrink:0}
.sidebar-logo .logo-sub{font-size:11px;font-weight:600;color:rgba(255,255,255,.4);display:block;text-transform:uppercase;letter-spacing:.5px;margin-top:2px}
.sidebar-nav{flex:1;padding:16px 10px;overflow-y:auto}
.sidebar-section-label{font-size:10px;font-weight:700;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:.8px;padding:12px 10px 6px}
.sidebar-link{
  display:flex;align-items:center;gap:12px;
  padding:11px 12px;border-radius:var(--radius-sm);
  color:rgba(255,255,255,.65);font-size:14px;font-weight:500;
  text-decoration:none;transition:.2s;margin-bottom:2px;position:relative;
}
.sidebar-link:hover{background:rgba(255,255,255,.07);color:white}
.sidebar-link.active{background:var(--green);color:white;font-weight:700}
.sidebar-link .icon{font-size:18px;width:22px;text-align:center;flex-shrink:0}
.sidebar-badge{
  margin-left:auto;background:var(--red);color:white;
  font-size:11px;font-weight:700;padding:2px 7px;border-radius:20px;
}
.sidebar-footer{
  padding:16px;border-top:1px solid rgba(255,255,255,.1);
}
.sidebar-user{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.sidebar-avatar{width:36px;height:36px;background:var(--green);border-radius:50%;display:grid;place-items:center;font-weight:700;color:white;font-size:14px;flex-shrink:0}
.sidebar-username{font-size:13px;font-weight:600;color:white;line-height:1.3}
.sidebar-role{font-size:11px;color:rgba(255,255,255,.4)}
.sidebar-logout{display:block;text-align:center;padding:9px;background:rgba(255,255,255,.07);border-radius:var(--radius-sm);color:rgba(255,255,255,.6);font-size:13px;font-weight:600;text-decoration:none;transition:.2s}
.sidebar-logout:hover{background:rgba(227,30,36,.3);color:#ff8080}

/* ── Main ────────────────────────────────────────── */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh}
.topbar{
  background:white;border-bottom:1px solid var(--border);
  padding:0 28px;height:64px;display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:40;
}
.topbar-title{font-family:var(--font-head);font-size:20px;font-weight:800;color:var(--navy)}
.topbar-right{display:flex;align-items:center;gap:12px}
.alert-pill{display:flex;align-items:center;gap:6px;padding:6px 12px;border-radius:20px;font-size:13px;font-weight:600}
.alert-pill-red{background:#fef2f2;color:#b91c1c}
.alert-pill-yellow{background:#fffbeb;color:#92400e}
.content{padding:28px;flex:1}

/* ── Composants ─────────────────────────────────── */
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.stat-card{background:white;border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px}
.stat-card-icon{font-size:28px;margin-bottom:12px}
.stat-card-num{font-family:var(--font-head);font-size:30px;font-weight:800;color:var(--navy)}
.stat-card-label{font-size:13px;color:var(--muted);margin-top:4px}
.stat-card-green .stat-card-num{color:var(--green)}
.stat-card-red .stat-card-num{color:var(--red)}
.stat-card-gold .stat-card-num{color:#b45309}

.section-card{background:white;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:20px;overflow:hidden}
.section-card-header{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.section-card-title{font-family:var(--font-head);font-size:16px;font-weight:800;color:var(--navy);display:flex;align-items:center;gap:8px}
.section-card-body{padding:0}

table{width:100%;border-collapse:collapse;font-size:14px}
thead th{background:var(--navy);color:white;padding:12px 16px;text-align:left;font-weight:600;font-size:13px;letter-spacing:.3px}
tbody tr{border-bottom:1px solid var(--border);transition:background .15s}
tbody tr:hover{background:var(--green-light)}
tbody td{padding:12px 16px;color:var(--slate);vertical-align:middle}
tbody tr:last-child{border-bottom:none}

.badge{display:inline-flex;align-items:center;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:700}
.badge-green{background:var(--green-light);color:var(--green-dark)}
.badge-red{background:#fef2f2;color:#b91c1c}
.badge-yellow{background:#fffbeb;color:#92400e}
.badge-blue{background:#eff6ff;color:#1e40af}
.badge-gray{background:#f3f4f6;color:#4b5563}

.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:var(--radius-sm);font-size:13px;font-weight:700;border:none;cursor:pointer;transition:.2s;text-decoration:none}
.btn-green{background:var(--green);color:white}.btn-green:hover{background:var(--green-dark)}
.btn-red{background:var(--red);color:white}.btn-red:hover{background:#c01a1f}
.btn-navy{background:var(--navy);color:white}.btn-navy:hover{background:var(--navy-mid)}
.btn-ghost{background:var(--bg);color:var(--slate);border:1px solid var(--border)}.btn-ghost:hover{border-color:var(--green);color:var(--green)}

.alert{padding:13px 18px;border-radius:var(--radius-sm);font-size:14px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}

.empty-row td{text-align:center;padding:32px;color:var(--muted);font-style:italic}

/* Dashboard widgets */
.dash-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
.top-conducteur{display:flex;align-items:center;gap:12px;padding:13px 16px;border-bottom:1px solid var(--border)}
.top-conducteur:last-child{border-bottom:none}
.top-conducteur-rank{width:28px;height:28px;border-radius:50%;background:var(--navy);color:white;display:grid;place-items:center;font-size:12px;font-weight:800;flex-shrink:0}
.top-conducteur-rank.gold{background:#f59e0b}
.top-conducteur-rank.silver{background:#9ca3af}
.top-conducteur-rank.bronze{background:#b45309}
.mois-bar{display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid var(--border)}
.mois-bar:last-child{border-bottom:none}
.mois-bar-fill{height:10px;background:var(--green);border-radius:4px;transition:.3s}
.mois-bar-label{font-size:13px;color:var(--slate);min-width:70px}
.mois-bar-num{font-size:13px;font-weight:700;color:var(--navy);min-width:24px;text-align:right}

/* Étoiles */
.stars{color:var(--gold);letter-spacing:1px}
.stars-gray{color:#d1d5db}

[data-theme="dark"] .sidebar{background:#020617}
[data-theme="dark"] .sidebar-link{color:rgba(255,255,255,.5)}
[data-theme="dark"] .sidebar-link:hover{background:rgba(255,255,255,.05);color:white}
[data-theme="dark"] .main{background:#0f172a}
[data-theme="dark"] .topbar{background:#1e293b;border-color:#2d3748}
[data-theme="dark"] .topbar-title{color:#e2e8f0}
[data-theme="dark"] .content{background:#0f172a}
[data-theme="dark"] .stat-card{background:#1e293b;border-color:#2d3748}
[data-theme="dark"] .stat-card-num{color:#e2e8f0}
[data-theme="dark"] .stat-card-green .stat-card-num{color:var(--green)}
[data-theme="dark"] .section-card{background:#1e293b;border-color:#2d3748}
[data-theme="dark"] .section-card-header{border-color:#2d3748;background:#1e293b}
[data-theme="dark"] .section-card-title{color:#e2e8f0}
[data-theme="dark"] thead{background:#020617}
[data-theme="dark"] tbody tr{border-color:#2d3748;color:#94a3b8}
[data-theme="dark"] tbody tr:hover{background:rgba(21,208,224,.08)}
[data-theme="dark"] .mois-bar{border-color:#2d3748}
[data-theme="dark"] .mois-bar-label,[data-theme="dark"] .mois-bar-num{color:#94a3b8}
[data-theme="dark"] .top-conducteur{border-color:#2d3748}
[data-theme="dark"] .btn-ghost{background:#1e293b;color:#94a3b8;border-color:#2d3748}
[data-theme="dark"] .alert-pill-yellow{background:#451a03;color:#fbbf24}
[data-theme="dark"] .alert-pill-red{background:#450a0a;color:#f87171}
@media(max-width:900px){
  .sidebar{transform:translateX(-100%)}
  .main{margin-left:0}
  .stat-grid{grid-template-columns:repeat(2,1fr)}
  .dash-grid-2{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- ── SIDEBAR ───────────────────────────────────────────────── -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🚗</div>
    <div>
      KaayDem
      <span class="sidebar-sub" style="font-size:11px;color:rgba(255,255,255,.4);display:block;font-weight:500;margin-top:1px">Espace Administration</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section-label">Navigation</div>
    <?php foreach($sections as $key => $s): ?>
    <a href="admin.php?section=<?= $key ?>"
       class="sidebar-link <?= $section===$key?'active':'' ?>">
      <span class="icon"><?= $s['icon'] ?></span>
      <?= $s['label'] ?>
      <?php if($key==='conducteurs' && $data['stats']['en_attente']>0): ?>
        <span class="sidebar-badge"><?= $data['stats']['en_attente'] ?></span>
      <?php elseif($key==='signalements' && $data['stats']['signalements']>0): ?>
        <span class="sidebar-badge"><?= $data['stats']['signalements'] ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>

    <div class="sidebar-section-label" style="margin-top:16px">Accès rapide</div>
    <a href="<?= $viewsUrl ?>rechercher_trajet.php" class="sidebar-link" target="_blank">
      <span class="icon">🔍</span> Voir le site
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= htmlspecialchars($initiale) ?></div>
      <div>
        <div class="sidebar-username"><?= htmlspecialchars($adminNom) ?></div>
        <div class="sidebar-role">Administrateur</div>
      </div>
    </div>
    <a href="<?= $baseUrl ?>logout.php" class="sidebar-logout">🚪 Déconnexion</a>
  </div>
</aside>

<!-- ── MAIN ──────────────────────────────────────────────────── -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title">
      <?= $sections[$section]['icon'] ?? '📊' ?> <?= $sections[$section]['label'] ?? 'Dashboard' ?>
    </div>
    <div class="topbar-right">
      <?php if($data['stats']['en_attente']>0): ?>
      <a href="admin.php?section=conducteurs" class="alert-pill alert-pill-yellow">
        ⏳ <?= $data['stats']['en_attente'] ?> conducteur<?= $data['stats']['en_attente']>1?'s':'' ?> en attente
      </a>
      <?php endif; ?>
      <?php if($data['stats']['signalements']>0): ?>
      <a href="admin.php?section=signalements" class="alert-pill alert-pill-red">
        🚩 <?= $data['stats']['signalements'] ?> signalement<?= $data['stats']['signalements']>1?'s':'' ?>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="content">
    <?php if($flash): ?>
    <div class="alert alert-<?= $flash['type']==='success'?'success':'error' ?>">
      <?= $flash['type']==='success'?'✓':'⚠️' ?> <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <?php if(isset($data['error'])): ?>
    <div class="alert alert-error">⚠️ Erreur BD : <?= htmlspecialchars($data['error']) ?></div>
    <?php endif; ?>

    <!-- ── STATS (toujours visibles) ───────────────────────── -->
    <div class="stat-grid">
      <div class="stat-card stat-card-green">
        <div class="stat-card-icon">👥</div>
        <div class="stat-card-num"><?= $data['stats']['utilisateurs'] ?></div>
        <div class="stat-card-label">Utilisateurs inscrits</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon">🚗</div>
        <div class="stat-card-num"><?= $data['stats']['conducteurs'] ?></div>
        <div class="stat-card-label">Conducteurs validés</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon">🗺️</div>
        <div class="stat-card-num"><?= $data['stats']['trajets'] ?></div>
        <div class="stat-card-label">Trajets actifs</div>
      </div>
      <div class="stat-card stat-card-gold">
        <div class="stat-card-icon">⭐</div>
        <div class="stat-card-num"><?= $data['stats']['note_moy']>0 ? $data['stats']['note_moy'].' ★' : '—' ?></div>
        <div class="stat-card-label">Note moyenne</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon">📊</div>
        <div class="stat-card-num"><?= $data['stats']['taux_occupation'] ?>%</div>
        <div class="stat-card-label">Taux d'occupation</div>
      </div>
    </div>

    <?php /* ════════════════ DASHBOARD ════════════════ */ ?>
    <?php if($section === 'dashboard'): ?>

    <div class="dash-grid-2">
      <!-- Trajets par mois -->
      <div class="section-card">
        <div class="section-card-header">
          <div class="section-card-title">📈 Trajets par mois</div>
        </div>
        <?php
          $max = max(array_column($data['trajets_mois'] ?? [['']], 'nb') ?: [1]);
          foreach($data['trajets_mois'] ?? [] as $m): ?>
        <div class="mois-bar">
          <span class="mois-bar-label"><?= htmlspecialchars($m['mois']) ?></span>
          <div style="flex:1;background:var(--border);border-radius:4px;height:10px">
            <div class="mois-bar-fill" style="width:<?= round($m['nb']/$max*100) ?>%"></div>
          </div>
          <span class="mois-bar-num"><?= $m['nb'] ?></span>
        </div>
        <?php endforeach; ?>
        <?php if(empty($data['trajets_mois'])): ?><div style="padding:20px;text-align:center;color:var(--muted);font-size:14px">Aucune donnée</div><?php endif; ?>
      </div>

      <!-- Top conducteurs -->
      <div class="section-card">
        <div class="section-card-header">
          <div class="section-card-title">🏆 Top conducteurs</div>
        </div>
        <?php $ranks = ['gold','silver','bronze']; ?>
        <?php foreach($data['top_conducteurs'] ?? [] as $i => $c): ?>
        <div class="top-conducteur">
          <div class="top-conducteur-rank <?= $ranks[$i] ?? '' ?>"><?= $i+1 ?></div>
          <div style="flex:1">
            <div style="font-weight:700;font-size:14px"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></div>
            <div style="font-size:12px;color:var(--muted)"><?= $c['nb_trajets'] ?> trajet<?= $c['nb_trajets']>1?'s':'' ?></div>
          </div>
          <div style="text-align:right">
            <div style="color:var(--gold);font-weight:700;font-size:14px"><?= $c['note']>0?$c['note'].' ★':'—' ?></div>
            <div style="font-size:12px;color:var(--muted)"><?= $c['nb_avis'] ?> avis</div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($data['top_conducteurs'])): ?><div style="padding:20px;text-align:center;color:var(--muted);font-size:14px">Aucune donnée</div><?php endif; ?>
      </div>
    </div>

    <!-- Conducteurs en attente sur le dashboard -->
    <?php if(!empty($data['conducteurs_pending'])): ?>
    <div class="section-card">
      <div class="section-card-header">
        <div class="section-card-title">⏳ Conducteurs en attente de validation</div>
        <a href="admin.php?section=conducteurs" class="btn btn-ghost">Voir tout</a>
      </div>
      <table>
        <thead><tr><th>Nom</th><th>Email</th><th>N° Permis</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach(array_slice($data['conducteurs_pending'],0,3) as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></td>
          <td><?= htmlspecialchars($c['email']) ?></td>
          <td><?= htmlspecialchars($c['numero_permis']) ?></td>
          <td style="display:flex;gap:8px;padding:10px 16px">
            <form method="POST"><input type="hidden" name="action" value="valider_conducteur"><input type="hidden" name="target_id" value="<?= $c['id'] ?>"><button class="btn btn-green">✓ Valider</button></form>
            <form method="POST"><input type="hidden" name="action" value="rejeter_conducteur"><input type="hidden" name="target_id" value="<?= $c['id'] ?>"><button class="btn btn-red">✗ Rejeter</button></form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php /* ════════════════ CONDUCTEURS ════════════════ */ ?>
    <?php elseif($section === 'conducteurs'): ?>

    <?php if(!empty($data['conducteurs_pending'])): ?>
    <div class="section-card">
      <div class="section-card-header">
        <div class="section-card-title">⏳ En attente de validation (<?= count($data['conducteurs_pending']) ?>)</div>
      </div>
      <table>
        <thead><tr><th>Nom</th><th>Email</th><th>Téléphone</th><th>N° Permis</th><th>Demande le</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($data['conducteurs_pending'] as $c): ?>
        <tr>
          <td style="font-weight:600"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></td>
          <td><?= htmlspecialchars($c['email']) ?></td>
          <td><?= htmlspecialchars($c['telephone']??'—') ?></td>
          <td><code style="background:var(--bg);padding:3px 8px;border-radius:4px;font-size:13px"><?= htmlspecialchars($c['numero_permis']) ?></code></td>
          <td style="font-size:13px"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
          <td style="display:flex;gap:8px;padding:10px 16px">
            <form method="POST"><input type="hidden" name="action" value="valider_conducteur"><input type="hidden" name="target_id" value="<?= $c['id'] ?>"><button class="btn btn-green">✓ Valider</button></form>
            <form method="POST"><input type="hidden" name="action" value="rejeter_conducteur"><input type="hidden" name="target_id" value="<?= $c['id'] ?>"><button class="btn btn-red">✗ Rejeter</button></form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <div class="section-card">
      <div class="section-card-header">
        <div class="section-card-title">🚗 Tous les conducteurs</div>
      </div>
      <table>
        <thead><tr><th>Nom</th><th>Email</th><th>Statut</th><th>Trajets</th><th>Note</th></tr></thead>
        <tbody>
        <?php foreach($data['conducteurs_all']??[] as $c): ?>
        <tr>
          <td style="font-weight:600"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></td>
          <td><?= htmlspecialchars($c['email']) ?></td>
          <td>
            <span class="badge <?= $c['statut']==='VALIDE'?'badge-green':($c['statut']==='EN_ATTENTE'?'badge-yellow':'badge-red') ?>">
              <?= $c['statut'] ?>
            </span>
          </td>
          <td><?= $c['nb_trajets'] ?></td>
          <td><?= $c['note']>0 ? '<span style="color:var(--gold);font-weight:700">'.$c['note'].' ★</span>' : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($data['conducteurs_all'])): ?><tr class="empty-row"><td colspan="5">Aucun conducteur inscrit</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php /* ════════════════ UTILISATEURS ════════════════ */ ?>
    <?php elseif($section === 'utilisateurs'): ?>
    <div class="section-card">
      <div class="section-card-header">
        <div class="section-card-title">👥 Tous les utilisateurs (<?= count($data['utilisateurs']??[]) ?>)</div>
      </div>
      <table>
        <thead><tr><th>Nom</th><th>Email</th><th>Téléphone</th><th>Rôle conducteur</th><th>Trajets</th><th>Réservations</th><th>Inscrit le</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach($data['utilisateurs']??[] as $u): ?>
        <tr>
          <td style="font-weight:600"><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['telephone']??'—') ?></td>
          <td>
            <?php if($u['statut_conducteur']): ?>
            <span class="badge <?= $u['statut_conducteur']==='VALIDE'?'badge-green':($u['statut_conducteur']==='EN_ATTENTE'?'badge-yellow':'badge-red') ?>">
              <?= $u['statut_conducteur'] ?>
            </span>
            <?php else: ?><span style="color:var(--muted);font-size:13px">Passager</span><?php endif; ?>
          </td>
          <td><?= $u['nb_trajets'] ?></td>
          <td><?= $u['nb_reservations'] ?></td>
          <td style="font-size:13px"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
          <td>
            <form method="POST" onsubmit="return confirm('Bannir <?= htmlspecialchars(addslashes($u['prenom'])) ?> ? Cette action est irréversible.')">
              <input type="hidden" name="action" value="bannir">
              <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
              <button class="btn btn-red" style="padding:5px 10px;font-size:12px">Bannir</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($data['utilisateurs'])): ?><tr class="empty-row"><td colspan="8">Aucun utilisateur</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php /* ════════════════ TRAJETS ════════════════ */ ?>
    <?php elseif($section === 'trajets'): ?>
    <div class="section-card">
      <div class="section-card-header">
        <div class="section-card-title">🗺️ Tous les trajets (50 derniers)</div>
      </div>
      <table>
        <thead><tr><th>Trajet</th><th>Conducteur</th><th>Départ</th><th>Prix</th><th>Places</th><th>Réservations</th><th>Statut</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach($data['trajets']??[] as $t): ?>
        <?php $dd = new DateTime($t['date_depart']); ?>
        <tr>
          <td style="font-weight:600"><?= htmlspecialchars($t['ville_depart']) ?> → <?= htmlspecialchars($t['ville_arrivee']) ?></td>
          <td><?= htmlspecialchars($t['prenom'].' '.$t['nom']) ?></td>
          <td style="font-size:13px"><?= $dd->format('d/m/Y H:i') ?></td>
          <td><?= number_format((float)$t['prix'],0,',',' ') ?> FCFA</td>
          <td><?= $t['places_disponibles'] ?></td>
          <td><?= $t['nb_reservations'] ?><?= $t['nb_attente']>0?' <span class="badge badge-yellow" style="font-size:11px">'.$t['nb_attente'].' att.</span>':'' ?></td>
          <td><?php if($t['annule']): ?><span class="badge badge-red">Annulé</span><?php elseif($dd < new DateTime()): ?><span class="badge badge-gray">Passé</span><?php else: ?><span class="badge badge-green">Actif</span><?php endif; ?></td>
          <td>
            <?php if(!$t['annule']): ?>
            <form method="POST" onsubmit="return confirm('Annuler ce trajet ?')">
              <input type="hidden" name="action" value="annuler_trajet">
              <input type="hidden" name="target_id" value="<?= $t['id'] ?>">
              <button class="btn btn-red" style="padding:5px 10px;font-size:12px">Annuler</button>
            </form>
            <?php else: ?>—<?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($data['trajets'])): ?><tr class="empty-row"><td colspan="8">Aucun trajet</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php /* ════════════════ SIGNALEMENTS ════════════════ */ ?>
    <?php elseif($section === 'signalements'): ?>
    <div class="section-card">
      <div class="section-card-header">
        <div class="section-card-title">🚩 Signalements ouverts (<?= count($data['signalements_open']??[]) ?>)</div>
      </div>
      <table>
        <thead><tr><th>Rapporteur</th><th>Personne signalée</th><th>Motif</th><th>Description</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($data['signalements_open']??[] as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['rprenom'].' '.$s['rnom']) ?></td>
          <td style="font-weight:600;color:var(--red)"><?= htmlspecialchars($s['sprenom'].' '.$s['snom']) ?></td>
          <td><span class="badge badge-red"><?= htmlspecialchars($s['motif']) ?></span></td>
          <td style="max-width:220px;font-size:13px"><?= htmlspecialchars(substr($s['description']??'',0,100)) ?><?= strlen($s['description']??'')>100?'…':'' ?></td>
          <td style="font-size:13px"><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
          <td style="display:flex;gap:6px;padding:10px 16px;flex-wrap:wrap">
            <form method="POST"><input type="hidden" name="action" value="traiter_signalement"><input type="hidden" name="target_id" value="<?= $s['id'] ?>"><button class="btn btn-navy">✓ Traité</button></form>
            <form method="POST" onsubmit="return confirm('Bannir cet utilisateur ?')"><input type="hidden" name="action" value="bannir"><input type="hidden" name="target_id" value="<?= $s['signale_uid'] ?>"><button class="btn btn-red">Bannir</button></form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($data['signalements_open'])): ?><tr class="empty-row"><td colspan="6">✓ Aucun signalement en attente</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if(!empty($data['signalements_done'])): ?>
    <div class="section-card">
      <div class="section-card-header">
        <div class="section-card-title" style="color:var(--muted)">✓ Signalements traités (20 derniers)</div>
      </div>
      <table>
        <thead><tr><th>Rapporteur</th><th>Signalé</th><th>Motif</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach($data['signalements_done'] as $s): ?>
        <tr style="opacity:.6">
          <td><?= htmlspecialchars($s['rprenom'].' '.$s['rnom']) ?></td>
          <td><?= htmlspecialchars($s['sprenom'].' '.$s['snom']) ?></td>
          <td><?= htmlspecialchars($s['motif']) ?></td>
          <td style="font-size:13px"><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php /* ════════════════ EVALUATIONS ════════════════ */ ?>
    <?php elseif($section === 'evaluations'): ?>
    <div class="dash-grid-2" style="margin-bottom:20px">
      <div class="section-card">
        <div class="section-card-header"><div class="section-card-title">📊 Distribution des notes</div></div>
        <?php
          $totalEval = array_sum(array_column($data['stats_eval']??[], 'nb')) ?: 1;
          for($n=5;$n>=1;$n--):
            $nb = 0;
            foreach($data['stats_eval']??[] as $e) if((int)$e['note']===$n) $nb=$e['nb'];
        ?>
        <div class="mois-bar">
          <span class="mois-bar-label"><?= str_repeat('★',$n) ?></span>
          <div style="flex:1;background:var(--border);border-radius:4px;height:10px">
            <div style="height:10px;background:var(--gold);border-radius:4px;width:<?= round($nb/$totalEval*100) ?>%"></div>
          </div>
          <span class="mois-bar-num"><?= $nb ?></span>
        </div>
        <?php endfor; ?>
      </div>
      <div class="section-card">
        <div class="section-card-header"><div class="section-card-title">📈 Chiffres clés</div></div>
        <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
          <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:var(--bg);border-radius:var(--radius-sm)">
            <span style="font-size:14px;color:var(--slate)">Note moyenne</span>
            <span style="font-family:var(--font-head);font-size:22px;font-weight:800;color:var(--gold)"><?= $data['stats']['note_moy']>0?$data['stats']['note_moy'].' ★':'—' ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:var(--bg);border-radius:var(--radius-sm)">
            <span style="font-size:14px;color:var(--slate)">Total évaluations</span>
            <span style="font-family:var(--font-head);font-size:22px;font-weight:800;color:var(--navy)"><?= array_sum(array_column($data['stats_eval']??[],'nb')) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:var(--bg);border-radius:var(--radius-sm)">
            <span style="font-size:14px;color:var(--slate)">Paiements en attente</span>
            <span style="font-family:var(--font-head);font-size:22px;font-weight:800;color:<?= $data['stats']['paiements_att']>0?'var(--red)':'var(--green)' ?>"><?= $data['stats']['paiements_att'] ?></span>
          </div>
        </div>
      </div>
    </div>
    <div class="section-card">
      <div class="section-card-header"><div class="section-card-title">⭐ Toutes les évaluations</div></div>
      <table>
        <thead><tr><th>Trajet</th><th>Date</th><th>Conducteur</th><th>Passager</th><th>Note</th><th>Commentaire</th></tr></thead>
        <tbody>
        <?php foreach($data['evaluations']??[] as $e): ?>
        <?php $dd = new DateTime($e['date_depart']); ?>
        <tr>
          <td style="font-weight:600"><?= htmlspecialchars($e['ville_depart']) ?> → <?= htmlspecialchars($e['ville_arrivee']) ?></td>
          <td style="font-size:13px"><?= $dd->format('d/m/Y') ?></td>
          <td><?= htmlspecialchars($e['c_prenom'].' '.$e['c_nom']) ?></td>
          <td><?= htmlspecialchars($e['p_prenom'].' '.$e['p_nom']) ?></td>
          <td><span style="color:var(--gold);font-weight:700"><?= str_repeat('★',(int)$e['note']) ?><span style="color:#d1d5db"><?= str_repeat('★',5-(int)$e['note']) ?></span></span></td>
          <td style="font-size:13px;font-style:italic;color:var(--slate)"><?= htmlspecialchars(substr($e['commentaire']??'',0,80)) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($data['evaluations'])): ?><tr class="empty-row"><td colspan="6">Aucune évaluation</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php endif; ?>
  </div><!-- /content -->
</div><!-- /main -->

</body>
</html>
