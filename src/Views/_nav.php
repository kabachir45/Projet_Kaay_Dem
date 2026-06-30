<?php
// Navigation partagée
$isLoggedIn = isset($_SESSION['utilisateur_id']);
$userNom = $_SESSION['nom'] ?? '';
$initiale = $userNom ? mb_strtoupper(mb_substr($userNom, 0, 1)) : '';
$activePage = $activePage ?? '';

// Base URL absolue — fonctionne quel que soit le fichier appelant
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$script   = $_SERVER['SCRIPT_NAME'];          // ex: /IAGEB/Projet_kay_dem/Projet_Kaay_Dem/index.php
// Remonte jusqu'au dossier racine du projet (celui qui contient index.php ET src/)
$parts    = explode('/', trim($script, '/'));
// On cherche "Projet_Kaay_Dem" ou le dossier racine commun
// Plus simple : on remonte depuis DOCUMENT_ROOT
$docRoot  = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$filePath = str_replace('\\', '/', __FILE__); // chemin absolu de _nav.php
// Racine du projet = deux dossiers au-dessus de src/Views/_nav.php
$projectRoot = dirname(dirname(dirname($filePath))); // .../Projet_Kaay_Dem
$base = $scheme . '://' . $host . str_replace($docRoot, '', $projectRoot) . '/';

$viewsUrl = $base . 'src/Views/';
?>
<nav class="navbar">
  <div class="nav-inner">
    <a href="<?= $base ?>index.php" class="nav-logo">
      <div class="logo-icon">🚗</div>
      Kaay<span class="accent">Dem</span>
    </a>

    <div class="nav-links">
      <a href="<?= $base ?>index.php" class="<?= $activePage==='accueil'?'active':'' ?>">Accueil</a>
      <?php if($isLoggedIn): ?>
      <a href="<?= $viewsUrl ?>rechercher_trajet.php" class="<?= $activePage==='recherche'?'active':'' ?>">Rechercher</a>
      <a href="<?= $viewsUrl ?>publier_trajet.php" class="<?= $activePage==='publier'?'active':'' ?>">Publier un trajet</a>
      <a href="<?= $viewsUrl ?>dashboard.php" class="<?= $activePage==='dashboard'?'active':'' ?>">Mon espace</a>
      <?php endif; ?>
    </div>

    <div class="nav-auth">
      <?php if($isLoggedIn): ?>
        <div class="nav-user">
          <div class="nav-user-avatar"><?= htmlspecialchars($initiale) ?></div>
          <span class="nav-user-name"><?= htmlspecialchars($userNom) ?></span>
          <a href="<?= $base ?>logout.php" class="btn-logout">Déconnexion</a>
        </div>
      <?php else: ?>
        <a href="<?= $viewsUrl ?>login.php" class="btn-ghost">Connexion</a>
        <a href="<?= $viewsUrl ?>register.php" class="btn-primary">Créer un compte</a>
      <?php endif; ?>
    </div>
    <button class="dark-toggle" onclick="toggleTheme()" id="themeBtn" style="margin-left:8px">🌙 Nuit</button>
  </div>
</nav>
<script>
function toggleTheme(){
  const html = document.documentElement;
  const curr = html.getAttribute("data-theme") || "light";
  const next = curr === "light" ? "dark" : "light";
  html.setAttribute("data-theme", next);
  localStorage.setItem("kaaydem_theme", next);
  document.getElementById("themeBtn").textContent = next === "dark" ? "☀️ Jour" : "🌙 Nuit";
}
// Mettre à jour le label au chargement
document.addEventListener("DOMContentLoaded", function(){
  const t = localStorage.getItem("kaaydem_theme") || "dark";
  const btn = document.getElementById("themeBtn");
  if(btn) btn.textContent = t === "dark" ? "☀️ Jour" : "🌙 Nuit";
});
</script>
<?php /* admin link ajouté dynamiquement dans le JS si besoin */ ?>
