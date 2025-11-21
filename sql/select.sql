SELECT * FROM parkings;
SELECT * FROM municipalities;
SELECT * FROM tariffs;
SELECT * FROM vehicles;
SELECT * FROM parkings_sessions;
SELECT * FROM places;

-- List of all parkings with their location
SELECT 
    p.name AS parking_name, 
    p.address, 
    p.capacity, 
    m.name AS municipality, 
    m.postal_code
FROM parkings p
JOIN municipalities m ON p.id_municipality = m.id_municipality;

-- Current hourly rates for each parking
SELECT 
    p.name AS parking_name, 
    t.price AS current_hourly_rate, 
    t.entry_time AS valid_from, 
    t.exit_time AS valid_until
FROM tariffs t
JOIN parkings p ON t.id_parking = p.id_parking
WHERE CURRENT_TIME BETWEEN t.entry_time AND t.exit_time;

-- Total number of physical spots per parking
SELECT 
    p.name AS parking_name, 
    COUNT(pl.id_place) AS total_physical_spots
FROM parkings p
JOIN places pl ON p.id_parking = pl.id_parking
GROUP BY p.id_parking, p.name;

-- List of vehicles currently parked (Active sessions)
SELECT 
    v.license_plate, 
    v.model, 
    p.name AS parking_name, 
    pl.id_place AS spot_id, 
    s.entry_datetime
FROM parkings_sessions s
JOIN vehicles v ON s.id_vehicle = v.id_vehicle
JOIN places pl ON s.id_place = pl.id_place
JOIN parkings p ON pl.id_parking = p.id_parking
WHERE s.exit_datetime IS NULL;

-- Number of free spots per parking
SELECT 
    p.name AS parking_name,
    p.capacity - COUNT(s.id_parking_session) AS free_spots
FROM parkings p
JOIN places pl ON p.id_parking = pl.id_parking
LEFT JOIN parkings_sessions s ON pl.id_place = s.id_place AND s.exit_datetime IS NULL
GROUP BY p.name, p.capacity;

-- Parkings with active vehicles (Occupancy check)
SELECT 
    p.name AS parking_name, 
    COUNT(s.id_parking_session) AS current_vehicles
FROM parkings_sessions s
JOIN places pl ON s.id_place = pl.id_place
JOIN parkings p ON pl.id_parking = p.id_parking
WHERE s.exit_datetime IS NULL
GROUP BY p.name
HAVING COUNT(s.id_parking_session) > 0; 

-- Revenue and duration for completed sessions
SELECT 
    v.license_plate,
    p.name AS parking_name,
    s.entry_datetime,
    s.exit_datetime,
    (s.exit_datetime - s.entry_datetime) AS duration,
    ROUND(
        CAST(
            (EXTRACT(EPOCH FROM (s.exit_datetime - s.entry_datetime)) / 3600) * t.price 
        AS NUMERIC), 2
    ) AS billed_amount
FROM parkings_sessions s
JOIN vehicles v ON s.id_vehicle = v.id_vehicle
JOIN places pl ON s.id_place = pl.id_place
JOIN parkings p ON pl.id_parking = p.id_parking
JOIN tariffs t ON p.id_parking = t.id_parking
WHERE s.exit_datetime IS NOT NULL
AND CAST(s.entry_datetime AS TIME) BETWEEN t.entry_time AND t.exit_time;

-- Average parking duration per parking
SELECT 
    p.name AS parking_name,
    AVG(s.exit_datetime - s.entry_datetime) AS average_duration
FROM parkings_sessions s
JOIN places pl ON s.id_place = pl.id_place
JOIN parkings p ON pl.id_parking = p.id_parking
WHERE s.exit_datetime IS NOT NULL
GROUP BY p.name;

-- Total revenue ranking per parking
SELECT 
    p.name AS parking_name,
    SUM(
        ROUND(
            CAST(
                (EXTRACT(EPOCH FROM (s.exit_datetime - s.entry_datetime)) / 3600) * t.price 
            AS NUMERIC), 2
        )
    ) AS total_revenue
FROM parkings_sessions s
JOIN places pl ON s.id_place = pl.id_place
JOIN parkings p ON pl.id_parking = p.id_parking
JOIN tariffs t ON p.id_parking = t.id_parking
WHERE s.exit_datetime IS NOT NULL
GROUP BY p.name
ORDER BY total_revenue DESC;

