<?php

namespace App\Exceptions;

/**
 * Levée lorsqu'une note d'évaluation est en dehors de l'intervalle [1, 5].
 */
class NoteInvalideException extends KaayDemException
{
    public function messageUtilisateur(): string
    {
        return "La note doit être comprise entre 1 et 5 étoiles.";
    }
}
