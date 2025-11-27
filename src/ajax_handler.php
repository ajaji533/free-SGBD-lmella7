<?php
// src/ajax_handler.php
require_once __DIR__ . '/config.php';

$conn_string = sprintf(
    "host=%s dbname=%s user=%s password=%s",
    DB_HOST,
    DB_NAME,
    DB_USER,
    DB_PASS
);

$conn = pg_connect($conn_string);
if (!$conn) {
    die("Erreur de connexion à PostgreSQL : " . htmlspecialchars(pg_last_error()));
}

$action = $_GET['action'] ?? '';

function displayTable($result) {
    if (pg_num_rows($result) === 0) {
        echo "<p>Aucun résultat trouvé.</p>";
        return;
    }

    echo "<table><thead><tr>";
    $numCols = pg_num_fields($result);
    for ($col = 0; $col < $numCols; $col++) {
        echo "<th>" . htmlspecialchars(pg_field_name($result, $col)) . "</th>";
    }
    echo "</tr></thead><tbody>";

    while ($row = pg_fetch_assoc($result)) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table>";
}

switch ($action) {
    case 'show_table':
        $table = $_GET['table'];
        $result = pg_query($conn, "SELECT * FROM $table LIMIT 100");
        if ($result) {
            displayTable($result);
        } else {
            echo "<p>Erreur : " . htmlspecialchars(pg_last_error($conn)) . "</p>";
        }
        break;

    case 'execute_query':
        $queryType = $_GET['query'];
        $queries = [
            'voitures_actuelles' => "
                SELECT p.name AS nom_parking, v.license_plate, v.model, s.entry_datetime
                FROM parkings_sessions s
                JOIN places pl ON s.id_place = pl.id_place
                JOIN parkings p ON pl.id_parking = p.id_parking
                JOIN vehicles v ON s.id_vehicle = v.id_vehicle
                WHERE s.exit_datetime IS NULL
                ORDER BY p.name
            ",
            'places_disponibles' => "
                SELECT p.name AS nom_parking, 
                       p.capacity - COUNT(s.id_parking_session) AS places_libres
                FROM parkings p
                JOIN places pl ON p.id_parking = pl.id_parking
                LEFT JOIN parkings_sessions s ON pl.id_place = s.id_place AND s.exit_datetime IS NULL
                GROUP BY p.id_parking, p.name, p.capacity
            ",
            'parkings_satures' => "
                SELECT p.name AS nom_parking, p.capacity,
                       COUNT(DISTINCT s.id_parking_session) AS occupation_actuelle
                FROM parkings p
                JOIN places pl ON p.id_parking = pl.id_parking
                JOIN parkings_sessions s ON pl.id_place = s.id_place
                WHERE s.exit_datetime IS NULL
                GROUP BY p.id_parking, p.name, p.capacity
                HAVING COUNT(DISTINCT s.id_parking_session) >= p.capacity
            ",
            'duree_moyenne' => "
                SELECT p.name AS nom_parking,
                       AVG(EXTRACT(EPOCH FROM (s.exit_datetime - s.entry_datetime))/3600) AS duree_moyenne_heures
                FROM parkings_sessions s
                JOIN places pl ON s.id_place = pl.id_place
                JOIN parkings p ON pl.id_parking = p.id_parking
                WHERE s.exit_datetime IS NOT NULL
                GROUP BY p.name
            ",
            'parkings_rentables' => "
                SELECT m.name AS commune, p.name AS parking,
                       SUM((EXTRACT(EPOCH FROM (s.exit_datetime - s.entry_datetime))/3600) * f.price) AS chiffre_affaires
                FROM parkings_sessions s
                JOIN places pl ON s.id_place = pl.id_place
                JOIN parkings p ON pl.id_parking = p.id_parking
                JOIN municipalities m ON p.id_municipality = m.id_municipality
                JOIN fees f ON p.id_parking = f.id_parking
                WHERE s.exit_datetime IS NOT NULL
                AND s.entry_datetime::time BETWEEN f.entry_time AND f.exit_time
                GROUP BY m.name, p.name
                ORDER BY chiffre_affaires DESC
                LIMIT 10
            ",
            'communes_demandees' => "
                SELECT m.name AS commune,
                       COUNT(s.id_parking_session) AS total_visites
                FROM parkings_sessions s
                JOIN places pl ON s.id_place = pl.id_place
                JOIN parkings p ON pl.id_parking = p.id_parking
                JOIN municipalities m ON p.id_municipality = m.id_municipality
                GROUP BY m.name
                ORDER BY total_visites DESC
                LIMIT 10
            "
        ];

        if (isset($queries[$queryType])) {
            $result = pg_query($conn, $queries[$queryType]);
            if ($result) {
                displayTable($result);
            } else {
                echo "<p>Erreur : " . htmlspecialchars(pg_last_error($conn)) . "</p>";
            }
        }
        break;

    case 'parking_info':
        $parkingId = (int)$_GET['parking_id'];
        $infoType = $_GET['info_type'];
        
        $queries = [
            'places' => "SELECT * FROM places WHERE id_parking = $parkingId",
            'tarifs' => "SELECT * FROM fees WHERE id_parking = $parkingId ORDER BY entry_time",
            'occupation' => "
                SELECT COUNT(*) as places_occupees,
                       (SELECT capacity FROM parkings WHERE id_parking = $parkingId) as capacite_totale
                FROM parkings_sessions s
                JOIN places pl ON s.id_place = pl.id_place
                WHERE pl.id_parking = $parkingId AND s.exit_datetime IS NULL
            ",
            'vehicules' => "
                SELECT v.license_plate, v.model, s.entry_datetime
                FROM parkings_sessions s
                JOIN places pl ON s.id_place = pl.id_place
                JOIN vehicles v ON s.id_vehicle = v.id_vehicle
                WHERE pl.id_parking = $parkingId AND s.exit_datetime IS NULL
                ORDER BY s.entry_datetime
            "
        ];

        if (isset($queries[$infoType])) {
            $result = pg_query($conn, $queries[$infoType]);
            if ($result) {
                displayTable($result);
            } else {
                echo "<p>Erreur : " . htmlspecialchars(pg_last_error($conn)) . "</p>";
            }
        }
        break;

    case 'execute_update':
        $updateType = $_GET['update'];
        $updates = [
            'fermer_sessions' => "
                UPDATE parkings_sessions 
                SET exit_datetime = entry_datetime + INTERVAL '2 hours' 
                WHERE exit_datetime IS NULL
            ",
            'ajuster_tarifs' => "
                UPDATE fees 
                SET price = price + 0.50 
                WHERE entry_time < TIME '06:00' OR entry_time >= TIME '22:00'
            "
        ];

        if (isset($updates[$updateType])) {
            $result = pg_query($conn, $updates[$updateType]);
            if ($result) {
                $affected = pg_affected_rows($result);
                echo "<p>Action exécutée avec succès. Lignes affectées : $affected</p>";
            } else {
                echo "<p>Erreur : " . htmlspecialchars(pg_last_error($conn)) . "</p>";
            }
        }
        break;

    default:
        echo "<p>Action non reconnue.</p>";
}
?>