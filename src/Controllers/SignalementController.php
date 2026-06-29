<?php

namespace App\Controllers;

use App\Repositories\SignalementRepository;
use App\Repositories\UtilisateurRepository;
use App\Models\Signalement;
use App\Exceptions\DonneesInvalidesException;

/**
 * SignalementController
 * Gère le dépôt d'un signalement par un utilisateur contre un autre.
 * S'appuie sur le modèle Signalement (qui interdit l'auto-signalement)
 * et sur les repositories.
 */
class SignalementController
{
    private SignalementRepository $signalements;
    private UtilisateurRepository $utilisateurs;

    public function __construct()
    {
        $this->signalements = new SignalementRepository();
        $this->utilisateurs = new UtilisateurRepository();
    }

    /**
     * Liste des utilisateurs signalables (pour le formulaire).
     */
    public function utilisateursSignalables(int $exclureUtilisateurId): array
    {
        return $this->utilisateurs->listerNonAdmins($exclureUtilisateurId);
    }

    /**
     * Enregistre un signalement.
     *
     * @throws DonneesInvalidesException si auto-signalement, motif vide ou cible inexistante
     */
    public function signaler(int $rapporteurId, int $signaleId, string $motif, ?string $description): Signalement
    {
        $motif = trim($motif);

        if ($signaleId === $rapporteurId) {
            throw new DonneesInvalidesException('Vous ne pouvez pas vous signaler vous-même.');
        }
        if ($motif === '') {
            throw new DonneesInvalidesException('Le motif est obligatoire.');
        }
        if (!$this->utilisateurs->existeNonAdmin($signaleId)) {
            throw new DonneesInvalidesException('Utilisateur introuvable.');
        }

        // Le constructeur du modèle est un garde-fou supplémentaire (auto-signalement)
        $signalement = new Signalement(
            $rapporteurId,
            $signaleId,
            $motif,
            ($description !== null && trim($description) !== '') ? trim($description) : null
        );

        $this->signalements->save($signalement);

        return $signalement;
    }
}
