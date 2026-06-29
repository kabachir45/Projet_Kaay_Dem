<?php

namespace App\Repositories;

use App\Core\Database;
use App\Interfaces\RepositoryInterface;
use App\Models\Utilisateur;
use App\Models\Administrateur;

/**
 * UtilisateurRepository
 * Gère toutes les opérations en base de données liées à Utilisateur.
 * Utilise exclusivement PDO — aucune dépendance à un ORM.
 */
class UtilisateurRepository implements RepositoryInterface
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // ── RepositoryInterface ───────────────────────────────────────────────────

    /**
     * Trouve un utilisateur par son ID.
     * @throws \RuntimeException si non trouvé
     */
    public function find(int $id): Utilisateur
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM utilisateurs WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new \RuntimeException("Utilisateur introuvable (id={$id}).");
        }

        return $this->hydrate($row);
    }

    /**
     * Retourne tous les utilisateurs.
     * @return Utilisateur[]
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM utilisateurs ORDER BY nom, prenom');
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll());
    }

    /**
     * Insère ou met à jour un utilisateur.
     * Si id est null → INSERT, sinon → UPDATE.
     */
    public function save(object $entity): void
    {
        /** @var Utilisateur $entity */
        if ($entity->getId() === null) {
            $this->insert($entity);
        } else {
            $this->update($entity);
        }
    }

    /**
     * Suppression physique — utilisée notamment pour le bannissement.
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM utilisateurs WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    // ── Requêtes métier supplémentaires ───────────────────────────────────────

    /**
     * Trouve un utilisateur par son email (utilisé pour la connexion).
     */
    public function findByEmail(string $email): ?Utilisateur
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM utilisateurs WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Vérifie si un email est déjà utilisé.
     */
    public function emailExiste(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM utilisateurs WHERE email = :email'
        );
        $stmt->execute([':email' => $email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Indique si l'utilisateur est administrateur.
     */
    public function estAdmin(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT est_admin FROM utilisateurs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Indique si l'utilisateur existe et n'est pas administrateur
     * (cible valide d'un signalement).
     */
    public function existeNonAdmin(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM utilisateurs WHERE id = :id AND est_admin = 0');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Liste les utilisateurs non-administrateurs (pour les listes déroulantes),
     * en excluant éventuellement un id (typiquement l'utilisateur courant).
     * @return array<int, array<string, mixed>>
     */
    public function listerNonAdmins(?int $exclureId = null): array
    {
        $sql    = 'SELECT id, nom, prenom FROM utilisateurs WHERE est_admin = 0';
        $params = [];
        if ($exclureId !== null) {
            $sql .= ' AND id != :exclure';
            $params[':exclure'] = $exclureId;
        }
        $sql .= ' ORDER BY prenom, nom';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Charge un administrateur (utilisateur avec est_admin = 1) sous forme de modèle.
     * Retourne null si l'id ne correspond pas à un administrateur.
     */
    public function findAdministrateur(int $id): ?Administrateur
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM utilisateurs WHERE id = :id AND est_admin = 1 LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $admin = new Administrateur($row['nom'], $row['prenom'], $row['email'], 'placeholder');
        $admin->setId((int) $row['id']);
        $admin->setMotDePasseHash($row['mot_de_passe']);
        $admin->setCreatedAt(new \DateTime($row['created_at']));
        $admin->setUpdatedAt(new \DateTime($row['updated_at']));

        return $admin;
    }

    // ── Persistance interne ───────────────────────────────────────────────────

    private function insert(Utilisateur $utilisateur): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, photo, created_at, updated_at)
             VALUES (:nom, :prenom, :email, :mdp, :tel, :photo, :created, :updated)'
        );

        $stmt->execute($this->buildParams($utilisateur));

        $utilisateur->setId((int) $this->pdo->lastInsertId());
    }

    private function update(Utilisateur $utilisateur): void
    {
        $utilisateur->touch();

        $stmt = $this->pdo->prepare(
            'UPDATE utilisateurs
             SET nom = :nom, prenom = :prenom, email = :email,
                 mot_de_passe = :mdp, telephone = :tel,
                 photo = :photo, updated_at = :updated
             WHERE id = :id'
        );

        $params = $this->buildParams($utilisateur);
        unset($params[':created']);   // created_at n'est pas modifié en UPDATE
        $params[':id'] = $utilisateur->getId();
        $stmt->execute($params);
    }

    /**
     * Construit le tableau de paramètres PDO à partir d'un Utilisateur.
     * On accède au hash via la réflexion pour éviter d'exposer un getter public.
     */
    private function buildParams(Utilisateur $utilisateur): array
    {
        // Accès au hash via ReflectionProperty — le getter public n'existe pas intentionnellement
        $ref  = new \ReflectionProperty(Utilisateur::class, 'motDePasse');
        $ref->setAccessible(true);
        $hash = $ref->getValue($utilisateur);

        return [
            ':nom'     => $utilisateur->getNom(),
            ':prenom'  => $utilisateur->getPrenom(),
            ':email'   => $utilisateur->getEmail(),
            ':mdp'     => $hash,
            ':tel'     => $utilisateur->getTelephone(),
            ':photo'   => $utilisateur->getPhoto(),
            ':created' => $utilisateur->getCreatedAt()->format('Y-m-d H:i:s'),
            ':updated' => $utilisateur->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Reconstruit un objet Utilisateur à partir d'une ligne BDD.
     * Utilise setMotDePasseHash() pour injecter le hash sans re-hasher.
     */
    private function hydrate(array $row): Utilisateur
    {
        $utilisateur = new Utilisateur(
            $row['nom'],
            $row['prenom'],
            $row['email'],
            'placeholder',     // mot de passe temporaire — sera écrasé juste après
            $row['telephone']
        );

        $utilisateur->setId((int) $row['id']);
        $utilisateur->setMotDePasseHash($row['mot_de_passe']);
        $utilisateur->setPhoto($row['photo'] ?? null);
        $utilisateur->setCreatedAt(new \DateTime($row['created_at']));
        $utilisateur->setUpdatedAt(new \DateTime($row['updated_at']));

        return $utilisateur;
    }
}
