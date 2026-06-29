<?php

namespace App\Exceptions;

/**
 * Levée lorsqu'on tente de réserver une place sur un trajet complet.
 */
class PlacesInsuffisantesException extends KaayDemException
{
    public function messageUtilisateur(): string
    {
        return "Ce trajet est complet : il n'y a plus de places disponibles.";
    }
}
