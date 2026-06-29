<?php

namespace App\Exceptions;

/**
 * Levée lorsqu'un conducteur tente d'enregistrer plus de 2 véhicules.
 */
class LimiteVehiculesException extends KaayDemException
{
    public function messageUtilisateur(): string
    {
        return "Un conducteur ne peut pas enregistrer plus de 2 véhicules.";
    }
}
