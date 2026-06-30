-- Migration : ajout des coordonnées GPS sur les trajets
USE Kaay_Dem;

ALTER TABLE trajets
  ADD COLUMN lat_depart  FLOAT DEFAULT NULL AFTER points_arret,
  ADD COLUMN lng_depart  FLOAT DEFAULT NULL AFTER lat_depart,
  ADD COLUMN lat_arrivee FLOAT DEFAULT NULL AFTER lng_depart,
  ADD COLUMN lng_arrivee FLOAT DEFAULT NULL AFTER lat_arrivee,
  ADD COLUMN distance_km FLOAT DEFAULT NULL AFTER lng_arrivee,
  ADD COLUMN duree_min   INT   DEFAULT NULL AFTER distance_km;

-- Coordonnées GPS des trajets de démonstration (pour qu'ils s'affichent
-- sur la carte Leaflet de la recherche). Villes : Dakar Plateau, Diamniadio,
-- Rufisque, Thiès.
UPDATE trajets SET lat_depart=14.6708, lng_depart=-17.4381, lat_arrivee=14.7167, lng_arrivee=-17.1833, distance_km=30, duree_min=35
  WHERE ville_depart='Dakar Plateau' AND ville_arrivee='Diamniadio';
UPDATE trajets SET lat_depart=14.7167, lng_depart=-17.1833, lat_arrivee=14.6708, lng_arrivee=-17.4381, distance_km=30, duree_min=35
  WHERE ville_depart='Diamniadio' AND ville_arrivee='Dakar Plateau';
UPDATE trajets SET lat_depart=14.7158, lng_depart=-17.2730, lat_arrivee=14.7167, lng_arrivee=-17.1833, distance_km=12, duree_min=18
  WHERE ville_depart='Rufisque' AND ville_arrivee='Diamniadio';
UPDATE trajets SET lat_depart=14.6708, lng_depart=-17.4381, lat_arrivee=14.7910, lng_arrivee=-16.9256, distance_km=70, duree_min=75
  WHERE ville_depart='Dakar Plateau' AND ville_arrivee='Thiès';
