<?php

namespace App\Models;

use App\Traits\Timestampable;
use App\Exceptions\PlacesInsuffisantesException;

/**
 * Classe Trajet
 * Publié par un conducteur validé, lié à son véhicule actif.
 *
 * Règles métier :
 * - Ne peut être créé que par un conducteur dont statut === VALIDE
 * - Le nombre de places disponibles décrémente à chaque réservation CONFIRMEE
 * - Un trajet annulé entraîne l'annulation de toutes ses réservations EN_ATTENTE/CONFIRMEE
 */
class Trajet
{
    use Timestampable;

    private ?int $id = null;
    private int $conducteurId;
    private ?int $vehiculeId;
    private string $villeDepart;
    private string $villeArrivee;
    private \DateTime $dateDepart;
    private float $prix;
    private int $placesDisponibles;
    private bool $annule = false;

    // Champs optionnels (renseignés à la publication)
    private ?string $pointsArret = null;
    private ?string $description = null;
    private ?float $latDepart   = null;
    private ?float $lngDepart    = null;
    private ?float $latArrivee   = null;
    private ?float $lngArrivee    = null;
    private ?float $distanceKm   = null;
    private ?int   $dureeMin      = null;

    /** @var Reservation[] */
    private array $reservations = [];

    public function __construct(
        int       $conducteurId,
        ?int      $vehiculeId,
        string    $villeDepart,
        string    $villeArrivee,
        \DateTime $dateDepart,
        float     $prix,
        int       $placesDisponibles
    ) {
        $this->conducteurId      = $conducteurId;
        $this->vehiculeId        = $vehiculeId;
        $this->villeDepart       = $villeDepart;
        $this->villeArrivee      = $villeArrivee;
        $this->dateDepart        = $dateDepart;
        $this->prix              = $prix;
        $this->placesDisponibles = $placesDisponibles;
        $this->initTimestamps();
    }


    public function getId(): ?int            { return $this->id; }
    public function setId(int $id): void     { $this->id = $id; }

    public function getConducteurId(): int         { return $this->conducteurId; }
    public function getVehiculeId(): ?int          { return $this->vehiculeId; }
    public function getVilleDepart(): string       { return $this->villeDepart; }
    public function getVilleArrivee(): string      { return $this->villeArrivee; }
    public function getDateDepart(): \DateTime     { return $this->dateDepart; }
    public function getPrix(): float               { return $this->prix; }
    public function getPlacesDisponibles(): int    { return $this->placesDisponibles; }
    public function estAnnule(): bool              { return $this->annule; }

    public function getPointsArret(): ?string { return $this->pointsArret; }
    public function getDescription(): ?string { return $this->description; }
    public function getLatDepart(): ?float    { return $this->latDepart; }
    public function getLngDepart(): ?float    { return $this->lngDepart; }
    public function getLatArrivee(): ?float   { return $this->latArrivee; }
    public function getLngArrivee(): ?float   { return $this->lngArrivee; }
    public function getDistanceKm(): ?float   { return $this->distanceKm; }
    public function getDureeMin(): ?int       { return $this->dureeMin; }

    public function setVilleDepart(string $v): void       { $this->villeDepart = $v; $this->touch(); }
    public function setVilleArrivee(string $v): void      { $this->villeArrivee = $v; $this->touch(); }
    public function setDateDepart(\DateTime $d): void     { $this->dateDepart = $d; $this->touch(); }
    public function setPrix(float $p): void               { $this->prix = $p; $this->touch(); }
    public function setVehiculeId(?int $id): void         { $this->vehiculeId = $id; $this->touch(); }
    public function setPointsArret(?string $p): void      { $this->pointsArret = $p; }
    public function setDescription(?string $d): void      { $this->description = $d; }

    /**
     * Renseigne les coordonnées et l'itinéraire calculés (carte / OSRM).
     */
    public function setItineraire(
        ?float $latDepart, ?float $lngDepart,
        ?float $latArrivee, ?float $lngArrivee,
        ?float $distanceKm, ?int $dureeMin
    ): void {
        $this->latDepart  = $latDepart;
        $this->lngDepart  = $lngDepart;
        $this->latArrivee = $latArrivee;
        $this->lngArrivee = $lngArrivee;
        $this->distanceKm = $distanceKm;
        $this->dureeMin   = $dureeMin;
    }


    public function aDesPlaces(): bool
    {
        return $this->placesDisponibles > 0;
    }

    /**
     * Décrémente les places disponibles lors d'une confirmation de réservation.
     * @throws PlacesInsuffisantesException si plus de places disponibles
     */
    public function reserverPlace(): void
    {
        if (!$this->aDesPlaces()) {
            throw new PlacesInsuffisantesException("Plus de places disponibles sur ce trajet.");
        }
        $this->placesDisponibles--;
        $this->touch();
    }

    /**
     * Restitue une place lors d'une annulation de réservation.
     */
    public function libererPlace(): void
    {
        $this->placesDisponibles++;
        $this->touch();
    }

    /**
     * Annule le trajet et cascade l'annulation sur les réservations actives.
     */
    public function annuler(): void
    {
        $this->annule = true;
        foreach ($this->reservations as $reservation) {
            if ($reservation->estActive()) {
                $reservation->annuler();
            }
        }
        $this->touch();
    }


    /** @return Reservation[] */
    public function getReservations(): array { return $this->reservations; }

    public function ajouterReservation(Reservation $r): void
    {
        $this->reservations[] = $r;
    }
}
