<?php
// src/index.php
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Parkings - Tableau de Bord</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .button-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 15px; }
        .btn { 
            background: #3498db; 
            color: white; 
            border: none; 
            padding: 12px 15px; 
            border-radius: 5px; 
            cursor: pointer; 
            text-align: center;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219a52; }
        .parking-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; }
        .parking-card { 
            background: #ecf0f1; 
            padding: 15px; 
            border-radius: 5px; 
            border-left: 4px solid #3498db;
        }
        .result-container { margin-top: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
        th { background-color: #f2f2f2; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏢 Système de Gestion des Parkings</h1>
            <p>Base de données : <?= DB_NAME ?></p>
        </div>

        <!-- Section Tables Principales -->
        <div class="section">
            <h2>📊 Tables Principales</h2>
            <div class="button-grid">
                <button class="btn" onclick="showTable('municipalities')">Communes</button>
                <button class="btn" onclick="showTable('parkings')">Parkings</button>
                <button class="btn" onclick="showTable('places')">Places</button>
                <button class="btn" onclick="showTable('fees')">Tarifs</button>
                <button class="btn" onclick="showTable('vehicles')">Véhicules</button>
                <button class="btn" onclick="showTable('parkings_sessions')">Sessions</button>
            </div>
        </div>

        <!-- Section Requêtes Spécifiques -->
        <div class="section">
            <h2>🔍 Requêtes Spécifiques</h2>
            <div class="button-grid">
                <button class="btn btn-success" onclick="executeQuery('voitures_actuelles')">Voitures Actuellement Garées</button>
                <button class="btn btn-success" onclick="executeQuery('places_disponibles')">Places Disponibles</button>
                <button class="btn btn-success" onclick="executeQuery('parkings_satures')">Parkings Saturés</button>
                <button class="btn btn-success" onclick="executeQuery('duree_moyenne')">Durée Moyenne</button>
                <button class="btn btn-success" onclick="executeQuery('parkings_rentables')">Parkings les Plus Rentables</button>
                <button class="btn btn-success" onclick="executeQuery('communes_demandees')">Communes les Plus Demandées</button>
            </div>
        </div>

        <!-- Section par Parking -->
        <div class="section">
            <h2>🚗 Informations par Parking</h2>
            <div class="parking-list">
                <?php
                $parkings = pg_query($conn, "SELECT id_parking, name FROM parkings ORDER BY name");
                while ($parking = pg_fetch_assoc($parkings)) {
                    echo '<div class="parking-card">';
                    echo '<h3>' . htmlspecialchars($parking['name']) . '</h3>';
                    echo '<button class="btn" onclick="showParkingInfo(' . $parking['id_parking'] . ', \'places\')">Places</button>';
                    echo '<button class="btn" onclick="showParkingInfo(' . $parking['id_parking'] . ', \'tarifs\')">Tarifs</button>';
                    echo '<button class="btn" onclick="showParkingInfo(' . $parking['id_parking'] . ', \'occupation\')">Occupation</button>';
                    echo '<button class="btn" onclick="showParkingInfo(' . $parking['id_parking'] . ', \'vehicules\')">Véhicules Actuels</button>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Section Actions -->
        <div class="section">
            <h2>⚙️ Actions</h2>
            <div class="button-grid">
                <a href="selects.php" class="btn">Voir Toutes les Requêtes SELECT</a>
                <button class="btn btn-danger" onclick="executeUpdate('fermer_sessions')">Fermer Sessions Ouvertes</button>
                <button class="btn" onclick="executeUpdate('ajuster_tarifs')">Ajuster Tarifs Nuit</button>
            </div>
        </div>

        <!-- Zone de résultats -->
        <div id="result-container" class="section result-container hidden">
            <h2 id="result-title">Résultats</h2>
            <div id="result-content"></div>
        </div>
    </div>

    <script>
        function showTable(tableName) {
            fetch('ajax_handler.php?action=show_table&table=' + tableName)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('result-title').textContent = 'Table : ' + tableName;
                    document.getElementById('result-content').innerHTML = data;
                    document.getElementById('result-container').classList.remove('hidden');
                });
        }

        function executeQuery(queryType) {
            fetch('ajax_handler.php?action=execute_query&query=' + queryType)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('result-title').textContent = 'Requête : ' + queryType;
                    document.getElementById('result-content').innerHTML = data;
                    document.getElementById('result-container').classList.remove('hidden');
                });
        }

        function showParkingInfo(parkingId, infoType) {
            fetch('ajax_handler.php?action=parking_info&parking_id=' + parkingId + '&info_type=' + infoType)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('result-title').textContent = 'Info Parking - ' + infoType;
                    document.getElementById('result-content').innerHTML = data;
                    document.getElementById('result-container').classList.remove('hidden');
                });
        }

        function executeUpdate(actionType) {
            if (confirm('Êtes-vous sûr de vouloir exécuter cette action ?')) {
                fetch('ajax_handler.php?action=execute_update&update=' + actionType)
                    .then(response => response.text())
                    .then(data => {
                        alert('Action exécutée avec succès !');
                        document.getElementById('result-title').textContent = 'Résultat de l\'action';
                        document.getElementById('result-content').innerHTML = data;
                        document.getElementById('result-container').classList.remove('hidden');
                    });
            }
        }
    </script>
</body>
</html>