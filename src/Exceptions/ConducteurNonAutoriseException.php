<?php

namespace App\Exceptions;

/**
 * Levée lorsqu'un utilisateur tente de publier un trajet sans être
 * conducteur validé par l'administrateur.
 */
class ConducteurNonAutoriseException extends KaayDemException
{
    public function messageUtilisateur(): string
    {
        return "Vous devez être conducteur validé par l'administration pour publier un trajet.";
    }
}
