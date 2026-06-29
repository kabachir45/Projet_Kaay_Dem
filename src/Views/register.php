<?php
require_once __DIR__ . '/../../vendor/autoload.php';
session_start();
include __DIR__ . '/_urls.php';

if (isset($_SESSION['utilisateur_id'])) {
    header("Location: dashboard.php"); exit;
}

use App\Controllers\AuthController;
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($_POST["mot_de_passe"] !== $_POST["confirmation"]) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {
        $controller = new AuthController();
        if ($controller->inscrire($_POST)) {
            header("Location: login.php?success=1"); exit;
        } else {
            $message = "Cette adresse e-mail est déjà utilisée.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Inscription — Kaay Dem !</title>
<?php include __DIR__ . '/_style.php'; ?>
<style>
.auth-layout{min-height:100vh;display:grid;grid-template-columns:1fr 1fr}
.auth-left{background:linear-gradient(135deg,var(--navy) 0%,var(--navy-mid) 60%,#0f4c2a 100%);display:flex;flex-direction:column;justify-content:center;align-items:center;padding:60px 48px;color:white}
.auth-left-logo{font-family:var(--font-head);font-size:32px;font-weight:800;margin-bottom:40px}
.auth-left-logo em{font-style:normal;color:var(--gold)}
.benefit{display:flex;align-items:flex-start;gap:14px;margin-bottom:24px}
.benefit-icon{width:42px;height:42px;background:rgba(255,255,255,.1);border-radius:10px;display:grid;place-items:center;font-size:20px;flex-shrink:0}
.benefit-title{font-weight:700;font-size:15px;margin-bottom:2px}
.benefit-desc{font-size:13px;color:rgba(255,255,255,.6)}
.auth-right{display:flex;align-items:center;justify-content:center;padding:48px;background:var(--bg)}
.auth-form-wrap{width:100%;max-width:440px}
.auth-form-title{font-family:var(--font-head);font-size:28px;font-weight:800;color:var(--navy);margin-bottom:6px}
.auth-form-sub{color:var(--muted);font-size:15px;margin-bottom:32px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.back-home{display:inline-flex;align-items:center;gap:6px;color:var(--muted);font-size:13px;font-weight:500;margin-bottom:28px;transition:.2s}
.back-home:hover{color:var(--green)}
.form-footer{margin-top:20px;text-align:center;font-size:14px;color:var(--muted)}
.form-footer a{color:var(--green);font-weight:600}
@media(max-width:768px){
  .auth-layout{grid-template-columns:1fr}
  .auth-left{display:none}
  .auth-right{padding:32px 20px}
  .form-row{grid-template-columns:1fr}
}
</style>
</head>
<body style="margin:0">
<div class="auth-layout">
  <div class="auth-left">
    <div class="auth-left-logo">🚗 Kaay<em>Dem</em> !</div>
    <div style="max-width:320px">
      <div style="font-family:var(--font-head);font-size:22px;font-weight:800;margin-bottom:32px">Pourquoi rejoindre Kaay Dem !  ?</div>
      <div class="benefit">
        <div class="benefit-icon">💰</div>
        <div>
          <div class="benefit-title">Économisez jusqu'à 15 000 FCFA/mois</div>
          <div class="benefit-desc">En partageant les frais de transport avec d'autres étudiants</div>
        </div>
      </div>
      <div class="benefit">
        <div class="benefit-icon">🛡️</div>
        <div>
          <div class="benefit-title">Conducteurs vérifiés</div>
          <div class="benefit-desc">Chaque conducteur est validé par notre équipe avant de publier</div>
        </div>
      </div>
      <div class="benefit">
        <div class="benefit-icon">🤝</div>
        <div>
          <div class="benefit-title">Communauté étudiante</div>
          <div class="benefit-desc">Voyagez avec vos camarades d'ISM Campus Baobab et d'autres campus</div>
        </div>
      </div>
    </div>
  </div>

  <div class="auth-right">
    <div class="auth-form-wrap">
      <a href="<?= $baseUrl ?>index.php" class="back-home">← Retour à l'accueil</a>

      <h1 class="auth-form-title">Créer un compte</h1>
      <p class="auth-form-sub">Gratuit et sans engagement</p>

      <?php if($message): ?>
      <div class="alert alert-error">⚠️ <?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Prénom</label>
            <input class="form-control" type="text" name="prenom" placeholder="Fatoumata" required>
          </div>
          <div class="form-group">
            <label class="form-label">Nom</label>
            <input class="form-control" type="text" name="nom" placeholder="Ouedraogo" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Adresse e-mail</label>
          <input class="form-control" type="email" name="email" placeholder="prenom.nom@ism.sn" required>
        </div>
        <div class="form-group">
          <label class="form-label">Téléphone</label>
          <input class="form-control" type="tel" name="telephone" placeholder="+221 77 000 00 00" required>
        </div>
        <div class="form-group">
          <label class="form-label">Mot de passe</label>
          <input class="form-control" type="password" name="mot_de_passe" placeholder="8 caractères minimum" required minlength="8">
        </div>
        <div class="form-group">
          <label class="form-label">Confirmer le mot de passe</label>
          <input class="form-control" type="password" name="confirmation" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-green btn-lg" style="width:100%;justify-content:center;margin-top:4px">
          Créer mon compte →
        </button>
      </form>

      <div class="form-footer">
        Déjà inscrit ? <a href="login.php">Se connecter</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
