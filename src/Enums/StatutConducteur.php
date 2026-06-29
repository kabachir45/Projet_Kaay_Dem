<?php

namespace App\Enums;

/**
 * Enum StatutConducteur
 * Représente l'état de validation d'un profil conducteur par l'administrateur.
 * Un conducteur doit être VALIDE pour pouvoir publier des trajets.
 */
enum StatutConducteur: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case VALIDE     = 'VALIDE';
    case REFUSE     = 'REFUSE';

    /**
     * Retourne le libellé pour l'affichage dans les vues.
     */
    public function label(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente de validation',
            self::VALIDE     => 'Validé',
            self::REFUSE     => 'Refusé',
        };
    }

    /**
     * Indique si le conducteur est autorisé à publier des trajets.
     */
    public function estAutorise(): bool
    {
        return $this === self::VALIDE;
    }
}
