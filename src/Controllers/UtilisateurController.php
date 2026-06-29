<?php

namespace App\Controllers;

use App\Repositories\UtilisateurRepository;
use App\Models\Utilisateur;
use App\Exceptions\DonneesInvalidesException;

/**
 * UtilisateurController
 * Gère la consultation et la mise à jour du profil de l'utilisateur connecté
 * (informations personnelles et mot de passe), en s'appuyant sur le modèle
 * Utilisateur et son repository.
 */
class UtilisateurController
{
    private UtilisateurRepository $repository;

    public function __construct()
    {
        $this->repository = new UtilisateurRepository();
    }

    /**
     * Charge l'utilisateur (modèle).
     * @throws \RuntimeException si introuvable
     */
    public function profil(int $id): Utilisateur
    {
        return $this->repository->find($id);
    }

    /**
     * Met à jour les informations personnelles.
     *
     * @throws DonneesInvalidesException si nom ou prénom manquant
     */
    public function modifierProfil(int $utilisateurId, array $data): Utilisateur
    {
        $nom       = trim((string) ($data['nom'] ?? ''));
        $prenom    = trim((string) ($data['prenom'] ?? ''));
        $telephone = trim((string) ($data['telephone'] ?? ''));

        if ($nom === '' || $prenom === '') {
            throw new DonneesInvalidesException('Le nom et le prénom sont obligatoires.');
        }

        $utilisateur = $this->repository->find($utilisateurId);
        $utilisateur->setNom($nom);
        $utilisateur->setPrenom($prenom);
        $utilisateur->setTelephone($telephone);

        $this->repository->save($utilisateur);

        return $utilisateur;
    }

    /**
     * Change le mot de passe après vérification de l'ancien.
     * La vérification et le re-hachage sont encapsulés dans le modèle
     * (Utilisateur::changerMotDePasse) : le hash n'est jamais exposé.
     *
     * @throws DonneesInvalidesException si confirmation invalide, trop court,
     *         ou ancien mot de passe incorrect
     */
    public function changerMotDePasse(int $utilisateurId, string $ancien, string $nouveau, string $confirmation): void
    {
        if ($nouveau !== $confirmation) {
            throw new DonneesInvalidesException('Les mots de passe ne correspondent pas.');
        }
        if (strlen($nouveau) < 8) {
            throw new DonneesInvalidesException('Le mot de passe doit faire au moins 8 caractères.');
        }

        $utilisateur = $this->repository->find($utilisateurId);

        if (!$utilisateur->changerMotDePasse($ancien, $nouveau)) {
            throw new DonneesInvalidesException('Ancien mot de passe incorrect.');
        }

        $this->repository->save($utilisateur);
    }
}
