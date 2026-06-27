<?php

namespace App\Models;

use App\Traits\Timestampable;
use App\Interfaces\EvaluableInterface;
use App\Enums\StatutConducteur;

/**
 * Classe ProfilConducteur
 * Profil optionnel d'un Utilisateur souhaitant proposer des trajets.
 * Doit être validé par un Administrateur avant de pouvoir publier.
 *
 * Règles métier :
 * - Maximum 2 véhicules, un seul actif à la fois
 * - Ne peut publier de trajets que si statut === VALIDE
 * - Implémente EvaluableInterface : expose note moyenne et liste d'évaluations
 */
class ProfilConducteur implements EvaluableInterface
{
    use Timestampable;

    private ?int $id = null;
    private int $utilisateurId;
    private string $numeroPemis;
    private StatutConducteur $statut;

    /** @var Vehicule[] */
    private array $vehicules = [];

    /** @var Evaluation[] */
    private array $evaluations = [];

    public function __construct(int $utilisateurId, string $numeroPermis)
    {
        $this->utilisateurId = $utilisateurId;
        $this->numeroPemis   = $numeroPermis;
        $this->statut        = StatutConducteur::EN_ATTENTE;
        $this->initTimestamps();
    }


    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getUtilisateurId(): int         { return $this->utilisateurId; }
    public function getNumeroPermis(): string        { return $this->numeroPemis; }
    public function getStatut(): StatutConducteur   { return $this->statut; }

    public function setStatut(StatutConducteur $statut): void
    {
        $this->statut = $statut;
        $this->touch();
    }


    /**
     * @return Vehicule[]
     */
    public function getVehicules(): array { return $this->vehicules; }

    /**
     * @throws \OverflowException si le conducteur possède déjà 2 véhicules
     */
    public function ajouterVehicule(Vehicule $vehicule): void
    {
        if (count($this->vehicules) >= 2) {
            throw new \OverflowException("Un conducteur ne peut pas posséder plus de 2 véhicules.");
        }
        $this->vehicules[] = $vehicule;
        $this->touch();
    }

    public function getVehiculeActif(): ?Vehicule
    {
        foreach ($this->vehicules as $v) {
            if ($v->estActif()) return $v;
        }
        return null;
    }

    /**
     * Définit un véhicule comme actif et désactive les autres.
     */
    public function activerVehicule(Vehicule $cible): void
    {
        foreach ($this->vehicules as $v) {
            $v->setActif($v === $cible);
        }
        $this->touch();
    }


    public function estAutorise(): bool
    {
        return $this->statut->estAutorise();
    }


    public function getNote(): float
    {
        if (empty($this->evaluations)) return 0.0;

        $total = array_sum(array_map(fn(Evaluation $e) => $e->getNote(), $this->evaluations));
        return round($total / count($this->evaluations), 2);
    }

    public function getEvaluations(): array
    {
        return $this->evaluations;
    }

    public function ajouterEvaluation(Evaluation $evaluation): void
    {
        $this->evaluations[] = $evaluation;
    }
}
