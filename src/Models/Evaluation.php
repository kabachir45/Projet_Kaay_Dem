<?php

namespace App\Models;

use App\Traits\Timestampable;
use App\Exceptions\NoteInvalideException;

/**
 * Classe Evaluation
 * Créée par un passager après un trajet TERMINE.
 * Contribue au calcul de la note moyenne du ProfilConducteur.
 *
 * Règles métier :
 * - Note comprise entre 1 et 5 (validée à la construction)
 * - Liée à une unique Reservation (0..1)
 * - Le commentaire est optionnel
 */
class Evaluation
{
    use Timestampable;

    private ?int $id = null;
    private int $reservationId;
    private int $auteurId;       // passager qui évalue
    private int $conducteurId;   // conducteur évalué
    private int $note;           // 1 à 5
    private ?string $commentaire;

    public function __construct(
        int     $reservationId,
        int     $auteurId,
        int     $conducteurId,
        int     $note,
        ?string $commentaire = null
    ) {
        $this->validerNote($note);

        $this->reservationId = $reservationId;
        $this->auteurId      = $auteurId;
        $this->conducteurId  = $conducteurId;
        $this->note          = $note;
        $this->commentaire   = $commentaire;
        $this->initTimestamps();
    }

    // ── Getters / Setters ─────────────────────────────────────────────────────

    public function getId(): ?int          { return $this->id; }
    public function setId(int $id): void   { $this->id = $id; }

    public function getReservationId(): int  { return $this->reservationId; }
    public function getAuteurId(): int       { return $this->auteurId; }
    public function getConducteurId(): int   { return $this->conducteurId; }
    public function getNote(): int           { return $this->note; }
    public function getCommentaire(): ?string { return $this->commentaire; }

    public function setCommentaire(?string $commentaire): void
    {
        $this->commentaire = $commentaire;
        $this->touch();
    }

    // ── Validation ────────────────────────────────────────────────────────────

    private function validerNote(int $note): void
    {
        if ($note < 1 || $note > 5) {
            throw new NoteInvalideException("La note doit être comprise entre 1 et 5. Reçu : {$note}");
        }
    }
}
