<?php

namespace App\Controllers;

use App\Core\Database;
use App\Repositories\ReservationRepository;
use App\Repositories\TrajetRepository;
use App\Repositories\ProfilPassagerRepository;
use App\Repositories\ProfilConducteurRepository;
use App\Models\Reservation;
use App\Exceptions\PlacesInsuffisantesException;
use App\Exceptions\ReservationConflitException;
use App\Exceptions\AccesRefuseException;

/**
 * ReservationController
 * Orchestre le cycle de réservation en s'appuyant sur la couche objet
 * (modèles + repositories + énumération + exceptions métier).
 *
 * C'est ici que vit la logique métier : la vue (reserver.php) se contente
 * d'appeler ce contrôleur et d'afficher le résultat — séparation MVC stricte.
 */
class ReservationController
{
    private ReservationRepository $reservations;
    private TrajetRepository $trajets;
    private ProfilPassagerRepository $passagers;
    private ProfilConducteurRepository $conducteurs;
    private \PDO $pdo;

    public function __construct()
    {
        $this->reservations = new ReservationRepository();
        $this->trajets      = new TrajetRepository();
        $this->passagers    = new ProfilPassagerRepository();
        $this->conducteurs  = new ProfilConducteurRepository();
        // Connexion partagée (singleton) : la transaction ci-dessous couvre
        // bien les écritures faites par les deux repositories.
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Réserve une place sur un trajet pour l'utilisateur connecté.
     *
     * @throws PlacesInsuffisantesException si le trajet est complet ou indisponible
     * @throws ReservationConflitException  si le passager a déjà réservé ce trajet
     * @throws \RuntimeException            si le trajet n'existe pas
     */
    public function reserver(int $trajetId, int $utilisateurId): Reservation
    {
        // 1. Profil passager (créé à la volée si l'utilisateur réserve pour la 1re fois)
        $passagerId = $this->passagers->resoudreOuCreer($utilisateurId);

        // 2. Charger le trajet (lève \RuntimeException si introuvable)
        $trajet = $this->trajets->find($trajetId);

        if ($trajet->estAnnule() || $trajet->getDateDepart() <= new \DateTime()) {
            throw new PlacesInsuffisantesException("Ce trajet n'est plus disponible.");
        }

        // 3. Pas de doublon (réservation déjà active sur ce trajet)
        if ($this->reservations->existeDeja($trajetId, $passagerId)) {
            throw new ReservationConflitException();
        }

        // 3 bis. Pas de chevauchement horaire avec une autre réservation active
        $debut = $trajet->getDateDepart();
        $duree = $trajet->getDureeMin() ?? 60;
        $fin   = (clone $debut)->modify("+{$duree} minutes");
        if ($this->reservations->existeChevauchement($passagerId, $debut, $fin, $trajetId)) {
            throw new ReservationConflitException();
        }

        // 4. Transaction : création de la réservation + décrément des places
        $this->pdo->beginTransaction();
        try {
            $reservation = new Reservation($trajetId, $passagerId); // statut EN_ATTENTE
            $trajet->reserverPlace();   // peut lever PlacesInsuffisantesException

            $this->reservations->save($reservation);
            $this->trajets->save($trajet);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return $reservation;
    }

    /**
     * Annule une réservation du passager connecté et restitue la place au trajet.
     *
     * @throws AccesRefuseException          si la réservation n'appartient pas à l'utilisateur
     * @throws \App\Exceptions\TransitionInvalideException si la réservation n'est pas annulable
     * @throws \RuntimeException             si la réservation est introuvable
     */
    public function annuler(int $reservationId, int $utilisateurId): void
    {
        $reservation = $this->reservations->find($reservationId);

        // Contrôle d'appartenance : la réservation doit être celle du passager connecté
        $passagerId = $this->passagers->findIdByUtilisateur($utilisateurId);
        if ($passagerId === null || $reservation->getPassagerId() !== $passagerId) {
            throw new AccesRefuseException("Cette réservation ne vous appartient pas.");
        }

        $this->pdo->beginTransaction();
        try {
            $reservation->annuler();   // EN_ATTENTE/CONFIRMEE → ANNULEE, sinon TransitionInvalideException

            // Restitution de la place réservée
            $trajet = $this->trajets->find($reservation->getTrajetId());
            $trajet->libererPlace();

            $this->reservations->save($reservation);
            $this->trajets->save($trajet);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // ── Actions côté conducteur ───────────────────────────────────────────────

    /**
     * Confirme une réservation reçue (EN_ATTENTE → CONFIRMEE).
     */
    public function confirmerParConducteur(int $reservationId, int $utilisateurId): void
    {
        $reservation = $this->reservationDuConducteur($reservationId, $utilisateurId);
        $reservation->confirmer();
        $this->reservations->save($reservation);
    }

    /**
     * Marque une réservation comme terminée (CONFIRMEE → TERMINEE).
     */
    public function terminerParConducteur(int $reservationId, int $utilisateurId): void
    {
        $reservation = $this->reservationDuConducteur($reservationId, $utilisateurId);
        $reservation->terminer();
        $this->reservations->save($reservation);
    }

    /**
     * Confirme le règlement d'une réservation terminée.
     */
    public function confirmerPaiement(int $reservationId, int $utilisateurId): void
    {
        $reservation = $this->reservationDuConducteur($reservationId, $utilisateurId);
        $reservation->confirmerPaiement();
        $this->reservations->save($reservation);
    }

    /**
     * Charge une réservation en s'assurant qu'elle porte sur un trajet
     * appartenant bien au conducteur connecté.
     *
     * @throws AccesRefuseException si le trajet n'appartient pas à l'utilisateur
     * @throws \RuntimeException    si la réservation ou le trajet est introuvable
     */
    private function reservationDuConducteur(int $reservationId, int $utilisateurId): Reservation
    {
        $reservation = $this->reservations->find($reservationId);
        $trajet      = $this->trajets->find($reservation->getTrajetId());
        $profil      = $this->conducteurs->find($trajet->getConducteurId());

        if ($profil->getUtilisateurId() !== $utilisateurId) {
            throw new AccesRefuseException("Cette réservation ne porte pas sur l'un de vos trajets.");
        }

        return $reservation;
    }
}
