<?php

namespace App\Controllers;

use App\Core\Database;
use App\Models\Administrateur;
use App\Repositories\UtilisateurRepository;
use App\Repositories\ProfilConducteurRepository;
use App\Repositories\SignalementRepository;
use App\Repositories\TrajetRepository;
use App\Repositories\ReservationRepository;
use App\Exceptions\AccesRefuseException;

/**
 * AdminController
 * Regroupe les actions de modération réservées à l'administrateur.
 *
 * Le contrôle d'accès est centralisé dans le constructeur : l'identité est
 * chargée comme un modèle Administrateur (sous-type polymorphe de Personne)
 * et l'on vérifie peutAdministrer(). Les actions s'appuient ensuite sur les
 * modèles métier (ProfilConducteur, Trajet, Reservation, Signalement) et leurs
 * repositories — aucune requête SQL n'est écrite ici directement.
 */
class AdminController
{
    private Administrateur $admin;
    private UtilisateurRepository $utilisateurs;
    private ProfilConducteurRepository $conducteurs;
    private SignalementRepository $signalements;
    private TrajetRepository $trajets;
    private ReservationRepository $reservations;
    private \PDO $pdo;

    /**
     * @throws AccesRefuseException si l'utilisateur n'est pas administrateur
     */
    public function __construct(int $utilisateurId)
    {
        $this->utilisateurs = new UtilisateurRepository();

        $admin = $this->utilisateurs->findAdministrateur($utilisateurId);
        if ($admin === null || !$admin->peutAdministrer()) {
            throw new AccesRefuseException("Cette action est réservée aux administrateurs.");
        }
        $this->admin = $admin;

        $this->conducteurs  = new ProfilConducteurRepository();
        $this->signalements = new SignalementRepository();
        $this->trajets      = new TrajetRepository();
        $this->reservations = new ReservationRepository();
        $this->pdo          = Database::getInstance()->getConnection();
    }

    /**
     * Valide un profil conducteur (il pourra désormais publier des trajets).
     */
    public function validerConducteur(int $profilId): void
    {
        $profil = $this->conducteurs->find($profilId);
        $this->admin->validerConducteur($profil);   // logique portée par le modèle Administrateur
        $this->conducteurs->save($profil);
    }

    /**
     * Refuse un profil conducteur.
     */
    public function rejeterConducteur(int $profilId): void
    {
        $profil = $this->conducteurs->find($profilId);
        $this->admin->rejeterConducteur($profil);
        $this->conducteurs->save($profil);
    }

    /**
     * Bannit (supprime physiquement) un utilisateur. Un administrateur ne peut
     * pas en bannir un autre.
     *
     * @throws AccesRefuseException si la cible est elle-même administrateur
     */
    public function bannir(int $utilisateurId): void
    {
        if ($this->utilisateurs->estAdmin($utilisateurId)) {
            throw new AccesRefuseException("Impossible de bannir un administrateur.");
        }

        $utilisateur = $this->utilisateurs->find($utilisateurId);   // \RuntimeException si absent
        $cibleId = $this->admin->bannirUtilisateur($utilisateur);   // valide la cible via le modèle
        $this->utilisateurs->delete($cibleId);                      // CASCADE en BDD
    }

    /**
     * Marque un signalement comme traité.
     */
    public function traiterSignalement(int $signalementId): void
    {
        $signalement = $this->signalements->find($signalementId);
        $signalement->marquerTraite();              // lève \LogicException si déjà traité
        $this->signalements->save($signalement);
    }

    /**
     * Annule un trajet et, par cascade métier, toutes ses réservations actives.
     */
    public function annulerTrajet(int $trajetId): void
    {
        $trajet       = $this->trajets->find($trajetId);
        $reservations = $this->reservations->findByTrajet($trajetId);

        // On rattache les réservations au trajet pour que annuler() cascade dessus
        foreach ($reservations as $reservation) {
            $trajet->ajouterReservation($reservation);
        }

        $this->pdo->beginTransaction();
        try {
            $trajet->annuler();   // passe annule=true et annule chaque réservation active (transitions)

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
}
