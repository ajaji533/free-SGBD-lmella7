
-- 1) Corriger / modifier un code postal
--    Exemple : mettre à jour le code postal de "Begles"
UPDATE municipalities
SET postal_code = '33132'
WHERE name = 'Begles';

-- 2) Mettre à jour le nom d'une commune (Begles -> Bègles)
UPDATE municipalities
SET name = 'Bègles'
WHERE name = 'Begles';

-- 3) Augmenter la capacité du "Parking Mériadeck" de 20 places
UPDATE parkings
SET capacity = capacity + 20
WHERE name = 'Parking Mériadeck';

-- 4) Diminuer la capacité du "Parking Victoire" de 5 places
UPDATE parkings
SET capacity = capacity - 5
WHERE name = 'Parking Victoire';

-- 5) Changer l'adresse du "Parking Saint-Jean"
UPDATE parkings
SET address = 'Nouvelle Gare Saint-Jean, 33000 Bordeaux'
WHERE name = 'Parking Saint-Jean';

-- 6) Réduire de 10% tous les tarifs des parkings "Open-air"
UPDATE fees
SET price = price * 0.9
WHERE id_parking IN (
    SELECT id_parking
    FROM parkings
    WHERE type = 'Open-air'
);

-- 7) Augmenter de 0.50€ tous les tarifs de nuit

UPDATE fees
SET price = price + 0.50
WHERE entry_time < TIME '06:00'
   OR entry_time >= TIME '22:00';

-- 8) Ajouter 10 000 km aux véhicules très anciens
--    (par exemple mis en circulation avant 2005)
UPDATE vehicles
SET kilometers = kilometers + 10000
WHERE date_of_circulation < DATE '2005-01-01';

-- 9) Mettre l'état à "Excellent" pour les véhicules récents
--    (par exemple mis en circulation après 2020)
UPDATE vehicles
SET vehicle_state = 'Excellent'
WHERE date_of_circulation >= DATE '2020-01-01';

-- 10) Fermer toutes les sessions encore ouvertes
--     en mettant exit_datetime = entry_datetime + 2 heures
UPDATE parkings_sessions
SET exit_datetime = entry_datetime + INTERVAL '2 hours'
WHERE exit_datetime IS NULL;
