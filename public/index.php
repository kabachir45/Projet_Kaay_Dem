<?php

/**
 * NOTE D'ARCHITECTURE — point d'entrée et routage
 * ------------------------------------------------
 * Le module demande un « routeur maison ». La brique correspondante existe :
 * App\Core\Router (src/Core/Router.php) implémente ce concept — table de routes
 * (méthode HTTP + URL → "Controller@action"), extraction d'un paramètre
 * numérique, et dispatch vers le contrôleur.
 *
 * Choix retenu pour la version rendue : l'application est servie « par pages ».
 * Chaque vue de src/Views/ est un point d'entrée léger qui délègue toute la
 * logique métier à un Contrôleur (App\Controllers\*), lequel s'appuie sur les
 * Modèles et Repositories. La séparation MVC Vue → Contrôleur → Modèle est donc
 * respectée ; seul le routage centralisé via ce front-contrôleur n'est pas
 * activé. Voir la section « Choix d'architecture — routage » du README pour la
 * justification détaillée.
 *
 * Ce fichier redirige donc vers la page d'accueil réelle de l'application.
 */

header('Location: ../index.php');
exit;
