<?php

namespace App\Controllers;

use App\Repositories\UtilisateurRepository;

class UtilisateurController
{
    private UtilisateurRepository $repository;

    public function __construct()
    {
        $this->repository = new UtilisateurRepository();
    }

    public function profil(int $id)
    {
        return $this->repository->find($id);
    }

    public function modifier($utilisateur): void
    {
        $this->repository->save($utilisateur);
    }

    public function supprimer(int $id): void
    {
        $this->repository->delete($id);
    }
}