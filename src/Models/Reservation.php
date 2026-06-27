<?php

namespace App\Models;

use App\Traits\Timestampable;
use App\Enums\StatutReservation;

/**
 * Classe Reservation
 * Cycle de vie : EN_ATTENTE → CONFIRMEE → TERMINEE
 *                EN_ATTENTE → ANNULEE
 *                CONFIRMEE  → ANNULEE
 *
 * Règles métier :
 * - Toute transition invalide lève une exception
 * - La libération de place sur le Trajet est déclenchée par annuler()
 * - Une évaluation ne peut être créée que si statut === TERMINEE
 */
class Reservation
{
    use Timestampable;

    private ?int $id = null;
    private int $trajetId;
    private int $passagerId;
    private StatutReservation $statut;
    private ?Evaluation $evaluation = null;

    public function __construct(int $trajetId, int $passagerId)
    {
        $this->trajetId   = $trajetId;
        $this->passagerId = $passagerId;
        $this->statut     = StatutReservation::EN_ATTENTE;
        $this->initTimestamps();
    }


    public function getId(): ?int          { return $this->id; }
    public function setId(int $id): void   { $this->id = $id; }

    public function getTrajetId(): int           { return $this->trajetId; }
    public function getPassagerId(): int         { return $this->passagerId; }
    public function getStatut(): StatutReservation { return $this->statut; }
    public function getEvaluation(): ?Evaluation { return $this->evaluation; }


    /**
     * Applique une transition de statut en vérifiant sa validité.
     * @throws \LogicException si la transition est interdite
     */
    private function transitionner(StatutReservation $cible): void
    {
        if (!$this->statut->peutTransitionnerVers($cible)) {
            throw new \LogicException(
                "Transition invalide : {$this->statut->label()} → {$cible->label()}"
            );
        }
        $this->statut = $cible;
        $this->touch();
    }

    public function confirmer(): void  { $this->transitionner(StatutReservation::CONFIRMEE); }
    public function terminer(): void   { $this->transitionner(StatutReservation::TERMINEE); }
    public function annuler(): void    { $this->transitionner(StatutReservation::ANNULEE); }


    public function estActive(): bool
    {
        return in_array($this->statut, [
            StatutReservation::EN_ATTENTE,
            StatutReservation::CONFIRMEE,
        ]);
    }

    public function estTerminee(): bool
    {
        return $this->statut === StatutReservation::TERMINEE;
    }


    /**
     * @throws \LogicException si la réservation n'est pas TERMINEE
     */
    public function ajouterEvaluation(Evaluation $evaluation): void
    {
        if (!$this->estTerminee()) {
            throw new \LogicException("Une évaluation ne peut être ajoutée qu'après un trajet terminé.");
        }
        if ($this->evaluation !== null) {
            throw new \LogicException("Cette réservation a déjà été évaluée.");
        }
        $this->evaluation = $evaluation;
    }

    public function peutEtreEvaluee(): bool
    {
        return $this->estTerminee() && $this->evaluation === null;
    }
}
