<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();
$isLoggedIn = isset($_SESSION['utilisateur_id']);
$userNom = $_SESSION['nom'] ?? '';
$activePage = 'accueil';
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$docRoot  = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$projPath = str_replace('\\', '/', __DIR__);
$base     = $scheme . '://' . $host . str_replace($docRoot, '', $projPath) . '/';
$viewsUrl = $base . 'src/Views/';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Kaay Dem ! — Covoiturage étudiant</title>
<?php include __DIR__ . '/src/Views/_style.php'; ?>
<style>
.hero{
  background:linear-gradient(135deg,var(--navy) 0%,var(--navy-mid) 60%,#0f4c2a 100%);
  color:white;position:relative;overflow:hidden;
  padding:90px 24px 80px;
}
.hero::before{
  content:'';position:absolute;inset:0;
  background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.hero-inner{max-width:1100px;margin:0 auto;position:relative;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600;margin-bottom:24px;backdrop-filter:blur(4px)}
.hero-badge span{width:8px;height:8px;background:#4ade80;border-radius:50%;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.hero h1{font-family:var(--font-head);font-size:clamp(36px,5vw,56px);font-weight:800;line-height:1.1;margin-bottom:20px}
.hero h1 em{font-style:normal;color:var(--gold)}
.hero p{font-size:18px;color:rgba(255,255,255,.75);line-height:1.7;margin-bottom:36px;max-width:480px}
.hero-cta{display:flex;gap:14px;flex-wrap:wrap}
.hero-img-area{display:flex;justify-content:center;align-items:center}
.hero-card-demo{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:28px;backdrop-filter:blur(8px);width:100%;max-width:340px}
.hcd-title{font-size:13px;font-weight:600;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.5px;margin-bottom:16px}
.hcd-route{display:flex;align-items:center;gap:12px;margin-bottom:20px}
.hcd-city{font-weight:700;font-size:16px}
.hcd-arrow{color:var(--gold);font-size:20px}
.hcd-meta{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap}
.hcd-chip{background:rgba(255,255,255,.1);padding:5px 12px;border-radius:20px;font-size:13px}
.hcd-driver{display:flex;align-items:center;gap:10px}
.hcd-avatar{width:36px;height:36px;background:var(--green);border-radius:50%;display:grid;place-items:center;font-weight:700;font-size:14px}
.hcd-rating{color:var(--gold);font-size:14px;font-weight:600}

.stats-strip{background:var(--white);border-bottom:1px solid var(--border)}
.stats-inner{max-width:1100px;margin:0 auto;padding:32px 24px;display:grid;grid-template-columns:repeat(4,1fr);gap:0}
.stat-item{text-align:center;padding:0 20px}
.stat-item:not(:last-child){border-right:1px solid var(--border)}
.stat-num{font-family:var(--font-head);font-size:36px;font-weight:800;color:var(--green)}
.stat-label{font-size:14px;color:var(--muted);margin-top:4px;font-weight:500}

.section{padding:72px 24px}
.section-inner{max-width:1100px;margin:0 auto}
.section-tag{display:inline-block;background:var(--green-light);color:var(--green-dark);font-size:12px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;padding:5px 12px;border-radius:20px;margin-bottom:16px}
.section-title{font-family:var(--font-head);font-size:clamp(28px,4vw,40px);font-weight:800;color:var(--navy);margin-bottom:16px;line-height:1.2}
.section-sub{font-size:17px;color:var(--muted);max-width:540px;line-height:1.7}

.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:48px}
.step{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:32px 28px;position:relative;transition:.3s}
.step:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg);border-color:var(--green)}
.step-num{width:44px;height:44px;background:var(--green);color:white;border-radius:12px;display:grid;place-items:center;font-family:var(--font-head);font-weight:800;font-size:18px;margin-bottom:20px}
.step h3{font-family:var(--font-head);font-size:19px;font-weight:700;margin-bottom:10px;color:var(--navy)}
.step p{color:var(--muted);font-size:15px;line-height:1.6}
.step-icon{font-size:28px;margin-bottom:14px}

.cities{background:var(--navy);padding:56px 24px}
.cities-inner{max-width:1100px;margin:0 auto;text-align:center}
.cities-title{font-family:var(--font-head);font-size:28px;font-weight:800;color:white;margin-bottom:8px}
.cities-sub{color:rgba(255,255,255,.6);margin-bottom:36px}
.cities-grid{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
.city-pill{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);padding:10px 22px;border-radius:50px;color:white;font-weight:600;font-size:15px;transition:.2s}
.city-pill:hover{background:var(--green);border-color:var(--green)}

.cta-banner{background:linear-gradient(135deg,var(--green) 0%,var(--green-dark) 100%);padding:72px 24px;text-align:center}
.cta-banner h2{font-family:var(--font-head);font-size:clamp(28px,4vw,40px);font-weight:800;color:white;margin-bottom:16px}
.cta-banner p{color:rgba(255,255,255,.8);font-size:18px;margin-bottom:36px}
.cta-banner .cta-btns{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}

.testimonials{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:48px}
.testi{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:28px}
.testi-stars{color:var(--gold);font-size:18px;margin-bottom:14px}
.testi-text{color:var(--slate);font-size:15px;line-height:1.7;margin-bottom:20px;font-style:italic}
.testi-author{display:flex;align-items:center;gap:12px}
.testi-avatar{width:40px;height:40px;border-radius:50%;background:var(--green);display:grid;place-items:center;font-weight:700;color:white;font-size:14px}
.testi-name{font-weight:700;font-size:14px;color:var(--navy)}
.testi-role{font-size:13px;color:var(--muted)}

@media(max-width:900px){
  .hero-inner{grid-template-columns:1fr}
  .hero-img-area{display:none}
  .steps{grid-template-columns:1fr}
  .stats-inner{grid-template-columns:repeat(2,1fr)}
  .stat-item:nth-child(2){border-right:none}
  .testimonials{grid-template-columns:1fr}
}
</style>
</head>
<body>

<?php
$activePage = 'accueil';
include __DIR__ . '/src/Views/_nav.php';
?>

<section class="hero">
  <div class="hero-inner">
    <div class="hero-content">
      <div class="hero-badge">
        <span></span> Plateforme étudiante active
      </div>
      <h1>Voyagez ensemble,<br>dépensez <em>moins</em></h1>
      <p>Kaay Dem ! connecte les étudiants de Dakar, Rufisque, Diamniadio et des campus environnants pour des trajets quotidiens abordables et sûrs.</p>
      <div class="hero-cta">
        <?php if($isLoggedIn): ?>
          <a href="<?= $viewsUrl ?>rechercher_trajet.php" class="btn btn-lg btn-green">🔍 Trouver un trajet</a>
          <a href="<?= $viewsUrl ?>dashboard.php" class="btn btn-lg btn-outline" style="background:rgba(255,255,255,.1);color:white;border-color:rgba(255,255,255,.3)">Mon espace</a>
        <?php else: ?>
          <a href="<?= $viewsUrl ?>login.php" class="btn btn-lg btn-green">Commencer — c'est gratuit</a>
          <a href="<?= $viewsUrl ?>rechercher_trajet.php" class="btn btn-lg btn-outline" style="background:rgba(255,255,255,.1);color:white;border-color:rgba(255,255,255,.3)">Voir les trajets</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="hero-img-area">
      <div class="hero-card-demo">
        <div class="hcd-title">Trajet disponible</div>
        <div class="hcd-route">
          <div class="hcd-city">Dakar Plateau</div>
          <div class="hcd-arrow">→</div>
          <div class="hcd-city">Diamniadio</div>
        </div>
        <div class="hcd-meta">
          <div class="hcd-chip">📅 Lundi 8h00</div>
          <div class="hcd-chip">💺 3 places</div>
          <div class="hcd-chip">💰 500 FCFA</div>
        </div>
        <div class="hcd-driver">
          <div class="hcd-avatar">M</div>
          <div>
            <div style="font-weight:700;font-size:14px">Mamadou SY</div>
            <div class="hcd-rating">★★★★★ 4.9</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="stats-strip">
  <div class="stats-inner">
    <div class="stat-item">
      <div class="stat-num">1 200+</div>
      <div class="stat-label">Étudiants inscrits</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">850+</div>
      <div class="stat-label">Trajets effectués</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">4.8 ★</div>
      <div class="stat-label">Note moyenne conducteurs</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">6 villes</div>
      <div class="stat-label">Desservies autour de Dakar</div>
    </div>
  </div>
</div>

<section class="section" style="background:var(--bg)">
  <div class="section-inner">
    <div class="section-tag">Simple et rapide</div>
    <h2 class="section-title">Comment ça marche ?</h2>
    <p class="section-sub">En trois étapes, trouvez ou proposez un covoiturage entre étudiants.</p>
    <div class="steps">
      <div class="step">
        <div class="step-num">1</div>
        <div class="step-icon">📝</div>
        <h3>Créez votre compte</h3>
        <p>Inscrivez-vous en 2 minutes avec votre email étudiant. Choisissez votre rôle : passager, conducteur ou les deux.</p>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <div class="step-icon">🔍</div>
        <h3>Recherchez ou publiez</h3>
        <p>Cherchez un trajet selon votre ville de départ, destination et date. Ou publiez votre propre trajet si vous conduisez.</p>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <div class="step-icon">🚗</div>
        <h3>Voyagez & évaluez</h3>
        <p>Réservez votre place, retrouvez-vous au point de départ et évaluez votre conducteur après le voyage.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" style="background:var(--white)">
  <div class="section-inner" style="max-width:760px;text-align:center">
    <div class="section-tag">Pour les étudiants</div>
    <h2 class="section-title" style="font-size:28px">Conçu pour votre quotidien étudiant</h2>
    <p style="color:var(--muted);line-height:1.8;margin:0 auto 28px;max-width:600px">Les trajets Dakar–Diamniadio coûtent cher. Avec Kaay Dem !, partagez les frais, rencontrez des camarades et arrivez à l'heure à l'ISM Campus Baobab.</p>
    <ul style="list-style:none;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;text-align:left;max-width:640px;margin:0 auto">
      <li style="display:flex;align-items:center;gap:10px;color:var(--slate)"><span style="color:var(--green);font-size:18px">✓</span> Tarifs négociés entre étudiants</li>
      <li style="display:flex;align-items:center;gap:10px;color:var(--slate)"><span style="color:var(--green);font-size:18px">✓</span> Conducteurs validés par l'administration</li>
      <li style="display:flex;align-items:center;gap:10px;color:var(--slate)"><span style="color:var(--green);font-size:18px">✓</span> Évaluations et avis après chaque trajet</li>
      <li style="display:flex;align-items:center;gap:10px;color:var(--slate)"><span style="color:var(--green);font-size:18px">✓</span> Zéro commission — prix entre étudiants</li>
    </ul>
  </div>
</section>

<div class="cities">
  <div class="cities-inner">
    <h2 class="cities-title">🗺️ Trajets disponibles autour de Dakar</h2>
    <p class="cities-sub">Retrouvez des covoiturages entre toutes ces localités</p>
    <div class="cities-grid">
      <div class="city-pill">📍 Dakar Plateau</div>
      <div class="city-pill">📍 Rufisque</div>
      <div class="city-pill">📍 Diamniadio</div>
      <div class="city-pill">📍 Pikine</div>
      <div class="city-pill">📍 Guédiawaye</div>
      <div class="city-pill">📍 Thiès</div>
    </div>
  </div>
</div>

<section class="section" style="background:var(--bg)">
  <div class="section-inner" style="max-width:880px;text-align:center">
    <div class="section-tag">Conducteurs vérifiés</div>
    <h2 class="section-title" style="font-size:28px">Roulez en toute confiance</h2>
    <p style="color:var(--muted);line-height:1.8;margin:0 auto 28px;max-width:600px">Chaque conducteur est validé par notre équipe avant de pouvoir publier un trajet : permis de conduire, véhicule en règle et identité vérifiée.</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px">
      <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--radius-sm);padding:22px 18px;text-align:center">
        <div style="font-size:28px;margin-bottom:8px">🛡️</div>
        <div style="font-weight:700;font-size:14px;color:var(--navy)">Conducteurs validés</div>
        <div style="font-size:13px;color:var(--muted)">Permis &amp; documents vérifiés</div>
      </div>
      <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--radius-sm);padding:22px 18px;text-align:center">
        <div style="font-size:28px;margin-bottom:8px">⭐</div>
        <div style="font-weight:700;font-size:14px;color:var(--navy)">Système d'évaluation</div>
        <div style="font-size:13px;color:var(--muted)">Notes après chaque trajet</div>
      </div>
      <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--radius-sm);padding:22px 18px;text-align:center">
        <div style="font-size:28px;margin-bottom:8px">🗺️</div>
        <div style="font-weight:700;font-size:14px;color:var(--navy)">Trajets sur carte</div>
        <div style="font-size:13px;color:var(--muted)">Itinéraires visualisés en direct</div>
      </div>
    </div>
  </div>
</section>

<section class="section" style="background:var(--white)">
  <div class="section-inner">
    <div class="section-tag">Avis d'étudiants</div>
    <h2 class="section-title">Ils utilisent Kaay Dem !</h2>
    <div class="testimonials">
      <div class="testi">
        <div class="testi-stars">★★★★★</div>
        <p class="testi-text">« Je fais le trajet Rufisque–Diamniadio tous les jours. Depuis que j'utilise Kaay Dem !, j'économise plus de 15 000 FCFA par mois ! »</p>
        <div class="testi-author">
          <div class="testi-avatar">F</div>
          <div>
            <div class="testi-name">Fatoumata O.</div>
            <div class="testi-role">Étudiante L3 Informatique</div>
          </div>
        </div>
      </div>
      <div class="testi">
        <div class="testi-stars">★★★★★</div>
        <p class="testi-text">« En tant que conducteur, je couvre mes frais d'essence et je fais de bonnes rencontres. L'application est simple et bien faite. »</p>
        <div class="testi-author">
          <div class="testi-avatar">M</div>
          <div>
            <div class="testi-name">Mamadou S.</div>
            <div class="testi-role">Étudiant M2 Commerce</div>
          </div>
        </div>
      </div>
      <div class="testi">
        <div class="testi-stars">★★★★☆</div>
        <p class="testi-text">« Le système de réservation est pratique et les conducteurs sont ponctués. Je recommande à tous les étudiants du campus. »</p>
        <div class="testi-author">
          <div class="testi-avatar">A</div>
          <div>
            <div class="testi-name">Aminata D.</div>
            <div class="testi-role">Étudiante L2 Marketing</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="cta-banner">
  <h2>Prêt à rejoindre la communauté ?</h2>
  <p>Créez votre compte gratuitement et trouvez votre prochain trajet en quelques secondes.</p>
  <div class="cta-btns">
    <?php if($isLoggedIn): ?>
      <a href="<?= $viewsUrl ?>rechercher_trajet.php" class="btn btn-lg btn-navy">🔍 Rechercher un trajet</a>
      <a href="<?= $viewsUrl ?>publier_trajet.php" class="btn btn-lg btn-outline">🚘 Publier un trajet</a>
    <?php else: ?>
      <a href="<?= $viewsUrl ?>register.php" class="btn btn-lg btn-navy">Créer un compte gratuit</a>
      <a href="<?= $viewsUrl ?>login.php" class="btn btn-lg btn-outline">Se connecter</a>
    <?php endif; ?>
  </div>
</div>

<footer>
  <div class="footer-stripe"></div>
  <strong>Kaay Dem !</strong> — Plateforme de covoiturage étudiant<br>
  ISM Campus Baobab · Dakar, Sénégal · 2025-2026
</footer>

</body>
</html>
