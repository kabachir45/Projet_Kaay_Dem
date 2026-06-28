<?php

namespace App\Repositories;

use App\Core\Database;
use App\Interfaces\RepositoryInterface;
use App\Models\Trajet;

/**
 * TrajetRepository
 * Gère toutes les opérations en base de données liées à Trajet.
 */
class TrajetRepository implements RepositoryInterface
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * @throws \RuntimeException si non trouvé
     */
    public function find(int $id): Trajet
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM trajets WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new \RuntimeException("Trajet introuvable (id={$id}).");
        }

        return $this->hydrate($row);
    }

    /**
     * @return Trajet[]
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM trajets WHERE annule = 0 ORDER BY date_depart ASC'
        );
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll());
    }

    public function save(object $entity): void
    {
        /** @var Trajet $entity */
        if ($entity->getId() === null) {
            $this->insert($entity);
        } else {
            $this->update($entity);
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM trajets WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * Recherche des trajets disponibles selon ville départ, arrivée et date.
     * @return Trajet[]
     */
    public function rechercher(string $depart, string $arrivee, \DateTime $date): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM trajets
             WHERE ville_depart = :depart
               AND ville_arrivee = :arrivee
               AND DATE(date_depart) = :date
               AND annule = 0
               AND places_disponibles > 0
             ORDER BY date_depart ASC'
        );
        $stmt->execute([
            ':depart'  => $depart,
            ':arrivee' => $arrivee,
            ':date'    => $date->format('Y-m-d'),
        ]);

        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll());
    }

    /**
     * Retourne tous les trajets publiés par un conducteur donné.
     * @return Trajet[]
     */
    public function findByConducteur(int $conducteurId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM trajets WHERE conducteur_id = :id ORDER BY date_depart DESC'
        );
        $stmt->execute([':id' => $conducteurId]);

        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll());
    }


    private function insert(Trajet $trajet): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO trajets
                (conducteur_id, vehicule_id, ville_depart, ville_arrivee,
                 date_depart, prix, places_disponibles, annule, created_at, updated_at)
             VALUES
                (:conducteur_id, :vehicule_id, :depart, :arrivee,
                 :date_depart, :prix, :places, :annule, :created, :updated)'
        );

        $stmt->execute($this->buildParams($trajet));
        $trajet->setId((int) $this->pdo->lastInsertId());
    }

    private function update(Trajet $trajet): void
    {
        $trajet->touch();

        $stmt = $this->pdo->prepare(
            'UPDATE trajets
             SET ville_depart = :depart, ville_arrivee = :arrivee,
                 date_depart = :date_depart, prix = :prix,
                 places_disponibles = :places, annule = :annule,
                 updated_at = :updated
             WHERE id = :id'
        );

        $params = $this->buildParams($trajet);
        $params[':id'] = $trajet->getId();
        $stmt->execute($params);
    }

    private function buildParams(Trajet $trajet): array
    {
        return [
            ':conducteur_id' => $trajet->getConducteurId(),
            ':vehicule_id'   => $trajet->getVehiculeId(),
            ':depart'        => $trajet->getVilleDepart(),
            ':arrivee'       => $trajet->getVilleArrivee(),
            ':date_depart'   => $trajet->getDateDepart()->format('Y-m-d H:i:s'),
            ':prix'          => $trajet->getPrix(),
            ':places'        => $trajet->getPlacesDisponibles(),
            ':annule'        => $trajet->estAnnule() ? 1 : 0,
            ':created'       => $trajet->getCreatedAt()->format('Y-m-d H:i:s'),
            ':updated'       => $trajet->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    private function hydrate(array $row): Trajet
    {
        $trajet = new Trajet(
            (int)   $row['conducteur_id'],
            (int)   $row['vehicule_id'],
                    $row['ville_depart'],
                    $row['ville_arrivee'],
            new \DateTime($row['date_depart']),
            (float) $row['prix'],
            (int)   $row['places_disponibles']
        );

        $trajet->setId((int) $row['id']);
        $trajet->setCreatedAt(new \DateTime($row['created_at']));
        $trajet->setUpdatedAt(new \DateTime($row['updated_at']));

        if ((bool) $row['annule']) {
            $ref = new \ReflectionProperty(Trajet::class, 'annule');
            $ref->setAccessible(true);
            $ref->setValue($trajet, true);
        }

        return $trajet;
    }
}
