<?php

namespace App\Models;

use App\Traits\Timestampable;

/**
 * Classe Utilisateur
 * Entité centrale de la plateforme. Un utilisateur peut cumuler
 * un ProfilConducteur et/ou un ProfilPassager (architecture par composition).
 *
 * Règles métier :
 * - Le mot de passe n'est jamais exposé via getter
 * - Les profils sont optionnels (0..1) et ajoutables post-inscription
 * - La session mono-rôle est gérée au niveau Controller, pas ici
 */
class Utilisateur
{
    use Timestampable;

    private ?int $id = null;
    private string $nom;
    private string $prenom;
    private string $email;
    private string $motDePasse;       // hash — jamais de getter public
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
        $this->nom        = $nom;
        $this->prenom     = $prenom;
        $this->email      = $email;
        $this->motDePasse = password_hash($motDePasse, PASSWORD_BCRYPT);
        $this->telephone  = $telephone;
        $this->initTimestamps();
    }


    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getNom(): string    { return $this->nom; }
    public function getPrenom(): string { return $this->prenom; }
    public function getEmail(): string  { return $this->email; }
    public function getTelephone(): string { return $this->telephone; }
    public function getPhoto(): ?string { return $this->photo; }

    public function setNom(string $nom): void       { $this->nom = $nom; }
    public function setPrenom(string $prenom): void { $this->prenom = $prenom; }
    public function setEmail(string $email): void   { $this->email = $email; }
    public function setTelephone(string $tel): void { $this->telephone = $tel; }
    public function setPhoto(?string $photo): void  { $this->photo = $photo; }

    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }


    /**
     * Seul point d'accès au mot de passe.
     * Jamais de getMotDePasse() — le hash reste privé.
     */
    public function verifierMotDePasse(string $saisie): bool
    {
        return password_verify($saisie, $this->motDePasse);
    }

    public function changerMotDePasse(string $ancien, string $nouveau): bool
    {
        if (!$this->verifierMotDePasse($ancien)) {
            return false;
        }
        $this->motDePasse = password_hash($nouveau, PASSWORD_BCRYPT);
        $this->touch();
        return true;
    }

    /**
     * Utilisé par le Repository lors de la reconstruction depuis la BDD.
     * Le hash est stocké tel quel, sans re-hashage.
     */
    public function setMotDePasseHash(string $hash): void
    {
        $this->motDePasse = $hash;
    }


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
