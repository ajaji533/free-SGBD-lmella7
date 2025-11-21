
-- 1) Corriger / changer un code postal
--    (ex : on met à jour le code postal de "Begles")
UPDATE municipalities
SET postal_code = '33132'
WHERE name = 'Begles';

-- 2) Augmenter la capacité du "Parking Mériadeck" de 20 places
UPDATE parkings
SET capacity = capacity + 20
WHERE name = 'Parking Mériadeck';

-- 3) Réduire de 10% tous les tarifs du "Parking Victoire"
UPDATE fees
SET price = price * 0.9
WHERE id_parking = (
  SELECT id_parking
  FROM parkings
  WHERE name = 'Parking Victoire'
);

-- 4) Augmenter de 0.50€ les tarifs de nuit
--    (ici on considère la nuit comme [00:00, 06:00[ ou [22:00, 23:59])
UPDATE fees
SET price = price + 0.50
WHERE (entry_time >= TIME '00:00' AND exit_time <= TIME '06:00')
   OR (entry_time >= TIME '22:00');

-- 5) Ajouter 10 000 km aux véhicules très anciens
--    (par exemple date de circulation avant 2005)
UPDATE vehicles
SET kilometers = kilometers + 10000
WHERE date_of_circulation < DATE '2005-01-01';

-- 6) Mettre l'état à "Excellent" pour les véhicules récents
--    (par exemple mis en circulation après 2020)
UPDATE vehicles
SET vehicle_state = 'Excellent'
WHERE date_of_circulation >= DATE '2020-01-01';

-- 7) Affecter "Parking Chartrons" à la commune "Le Bouscat"
UPDATE parkings
SET id_municipality = (
    SELECT id_municipality
    FROM municipalities
    WHERE name = 'Le Bouscat'
)
WHERE name = 'Parking Chartrons';

-- 8) Fermer toutes les sessions encore ouvertes
--    en leur mettant une heure de sortie = entrée + 2h
UPDATE parkings_sessions
SET exit_datetime = entry_datetime + INTERVAL '2 hours'
WHERE exit_datetime IS NULL;

-- 9) Déplacer toutes les places du "Parking Gambetta"
--    de la moitié inférieure vers la moitié supérieure
--    (exemple purement illustratif)
UPDATE places
SET id_place = id_place + 20
WHERE id_parking = (
    SELECT id_parking FROM parkings WHERE name = 'Parking Gambetta'
)
AND id_place <= 20;

-- 10) Mettre à jour l’adresse du "Parking Saint-Jean"
UPDATE parkings
SET address = 'Nouvelle Gare Saint-Jean, 33000 Bordeaux'
WHERE name = 'Parking Saint-Jean';
