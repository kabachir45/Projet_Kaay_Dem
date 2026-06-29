<?php

namespace App\Exceptions;

/**
 * Levée lorsqu'une transition du cycle de vie d'une réservation est interdite
 * (ex. passer de TERMINEE à CONFIRMEE).
 */
class TransitionInvalideException extends KaayDemException
{
    public function messageUtilisateur(): string
    {
        return "Cette action n'est pas autorisée dans l'état actuel de la réservation.";
    }
}
