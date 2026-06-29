<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Signalement;

/**
 * SignalementRepository
 * Accès aux signalements via PDO. La table ne possède pas de colonne
 * updated_at : seule la colonne `traite` est mise à jour.
 */
class SignalementRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * @throws \RuntimeException si le signalement n'existe pas
     */
    public function find(int $id): Signalement
    {
        $stmt = $this->pdo->prepare('SELECT * FROM signalements WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new \RuntimeException("Signalement introuvable (id={$id}).");
        }

        return $this->hydrate($row);
    }

    /**
     * Insère un nouveau signalement (id null) ou met à jour son état `traite`.
     */
    public function save(Signalement $signalement): void
    {
        if ($signalement->getId() === null) {
            $this->insert($signalement);
        } else {
            $this->update($signalement);
        }
    }

    private function insert(Signalement $signalement): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO signalements (rapporteur_id, signale_id, motif, description, traite, created_at)
             VALUES (:rapporteur, :signale, :motif, :description, :traite, :created)'
        );
        $stmt->execute([
            ':rapporteur'  => $signalement->getRapporteurId(),
            ':signale'     => $signalement->getSignaleId(),
            ':motif'       => $signalement->getMotif(),
            ':description' => $signalement->getDescription(),
            ':traite'      => $signalement->estTraite() ? 1 : 0,
            ':created'     => $signalement->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
        $signalement->setId((int) $this->pdo->lastInsertId());
    }

    private function update(Signalement $signalement): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE signalements SET traite = :traite WHERE id = :id'
        );
        $stmt->execute([
            ':traite' => $signalement->estTraite() ? 1 : 0,
            ':id'     => $signalement->getId(),
        ]);
    }

    private function hydrate(array $row): Signalement
    {
        $signalement = new Signalement(
            (int) $row['rapporteur_id'],
            (int) $row['signale_id'],
            $row['motif'],
            $row['description'] ?? null
        );

        $signalement->setId((int) $row['id']);
        $signalement->setCreatedAt(new \DateTime($row['created_at']));

        // `traite` n'a pas de setter public (seul marquerTraite() le passe à true) :
        // on restaure l'état persité via réflexion, comme pour les autres entités.
        if ((bool) $row['traite']) {
            $ref = new \ReflectionProperty(Signalement::class, 'traite');
            $ref->setAccessible(true);
            $ref->setValue($signalement, true);
        }

        return $signalement;
    }
}
