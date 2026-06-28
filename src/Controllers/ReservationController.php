<?php

namespace App\Controllers;

use App\Repositories\ReservationRepository;
use App\Models\Reservation;

class ReservationController
{
    private ReservationRepository $repository;

    public function __construct()
    {
        $this->repository = new ReservationRepository();
    }

    public function reserver(Reservation $reservation): void
    {
        $this->repository->save($reservation);
    }

    public function liste(): array
    {
        return $this->repository->findAll();
    }

    public function annuler(int $id): void
    {
        $this->repository->delete($id);
    }
}