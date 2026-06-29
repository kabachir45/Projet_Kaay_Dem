<?php

namespace App\Models;

/**
 * Classe Utilisateur
 * Personne pouvant cumuler un ProfilConducteur et/ou un ProfilPassager
 * (architecture par composition pour le double rôle).
 *
 * Hérite de Personne (identité + gestion sécurisée du mot de passe).
 *
 * Règles métier :
 * - Le mot de passe n'est jamais exposé via getter (cf. Personne)
 * - Les profils sont optionnels (0..1) et ajoutables post-inscription
 * - La session mono-rôle est gérée au niveau Controller, pas ici
 */
class Utilisateur extends Personne
{
    private string $telephone;
    private ?string $photo = null;

    private ?ProfilConducteur $profilConducteur = null;
    private ?ProfilPassager   $profilPassager   = null;

    public function __construct(
        string $nom,
        string $prenom,
        string $email,
        string $motDePasse,
        string $telephone
    ) {
        parent::__construct($nom, $prenom, $email, $motDePasse);
        $this->telephone = $telephone;
    }

    // ── Coordonnées ───────────────────────────────────────────────────────────

    public function getTelephone(): string { return $this->telephone; }
    public function getPhoto(): ?string    { return $this->photo; }

    public function setTelephone(string $tel): void { $this->telephone = $tel; $this->touch(); }
    public function setPhoto(?string $photo): void  { $this->photo = $photo; }

    // ── Polymorphisme (cf. Personne) ──────────────────────────────────────────

    public function getRole(): string
    {
        return 'Utilisateur';
    }

    public function peutAdministrer(): bool
    {
        return false;
    }

    // ── Profils / double rôle ─────────────────────────────────────────────────

    public function getProfilConducteur(): ?ProfilConducteur
    {
        return $this->profilConducteur;
    }

    public function getProfilPassager(): ?ProfilPassager
    {
        return $this->profilPassager;
    }

    public function ajouterProfilConducteur(ProfilConducteur $profil): void
    {
        $this->profilConducteur = $profil;
        $this->touch();
    }

    public function ajouterProfilPassager(ProfilPassager $profil): void
    {
        $this->profilPassager = $profil;
        $this->touch();
    }

    public function estConducteur(): bool
    {
        return $this->profilConducteur !== null;
    }

    public function estPassager(): bool
    {
        return $this->profilPassager !== null;
    }
}
