<?php
/**
 * Script utilitaire — génère les hash pour les données de démo.
 * Exécuter UNE SEULE FOIS : php generate_passwords.php
 * Puis coller les hash dans Kaay_Dem.sql
 */
$passwords = [
    'admin'     => 'admin1234',
    'conducteur'=> 'test1234',
    'passager'  => 'test1234',
];

foreach ($passwords as $label => $pwd) {
    $hash = password_hash($pwd, PASSWORD_BCRYPT);
    echo "$label ($pwd) : $hash\n";
}
