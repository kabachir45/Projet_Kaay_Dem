<?php

namespace App\Models;

use App\Traits\Timestampable;

/**
 * Classe abstraite Personne
 * Super-classe commune à toutes les personnes physiques de la plateforme
 * (Utilisateur et Administrateur).
 *
 * Justification de l'héritage :
 * Utilisateur et Administrateur partagent une identité (nom, prénom, email)
 * et la gestion sécurisée du mot de passe (hachage bcrypt, vérification sans
 * getter exposant le hash). Cette logique est factorisée ici une seule fois.
 *
 * Polymorphisme :
 * - getRole()        : libellé du rôle, redéfini par chaque sous-classe ;
 * - peutAdministrer(): droit d'accès aux fonctions d'administration.
 * Une collection de Personne peut ainsi être parcourue uniformément
 * (ex. affichage du rôle) sans connaître le type concret.
 *
 * Encapsulation :
 * Le hash du mot de passe est `protected` et ne dispose d'aucun getter public ;
 * seul verifierMotDePasse() permet de le confronter à une saisie.
 */
abstract class Personne
{
    use Timestampable;

    protected ?int $id = null;
    protected string $nom;
    protected string $prenom;
    protected string $email;
    protected string $motDePasse;   // hash bcrypt — jamais de getter public

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

    // ── Identité ──────────────────────────────────────────────────────────────

    public function getId(): ?int        { return $this->id; }
    public function setId(int $id): void  { $this->id = $id; }

    public function getNom(): string     { return $this->nom; }
    public function getPrenom(): string  { return $this->prenom; }
    public function getEmail(): string   { return $this->email; }

    public function setNom(string $nom): void       { $this->nom = $nom; $this->touch(); }
    public function setPrenom(string $prenom): void { $this->prenom = $prenom; $this->touch(); }
    public function setEmail(string $email): void   { $this->email = $email; $this->touch(); }

    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    // ── Mot de passe (encapsulation stricte) ──────────────────────────────────

    /**
     * Seul point d'accès au mot de passe : on confronte une saisie au hash.
     * Aucun getMotDePasse() n'est exposé.
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
     * Réinjecte un hash déjà calculé (reconstruction depuis la BDD),
     * sans le re-hasher. Utilisé par les repositories.
     */
    public function setMotDePasseHash(string $hash): void
    {
        $this->motDePasse = $hash;
    }

    // ── Polymorphisme ─────────────────────────────────────────────────────────

    /**
     * Libellé du rôle de la personne (ex. « Utilisateur », « Administrateur »).
     */
    abstract public function getRole(): string;

    /**
     * Indique si la personne a accès aux fonctions d'administration.
     */
    abstract public function peutAdministrer(): bool;
}
