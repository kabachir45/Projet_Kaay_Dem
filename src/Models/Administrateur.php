<?php

namespace App\Models;

use App\Enums\StatutConducteur;

/**
 * Classe Administrateur
 * Personne disposant des droits de modération de la plateforme.
 *
 * Hérite de Personne (identité + gestion sécurisée du mot de passe), mais
 * ne possède pas de profils conducteur/passager : c'est ce qui le distingue
 * d'un Utilisateur ordinaire.
 *
 * Règles métier :
 * - Seul l'administrateur peut valider/rejeter un ProfilConducteur
 * - Seul l'administrateur peut bannir (supprimer physiquement) un utilisateur
 */
class Administrateur extends Personne
{
    // ── Polymorphisme (cf. Personne) ──────────────────────────────────────────

    public function getRole(): string
    {
        return 'Administrateur';
    }

    public function peutAdministrer(): bool
    {
        return true;
    }

    // ── Actions métier ────────────────────────────────────────────────────────

    /**
     * Valide le profil conducteur d'un utilisateur.
     */
    public function validerConducteur(ProfilConducteur $profil): void
    {
        $profil->setStatut(StatutConducteur::VALIDE);
    }

    /**
     * Rejette le profil conducteur d'un utilisateur.
     */
    public function rejeterConducteur(ProfilConducteur $profil): void
    {
        $profil->setStatut(StatutConducteur::REFUSE);
    }

    /**
     * La suppression physique de l'utilisateur est déléguée au Repository
     * (suppression en BDD avec CASCADE). Cette méthode est un point d'entrée
     * sémantique — l'action réelle passe par UtilisateurRepository::delete().
     */
    public function bannirUtilisateur(Utilisateur $utilisateur): int
    {
        if ($utilisateur->getId() === null) {
            throw new \InvalidArgumentException("Impossible de bannir un utilisateur sans ID.");
        }
        return $utilisateur->getId();
    }
}
