<?php

namespace App\Models;

use App\Traits\Timestampable;

/**
 * Classe Vehicule
 * Rattaché à un ProfilConducteur (max. 2 par conducteur, un seul actif).
 *
 * Règles métier :
 * - Un seul véhicule actif à la fois par conducteur
 * - L'activation/désactivation est gérée par ProfilConducteur::activerVehicule()
 */
class Vehicule
{
    use Timestampable;

    private ?int $id = null;
    private int $conducteurId;
    private string $marque;
    private string $modele;
    private string $immatriculation;
    private int $nombrePlaces;
    private bool $actif;

    public function __construct(
        int    $conducteurId,
        string $marque,
        string $modele,
        string $immatriculation,
        int    $nombrePlaces,
        bool   $actif = false
    ) {
        $this->conducteurId    = $conducteurId;
        $this->marque          = $marque;
        $this->modele          = $modele;
        $this->immatriculation = $immatriculation;
        $this->nombrePlaces    = $nombrePlaces;
        $this->actif           = $actif;
        $this->initTimestamps();
    }


    public function getId(): ?int          { return $this->id; }
    public function setId(int $id): void   { $this->id = $id; }

    public function getConducteurId(): int       { return $this->conducteurId; }
    public function getMarque(): string          { return $this->marque; }
    public function getModele(): string          { return $this->modele; }
    public function getImmatriculation(): string { return $this->immatriculation; }
    public function getNombrePlaces(): int       { return $this->nombrePlaces; }
    public function estActif(): bool             { return $this->actif; }

    public function setMarque(string $marque): void             { $this->marque = $marque; $this->touch(); }
    public function setModele(string $modele): void             { $this->modele = $modele; $this->touch(); }
    public function setImmatriculation(string $immat): void     { $this->immatriculation = $immat; $this->touch(); }
    public function setNombrePlaces(int $places): void          { $this->nombrePlaces = $places; $this->touch(); }
    public function setActif(bool $actif): void                 { $this->actif = $actif; $this->touch(); }

    public function getLibelle(): string
    {
        return $this->marque . ' ' . $this->modele . ' (' . $this->immatriculation . ')';
    }
}
