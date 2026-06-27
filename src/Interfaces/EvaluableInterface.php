<?php

namespace App\Interfaces;

/**
 * Interface EvaluableInterface
 * Contrat pour toute entité pouvant recevoir des évaluations et avoir une note moyenne.
 * Implémentée par ProfilConducteur.
 */
interface EvaluableInterface
{
    /**
     * Retourne la note moyenne de l'entité.
     */
    public function getNote(): float;

    /**
     * Retourne la liste des évaluations reçues.
     */
    public function getEvaluations(): array;
}
