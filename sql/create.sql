CREATE TABLE parkings (
    id_parking SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    address VARCHAR(255) NOT NULL,
    capacity INT NOT NULL,
    FOREIGN KEY (id_parking) REFERENCES municipalities(id_municipality)
);

CREATE TABLE places (
    id_place SERIAL PRIMARY KEY,
    FOREIGN KEY (id_place) REFERENCES parkings(id_parking)
);

CREATE TABLE tariffs (
    id_tariff SERIAL PRIMARY KEY,
    entry_time TIME NOT NULL,
    exit_time TIME NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_tariff) REFERENCES parkings(id_parking)
);

CREATE TABLE vehicles (
    id_vehicle SERIAL PRIMARY KEY,
    license_plate VARCHAR(20) NOT NULL,
    model VARCHAR(50) NOT NULL,
    kilometers INT NOT NULL,
    vehicle_state VARCHAR(50) NOT NULL,
    date_of_circulation DATE NOT NULL
);

CREATE TABLE parkings_sessions (
    id_parking_session SERIAL PRIMARY KEY,
    entry_datetime TIMESTAMP NOT NULL,
    exit_datetime TIMESTAMP NOT NULL,
    FOREIGN KEY (id_parking_session) REFERENCES places(id_place),
    FOREIGN KEY (id_parking_session) REFERENCES vehicles(id_vehicle)
);

CREATE TABLE municipalities (
    id_municipality SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL
);
