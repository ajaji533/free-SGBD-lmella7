USE parking_management;

SELECT p.nom AS parking, p.numero_type_adresse AS adresse, p.capacite, c.nom_commune, c.code_postal
FROM PARKINGS p
JOIN COMMUNES c ON p.id_commune = c.id_commune;

SELECT p.nom AS parking, t.tarif_horaire, t.debut_validite, t.fin_validite
FROM TARIFICATIONS t
JOIN PARKINGS p ON t.id_parking = p.id_parking
WHERE CURDATE() BETWEEN t.debut_validite AND t.fin_validite;

SELECT p.nom AS parking, COUNT(pl.id_place) AS nombre_places
FROM PARKINGS p
JOIN PLACES pl ON p.id_parking = pl.id_parking
GROUP BY p.id_parking, p.nom;

SELECT v.immatriculation, v.marque, p.nom AS parking, pl.numero_place, s.date_jour, s.heure_entree
FROM STATIONNEMENTS s
JOIN VEHICULES v ON s.id_vehicule = v.id_vehicule
JOIN PLACES pl ON s.id_place = pl.id_place
JOIN PARKINGS p ON pl.id_parking = p.id_parking
WHERE s.heure_sortie IS NULL;

SELECT 
    v.immatriculation,
    p.nom AS parking,
    s.date_jour,
    s.heure_entree,
    s.heure_sortie,
    TIMEDIFF(s.heure_sortie, s.heure_entree) AS duree,
    ROUND((TIME_TO_SEC(TIMEDIFF(s.heure_sortie, s.heure_entree)) / 3600) * t.tarif_horaire, 2) AS cout_estime
FROM STATIONNEMENTS s
JOIN VEHICULES v ON s.id_vehicule = v.id_vehicule
JOIN PLACES pl ON s.id_place = pl.id_place
JOIN PARKINGS p ON pl.id_parking = p.id_parking
JOIN TARIFICATIONS t ON p.id_parking = t.id_parking
WHERE s.heure_sortie IS NOT NULL
AND CURDATE() BETWEEN t.debut_validite AND t.fin_validite;