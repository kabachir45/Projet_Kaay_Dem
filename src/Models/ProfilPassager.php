<?php

namespace App\Models;

use App\Traits\Timestampable;

/**
 * Classe ProfilPassager
 * Profil optionnel d'un Utilisateur souhaitant réserver des trajets.
 * Activable dès l'inscription, sans validation administrative.
 *
 * Règles métier :
 * - Aucune restriction d'activation (contrairement au ProfilConducteur)
 * - Historique des réservations accessible via ce profil
 */
class ProfilPassager
{
    use Timestampable;

    private ?int $id = null;
    private int $utilisateurId;

    /** @var Reservation[] */
    private array $reservations = [];

    public function __construct(int $utilisateurId)
    {
        $this->utilisateurId = $utilisateurId;
        $this->initTimestamps();
    }

    // ── Getters / Setters ─────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getUtilisateurId(): int { return $this->utilisateurId; }

    // ── Réservations ──────────────────────────────────────────────────────────

    /**
     * @return Reservation[]
     */
    public function getReservations(): array
    {
        return $this->reservations;
    }

    public function ajouterReservation(Reservation $reservation): void
    {
        $this->reservations[] = $reservation;
    }

    /**
     * Retourne uniquement les réservations actives (EN_ATTENTE ou CONFIRMEE).
     *
     * @return Reservation[]
     */
    public function getReservationsActives(): array
    {
        return array_filter(
            $this->reservations,
            fn(Reservation $r) => $r->estActive()
        );
    }
}
