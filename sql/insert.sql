USE parking_management;

INSERT INTO COMMUNES (code_postal, nom_commune) VALUES
('75001', 'Paris 1er'),
('69001', 'Lyon 1er'),
('13001', 'Marseille 1er'),
('31000', 'Toulouse'),
('33000', 'Bordeaux');

INSERT INTO PARKINGS (nom, numero_type_adresse, capacite, id_commune) VALUES
('Parking Centre Ville', '12 Rue de la République', 150, 1),
('Parking Gare', '5 Avenue de la Gare', 200, 2),
('Parking Commercial', '25 Boulevard du Commerce', 100, 3),
('Parking Historique', '8 Place du Capitole', 80, 4),
('Parking Riviera', '15 Quai des Chartrons', 120, 5);

INSERT INTO TARIFICATIONS (id_parking, debut_validite, fin_validite, tarif_horaire) VALUES
(1, '2024-01-01', '2024-12-31', 2.50),
(2, '2024-01-01', '2024-12-31', 3.00),
(3, '2024-01-01', '2024-12-31', 2.00),
(4, '2024-01-01', '2024-12-31', 2.80),
(5, '2024-01-01', '2024-12-31', 2.20);

INSERT INTO PLACES (id_parking, numero_place) VALUES
(1, 'A001'), (1, 'A002'), (1, 'A003'),
(2, 'B001'), (2, 'B002'), (2, 'B003'),
(3, 'C001'), (3, 'C002'), (3, 'C003'),
(4, 'D001'), (4, 'D002'), (4, 'D003'),
(5, 'E001'), (5, 'E002'), (5, 'E003');

INSERT INTO VEHICULES (immatriculation, marque, date_mise_en_circulation, kilometrage, etat) VALUES
('AB-123-CD', 'Renault', '2020-05-15', 45000, 'Bon'),
('EF-456-GH', 'Peugeot', '2019-08-20', 62000, 'Très bon'),
('IJ-789-KL', 'Citroën', '2021-01-10', 25000, 'Excellent'),
('MN-012-OP', 'BMW', '2022-03-25', 15000, 'Neuf'),
('QR-345-ST', 'Mercedes', '2018-11-30', 78000, 'Bon');

INSERT INTO STATIONNEMENTS (id_place, id_vehicule, date_jour, heure_entree, heure_sortie) VALUES
(1, 1, '2024-01-15', '08:30:00', '12:45:00'),
(2, 2, '2024-01-15', '09:15:00', '11:30:00'),
(3, 3, '2024-01-15', '10:00:00', '14:20:00'),
(4, 4, '2024-01-16', '13:45:00', '16:30:00'),
(5, 5, '2024-01-16', '15:20:00', '18:10:00');