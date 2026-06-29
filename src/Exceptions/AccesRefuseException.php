<?php

namespace App\Exceptions;

/**
 * Levée lorsqu'un utilisateur tente une action réservée à un autre rôle
 * (ex. une action d'administration sans être administrateur).
 */
class AccesRefuseException extends KaayDemException
{
    public function messageUtilisateur(): string
    {
        return $this->getMessage() !== '' ? $this->getMessage() : "Accès refusé : action non autorisée.";
    }
}
