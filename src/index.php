<?php
require_once 'db.php';

$conn = db_connect();


$selectedCity = filter_input(INPUT_GET, 'city', FILTER_VALIDATE_INT);
$selectedType = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);


$sqlCounts = "
    SELECT
      (SELECT COUNT(*) FROM parkings) AS nb_parkings,
      (SELECT COUNT(*) FROM places)   AS nb_places,
      (SELECT COUNT(*) 
         FROM parkings_sessions 
        WHERE exit_datetime IS NULL)  AS nb_vehicles_now
";
$resCounts = pg_query($conn, $sqlCounts);
if (!$resCounts) {
    $counts = ['nb_parkings' => 0, 'nb_places' => 0, 'nb_vehicles_now' => 0];
} else {
    $counts = pg_fetch_assoc($resCounts);
}


$sqlCities = "
    SELECT DISTINCT m.id_municipality, m.name
    FROM municipalities m
    JOIN parkings p ON p.id_municipality = m.id_municipality
    ORDER BY m.name;
";
$resCities = pg_query($conn, $sqlCities);


$sqlTypes = "
    SELECT DISTINCT type
    FROM parkings
    ORDER BY type;
";
$resTypes = pg_query($conn, $sqlTypes);


$conditions = [];
$params = [];

if ($selectedCity) {
    $conditions[] = "p.id_municipality = $" . (count($params) + 1);
    $params[] = $selectedCity;
}

if ($selectedType) {
    $conditions[] = "p.type = $" . (count($params) + 1);
    $params[] = $selectedType;
}

$sqlList = "
    SELECT 
        p.id_parking,
        p.name,
        p.type,
        p.address,
        p.capacity,
        m.name AS city,
        m.postal_code,
        COUNT(ps.id_parking_session) 
            FILTER (WHERE ps.exit_datetime IS NULL) AS occupied,
        p.capacity 
            - COUNT(ps.id_parking_session) 
                FILTER (WHERE ps.exit_datetime IS NULL) AS free
    FROM parkings p
    JOIN municipalities m 
        ON p.id_municipality = m.id_municipality
    LEFT JOIN parkings_sessions ps 
        ON p.id_parking = ps.id_parking
        AND ps.exit_datetime IS NULL
";

if ($conditions) {
    $sqlList .= " WHERE " . implode(" AND ", $conditions);
}

$sqlList .= "
    GROUP BY 
        p.id_parking, p.name, p.type, p.address, p.capacity,
        m.name, m.postal_code
    ORDER BY m.name, p.name;
";

if ($params) {
    $result = pg_query_params($conn, $sqlList, $params);
} else {
    $result = pg_query($conn, $sqlList);
}

if (!$result) {
    die("Erreur lors de la requête : " . pg_last_error($conn));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Parkings – Accueil</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">

    <header class="hero">
        <h1>Gestion des parkings</h1>
        <p>Visualisation des parkings, des places disponibles et des véhicules garés.</p>

        <nav class="nav">
            <a href="index.php" class="nav-link nav-link-active">Parkings</a>
            <a href="vehicle_search.php" class="nav-link">Recherche véhicule</a>
        </nav>
    </header>

    <main class="main">

        
        <section class="dashboard">
            <div class="card-metric">
                <span class="metric-label">Parkings</span>
                <span class="metric-value">
                    <?= (int)$counts['nb_parkings'] ?>
                </span>
            </div>
            <div class="card-metric">
                <span class="metric-label">Places</span>
                <span class="metric-value">
                    <?= (int)$counts['nb_places'] ?>
                </span>
            </div>
            <div class="card-metric">
                <span class="metric-label">Véhicules garés</span>
                <span class="metric-value">
                    <?= (int)$counts['nb_vehicles_now'] ?>
                </span>
            </div>
        </section>

        
        <section>
            <h2>Filtrer les parkings</h2>
            <form method="get" class="filter-form">
                <label>
                    Commune
                    <select name="city">
                        <option value="">Toutes</option>
                        <?php if ($resCities): ?>
                            <?php while ($city = pg_fetch_assoc($resCities)) : ?>
                                <option value="<?= (int)$city['id_municipality'] ?>"
                                    <?= ($selectedCity == $city['id_municipality']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($city['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </label>

                <label>
                    Type
                    <select name="type">
                        <option value="">Tous</option>
                        <?php if ($resTypes): ?>
                            <?php while ($type = pg_fetch_assoc($resTypes)) : ?>
                                <option value="<?= htmlspecialchars($type['type']) ?>"
                                    <?= ($selectedType === $type['type']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['type']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </label>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-secondary">Filtrer</button>
                    <a href="index.php" class="btn btn-link">Réinitialiser</a>
                </div>
            </form>
        </section>

        
        <section>
            <h2>Liste des parkings</h2>
            <div class="parking-grid">
                <?php while ($row = pg_fetch_assoc($result)) : 
                    $capacity = (int)$row['capacity'];
                    $occupied = (int)$row['occupied'];
                    $free     = max(0, (int)$row['free']);
                    $rate     = $capacity > 0 ? round($occupied * 100 / $capacity) : 0;

                    
                    $label = "Fluide";
                    $labelClass = "tag-green";

                    if ($rate >= 90) {
                        $label = "Saturé";
                        $labelClass = "tag-red";
                    } elseif ($rate >= 50) {
                        $label = "Chargé";
                        $labelClass = "tag-orange";
                    }
                ?>
                    <article class="parking-card">
                        <div class="parking-header">
                            <h3><?= htmlspecialchars($row['name']) ?></h3>
                            <span class="badge">
                                <?= htmlspecialchars($row['type']) ?>
                            </span>
                        </div>

                        <p class="parking-city">
                            <?= htmlspecialchars($row['city']) ?>
                            (<?= htmlspecialchars($row['postal_code']) ?>)
                        </p>

                        <p class="parking-address">
                            <?= htmlspecialchars($row['address']) ?>
                        </p>

                        <div class="parking-stats">
                            <span><strong>Capacité :</strong> <?= $capacity ?></span>
                            <span class="busy">
                                <strong>Occupées :</strong> <?= $occupied ?>
                            </span>
                            <span class="free">
                                <strong>Libres :</strong> <?= $free ?>
                            </span>
                        </div>

                        <div class="parking-occupation">
                            <span class="tag <?= $labelClass ?>">
                                <?= $label ?> (<?= $rate ?>%)
                            </span>
                        </div>

                        <a class="btn btn-primary" href="parking.php?id=<?= (int)$row['id_parking'] ?>">
                            Voir les détails / Réserver
                        </a>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>

    </main>

    <footer class="footer">
        Projet SGBD – ENSEIRB-MATMECA – <?= date('Y') ?>
    </footer>

</div>
</body>
</html>
