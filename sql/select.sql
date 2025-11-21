SELECT * FROM parkings;
SELECT * FROM municipalities;
SELECT * FROM fees;
SELECT * FROM vehicles;
SELECT * FROM parkings_sessions;
SELECT * FROM places;


-- ============================================================
-- CONSULTATION QUERIES (Reporting & Real-time monitoring)
-- ============================================================

-- 1. Global Overview: List all parkings with their full location details
-- Useful for: General mapping and inventory of parking assets.
SELECT 
    p.name AS parking_name, 
    p.address, 
    p.capacity, 
    m.name AS municipality, 
    m.postal_code
FROM parkings p
JOIN municipalities m ON p.id_municipality = m.id_municipality;

-- 2. Pricing: Get current valid hourly rates based on the time of day
-- Useful for: Displaying the right price to customers entering now.
SELECT 
    p.name AS parking_name, 
    t.price AS current_hourly_rate, 
    t.entry_time AS valid_from, 
    t.exit_time AS valid_until
FROM fees
JOIN parkings p ON t.id_parking = p.id_parking
WHERE CURRENT_TIME BETWEEN t.entry_time AND t.exit_time;

-- 3. Capacity Analysis: Total number of defined spots per parking
-- Useful for: Verifying that the physical spots match the declared capacity.
SELECT 
    p.name AS parking_name, 
    COUNT(pl.id_place) AS total_physical_spots
FROM parkings p
JOIN places pl ON p.id_parking = pl.id_parking
GROUP BY p.id_parking, p.name;

-- 4. Real-Time Monitoring: List of vehicles currently parked (Active Sessions)
-- Useful for: Security patrols and current occupancy checks.
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

-- 5. Availability Check: Calculate number of free spots per parking
-- Useful for: Showing "Full" or "Open" signs at the entrance.
SELECT 
    p.name AS parking_name,
    p.capacity - COUNT(s.id_parking_session) AS free_spots
FROM parkings p
JOIN places pl ON p.id_parking = pl.id_parking
LEFT JOIN parkings_sessions s ON pl.id_place = s.id_place AND s.exit_datetime IS NULL
GROUP BY p.name, p.capacity;

-- ============================================================
-- STATISTICAL & ANALYTICAL QUERIES
-- ============================================================

-- 6. High Occupancy Alert: Parkings with significant activity
-- (Example: Parkings with more than 0 cars currently. Change 0 to 10 for real usage)
SELECT 
    p.name AS parking_name, 
    COUNT(s.id_parking_session) AS current_vehicles
FROM parkings_sessions s
JOIN places pl ON s.id_place = pl.id_place
JOIN parkings p ON pl.id_parking = p.id_parking
WHERE s.exit_datetime IS NULL
GROUP BY p.name
HAVING COUNT(s.id_parking_session) > 0; 

-- 7. Financial Projection: Calculate revenue and duration for completed sessions
-- Useful for: Billing generation and revenue estimation.
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
JOIN feesN p.id_parking = t.id_parking
WHERE s.exit_datetime IS NOT NULL
AND CAST(s.entry_datetime AS TIME) BETWEEN t.entry_time AND t.exit_time;

-- 8. Performance Metrics: Average parking duration per location
-- Useful for: Understanding turnover rates.
SELECT 
    p.name AS parking_name,
    AVG(s.exit_datetime - s.entry_datetime) AS average_duration
FROM parkings_sessions s
JOIN places pl ON s.id_place = pl.id_place
JOIN parkings p ON pl.id_parking = p.id_parking
WHERE s.exit_datetime IS NOT NULL
GROUP BY p.name;

-- 9. Revenue Ranking: Top performing parkings by total generated revenue
-- Useful for: Business strategy and profitability analysis.
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
JOIN feesN p.id_parking = t.id_parking
WHERE s.exit_datetime IS NOT NULL
GROUP BY p.name
ORDER BY total_revenue DESC;

-- 10. User Behaviour: Vehicles that visited at least 2 DIFFERENT parkings today
-- Useful for: Analyzing traffic flow between campuses.
SELECT 
    v.license_plate, 
    COUNT(DISTINCT p.id_parking) AS parkings_visited
FROM parkings_sessions s
JOIN places pl ON s.id_place = pl.id_place
JOIN parkings p ON pl.id_parking = p.id_parking
JOIN vehicles v ON s.id_vehicle = v.id_vehicle
WHERE DATE(s.entry_datetime) = CURRENT_DATE
GROUP BY v.license_plate
HAVING COUNT(DISTINCT p.id_parking) >= 2;