<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Evaluation;
use App\Enums\StatutReservation;

/**
 * EvaluationRepository
 * Persistance des évaluations (note + commentaire liés à une réservation),
 * via PDO et requêtes préparées.
 *
 * NB : la table `evaluations` ne stocke que reservation_id / note / commentaire.
 * L'auteur et le conducteur évalué sont déductibles via la chaîne
 * réservation → trajet → conducteur, calculée à la lecture (moyennes dans les vues).
 */
class EvaluationRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Retourne l'id du conducteur (profil_conducteur) à évaluer SI la réservation
     * est TERMINEE et appartient bien à l'utilisateur ; null sinon.
     */
    public function conducteurIdSiEvaluable(int $reservationId, int $utilisateurId): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.conducteur_id
             FROM reservations r
             JOIN profil_passager pp ON pp.id = r.passager_id
             JOIN trajets t          ON t.id = r.trajet_id
             WHERE r.id = :rid AND pp.utilisateur_id = :uid AND r.statut = :statut
             LIMIT 1'
        );
        $stmt->execute([
            ':rid'    => $reservationId,
            ':uid'    => $utilisateurId,
            ':statut' => StatutReservation::TERMINEE->value,
        ]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /**
     * Enregistre l'évaluation. Comme une réservation ne peut être évaluée
     * qu'une fois (contrainte UNIQUE sur reservation_id), une nouvelle
     * soumission met à jour la note et le commentaire existants.
     */
    public function save(Evaluation $evaluation): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO evaluations (reservation_id, note, commentaire)
             VALUES (:rid, :note, :com)
             ON DUPLICATE KEY UPDATE note = :note2, commentaire = :com2'
        );
        $stmt->execute([
            ':rid'   => $evaluation->getReservationId(),
            ':note'  => $evaluation->getNote(),
            ':com'   => $evaluation->getCommentaire(),
            ':note2' => $evaluation->getNote(),
            ':com2'  => $evaluation->getCommentaire(),
        ]);
    }
}
