<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\AuthController;

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $controller = new AuthController();

    if ($controller->connecter(
        $_POST["email"],
        $_POST["mot_de_passe"]
    )) {

        header("Location: dashboard.php");
        exit;

    } else {

        $message = "Email ou mot de passe incorrect.";

    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<title>Connexion | Kaay_Dem</title>

<style>

body{

font-family:Arial;

background:#f4f6f9;

}

.container{

width:400px;

margin:70px auto;

background:white;

padding:30px;

border-radius:10px;

box-shadow:0 0 15px rgba(0,0,0,.2);

}

h2{

text-align:center;

color:#003f88;

margin-bottom:20px;

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

cursor:pointer;

font-size:16px;

}

button:hover{

background:#0056d6;

}

.error{

color:red;

margin-bottom:15px;

text-align:center;

}

a{

text-decoration:none;

color:#0d6efd;

}

</style>

</head>

<body>

<div class="container">

<h2>Connexion</h2>

<?php if($message!=""): ?>

<p class="error"><?= $message ?></p>

<?php endif; ?>

<form method="POST">

<input
type="email"
name="email"
placeholder="Adresse e-mail"
required>

<input
type="password"
name="mot_de_passe"
placeholder="Mot de passe"
required>

<button>

Se connecter

</button>

</form>

<br>

<center>

<a href="register.php">

Créer un compte

</a>

</center>

</div>

</body>

</html>