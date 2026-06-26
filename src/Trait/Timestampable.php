<?php

namespace App\Traits;

/**
 * Trait Timestampable
 * Ajoute les champs createdAt et updatedAt à toute entité qui l'utilise.
 * Usage : use Timestampable; dans la classe concernée.
 */
trait Timestampable
{
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Initialise les timestamps à maintenant.
     * À appeler dans le constructeur de la classe hôte.
     */
    public function initTimestamps(): void
    {
        $now = new \DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    /**
     * Met à jour updatedAt à maintenant.
     * À appeler avant chaque save().
     */
    public function touch(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
