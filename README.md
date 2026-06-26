
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
- une problématique concrète pour les étudiants de l'ISM ;
- une meilleure exploitation des concepts de la programmation orientée objet (composition, interfaces, traits, énumérations, etc.).

---

## Comparaison avec CityAlert

| Critère | Kaay Dem ! | CityAlert |
|----------|------------|-----------|
| Domaine | Covoiturage étudiant | Signalement citoyen |
| Acteurs | Conducteur, Passager, Administrateur | Citoyen, Agent municipal, Administrateur |
| Complexité métier | Élevée | Moyenne |
| Double rôle utilisateur | Oui | Non |
| Cycle métier | Réservation et évaluation | Signalement et commentaires |
| Pertinence locale | Forte | Moyenne |

---

## Architecture UML


Le modèle est constitué des principales classes suivantes :


Utilisateur
├── ProfilConducteur
│   └── Vehicule
├── ProfilPassager
├── Trajet
│   └── Reservation
│       └── Evaluation
├── Signalement
└── Administrateur



Le projet comprend également :

* le trait `Timestampable` ;
* l'interface `RepositoryInterface` ;
* l'interface `EvaluableInterface` ;
* l'énumération `StatutReservation` ;
* l'énumération `StatutConducteur`.



## Choix de conception

| Élément                           | Solution retenue                     | Justification                                             |
| --------------------------------- | ------------------------------------ | --------------------------------------------------------- |
| Double rôle conducteur / passager | Composition                          | Un utilisateur peut exercer les deux rôles simultanément. |
| Conducteur → Véhicules            | Composition (maximum deux véhicules) | Respect des contraintes métier.                           |
| Trajet → Réservation              | Association                          | Conservation de l'historique des réservations.            |
| Réservation → Évaluation          | Association facultative              | Une évaluation n'est pas obligatoire.                     |
| Suppression d'un utilisateur      | Suppression physique                 | Simplification de l'architecture du projet.               |

---

## Technologies utilisées

* PHP 8
* Programmation Orientée Objet
* UML
* Interfaces
* Traits
* Énumérations
* Architecture Repository

---

## Auteurs


Projet réalisé dans le cadre du module **PHP Programmation Orientée Objet**.

**Établissement :** ISM Campus Baobab

**Année universitaire :** 2025-2026

```

