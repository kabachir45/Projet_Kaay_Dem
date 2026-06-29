<?php

namespace App\Exceptions;

/**
 * Levée lorsqu'un formulaire contient des données invalides ou incomplètes
 * (champ obligatoire manquant, date dans le passé, etc.).
 *
 * Le message passé au constructeur est directement présentable à
 * l'utilisateur (il est construit par le contrôleur de façon explicite).
 */
class DonneesInvalidesException extends KaayDemException
{
    public function messageUtilisateur(): string
    {
        return $this->getMessage() !== '' ? $this->getMessage() : "Données invalides.";
    }
}
