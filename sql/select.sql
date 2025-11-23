-- ============================================================
-- 1. CONSULTATION
-- ============================================================

-- Informations sur les parkings (Nom, Adresse, Capacité, Commune)
SELECT 
    p.name AS nom_parking, 
    p.address AS adresse, 
    p.capacity AS capacite, 
    m.name AS commune
FROM parkings p
JOIN municipalities m ON p.id_municipality = m.id_municipality;

-- Informations sur les voitures (Toutes les voitures enregistrées)
SELECT * FROM vehicles;

-- Informations sur les places (Nombre de places physiques par parking)
SELECT 
    p.name AS nom_parking, 
    COUNT(pl.id_place) AS nombre_places_installees
FROM parkings p
JOIN places pl ON p.id_parking = pl.id_parking
GROUP BY p.name;

-- Liste des voitures par parking (Actuellement garées)
SELECT 
    p.name AS nom_parking, 
    v.license_plate AS immatriculation, 
    v.model AS modele, 
    s.entry_datetime AS heure_entree
FROM parkings_sessions s
JOIN places pl ON s.id_place = pl.id_place
JOIN parkings p ON pl.id_parking = p.id_parking
JOIN vehicles v ON s.id_vehicle = v.id_vehicle
WHERE s.exit_datetime IS NULL
ORDER BY p.name;

-- Liste des parkings par commune
SELECT 
    m.name AS commune, 
    p.name AS nom_parking
FROM parkings p
JOIN municipalities m ON p.id_municipality = m.id_municipality
ORDER BY m.name;

-- Liste des parkings qui sont saturés à un jour donné (Ex: 2025-11-05)
SELECT 
    p.name AS nom_parking,
    p.capacity AS capacite,
    COUNT(DISTINCT s.id_parking_session) AS occupation_jour
FROM parkings p
JOIN places pl ON p.id_parking = pl.id_parking
JOIN parkings_sessions s ON pl.id_place = s.id_place
WHERE DATE(s.entry_datetime) = '2025-11-05'
GROUP BY p.id_parking, p.name, p.capacity
HAVING COUNT(DISTINCT s.id_parking_session) >= p.capacity;

-- Liste des places disponibles, par parking, à un moment donné (MAINTENANT)
SELECT 
    p.name AS nom_parking,
    p.capacity - COUNT(s.id_parking_session) AS places_libres
FROM parkings p
JOIN places pl ON p.id_parking = pl.id_parking
LEFT JOIN parkings_sessions s ON pl.id_place = s.id_place AND s.exit_datetime IS NULL
GROUP BY p.name, p.capacity;

-- Liste de voitures qui se sont garées dans deux parkings différents au cours d’une journée
SELECT 
    v.license_plate AS immatriculation, 
    COUNT(DISTINCT p.id_parking) AS nb_parkings_visites
FROM parkings_sessions s
JOIN places pl ON s.id_place = pl.id_place
JOIN parkings p ON pl.id_parking = p.id_parking
JOIN vehicles v ON s.id_vehicle = v.id_vehicle
WHERE DATE(s.entry_datetime) = CURRENT_DATE
GROUP BY v.license_plate
HAVING COUNT(DISTINCT p.id_parking) >= 2;

-- Toute autre requête : Tarifs horaires actuellement en vigueur
SELECT 
    p.name AS nom_parking, 
    t.price AS tarif_actuel, 
    t.entry_time AS debut_plage, 
    t.exit_time AS fin_plage
FROM fees t
JOIN parkings p ON t.id_parking = p.id_parking
WHERE CURRENT_TIME BETWEEN t.entry_time AND t.exit_time;


-- ============================================================
-- 2. STATISTIQUES
-- ============================================================

-- Moyenne du nombre de places disponibles par parking (Basé sur l'occupation actuelle)
SELECT 
    p.name AS nom_parking,
    AVG(p.capacity - (
        SELECT COUNT(*) 
        FROM parkings_sessions s2 
        JOIN places pl2 ON s2.id_place = pl2.id_place 
        WHERE pl2.id_parking = p.id_parking AND s2.exit_datetime IS NULL
    )) AS moyenne_places_libres
FROM parkings p
GROUP BY p.id_parking, p.name;

-- Durée moyenne de stationnement d’un véhicule par parking
SELECT 
    p.name AS nom_parking,
    AVG(s.exit_datetime - s.entry_datetime) AS duree_moyenne
FROM parkings_sessions s
JOIN places pl ON s.id_place = pl.id_place
JOIN parkings p ON pl.id_parking = p.id_parking
WHERE s.exit_datetime IS NOT NULL
GROUP BY p.name;

-- Coût moyen du stationnement d’un véhicule par mois
SELECT 
    TO_CHAR(s.exit_datetime, 'YYYY-MM') AS mois,
    AVG(
        (EXTRACT(EPOCH FROM (s.exit_datetime - s.entry_datetime)) / 3600) * t.price
    ) AS cout_moyen
FROM parkings_sessions s
JOIN places pl ON s.id_place = pl.id_place
JOIN parkings p ON pl.id_parking = p.id_parking
JOIN fees t ON p.id_parking = t.id_parking
WHERE s.exit_datetime IS NOT NULL
AND CAST(s.entry_datetime AS TIME) BETWEEN t.entry_time AND t.exit_time
GROUP BY TO_CHAR(s.exit_datetime, 'YYYY-MM');

-- Classement des parkings les moins utilisés (par nombre de sessions)
SELECT 
    p.name AS nom_parking,
    COUNT(s.id_parking_session) AS total_visites
FROM parkings p
JOIN places pl ON p.id_parking = pl.id_parking
LEFT JOIN parkings_sessions s ON pl.id_place = s.id_place
GROUP BY p.name
ORDER BY total_visites ASC;

-- Classement des parkings les plus rentables par commune et par mois
SELECT 
    m.name AS commune,
    TO_CHAR(s.exit_datetime, 'YYYY-MM') AS mois,
    p.name AS nom_parking,
    SUM(
        (EXTRACT(EPOCH FROM (s.exit_datetime - s.entry_datetime)) / 3600) * t.price
    ) AS chiffre_affaires
FROM parkings_sessions s
JOIN places pl ON s.id_place = pl.id_place
JOIN parkings p ON pl.id_parking = p.id_parking
JOIN municipalities m ON p.id_municipality = m.id_municipality
JOIN fees t ON p.id_parking = t.id_parking
WHERE s.exit_datetime IS NOT NULL
AND CAST(s.entry_datetime AS TIME) BETWEEN t.entry_time AND t.exit_time
GROUP BY m.name, mois, p.name
ORDER BY chiffre_affaires DESC;

-- Classement des communes les plus demandées par semaine
SELECT 
    m.name AS commune,
    EXTRACT(WEEK FROM s.entry_datetime) AS numero_semaine,
    COUNT(s.id_parking_session) AS total_visites
FROM parkings_sessions s
JOIN places pl ON s.id_place = pl.id_place
JOIN parkings p ON pl.id_parking = p.id_parking
JOIN municipalities m ON p.id_municipality = m.id_municipality
GROUP BY m.name, numero_semaine
ORDER BY total_visites DESC;