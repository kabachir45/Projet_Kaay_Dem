<?php

namespace App\Controllers;

use App\Repositories\EvaluationRepository;
use App\Models\Evaluation;
use App\Exceptions\DonneesInvalidesException;

/**
 * EvaluationController
 * Gère la notation d'un conducteur par un passager après un trajet terminé.
 * S'appuie sur le modèle Evaluation (qui valide la note 1..5) et sur
 * EvaluationRepository pour la persistance.
 */
class EvaluationController
{
    private EvaluationRepository $evaluations;

    public function __construct()
    {
        $this->evaluations = new EvaluationRepository();
    }

    /**
     * Enregistre l'évaluation d'une réservation terminée.
     *
     * @throws DonneesInvalidesException si la réservation n'est pas évaluable
     *         (inexistante, non terminée, ou n'appartenant pas à l'utilisateur)
     * @throws \App\Exceptions\NoteInvalideException si la note est hors de [1, 5]
     */
    public function evaluer(int $reservationId, int $utilisateurId, int $note, ?string $commentaire): Evaluation
    {
        // 1. La réservation doit être TERMINEE et appartenir à l'utilisateur
        $conducteurId = $this->evaluations->conducteurIdSiEvaluable($reservationId, $utilisateurId);
        if ($conducteurId === null) {
            throw new DonneesInvalidesException('Réservation introuvable ou non terminée.');
        }

        // 2. Construction du modèle (valide la note → NoteInvalideException sinon)
        $evaluation = new Evaluation(
            $reservationId,
            $utilisateurId,   // auteur de l'évaluation
            $conducteurId,    // conducteur évalué
            $note,
            ($commentaire !== null && $commentaire !== '') ? $commentaire : null
        );

        // 3. Persistance (insert ou mise à jour si déjà évaluée)
        $this->evaluations->save($evaluation);

        return $evaluation;
    }
}
