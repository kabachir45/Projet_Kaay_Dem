# Kaay Dem !

Plateforme de covoiturage étudiant développée dans le cadre du module de Programmation Orientée Objet.

---

## Présentation

**Kaay Dem !** est une plateforme de covoiturage destinée aux étudiants.

L'application permet de :

- gérer les comptes utilisateurs ;
- publier des trajets ;
- réserver des places ;
- évaluer les conducteurs ;
- signaler des comportements ou incidents.

Le projet a été développé en PHP natif en appliquant les principes de la programmation orientée objet.

---

## Choix du projet

Deux sujets étaient proposés :

- **Kaay Dem !** : plateforme de covoiturage ;
- **CityAlert** : plateforme de signalement citoyen.

Notre choix s'est porté sur **Kaay Dem !** pour les raisons suivantes :

- une modélisation objet plus riche ;
- une problématique concrète pour les étudiants de l'ISM Campus Baobab ;
- une meilleure exploitation des concepts de la programmation orientée objet (composition, interfaces, traits, énumérations, etc.).

---

## Comparaison avec CityAlert

| Critère | Kaay Dem ! | CityAlert |
|---|---|---|
| Domaine | Covoiturage étudiant | Signalement citoyen |
| Acteurs | Conducteur, Passager, Administrateur | Citoyen, Agent municipal, Administrateur |
| Complexité métier | Élevée | Moyenne |
| Double rôle utilisateur | Oui | Non |
| Cycle métier | Réservation et évaluation | Signalement et commentaires |
| Pertinence locale | Forte | Moyenne |

---

## Architecture UML

Le modèle est constitué des principales classes suivantes :

```
Personne (classe abstraite)
├── Utilisateur
│   ├── ProfilConducteur
│   │   └── Vehicule
│   ├── ProfilPassager
│   └── Trajet
│       └── Reservation
│           └── Evaluation
├── Administrateur
└── Signalement
```

`Utilisateur` et `Administrateur` **héritent** de la classe abstraite `Personne`
(identité + gestion sécurisée du mot de passe), qui définit deux méthodes
**polymorphes** : `getRole()` et `peutAdministrer()`. Le double rôle
conducteur/passager, lui, est géré par **composition** (un `Utilisateur` agrège
0..1 `ProfilConducteur` et 0..1 `ProfilPassager`).

Le projet comprend également :

- la classe abstraite `Personne` (héritage + polymorphisme) ;
- le trait `Timestampable` ;
- l'interface `RepositoryInterface` ;
- l'interface `EvaluableInterface` ;
- l'énumération `StatutReservation` ;
- l'énumération `StatutConducteur` ;
- une hiérarchie d'**exceptions personnalisées** sous `App\Exceptions`
  (`KaayDemException` abstraite → `PlacesInsuffisantesException`,
  `ReservationConflitException`, `TransitionInvalideException`,
  `LimiteVehiculesException`, `NoteInvalideException`).

---

## Choix de conception

| Élément | Solution retenue | Justification |
|---|---|---|
| Double rôle conducteur / passager | Composition | Un utilisateur peut exercer les deux rôles simultanément. |
| Conducteur → Véhicules | Composition (maximum deux véhicules) | Respect des contraintes métier. |
| Trajet → Réservation | Association | Conservation de l'historique des réservations. |
| Réservation → Évaluation | Association facultative | Une évaluation n'est pas obligatoire. |
| Suppression d'un utilisateur | Suppression physique | Simplification de l'architecture du projet. |

---

## Choix d'architecture — routage et point d'entrée

Le projet applique le patron **MVC** avec une séparation stricte des
responsabilités :

- **Vues** (`src/Views/`) : présentation uniquement. Elles ne contiennent aucune
  requête SQL ni logique métier ; elles délèguent toute action à un contrôleur.
- **Contrôleurs** (`src/Controllers/`) : orchestrent la logique métier, le
  contrôle d'accès par rôle et la gestion des exceptions.
- **Modèles + Repositories** (`src/Models/`, `src/Repositories/`) : entités
  métier et accès aux données via PDO (requêtes préparées).

**Sur le « routeur maison » et le point d'entrée unique.** La brique
`App\Core\Router` (`src/Core/Router.php`) implémente un routeur maison (table de
routes `méthode + URL → Controller@action`, extraction d'un paramètre numérique,
dispatch). Nous avons toutefois choisi de **servir l'application « par pages »**
plutôt que via un front-contrôleur unique :

- chaque vue est un point d'entrée léger qui instancie le contrôleur adéquat ;
- ce choix évite la réécriture d'URL (`.htaccess`/`mod_rewrite`) et garantit un
  fonctionnement identique sur n'importe quelle installation XAMPP, ce qui
  **fiabilise la démonstration** ;
- la séparation MVC — l'objectif pédagogique réel — reste pleinement respectée :
  aucune logique ni SQL dans les vues, tout passe par contrôleurs → modèles →
  repositories.

Le front-contrôleur `public/index.php` est donc neutralisé (il redirige vers la
page d'accueil) ; la classe `Router` est conservée comme démonstration du
concept. Faire transiter toutes les requêtes par `public/index.php` reste une
évolution possible sans remettre en cause la couche métier.

---

## Structure du projet

```
kaay-dem/
├── index.php                      # Page d'accueil (entrée de l'app, servie par pages)
├── public/
│   └── index.php                  # Front-contrôleur (démo du routeur, redirige vers l'app)
├── src/
│   ├── Core/                      # Infrastructure (Router maison, Database)
│   ├── Traits/
│   │   └── Timestampable.php
│   ├── Interfaces/
│   │   ├── RepositoryInterface.php
│   │   └── EvaluableInterface.php
│   ├── Enums/
│   │   ├── StatutReservation.php
│   │   └── StatutConducteur.php
│   ├── Models/
│   │   ├── Utilisateur.php
│   │   ├── ProfilConducteur.php
│   │   ├── ProfilPassager.php
│   │   ├── Administrateur.php
│   │   ├── Vehicule.php
│   │   ├── Trajet.php
│   │   ├── Reservation.php
│   │   ├── Evaluation.php
│   │   └── Signalement.php
│   ├── Repositories/
│   ├── Controllers/
│   └── Views/
├── config/
│   └── database.php
└── composer.json
```

---

## Avancement de l'implémentation

###  Étape 1 — Fondations

Mise en place de l'autoload PSR-4 via Composer et des éléments transversaux :

| Fichier | Rôle |
|---|---|
| `composer.json` | Autoload PSR-4 — namespace `App\` mappé sur `src/` |
| `Traits/Timestampable.php` | Injecte `createdAt` / `updatedAt` dans toutes les entités via `use Timestampable` |
| `Interfaces/RepositoryInterface.php` | Contrat CRUD (`find`, `findAll`, `save`, `delete`) imposé à tous les repositories |
| `Interfaces/EvaluableInterface.php` | Contrat (`getNote`, `getEvaluations`) imposé à `ProfilConducteur` |
| `Enums/StatutReservation.php` | 4 statuts + méthode `peutTransitionnerVers()` pour valider les transitions |
| `Enums/StatutConducteur.php` | 3 statuts + méthode `estAutorise()` pour contrôler la publication de trajets |

###  Étape 2 — Modèles

Implémentation des neuf classes métier :

| Fichier | Points clés |
|---|---|
| `Models/Utilisateur.php` | Pas de `getMotDePasse()` — accès uniquement via `verifierMotDePasse()`. Méthodes `estConducteur()` / `estPassager()` pour la gestion des rôles. |
| `Models/ProfilConducteur.php` | Implémente `EvaluableInterface`. `ajouterVehicule()` lève `\OverflowException` au-delà de 2 véhicules. `activerVehicule()` désactive automatiquement les autres. |
| `Models/ProfilPassager.php` | Activable sans validation. Expose `getReservationsActives()` pour filtrer les réservations en cours. |
| `Models/Vehicule.php` | Lié à un `ProfilConducteur`. Attribut `actif` géré exclusivement par `ProfilConducteur::activerVehicule()`. |
| `Models/Administrateur.php` | Entité distincte d'`Utilisateur`. Expose `validerConducteur()`, `rejeterConducteur()`, `bannirUtilisateur()`. |
| `Models/Trajet.php` | `annuler()` cascade l'annulation sur toutes les réservations actives. `reserverPlace()` lève `\UnderflowException` si plus de places. |
| `Models/Reservation.php` | Toutes les transitions passent par `transitionner()` qui s'appuie sur `StatutReservation::peutTransitionnerVers()`. Toute transition invalide lève `\LogicException`. |
| `Models/Evaluation.php` | Note validée entre 1 et 5 à la construction. Liée à une unique réservation TERMINEE. |
| `Models/Signalement.php` | Vérifie à la construction que `rapporteurId !== signaleId`. `marquerTraite()` lève `\LogicException` si déjà traité. |

###  Étape 3 — Repositories

Mise en place de la couche d'accès aux données via PDO :

| Fichier | Points clés |
|---|---|
| `Core/Database.php` | Singleton PDO — une seule connexion partagée sur toute la durée de la requête. `__clone()` et `__wakeup()` privés pour verrouiller le singleton. |
| `config/database.php` | Configuration de la connexion (host, dbname, user, password). À ne pas versionner avec de vraies credentials. |
| `Repositories/UtilisateurRepository.php` | CRUD complet + `findByEmail()` pour la connexion + `emailExiste()` pour l'inscription. Accès au hash `motDePasse` via `ReflectionProperty` pour préserver l'encapsulation. |
| `Repositories/TrajetRepository.php` | CRUD complet + `rechercher()` (ville départ, arrivée, date) + `findByConducteur()`. Restauration de `annule` via réflexion à l'hydratation. |
| `Repositories/ReservationRepository.php` | CRUD complet + `findByTrajet()` + `findByPassager()` + `existeDeja()` pour éviter les doublons. Restauration du `statut` via réflexion à l'hydratation. |

**Note sur `ReflectionProperty`** : les champs `motDePasse`, `annule` et `statut` n'ont pas de setter public par choix de conception (encapsulation stricte). Les repositories utilisent `ReflectionProperty` pour accéder à ces champs lors de la persistance et de la reconstruction depuis la base de données, sans exposer de getter ou setter non souhaité.

###  Étape 4 — Contrôleurs et flux MVC

| Élément | Points clés |
|---|---|
| `Core/Router.php` · `public/index.php` | Routeur maison + point d'entrée unique (déclaration des routes). |
| `Controllers/ReservationController.php` | **Flux de réservation câblé sur la couche objet** : les vues `reserver.php` (création) et `mes_reservations.php` (annulation avec restitution de la place) délèguent au contrôleur, qui orchestre `ProfilPassagerRepository`, `TrajetRepository` et `ReservationRepository`, manipule les modèles `Reservation`/`Trajet` et l'énum `StatutReservation`, le tout en transaction. Les erreurs métier remontent via les exceptions `App\Exceptions` et sont affichées proprement (message flash). |
| `Controllers/TrajetController.php` | **Publication, recherche et édition** : `publier_trajet.php` (création, rôle vérifié → `ConducteurNonAutoriseException`), `rechercher_trajet.php` (recherche filtrée + paginée via `rechercheAvancee`), et `modifier_trajet.php` (édition réservée au conducteur, **bloquée si une réservation est confirmée** — cf. sujet — coordonnées préservées). Validation via `DonneesInvalidesException`, persistance via `TrajetRepository`. |
| `Controllers/EvaluationController.php` | **Flux d'évaluation câblé sur la couche objet** : la vue `evaluer.php` délègue au contrôleur, qui vérifie via `EvaluationRepository` que la réservation est *terminée* et appartient bien au passager, construit le modèle `Evaluation` (validation de la note 1..5 → `NoteInvalideException`) et l'enregistre. |
| `Controllers/AdminController.php` | **Modération câblée sur la couche objet** : la vue `admin.php` délègue ses actions (valider/refuser un conducteur, bannir, traiter un signalement, annuler un trajet) au contrôleur. Le contrôle d'accès est centralisé : l'admin est chargé comme un modèle `Administrateur` (sous-type polymorphe de `Personne`) et `peutAdministrer()` est vérifié (`AccesRefuseException` sinon). Les actions s'appuient sur les modèles `ProfilConducteur`, `Trajet`/`Reservation` (annulation en cascade) et `Signalement`. |
| `Controllers/SignalementController.php` | **Dépôt de signalement câblé** : la vue `signaler.php` délègue au contrôleur, qui valide (pas d'auto-signalement, motif obligatoire, cible existante → `DonneesInvalidesException`), construit le modèle `Signalement` et l'enregistre via `SignalementRepository`. |
| `Controllers/ConducteurController.php` | **Demande pour devenir conducteur câblée** : la vue `devenir_conducteur.php` délègue au contrôleur, qui crée le modèle `ProfilConducteur` (statut *en attente*) et, optionnellement, un premier `Vehicule` (règle « max 2 » via `LimiteVehiculesException`), en transaction (`ProfilConducteurRepository` + `VehiculeRepository`). |
| `Controllers/ReservationController.php` *(côté conducteur)* | `mes_trajets.php` délègue aussi au contrôleur les actions du conducteur sur les réservations reçues : **confirmer**, **terminer**, **confirmer le paiement** (transitions du modèle `Reservation`), avec contrôle d'appartenance du trajet. L'**annulation d'un trajet** (cascade sur ses réservations) passe par `TrajetController::annulerParConducteur()`. |
| `Controllers/UtilisateurController.php` | **Gestion du profil câblée** : `profil.php` délègue la mise à jour des informations et le **changement de mot de passe** (encapsulé dans `Utilisateur::changerMotDePasse()`, sans jamais exposer le hash). |

> **Tous les flux passent désormais par l'architecture MVC** (vues →
> contrôleurs → modèles → repositories). Les vues ne contiennent plus de SQL :
> les mutations sont déléguées aux contrôleurs, et la lecture (recherche,
> tableaux de bord) passe par des méthodes de repository.
>
> La **recherche** (`rechercher_trajet.php` → `TrajetController::rechercher()` →
> `TrajetRepository::rechercheAvancee()`) gère les filtres **ville / date /
> prix maximum / places minimum** et la **pagination**, comme demandé par le sujet.

---


## Simplifications retenues

Dans le cadre d'un projet académique, une fonctionnalité a été volontairement simplifiée :

| Fonctionnalité | Approche retenue | Raison |
|---|---|---|
| Paiement en ligne | Prix stocké en `decimal` sur le trajet, règlement en dehors de la plateforme | L'intégration d'un gateway (Stripe, PayPal) est hors scope d'un projet POO PHP |

> La **carte des trajets** (fonctionnalité bonus du sujet) a été implémentée avec
> **Leaflet + OpenStreetMap** : les trajets disposant de coordonnées
> (`lat_depart`/`lng_depart`/`lat_arrivee`/`lng_arrivee`, cf. `migration_coords.sql`)
> sont affichés sur une carte interactive dans la recherche.

## Technologies utilisées

- PHP 8.1+
- Programmation Orientée Objet
- UML (draw.io)
- Interfaces
- Traits
- Énumérations (enums)
- Architecture MVC
- Architecture Repository
- Composer (autoload PSR-4)

---

## Auteurs

Projet réalisé dans le cadre du module **PHP Programmation Orientée Objet**.

**Établissement :** ISM Campus Baobab

**Année universitaire :** 2025-2026

| Mamadou SY | Fatoumata Ouedraogo | Mohamed El Bachir KA |
|---|---|---|