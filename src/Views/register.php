<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\AuthController;

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($_POST["mot_de_passe"] !== $_POST["confirmation"]) {

        $message = "Les mots de passe ne correspondent pas.";

    } else {

        $controller = new AuthController();

        if ($controller->inscrire($_POST)) {

            header("Location: login.php?success=1");
            exit;

        } else {

            $message = "Cette adresse e-mail existe déjà.";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<title>Inscription</title>

<style>

body{

font-family:Arial,Helvetica,sans-serif;

background:#f4f6f9;

}

.container{

width:420px;

margin:50px auto;

background:white;

padding:30px;

border-radius:10px;

box-shadow:0 0 15px rgba(0,0,0,.15);

}

h2{

text-align:center;

margin-bottom:25px;

color:#003f88;

}

input{

width:100%;

padding:12px;

margin-bottom:15px;

border:1px solid #ccc;

border-radius:6px;

}

button{

width:100%;

padding:12px;

background:#0d6efd;

color:white;

border:none;

border-radius:6px;

font-size:16px;

cursor:pointer;

}

button:hover{

background:#0056d6;

}

.error{

color:red;

margin-bottom:15px;

}

a{

text-decoration:none;

color:#0d6efd;

}

</style>

</head>

<body>

<div class="container">

<h2>Créer un compte</h2>

<?php if($message!=""): ?>

<p class="error"><?= htmlspecialchars($message) ?></p>

<?php endif; ?>

<form method="POST">

<input
type="text"
name="nom"
placeholder="Nom"
required>

<input
type="text"
name="prenom"
placeholder="Prénom"
required>

<input
type="email"
name="email"
placeholder="Adresse e-mail"
required>

<input
type="text"
name="telephone"
placeholder="Téléphone"
required>

<input
type="password"
name="mot_de_passe"
placeholder="Mot de passe"
required>

<input
type="password"
name="confirmation"
placeholder="Confirmer le mot de passe"
required>

<button>

Créer mon compte

</button>

</form>

<br>

<center>

<a href="login.php">

Déjà inscrit ? Se connecter

</a>

</center>

</div>

</body>

</html>