<?php
session_start();

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Mon Profil | Kaay_Dem</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial, Helvetica, sans-serif;
    background:#f4f6f9;
}

.container{
    width:600px;
    margin:60px auto;
}

.card{
    background:white;
    padding:40px;
    border-radius:15px;
    box-shadow:0 10px 25px rgba(0,0,0,.15);
}

h2{
    text-align:center;
    color:#0d6efd;
    margin-bottom:30px;
}

.info{
    margin-bottom:20px;
    padding:15px;
    border:1px solid #ddd;
    border-radius:8px;
    background:#fafafa;
}

.info strong{
    color:#003f88;
}

.actions{
    margin-top:30px;
    display:flex;
    gap:15px;
}

.btn{
    flex:1;
    text-decoration:none;
    text-align:center;
    padding:14px;
    border-radius:8px;
    font-weight:bold;
    transition:.3s;
}

.btn-primary{
    background:#0d6efd;
    color:white;
}

.btn-primary:hover{
    background:#0056d6;
}

.btn-secondary{
    background:#6c757d;
    color:white;
}

.btn-secondary:hover{
    background:#495057;
}

</style>

</head>

<body>

<div class="container">

<div class="card">

<h2>👤 Mon Profil</h2>

<div class="info">
<strong>Nom :</strong>
<?= $_SESSION['nom'] ?? 'Non renseigné'; ?>
</div>

<div class="info">
<strong>Prénom :</strong>
À récupérer depuis la base de données
</div>

<div class="info">
<strong>Email :</strong>
À récupérer depuis la base de données
</div>

<div class="info">
<strong>Téléphone :</strong>
À récupérer depuis la base de données
</div>

<div class="actions">

<a href="dashboard.php" class="btn btn-secondary">
⬅ Retour
</a>

<a href="#" class="btn btn-primary">
✏ Modifier mon profil
</a>

</div>

</div>

</div>

</body>

</html>