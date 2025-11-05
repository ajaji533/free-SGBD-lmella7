-- Insert municipalities
INSERT INTO municipalities (name, postal_code) VALUES
('Downtown', '10001'),
('Uptown', '10002'),
('Riverside', '10003');

-- Insert parkings
INSERT INTO parkings (name, type, address, capacity, id_municipality) VALUES
('Central Parking', 'Underground', '123 Main St', 200, 1),
('Riverside Lot', 'Open-air', '45 River Rd', 120, 3),
('Uptown Garage', 'Multilevel', '78 North Ave', 300, 2);

-- Insert places
INSERT INTO places (id_parking) VALUES
(1), (1), (2), (2), (3), (3);

-- Insert tariffs
INSERT INTO tariffs (id_parking, entry_time, exit_time, price) VALUES
(1, '08:00', '18:00', 2.50),
(2, '07:00', '19:00', 3.00),
(3, '09:00', '17:00', 2.00);

-- Insert vehicles
INSERT INTO vehicles (license_plate, model, kilometers, vehicle_state, date_of_circulation) VALUES
('ABC-123', 'Toyota Corolla', 45000, 'Good', '2020-05-15'),
('XYZ-789', 'Ford Focus', 60000, 'Average', '2019-03-10'),
('LMN-456', 'Tesla Model 3', 25000, 'Excellent', '2022-11-22');

-- Insert parking sessions
INSERT INTO parkings_sessions (id_place, id_vehicle, entry_datetime, exit_datetime) VALUES
(1, 1, '2025-11-05 08:15:00', '2025-11-05 10:30:00'),
(2, 2, '2025-11-05 09:00:00', '2025-11-05 12:45:00'),
(3, 3, '2025-11-05 10:00:00', NULL);
