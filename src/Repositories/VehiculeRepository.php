<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Vehicule;

/**
 * VehiculeRepository
 * Persistance des véhicules d'un conducteur, via PDO et requêtes préparées.
 */
class VehiculeRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Insère un nouveau véhicule (id null) ou met à jour un véhicule existant.
     */
    public function save(Vehicule $vehicule): void
    {
        if ($vehicule->getId() === null) {
            $this->insert($vehicule);
        } else {
            $this->update($vehicule);
        }
    }

    private function insert(Vehicule $vehicule): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO vehicules (conducteur_id, marque, modele, immatriculation, nombre_places, actif)
             VALUES (:c, :marque, :modele, :immat, :places, :actif)'
        );
        $stmt->execute($this->params($vehicule));
        $vehicule->setId((int) $this->pdo->lastInsertId());
    }

    private function update(Vehicule $vehicule): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE vehicules
             SET marque = :marque, modele = :modele, immatriculation = :immat,
                 nombre_places = :places, actif = :actif
             WHERE id = :id'
        );
        $params = $this->params($vehicule);
        unset($params[':c']);   // conducteur_id n'est pas modifiable
        $params[':id'] = $vehicule->getId();
        $stmt->execute($params);
    }

    private function params(Vehicule $vehicule): array
    {
        return [
            ':c'      => $vehicule->getConducteurId(),
            ':marque' => $vehicule->getMarque(),
            ':modele' => $vehicule->getModele(),
            ':immat'  => $vehicule->getImmatriculation(),
            ':places' => $vehicule->getNombrePlaces(),
            ':actif'  => $vehicule->estActif() ? 1 : 0,
        ];
    }
}
