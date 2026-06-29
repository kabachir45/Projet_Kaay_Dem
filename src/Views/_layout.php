<?php
/**
 * Layout partagé — inclure en début de chaque vue
 * Usage : include __DIR__ . '/_layout.php';
 * Variables attendues : $pageTitle (string), $activePage (string optionnel)
 */
$pageTitle = $pageTitle ?? 'Kaay Dem !';
$activePage = $activePage ?? '';
$isLoggedIn = isset($_SESSION['utilisateur_id']);
$userNom = $_SESSION['nom'] ?? '';
?>
