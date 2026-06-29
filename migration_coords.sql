-- Migration : ajout des coordonnées GPS sur les trajets
USE Kaay_Dem;

ALTER TABLE trajets
  ADD COLUMN lat_depart  FLOAT DEFAULT NULL AFTER points_arret,
  ADD COLUMN lng_depart  FLOAT DEFAULT NULL AFTER lat_depart,
  ADD COLUMN lat_arrivee FLOAT DEFAULT NULL AFTER lng_depart,
  ADD COLUMN lng_arrivee FLOAT DEFAULT NULL AFTER lat_arrivee,
  ADD COLUMN distance_km FLOAT DEFAULT NULL AFTER lng_arrivee,
  ADD COLUMN duree_min   INT   DEFAULT NULL AFTER distance_km;
