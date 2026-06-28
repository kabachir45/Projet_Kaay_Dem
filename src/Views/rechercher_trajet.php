<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Rechercher un trajet | Kaay_Dem</title>

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
    width:500px;
    margin:60px auto;
}

.card{
    background:white;
    padding:35px;
    border-radius:12px;
    box-shadow:0 8px 20px rgba(0,0,0,.15);
}

h2{
    text-align:center;
    color:#0d6efd;
    margin-bottom:30px;
}

input{
    width:100%;
    padding:14px;
    margin-bottom:18px;
    border:1px solid #ccc;
    border-radius:8px;
    font-size:16px;
}

button{
    width:100%;
    padding:14px;
    background:#0d6efd;
    color:white;
    border:none;
    border-radius:8px;
    font-size:17px;
    cursor:pointer;
    transition:.3s;
}

button:hover{
    background:#0056d6;
}

.retour{
    display:block;
    margin-top:20px;
    text-align:center;
    text-decoration:none;
    color:#0d6efd;
    font-weight:bold;
}

</style>

</head>

<body>

<div class="container">

<div class="card">

<h2>🔍 Rechercher un trajet</h2>

<form method="GET">

<input
type="text"
name="depart"
placeholder="Ville de départ"
required>

<input
type="text"
name="arrivee"
placeholder="Ville d'arrivée"
required>

<input
type="date"
name="date"
required>

<button type="submit">

Rechercher

</button>

</form>

<a href="dashboard.php" class="retour">

⬅ Retour au tableau de bord

</a>

</div>

</div>

</body>

</html>