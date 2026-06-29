<?php

namespace App\Repositories;

use App\Core\Database;
use App\Enums\StatutConducteur;
use App\Models\ProfilConducteur;

/**
 * ProfilConducteurRepository
 * Accès aux profils conducteur. Centralise les requêtes utilisées par la
 * publication de trajets (résolution du profil validé, véhicules associés),
 * toujours via PDO et requêtes préparées.
 */
class ProfilConducteurRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Retourne l'id du profil conducteur VALIDÉ d'un utilisateur,
     * ou null s'il n'est pas (encore) conducteur autorisé.
     */
    public function findIdValideByUtilisateur(int $utilisateurId): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM profil_conducteur
             WHERE utilisateur_id = :u AND statut = :statut
             LIMIT 1'
        );
        $stmt->execute([
            ':u'      => $utilisateurId,
            ':statut' => StatutConducteur::VALIDE->value,
        ]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /**
     * Retourne les véhicules d'un conducteur (pour le menu déroulant du formulaire).
     * @return array<int, array<string, mixed>>
     */
    public function findVehicules(int $conducteurId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, marque, modele, immatriculation
             FROM vehicules WHERE conducteur_id = :c ORDER BY marque, modele'
        );
        $stmt->execute([':c' => $conducteurId]);

        return $stmt->fetchAll();
    }

    /**
     * Charge un profil conducteur sous forme de modèle.
     * @throws \RuntimeException si introuvable
     */
    public function find(int $id): ProfilConducteur
    {
        $stmt = $this->pdo->prepare('SELECT * FROM profil_conducteur WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new \RuntimeException("Profil conducteur introuvable (id={$id}).");
        }

        return $this->hydrate($row);
    }

    /**
     * Retourne le profil conducteur d'un utilisateur (tout statut), ou null.
     */
    public function findByUtilisateur(int $utilisateurId): ?ProfilConducteur
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM profil_conducteur WHERE utilisateur_id = :u LIMIT 1'
        );
        $stmt->execute([':u' => $utilisateurId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Insère un nouveau profil (id null) ou met à jour son statut.
     */
    public function save(ProfilConducteur $profil): void
    {
        if ($profil->getId() === null) {
            $this->insert($profil);
        } else {
            $this->update($profil);
        }
    }

    private function insert(ProfilConducteur $profil): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO profil_conducteur (utilisateur_id, numero_permis, statut, created_at, updated_at)
             VALUES (:u, :permis, :statut, :created, :updated)'
        );
        $stmt->execute([
            ':u'       => $profil->getUtilisateurId(),
            ':permis'  => $profil->getNumeroPermis(),
            ':statut'  => $profil->getStatut()->value,
            ':created' => $profil->getCreatedAt()->format('Y-m-d H:i:s'),
            ':updated' => $profil->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);
        $profil->setId((int) $this->pdo->lastInsertId());
    }

    private function update(ProfilConducteur $profil): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE profil_conducteur SET statut = :statut, updated_at = :updated WHERE id = :id'
        );
        $stmt->execute([
            ':statut'  => $profil->getStatut()->value,
            ':updated' => $profil->getUpdatedAt()->format('Y-m-d H:i:s'),
            ':id'      => $profil->getId(),
        ]);
    }

    private function hydrate(array $row): ProfilConducteur
    {
        $profil = new ProfilConducteur((int) $row['utilisateur_id'], $row['numero_permis']);
        $profil->setId((int) $row['id']);
        $profil->setStatut(StatutConducteur::from($row['statut']));
        $profil->setCreatedAt(new \DateTime($row['created_at']));
        $profil->setUpdatedAt(new \DateTime($row['updated_at']));

        return $profil;
    }
}
