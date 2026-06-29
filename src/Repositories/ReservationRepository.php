<?php

namespace App\Repositories;

use App\Core\Database;
use App\Interfaces\RepositoryInterface;
use App\Models\Reservation;
use App\Enums\StatutReservation;

/**
 * ReservationRepository
 * Gère toutes les opérations en base de données liées à Reservation.
 */
class ReservationRepository implements RepositoryInterface
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * @throws \RuntimeException si non trouvée
     */
    public function find(int $id): Reservation
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM reservations WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new \RuntimeException("Réservation introuvable (id={$id}).");
        }

        return $this->hydrate($row);
    }

    /**
     * @return Reservation[]
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM reservations ORDER BY created_at DESC'
        );
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll());
    }

    public function save(object $entity): void
    {
        /** @var Reservation $entity */
        if ($entity->getId() === null) {
            $this->insert($entity);
        } else {
            $this->update($entity);
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM reservations WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }


    /**
     * Retourne toutes les réservations d'un trajet donné.
     * @return Reservation[]
     */
    public function findByTrajet(int $trajetId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM reservations WHERE trajet_id = :id ORDER BY created_at ASC'
        );
        $stmt->execute([':id' => $trajetId]);
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll());
    }

    /**
     * Retourne toutes les réservations d'un passager donné.
     * @return Reservation[]
     */
    public function findByPassager(int $passagerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM reservations WHERE passager_id = :id ORDER BY created_at DESC'
        );
        $stmt->execute([':id' => $passagerId]);
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll());
    }

    /**
     * Vérifie si un passager a déjà réservé un trajet donné.
     */
    public function existeDeja(int $trajetId, int $passagerId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM reservations
             WHERE trajet_id = :trajet AND passager_id = :passager
               AND statut NOT IN (:annulee)'
        );
        $stmt->execute([
            ':trajet'   => $trajetId,
            ':passager' => $passagerId,
            ':annulee'  => StatutReservation::ANNULEE->value,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Indique si le passager a déjà une réservation active sur un trajet dont
     * le créneau horaire chevauche [$debut, $fin]. La durée d'un trajet est
     * estimée via duree_min (60 min par défaut si inconnue).
     *
     * @param int $exclureTrajetId trajet à ignorer (celui qu'on est en train de réserver)
     */
    public function existeChevauchement(int $passagerId, \DateTime $debut, \DateTime $fin, int $exclureTrajetId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM reservations r
             JOIN trajets t ON t.id = r.trajet_id
             WHERE r.passager_id = :passager
               AND r.statut IN (:attente, :confirmee)
               AND t.annule = 0
               AND t.id <> :exclure
               AND t.date_depart < :fin
               AND DATE_ADD(t.date_depart, INTERVAL COALESCE(t.duree_min, 60) MINUTE) > :debut"
        );
        $stmt->execute([
            ':passager'  => $passagerId,
            ':attente'   => StatutReservation::EN_ATTENTE->value,
            ':confirmee' => StatutReservation::CONFIRMEE->value,
            ':exclure'   => $exclureTrajetId,
            ':fin'       => $fin->format('Y-m-d H:i:s'),
            ':debut'     => $debut->format('Y-m-d H:i:s'),
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function insert(Reservation $reservation): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO reservations (trajet_id, passager_id, statut, created_at, updated_at)
             VALUES (:trajet_id, :passager_id, :statut, :created, :updated)'
        );

        $stmt->execute($this->buildParams($reservation));
        $reservation->setId((int) $this->pdo->lastInsertId());
    }

    private function update(Reservation $reservation): void
    {
        $reservation->touch();

        $stmt = $this->pdo->prepare(
            'UPDATE reservations
             SET statut = :statut, paiement_confirme = :paiement, updated_at = :updated
             WHERE id = :id'
        );

        $stmt->execute([
            ':statut'   => $reservation->getStatut()->value,
            ':paiement' => $reservation->paiementConfirme() ? 1 : 0,
            ':updated'  => $reservation->getUpdatedAt()->format('Y-m-d H:i:s'),
            ':id'       => $reservation->getId(),
        ]);
    }

    private function buildParams(Reservation $reservation): array
    {
        return [
            ':trajet_id'   => $reservation->getTrajetId(),
            ':passager_id' => $reservation->getPassagerId(),
            ':statut'      => $reservation->getStatut()->value,
            ':created'     => $reservation->getCreatedAt()->format('Y-m-d H:i:s'),
            ':updated'     => $reservation->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    private function hydrate(array $row): Reservation
    {
        $reservation = new Reservation(
            (int) $row['trajet_id'],
            (int) $row['passager_id']
        );

        $reservation->setId((int) $row['id']);
        $reservation->setCreatedAt(new \DateTime($row['created_at']));
        $reservation->setUpdatedAt(new \DateTime($row['updated_at']));

  
        $statut = StatutReservation::from($row['statut']);
        $ref    = new \ReflectionProperty(Reservation::class, 'statut');
        $ref->setAccessible(true);
        $ref->setValue($reservation, $statut);

        if ((bool) ($row['paiement_confirme'] ?? false)) {
            $refP = new \ReflectionProperty(Reservation::class, 'paiementConfirme');
            $refP->setAccessible(true);
            $refP->setValue($reservation, true);
        }

        return $reservation;
    }
}
