<?php
require_once __DIR__ . '/../../vendor/autoload.php';
session_start();
include __DIR__ . '/_urls.php';

if (isset($_SESSION['utilisateur_id'])) {
    header("Location: dashboard.php"); exit;
}

use App\Controllers\AuthController;
$message = "";
$referer = $_GET['redirect'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $controller = new AuthController();
    if ($controller->connecter($_POST["email"], $_POST["mot_de_passe"])) {
        // Admin → espace admin, sinon dashboard ou redirect demandé
        if (!empty($_SESSION['est_admin'])) {
            header("Location: admin.php"); exit;
        }
        $dest = !empty($_POST['redirect']) ? $_POST['redirect'] : 'dashboard.php';
        header("Location: " . $dest); exit;
    } else {
        $message = "Email ou mot de passe incorrect.";
    }
}
$activePage = '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Connexion — Kaay Dem !</title>
<?php include __DIR__ . '/_style.php'; ?>
<style>
.auth-layout{min-height:100vh;display:grid;grid-template-columns:1fr 1fr}
.auth-left{background:linear-gradient(135deg,var(--navy) 0%,var(--navy-mid) 60%,#0f4c2a 100%);display:flex;flex-direction:column;justify-content:center;align-items:center;padding:60px 48px;color:white}
.auth-left-logo{font-family:var(--font-head);font-size:32px;font-weight:800;margin-bottom:40px}
.auth-left-logo em{font-style:normal;color:var(--gold)}
.auth-quote{font-size:22px;font-weight:700;font-family:var(--font-head);line-height:1.4;margin-bottom:16px}
.auth-quote-sub{color:rgba(255,255,255,.65);font-size:15px;line-height:1.7}
.auth-card-preview{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:16px;padding:20px;margin-top:40px;max-width:280px;width:100%}
.auth-right{display:flex;align-items:center;justify-content:center;padding:60px 48px;background:var(--bg)}
.auth-form-wrap{width:100%;max-width:420px}
.auth-form-title{font-family:var(--font-head);font-size:28px;font-weight:800;color:var(--navy);margin-bottom:6px}
.auth-form-sub{color:var(--muted);font-size:15px;margin-bottom:36px}
.divider{display:flex;align-items:center;gap:12px;margin:24px 0;color:var(--muted);font-size:13px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border)}
.form-footer{margin-top:24px;text-align:center;font-size:14px;color:var(--muted)}
.form-footer a{color:var(--green);font-weight:600}
.back-home{display:inline-flex;align-items:center;gap:6px;color:var(--muted);font-size:13px;font-weight:500;margin-bottom:32px;transition:.2s}
.back-home:hover{color:var(--green)}
@media(max-width:768px){
  .auth-layout{grid-template-columns:1fr}
  .auth-left{display:none}
  .auth-right{padding:32px 20px}
}
</style>
</head>
<body style="margin:0">
<div class="auth-layout">
  <!-- Panneau gauche -->
  <div class="auth-left">
    <div class="auth-left-logo">🚗 Kaay<em>Dem</em> !</div>
    <div style="max-width:320px">
      <div class="auth-quote">« Chaque trajet partagé, c'est une économie réelle et une rencontre de plus. »</div>
      <div class="auth-quote-sub">Rejoignez plus de 1 200 étudiants qui covoiturent entre Dakar, Rufisque et Diamniadio chaque semaine.</div>
    </div>
    <div class="auth-card-preview">
      <div style="font-size:12px;font-weight:600;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Prochain trajet</div>
      <div style="font-weight:700;font-size:16px;margin-bottom:8px">Dakar → Diamniadio</div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <div style="background:rgba(255,255,255,.1);padding:4px 10px;border-radius:12px;font-size:13px">🕗 7h30</div>
        <div style="background:rgba(255,255,255,.1);padding:4px 10px;border-radius:12px;font-size:13px">💺 2 places</div>
        <div style="background:rgba(255,255,255,.1);padding:4px 10px;border-radius:12px;font-size:13px">💰 500 FCFA</div>
      </div>
    </div>
  </div>

  <!-- Panneau droit -->
  <div class="auth-right">
    <div class="auth-form-wrap">
      <a href="<?= $baseUrl ?>index.php" class="back-home">← Retour à l'accueil</a>

      <h1 class="auth-form-title">Bon retour ! 👋</h1>
      <p class="auth-form-sub">Connectez-vous à votre compte Kaay Dem !</p>

      <?php if($message): ?>
      <div class="alert alert-error">⚠️ <?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($referer) ?>">
        <div class="form-group">
          <label class="form-label">Adresse e-mail</label>
          <input class="form-control" type="email" name="email" placeholder="prenom.nom@ism.sn" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Mot de passe</label>
          <input class="form-control" type="password" name="mot_de_passe" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-green btn-lg" style="width:100%;justify-content:center;margin-top:8px">
          Se connecter →
        </button>
      </form>

      <div class="form-footer">
        Pas encore de compte ? <a href="register.php">Créer un compte gratuit</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
