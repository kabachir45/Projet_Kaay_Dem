<?php

namespace App\Models;

use App\Traits\Timestampable;

/**
 * Classe Signalement
 * Déposé par un utilisateur contre un autre utilisateur.
 * Traité par un Administrateur.
 *
 * Règles métier :
 * - Un utilisateur ne peut pas se signaler lui-même
 * - Le motif est obligatoire, la description est optionnelle
 * - Un signalement traité ne peut plus être modifié
 */
class Signalement
{
    use Timestampable;

    private ?int $id = null;
    private int $rapporteurId;   // utilisateur qui signale
    private int $signaleId;      // utilisateur signalé
    private string $motif;
    private ?string $description;
    private bool $traite = false;

    public function __construct(
        int     $rapporteurId,
        int     $signaleId,
        string  $motif,
        ?string $description = null
    ) {
        if ($rapporteurId === $signaleId) {
            throw new \InvalidArgumentException("Un utilisateur ne peut pas se signaler lui-même.");
        }

        $this->rapporteurId = $rapporteurId;
        $this->signaleId    = $signaleId;
        $this->motif        = $motif;
        $this->description  = $description;
        $this->initTimestamps();
    }


    public function getId(): ?int          { return $this->id; }
    public function setId(int $id): void   { $this->id = $id; }

    public function getRapporteurId(): int   { return $this->rapporteurId; }
    public function getSignaleId(): int      { return $this->signaleId; }
    public function getMotif(): string       { return $this->motif; }
    public function getDescription(): ?string { return $this->description; }
    public function estTraite(): bool        { return $this->traite; }


    /**
     * Marque le signalement comme traité par l'administrateur.
     * @throws \LogicException si déjà traité
     */
    public function marquerTraite(): void
    {
        if ($this->traite) {
            throw new \LogicException("Ce signalement a déjà été traité.");
        }
        $this->traite = true;
        $this->touch();
    }
}
