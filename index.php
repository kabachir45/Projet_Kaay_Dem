<?php

require_once __DIR__ . '/vendor/autoload.php';

?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kaay_Dem | Plateforme de covoiturage étudiant</title>

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif;
            background:#f5f7fb;
            color:#333;
        }

        header{
            background:linear-gradient(135deg,#0d6efd,#003f88);
            color:white;
            padding:30px;
            box-shadow:0 3px 15px rgba(0,0,0,.2);
        }

        header h1{
            text-align:center;
            font-size:42px;
        }

        header p{
            text-align:center;
            margin-top:10px;
            font-size:18px;
        }

        nav{
            background:white;
            display:flex;
            justify-content:center;
            gap:30px;
            padding:18px;
            box-shadow:0 2px 10px rgba(0,0,0,.08);
        }

        nav a{
            text-decoration:none;
            color:#333;
            font-weight:bold;
            transition:.3s;
        }

        nav a:hover{
            color:#0d6efd;
        }

        .hero{

            width:90%;
            max-width:1100px;

            margin:60px auto;

        }

        .card{

            background:white;

            border-radius:15px;

            padding:60px;

            text-align:center;

            box-shadow:0 10px 30px rgba(0,0,0,.12);

        }

        .card h2{

            color:#003f88;

            font-size:36px;

            margin-bottom:20px;

        }

        .card p{

            font-size:18px;

            line-height:1.8;

            color:#666;

            margin-bottom:40px;

        }

        .buttons{

            display:flex;

            justify-content:center;

            gap:20px;

            flex-wrap:wrap;

        }

        .btn{

            text-decoration:none;

            padding:16px 35px;

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

            background:white;

            color:#0d6efd;

            border:2px solid #0d6efd;

        }

        .btn-secondary:hover{

            background:#0d6efd;

            color:white;

        }

        footer{

            margin-top:70px;

            background:#1d2636;

            color:white;

            text-align:center;

            padding:25px;

        }

        @media(max-width:768px){

            nav{

                flex-wrap:wrap;

            }

            .card{

                padding:30px;

            }

            .buttons{

                flex-direction:column;

            }

            .btn{

                width:100%;

            }

        }

    </style>

</head>

<body>

<header>

    <h1>🚗 Kaay_Dem</h1>

    <p>La plateforme intelligente de covoiturage dédiée aux étudiants</p>

</header>

<nav>

    <a href="index.php">Accueil</a>

    <a href="src/Views/rechercher_trajet.php">
        Rechercher un trajet
    </a>

    <a href="src/Views/publier_trajet.php">
        Publier un trajet
    </a>

    <a href="src/Views/login.php">
        Connexion
    </a>

    <a href="src/Views/register.php">
        Inscription
    </a>

</nav>

<section class="hero">

    <div class="card">

        <h2>Bienvenue sur Kaay_Dem</h2>

        <p>

            Trouvez facilement un covoiturage entre étudiants,
            publiez vos trajets et voyagez en toute sécurité.

        </p>

        <div class="buttons">

            <a class="btn btn-primary"
               href="src/Views/rechercher_trajet.php">

                🔍 Rechercher un trajet

            </a>

            <a class="btn btn-secondary"
               href="src/Views/publier_trajet.php">

                🚘 Publier un trajet

            </a>

        </div>

    </div>

</section>

<footer>

    © 2026 <strong>Kaay_Dem</strong> — Plateforme de covoiturage étudiant.

</footer>

</body>

</html>