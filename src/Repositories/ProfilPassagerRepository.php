<?php

namespace App\Repositories;

use App\Core\Database;

/**
 * ProfilPassagerRepository
 * Accès aux profils passager. Repository volontairement réduit aux
 * opérations réellement utilisées (résolution / création à la volée),
 * toujours via PDO et requêtes préparées.
 */
class ProfilPassagerRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Retourne l'id du profil passager d'un utilisateur, ou null s'il n'en a pas.
     */
    public function findIdByUtilisateur(int $utilisateurId): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM profil_passager WHERE utilisateur_id = :u LIMIT 1'
        );
        $stmt->execute([':u' => $utilisateurId]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /**
     * Retourne l'id du profil passager de l'utilisateur, en le créant si besoin.
     * Tout utilisateur peut devenir passager sans validation (cf. ProfilPassager).
     */
    public function resoudreOuCreer(int $utilisateurId): int
    {
        $existant = $this->findIdByUtilisateur($utilisateurId);
        if ($existant !== null) {
            return $existant;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO profil_passager (utilisateur_id) VALUES (:u)'
        );
        $stmt->execute([':u' => $utilisateurId]);

        return (int) $this->pdo->lastInsertId();
    }
}
