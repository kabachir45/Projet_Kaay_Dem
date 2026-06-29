<?php

namespace App\Exceptions;

/**
 * Levée lorsqu'un passager tente de réserver :
 * - un trajet qu'il a déjà réservé, ou
 * - un trajet qui chevauche horairement une autre de ses réservations actives.
 */
class ReservationConflitException extends KaayDemException
{
    public function messageUtilisateur(): string
    {
        return "Vous avez déjà une réservation sur ce trajet ou sur un trajet qui se chevauche.";
    }
}
