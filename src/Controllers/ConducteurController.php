<?php

namespace App\Controllers;

use App\Core\Database;
use App\Repositories\ProfilConducteurRepository;
use App\Repositories\VehiculeRepository;
use App\Models\ProfilConducteur;
use App\Models\Vehicule;
use App\Exceptions\DonneesInvalidesException;

/**
 * ConducteurController
 * Gère la demande pour devenir conducteur : création du profil conducteur
 * (en attente de validation) et, optionnellement, d'un premier véhicule.
 */
class ConducteurController
{
    private ProfilConducteurRepository $conducteurs;
    private VehiculeRepository $vehicules;
    private \PDO $pdo;

    public function __construct()
    {
        $this->conducteurs = new ProfilConducteurRepository();
        $this->vehicules   = new VehiculeRepository();
        $this->pdo         = Database::getInstance()->getConnection();
    }

    /**
     * Profil conducteur de l'utilisateur (tout statut), ou null s'il n'en a pas.
     */
    public function profilDe(int $utilisateurId): ?ProfilConducteur
    {
        return $this->conducteurs->findByUtilisateur($utilisateurId);
    }

    /**
     * Soumet une demande pour devenir conducteur.
     *
     * @param array<string, mixed> $data Champs du formulaire (permis + véhicule optionnel).
     * @throws DonneesInvalidesException si le permis manque ou si un profil existe déjà
     */
    public function devenir(int $utilisateurId, array $data): ProfilConducteur
    {
        if ($this->conducteurs->findByUtilisateur($utilisateurId) !== null) {
            throw new DonneesInvalidesException('Vous avez déjà un profil conducteur.');
        }

        $numeroPermis = trim((string) ($data['numero_permis'] ?? ''));
        if ($numeroPermis === '') {
            throw new DonneesInvalidesException('Le numéro de permis est obligatoire.');
        }

        // Véhicule optionnel : on ne le crée que si tous les champs sont renseignés
        $marque = trim((string) ($data['marque'] ?? ''));
        $modele = trim((string) ($data['modele'] ?? ''));
        $immat  = trim((string) ($data['immatriculation'] ?? ''));
        $places = (int) ($data['nombre_places'] ?? 0);
        $avecVehicule = ($marque !== '' && $modele !== '' && $immat !== '' && $places > 0);

        $profil = new ProfilConducteur($utilisateurId, $numeroPermis); // statut EN_ATTENTE

        $this->pdo->beginTransaction();
        try {
            $this->conducteurs->save($profil);   // attribue l'id du profil

            if ($avecVehicule) {
                $vehicule = new Vehicule($profil->getId(), $marque, $modele, $immat, $places);
                $profil->ajouterVehicule($vehicule);     // règle « max 2 » (LimiteVehiculesException)
                $profil->activerVehicule($vehicule);     // 1er véhicule actif par défaut
                $this->vehicules->save($vehicule);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return $profil;
    }
}
