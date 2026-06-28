<?php

namespace App\Controllers;

use App\Repositories\TrajetRepository;
use App\Models\Trajet;

class TrajetController
{
    private TrajetRepository $repository;

    public function __construct()
    {
        $this->repository = new TrajetRepository();
    }

    public function publier(Trajet $trajet): void
    {
        $this->repository->save($trajet);
    }

    public function liste(): array
    {
        return $this->repository->findAll();
    }

    public function supprimer(int $id): void
    {
        $this->repository->delete($id);
    }
}