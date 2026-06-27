<?php

namespace App\Models;

use App\Traits\Timestampable;
use App\Enums\StatutConducteur;

/**
 * Classe Administrateur
 * Entité distincte d'Utilisateur — pas d'héritage, pas de profils.
 * Gère la validation des conducteurs et la modération de la plateforme.
 *
 * Règles métier :
 * - Seul l'administrateur peut valider/rejeter un ProfilConducteur
 * - Seul l'administrateur peut bannir (supprimer physiquement) un utilisateur
 */
class Administrateur
{
    use Timestampable;

    private ?int $id = null;
    private string $nom;
    private string $prenom;
    private string $email;
    private string $motDePasse;   // hash — pas de getter public

    public function __construct(
        string $nom,
        string $prenom,
        string $email,
        string $motDePasse
    ) {
        $this->nom        = $nom;
        $this->prenom     = $prenom;
        $this->email      = $email;
        $this->motDePasse = password_hash($motDePasse, PASSWORD_BCRYPT);
        $this->initTimestamps();
    }

    // ── Getters / Setters ─────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getNom(): string    { return $this->nom; }
    public function getPrenom(): string { return $this->prenom; }
    public function getEmail(): string  { return $this->email; }

    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function setMotDePasseHash(string $hash): void
    {
        $this->motDePasse = $hash;
    }

    public function verifierMotDePasse(string $saisie): bool
    {
        return password_verify($saisie, $this->motDePasse);
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
        $profil->setStatut(StatutConducteur::REJETE);
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
