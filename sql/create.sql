CREATE TABLE IF NOT EXISTS municipalities (
    id_municipality SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL
);

CREATE TABLE IF NOT EXISTS parkings (
    id_parking SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    address VARCHAR(255) NOT NULL,
    capacity INT NOT NULL,
    id_municipality INT NOT NULL REFERENCES municipalities(id_municipality)
);

CREATE TABLE IF NOT EXISTS places (
    id_parking INT NOT NULL REFERENCES parkings(id_parking),
    id_place INT,
    PRIMARY KEY (id_parking, id_place)
);

CREATE TABLE IF NOT EXISTS fees (
    id_tariff SERIAL PRIMARY KEY,
    id_parking INT NOT NULL REFERENCES parkings(id_parking),
    entry_time TIME NOT NULL,
    exit_time TIME NOT NULL,
    price DECIMAL(10,2) NOT NULL --hourly price
);

CREATE TABLE IF NOT EXISTS vehicles (
    id_vehicle SERIAL PRIMARY KEY,
    license_plate VARCHAR(20) NOT NULL,
    model VARCHAR(50) NOT NULL,
    kilometers INT NOT NULL,
    vehicle_state VARCHAR(50) NOT NULL,
    date_of_circulation DATE NOT NULL
);

CREATE TABLE IF NOT EXISTS parkings_sessions (
    id_parking_session SERIAL PRIMARY KEY,
    id_parking INT NOT NULL,
    id_place INT NOT NULL,
    CONSTRAINT fk_places FOREIGN KEY (id_parking, id_place) REFERENCES places(id_parking, id_place),
    id_vehicle INT NOT NULL REFERENCES vehicles(id_vehicle),
    entry_datetime TIMESTAMP NOT NULL,
    exit_datetime TIMESTAMP
);


