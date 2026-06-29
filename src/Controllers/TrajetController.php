<?php

namespace App\Controllers;

use App\Core\Database;
use App\Repositories\TrajetRepository;
use App\Repositories\ProfilConducteurRepository;
use App\Repositories\ReservationRepository;
use App\Models\Trajet;
use App\Enums\StatutReservation;
use App\Exceptions\ConducteurNonAutoriseException;
use App\Exceptions\DonneesInvalidesException;
use App\Exceptions\AccesRefuseException;

/**
 * TrajetController
 * Orchestre la publication et la consultation des trajets via la couche objet.
 * Aucune requête SQL ici : tout passe par les repositories et le modèle Trajet.
 */
class TrajetController
{
    private TrajetRepository $trajets;
    private ProfilConducteurRepository $conducteurs;
    private ReservationRepository $reservations;
    private \PDO $pdo;

    public function __construct()
    {
        $this->trajets      = new TrajetRepository();
        $this->conducteurs  = new ProfilConducteurRepository();
        $this->reservations = new ReservationRepository();
        $this->pdo          = Database::getInstance()->getConnection();
    }

    /**
     * Indique si l'utilisateur est conducteur validé (et peut donc publier).
     */
    public function profilConducteurValide(int $utilisateurId): ?int
    {
        return $this->conducteurs->findIdValideByUtilisateur($utilisateurId);
    }

    /**
     * Véhicules du conducteur, pour alimenter le formulaire.
     */
    public function vehiculesDe(int $conducteurId): array
    {
        return $this->conducteurs->findVehicules($conducteurId);
    }

    /**
     * Recherche paginée et filtrée de trajets disponibles.
     *
     * @param array<string, mixed> $criteres depart, arrivee, date, prix_max, places_min, page
     * @return array{trajets: array, total: int, page: int, par_page: int, pages: int}
     */
    public function rechercher(array $criteres): array
    {
        return $this->trajets->rechercheAvancee($criteres);
    }

    /**
     * Publie un nouveau trajet pour l'utilisateur connecté.
     *
     * @param array<string, mixed> $data Champs bruts issus du formulaire.
     * @throws ConducteurNonAutoriseException si l'utilisateur n'est pas conducteur validé
     * @throws DonneesInvalidesException      si le formulaire est incomplet/incohérent
     */
    public function publier(int $utilisateurId, array $data): Trajet
    {
        // 1. Contrôle d'accès par rôle : conducteur validé obligatoire
        $conducteurId = $this->conducteurs->findIdValideByUtilisateur($utilisateurId);
        if ($conducteurId === null) {
            throw new ConducteurNonAutoriseException();
        }

        // 2. Validation des données
        $villeDepart  = trim((string) ($data['ville_depart']  ?? ''));
        $villeArrivee = trim((string) ($data['ville_arrivee'] ?? ''));
        $dateDepart   = (string) ($data['date_depart']  ?? '');
        $heureDepart  = (string) ($data['heure_depart'] ?? '');
        $nbPlaces     = (int) ($data['nb_places']      ?? 0);
        $prix         = (float) ($data['prix_par_place'] ?? 0);

        if ($villeDepart === '' || $villeArrivee === '' || $dateDepart === '' || $heureDepart === '' || $nbPlaces < 1) {
            throw new DonneesInvalidesException('Veuillez remplir tous les champs obligatoires.');
        }
        if ($dateDepart < date('Y-m-d')) {
            throw new DonneesInvalidesException('La date de départ ne peut pas être dans le passé.');
        }
        if ($prix < 0) {
            throw new DonneesInvalidesException('Le prix ne peut pas être négatif.');
        }

        $dateHeure = new \DateTime($dateDepart . ' ' . $heureDepart . ':00');

        // 3. Construction du modèle Trajet
        $vehiculeId = (int) ($data['vehicule_id'] ?? 0) ?: null;

        $trajet = new Trajet(
            $conducteurId,
            $vehiculeId,
            $villeDepart,
            $villeArrivee,
            $dateHeure,
            $prix,
            $nbPlaces
        );

        $trajet->setPointsArret(trim((string) ($data['points_arret'] ?? '')) ?: null);
        $trajet->setDescription(trim((string) ($data['description'] ?? '')) ?: null);
        $trajet->setItineraire(
            $this->floatOrNull($data['lat_depart']  ?? ''),
            $this->floatOrNull($data['lng_depart']  ?? ''),
            $this->floatOrNull($data['lat_arrivee'] ?? ''),
            $this->floatOrNull($data['lng_arrivee'] ?? ''),
            $this->floatOrNull($data['distance_km'] ?? ''),
            ($data['duree_min'] ?? '') !== '' ? (int) $data['duree_min'] : null
        );

        // 4. Persistance
        $this->trajets->save($trajet);

        return $trajet;
    }

    /**
     * Annule un trajet du conducteur connecté et, par cascade, ses réservations actives.
     *
     * @throws AccesRefuseException si le trajet n'appartient pas à l'utilisateur
     * @throws \RuntimeException    si le trajet est introuvable
     */
    public function annulerParConducteur(int $trajetId, int $utilisateurId): void
    {
        $trajet = $this->trajetDuConducteur($trajetId, $utilisateurId);

        $reservations = $this->reservations->findByTrajet($trajetId);
        foreach ($reservations as $reservation) {
            $trajet->ajouterReservation($reservation);
        }

        $this->pdo->beginTransaction();
        try {
            $trajet->annuler();   // annule + cascade sur les réservations actives

            $this->trajets->save($trajet);
            foreach ($reservations as $reservation) {
                $this->reservations->save($reservation);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Charge un trajet pour édition en vérifiant qu'il appartient au conducteur.
     * @throws AccesRefuseException si le trajet n'est pas celui de l'utilisateur
     */
    public function chargerPourEdition(int $trajetId, int $utilisateurId): Trajet
    {
        return $this->trajetDuConducteur($trajetId, $utilisateurId);
    }

    /**
     * Indique si un trajet possède au moins une réservation confirmée
     * (auquel cas il ne peut plus être modifié).
     */
    public function aReservationConfirmee(int $trajetId): bool
    {
        foreach ($this->reservations->findByTrajet($trajetId) as $reservation) {
            if ($reservation->getStatut() === StatutReservation::CONFIRMEE) {
                return true;
            }
        }
        return false;
    }

    /**
     * Modifie un trajet du conducteur. Autorisé uniquement tant qu'aucune
     * réservation n'est confirmée (cf. sujet). Les coordonnées GPS existantes
     * sont préservées.
     *
     * @param array<string, mixed> $data
     * @throws AccesRefuseException     si le trajet n'appartient pas à l'utilisateur
     * @throws DonneesInvalidesException si une réservation est confirmée ou données invalides
     */
    public function modifier(int $trajetId, int $utilisateurId, array $data): Trajet
    {
        $trajet = $this->trajetDuConducteur($trajetId, $utilisateurId);

        if ($this->aReservationConfirmee($trajetId)) {
            throw new DonneesInvalidesException("Ce trajet a une réservation confirmée : il ne peut plus être modifié.");
        }

        $villeDepart  = trim((string) ($data['ville_depart']  ?? ''));
        $villeArrivee = trim((string) ($data['ville_arrivee'] ?? ''));
        $dateDepart   = (string) ($data['date_depart']  ?? '');
        $heureDepart  = (string) ($data['heure_depart'] ?? '');
        $prix         = (float) ($data['prix_par_place'] ?? 0);

        if ($villeDepart === '' || $villeArrivee === '' || $dateDepart === '' || $heureDepart === '') {
            throw new DonneesInvalidesException('Veuillez remplir tous les champs obligatoires.');
        }
        if ($dateDepart < date('Y-m-d')) {
            throw new DonneesInvalidesException('La date de départ ne peut pas être dans le passé.');
        }
        if ($prix < 0) {
            throw new DonneesInvalidesException('Le prix ne peut pas être négatif.');
        }

        $trajet->setVilleDepart($villeDepart);
        $trajet->setVilleArrivee($villeArrivee);
        $trajet->setDateDepart(new \DateTime($dateDepart . ' ' . $heureDepart . ':00'));
        $trajet->setPrix($prix);
        $trajet->setPointsArret(trim((string) ($data['points_arret'] ?? '')) ?: null);
        $trajet->setDescription(trim((string) ($data['description'] ?? '')) ?: null);

        $this->trajets->save($trajet);

        return $trajet;
    }

    /**
     * Charge un trajet en s'assurant qu'il appartient au conducteur connecté.
     * @throws AccesRefuseException si le trajet n'est pas celui de l'utilisateur
     */
    private function trajetDuConducteur(int $trajetId, int $utilisateurId): Trajet
    {
        $trajet = $this->trajets->find($trajetId);
        $profil = $this->conducteurs->find($trajet->getConducteurId());

        if ($profil->getUtilisateurId() !== $utilisateurId) {
            throw new AccesRefuseException("Ce trajet ne fait pas partie de vos trajets.");
        }

        return $trajet;
    }

    private function floatOrNull(mixed $value): ?float
    {
        return ($value === '' || $value === null) ? null : (float) $value;
    }
}
