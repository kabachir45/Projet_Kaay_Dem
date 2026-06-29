<?php

namespace App\Exceptions;

/**
 * Classe abstraite KaayDemException
 * Racine de toutes les exceptions métier de la plateforme.
 *
 * Permet d'attraper d'un seul `catch (KaayDemException $e)` toutes les
 * erreurs métier (places insuffisantes, conflit de réservation, transition
 * de statut invalide, etc.) tout en les distinguant des erreurs techniques
 * (PDOException, RuntimeException du routeur…).
 */
abstract class KaayDemException extends \RuntimeException
{
    /**
     * Message court et lisible destiné à l'utilisateur final.
     * Chaque exception métier le redéfinit.
     */
    abstract public function messageUtilisateur(): string;
}
