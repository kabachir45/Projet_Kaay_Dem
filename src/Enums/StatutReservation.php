<?php

namespace App\Enums;

/**
 * Enum StatutReservation
 * Représente le cycle de vie d'une réservation.
 * Transitions valides : EN_ATTENTE → CONFIRMEE → TERMINEE
 *                       EN_ATTENTE → ANNULEE
 *                       CONFIRMEE  → ANNULEE
 */
enum StatutReservation: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case CONFIRMEE  = 'CONFIRMEE';
    case TERMINEE   = 'TERMINEE';
    case ANNULEE    = 'ANNULEE';

    /**
     * Retourne le libellé lisible pour l'affichage dans les vues.
     */
    public function label(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::CONFIRMEE  => 'Confirmée',
            self::TERMINEE   => 'Terminée',
            self::ANNULEE    => 'Annulée',
        };
    }

    /**
     * Vérifie si une transition vers le statut cible est autorisée.
     */
    public function peutTransitionnerVers(self $cible): bool
    {
        return match($this) {
            self::EN_ATTENTE => in_array($cible, [self::CONFIRMEE, self::ANNULEE]),
            self::CONFIRMEE  => in_array($cible, [self::TERMINEE, self::ANNULEE]),
            default          => false,
        };
    }
}
