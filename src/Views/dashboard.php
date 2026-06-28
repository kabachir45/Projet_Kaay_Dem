<?php

session_start();

if (!isset($_SESSION["utilisateur_id"])) {

    header("Location: login.php");

    exit;

}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<title>Dashboard</title>

<style>

body{

font-family:Arial;

background:#f4f6f9;

}

.container{

width:900px;

margin:50px auto;

}

.card{

background:white;

padding:30px;

border-radius:10px;

box-shadow:0 0 15px rgba(0,0,0,.1);

}

.menu{

display:grid;

grid-template-columns:repeat(2,1fr);

gap:20px;

margin-top:30px;

}

.menu a{

padding:20px;

background:#0d6efd;

color:white;

text-decoration:none;

text-align:center;

border-radius:8px;

font-size:18px;

}

.menu a:hover{

background:#0056d6;

}

</style>

</head>

<body>

<div class="container">

<div class="card">

<h1>

Bienvenue

<?= $_SESSION["nom"] ?>

👋

</h1>

<div class="menu">

<a href="publier_trajet.php">

🚘 Publier un trajet

</a>

<a href="rechercher_trajet.php">

🔍 Rechercher un trajet

</a>

<a href="mes_trajets.php">

📋 Mes trajets

</a>

<a href="mes_reservations.php">

📅 Mes réservations

</a>

<a href="profil.php">

👤 Mon profil

</a>

<a href="../../logout.php">

🚪 Déconnexion

</a>

</div>

</div>

</div>

</body>

</html>