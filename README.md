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
Utilisateur
├── ProfilConducteur
│   └── Vehicule
├── ProfilPassager
├── Trajet
│   └── Reservation
│       └── Evaluation
├── Signalement
└── Administrateur
```

Le projet comprend également :

- le trait `Timestampable` ;
- l'interface `RepositoryInterface` ;
- l'interface `EvaluableInterface` ;
- l'énumération `StatutReservation` ;
- l'énumération `StatutConducteur`.

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

## Structure du projet

```
kaay-dem/
├── public/
│   └── index.php                  # Point d'entrée unique
├── src/
│   ├── Core/                      # Infrastructure (Router, Database)
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

###  Étape 4 — Core en cours

`Router` · `index.php`

---


## Simplifications retenues

Dans le cadre d'un projet académique, deux fonctionnalités ont été volontairement simplifiées :

| Fonctionnalité | Approche retenue | Raison |
|---|---|---|
| Géolocalisation / Maps | Champs texte `ville_depart` et `ville_arrivee` | Une intégration Google Maps ou OpenStreetMap nécessiterait une clé API externe |
| Paiement en ligne | Prix stocké en `float` sur le trajet, règlement en dehors de la plateforme | L'intégration d'un gateway (Stripe, PayPal) est hors scope d'un projet POO PHP |

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