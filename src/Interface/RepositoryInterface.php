<?php

namespace App\Interfaces;

/**
 * Interface RepositoryInterface
 */
interface RepositoryInterface
{
    /**
     * Trouve une entité par son identifiant.
     */
    public function find(int $id): mixed;

    /**
     * Retourne toutes les entités.
     */
    public function findAll(): array;

    /**
     * Persiste une entité (insertion ou mise à jour).
     */
    public function save(object $entity): void;

    /**
     * Supprime une entité par son identifiant.
     */
    public function delete(int $id): void;
}
