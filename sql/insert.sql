-- Insert municipalities
INSERT INTO municipalities (name, postal_code) VALUES
('Bordeaux', '33000'),
('Mérignac', '33700'),
('Pessac', '33600'),
('Gradignan', '33170'),
('Villenave-dOrnon', '33140'),
('Cenon', '33150'),
('Eysines', '33320'),
('Le Bouscat', '33110'),
('Floirac', '33270'),
('Bastide', '33100'),
('Libourne', '33500'),
('Saint-Médard-en-Jalles', '33160'),
('Bruges', '33520'),
('Lormont', '33310'),
('Cestas', '33610'),
('Begles', '33130'),
('Talence', '33400');

-- Insert parkings
INSERT INTO parkings (name, type, address, capacity, id_municipality) VALUES
('Parking Mériadeck', 'Underground', 'Place Charles de Gaulle, 33000 Bordeaux', 80, 1),
('Parking Quinconces', 'Underground', 'Place des Quinconces, 33000 Bordeaux', 60, 2),
('Parking Gambetta', 'Multilevel', 'Cours de l''Intendance, 33000 Bordeaux', 40, 3),
('Parking Cité Mondiale', 'Underground', 'Quai Richelieu, 33000 Bordeaux', 35, 4),
('Parking Victoire', 'Open-air', 'Place de la Victoire, 33000 Bordeaux', 25, 5),
('Parking Palais Rohan', 'Underground', 'Place Pey Berland, 33000 Bordeaux', 20, 6),
('Parking Saint-Jean', 'Multilevel', 'Gare Saint-Jean, 33000 Bordeaux', 70, 7),
('Parking Chartrons', 'Open-air', 'Rue Notre Dame, 33000 Bordeaux', 18, 8);

-- Insert places
-- Génère des places pour chaque parking (ajustez les nombres si nécessaire)
INSERT INTO places (id_parking, id_place)
SELECT 1, g FROM generate_series(1,80) AS g;  -- Parking Mériadeck (80 places)

INSERT INTO places (id_parking, id_place)
SELECT 2, g FROM generate_series(1,60) AS g;  -- Parking Quinconces (60 places)

INSERT INTO places (id_parking, id_place)
SELECT 3, g FROM generate_series(1,40) AS g;  -- Parking Gambetta (40 places)

INSERT INTO places (id_parking, id_place)
SELECT 4, g FROM generate_series(1,35) AS g;  -- Parking Cité Mondiale (35 places)

INSERT INTO places (id_parking, id_place)
SELECT 5, g FROM generate_series(1,25) AS g;  -- Parking Victoire (25 places)

INSERT INTO places (id_parking, id_place)
SELECT 6, g FROM generate_series(1,20) AS g;  -- Parking Palais Rohan (20 places)

INSERT INTO places (id_parking, id_place)
SELECT 7, g FROM generate_series(1,70) AS g;  -- Parking Saint-Jean (70 places)

INSERT INTO places (id_parking, id_place)
SELECT 8, g FROM generate_series(1,18) AS g;  -- Parking Chartrons (18 places)

-- Insert fees
INSERT INTO fees (id_parking, entry_time, exit_time, price) VALUES
(1, '00:00', '08:00', 1.00),
(1, '08:00', '18:00', 2.50),
(1, '18:00', '23:59', 1.50),
(2, '00:00', '07:00', 1.50),
(2, '07:00', '12:00', 2.50),
(2, '12:00', '14:00', 3.50),
(2, '14:00', '19:00', 2.50),
(2, '19:00', '23:59', 2.00),
(3, '00:00', '09:00', 1.00),
(3, '09:00', '17:00', 2.50),
(3, '17:00', '23:59', 1.75),
(4, '00:00', '06:00', 1.00),
(4, '06:00', '19:00', 2.20),
(4, '19:00', '23:59', 1.20),
(5, '00:00', '08:00', 0.80),
(5, '08:00', '18:00', 1.80),
(5, '18:00', '23:59', 1.00),
(6, '00:00', '06:00', 0.90),
(6, '06:00', '08:00', 1.00),
(6, '08:00', '20:00', 2.00),
(6, '20:00', '23:59', 1.00),
(7, '00:00', '06:00', 1.50),
(7, '06:00', '22:00', 2.75),
(7, '22:00', '23:59', 1.25),
(8, '00:00', '08:00', 0.70),
(8, '08:00', '18:00', 1.50),
(8, '18:00', '23:59', 0.90);

-- Insert vehicles
WITH models AS (
  SELECT ARRAY[
    'Toyota Corolla','Toyota Yaris','Toyota RAV4','Honda Civic','Honda Accord','Honda CR-V',
    'Ford Focus','Ford Fiesta','Ford Mustang','Chevrolet Cruze','Chevrolet Spark',
    'Nissan Qashqai','Nissan Leaf','Nissan Micra','Mazda 3','Mazda CX-5',
    'BMW 3 Series','BMW X1','Mercedes C 200','Mercedes A 180','Audi A3','Audi Q5',
    'Volkswagen Golf','Volkswagen Polo','Renault Clio','Renault Megane','Peugeot 208','Peugeot 308',
    'Citroen C3','Citroen C4','Opel Astra','Kia Rio','Kia Sportage','Hyundai i30','Hyundai Tucson',
    'Subaru Impreza','Subaru Forester','Skoda Octavia','Dacia Sandero','Mini Cooper',
    'Porsche 911','Lamborghini Huracan','Ferrari F8','Alfa Romeo Giulia','Fiat 500',
    'Seat Leon','Jaguar XE','Land Rover Discovery','Mitsubishi Outlander','Suzuki Swift'
  ] AS arr
),
states AS (SELECT ARRAY['Excellent','Very good','Good','Average','Poor'] AS arr2)
INSERT INTO vehicles (license_plate, model, kilometers, vehicle_state, date_of_circulation)
SELECT
  -- license plates V0001..V0200
  'V' || lpad(g::text,4,'0') AS license_plate,
  models.arr[ ((g-1) % array_length(models.arr,1)) + 1 ] AS model,
  (1000 + (random()*249000))::int AS kilometers,
  states.arr2[ ((g-1) % array_length(states.arr2,1)) + 1 ] AS vehicle_state,
  (date '2000-01-01' + (floor(random()*9500))::int) AS date_of_circulation
FROM generate_series(1,300) AS gs(g), models, states;

-- Insert parking sessions

WITH vcount AS (SELECT count(*) AS n FROM vehicles)
INSERT INTO parkings_sessions (id_parking, id_place, id_vehicle, entry_datetime, exit_datetime)
SELECT
  p.id_parking,
  p.id_place,
  (floor(random()*vcount.n)+1)::int AS id_vehicle,
  p.entry_ts AS entry_datetime,
  CASE WHEN random() < 0.12 THEN NULL
       ELSE p.entry_ts + ( (floor(random()*(72*60)) + 5)::int || ' minutes')::interval
  END AS exit_datetime
FROM generate_series(1,300) gs(g),
LATERAL (
  SELECT id_parking, id_place,
    (now() - (floor(random()*365)::int || ' days')::interval - (floor(random()*86400)::int || ' seconds')::interval) AS entry_ts
  FROM places
  ORDER BY random() LIMIT 1
) AS p,
vcount;