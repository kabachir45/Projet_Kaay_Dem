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


    /**
     * Recherche paginée de trajets disponibles, avec filtres optionnels.
     * Méthode de lecture/reporting : renvoie des lignes enrichies (conducteur,
     * véhicule, note moyenne) directement exploitables par la vue, plus les
     * métadonnées de pagination.
     *
     * @param array<string, mixed> $criteres depart, arrivee, date, prix_max,
     *                                        places_min, page, par_page
     * @return array{trajets: array, total: int, page: int, par_page: int, pages: int}
     */
    public function rechercheAvancee(array $criteres): array
    {
        $where  = 'WHERE t.annule = 0 AND t.places_disponibles > 0 AND t.date_depart > NOW()';
        $params = [];

        if (!empty($criteres['depart'])) {
            $where .= ' AND t.ville_depart LIKE :depart';
            $params[':depart'] = '%' . $criteres['depart'] . '%';
        }
        if (!empty($criteres['arrivee'])) {
            $where .= ' AND t.ville_arrivee LIKE :arrivee';
            $params[':arrivee'] = '%' . $criteres['arrivee'] . '%';
        }
        if (!empty($criteres['date'])) {
            $where .= ' AND DATE(t.date_depart) = :date';
            $params[':date'] = $criteres['date'];
        }
        if (isset($criteres['prix_max']) && $criteres['prix_max'] !== '' && (float) $criteres['prix_max'] > 0) {
            $where .= ' AND t.prix <= :prix_max';
            $params[':prix_max'] = (float) $criteres['prix_max'];
        }
        if (!empty($criteres['places_min'])) {
            $where .= ' AND t.places_disponibles >= :places_min';
            $params[':places_min'] = (int) $criteres['places_min'];
        }

        // Total (sans les jointures d'agrégation, qui ne filtrent pas)
        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM trajets t
             JOIN profil_conducteur pc ON pc.id = t.conducteur_id
             JOIN utilisateurs u       ON u.id  = pc.utilisateur_id
             {$where}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Pagination (offset/limit injectés comme entiers validés)
        $parPage = max(1, (int) ($criteres['par_page'] ?? 6));
        $pages   = max(1, (int) ceil($total / $parPage));
        $page    = min($pages, max(1, (int) ($criteres['page'] ?? 1)));
        $offset  = ($page - 1) * $parPage;

        $stmt = $this->pdo->prepare(
            "SELECT t.*,
                    u.nom AS conducteur_nom, u.prenom AS conducteur_prenom,
                    v.marque, v.modele,
                    COALESCE(ROUND(AVG(e.note),1), 0) AS note_moyenne,
                    COUNT(DISTINCT e.id) AS nb_evaluations
             FROM trajets t
             JOIN profil_conducteur pc ON pc.id = t.conducteur_id
             JOIN utilisateurs u       ON u.id  = pc.utilisateur_id
             LEFT JOIN vehicules v     ON v.id  = t.vehicule_id
             LEFT JOIN reservations r  ON r.trajet_id = t.id AND r.statut = 'TERMINEE'
             LEFT JOIN evaluations e   ON e.reservation_id = r.id
             {$where}
             GROUP BY t.id
             ORDER BY t.date_depart ASC
             LIMIT {$offset}, {$parPage}"
        );
        $stmt->execute($params);

        return [
            'trajets'  => $stmt->fetchAll(),
            'total'    => $total,
            'page'     => $page,
            'par_page' => $parPage,
            'pages'    => $pages,
        ];
    }

    private function insert(Trajet $trajet): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO trajets
                (conducteur_id, vehicule_id, ville_depart, ville_arrivee, points_arret,
                 lat_depart, lng_depart, lat_arrivee, lng_arrivee, distance_km, duree_min,
                 date_depart, prix, places_disponibles, description, annule, created_at, updated_at)
             VALUES
                (:conducteur_id, :vehicule_id, :depart, :arrivee, :points_arret,
                 :lat_depart, :lng_depart, :lat_arrivee, :lng_arrivee, :distance_km, :duree_min,
                 :date_depart, :prix, :places, :description, :annule, :created, :updated)'
        );

        $stmt->execute($this->buildParams($trajet));
        $trajet->setId((int) $this->pdo->lastInsertId());
    }

    private function update(Trajet $trajet): void
    {
        $trajet->touch();

        $stmt = $this->pdo->prepare(
            'UPDATE trajets
             SET vehicule_id = :vehicule_id, ville_depart = :depart, ville_arrivee = :arrivee,
                 points_arret = :points_arret, description = :description,
                 lat_depart = :lat_depart, lng_depart = :lng_depart,
                 lat_arrivee = :lat_arrivee, lng_arrivee = :lng_arrivee,
                 distance_km = :distance_km, duree_min = :duree_min,
                 date_depart = :date_depart, prix = :prix,
                 places_disponibles = :places, annule = :annule,
                 updated_at = :updated
             WHERE id = :id'
        );

        // On ne lie que les paramètres présents dans la requête
        // (conducteur_id et created_at ne sont pas modifiables ici).
        $params = $this->buildParams($trajet);
        unset($params[':conducteur_id'], $params[':created']);
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
            ':points_arret'  => $trajet->getPointsArret(),
            ':lat_depart'    => $trajet->getLatDepart(),
            ':lng_depart'    => $trajet->getLngDepart(),
            ':lat_arrivee'   => $trajet->getLatArrivee(),
            ':lng_arrivee'   => $trajet->getLngArrivee(),
            ':distance_km'   => $trajet->getDistanceKm(),
            ':duree_min'     => $trajet->getDureeMin(),
            ':date_depart'   => $trajet->getDateDepart()->format('Y-m-d H:i:s'),
            ':prix'          => $trajet->getPrix(),
            ':places'        => $trajet->getPlacesDisponibles(),
            ':description'   => $trajet->getDescription(),
            ':annule'        => $trajet->estAnnule() ? 1 : 0,
            ':created'       => $trajet->getCreatedAt()->format('Y-m-d H:i:s'),
            ':updated'       => $trajet->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    private function hydrate(array $row): Trajet
    {
        $trajet = new Trajet(
            (int)   $row['conducteur_id'],
                    $row['vehicule_id'] !== null ? (int) $row['vehicule_id'] : null,
                    $row['ville_depart'],
                    $row['ville_arrivee'],
            new \DateTime($row['date_depart']),
            (float) $row['prix'],
            (int)   $row['places_disponibles']
        );

        $trajet->setId((int) $row['id']);
        $trajet->setCreatedAt(new \DateTime($row['created_at']));
        $trajet->setUpdatedAt(new \DateTime($row['updated_at']));

        $trajet->setPointsArret($row['points_arret'] ?? null);
        $trajet->setDescription($row['description'] ?? null);
        $trajet->setItineraire(
            isset($row['lat_depart'])  ? (float) $row['lat_depart']  : null,
            isset($row['lng_depart'])  ? (float) $row['lng_depart']  : null,
            isset($row['lat_arrivee']) ? (float) $row['lat_arrivee'] : null,
            isset($row['lng_arrivee']) ? (float) $row['lng_arrivee'] : null,
            isset($row['distance_km']) ? (float) $row['distance_km'] : null,
            isset($row['duree_min'])   ? (int)   $row['duree_min']   : null,
        );

        if ((bool) $row['annule']) {
            $ref = new \ReflectionProperty(Trajet::class, 'annule');
            $ref->setAccessible(true);
            $ref->setValue($trajet, true);
        }

        return $trajet;
    }
}
