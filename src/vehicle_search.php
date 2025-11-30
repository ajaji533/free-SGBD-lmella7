<?php
require_once 'db.php';

$conn = db_connect();

$plate = trim($_GET['plate'] ?? '');
$results = null;
$error = null;

if ($plate !== '') {
    $sqlSearch = "
        SELECT 
            v.license_plate,
            v.model,
            v.kilometers,
            v.vehicle_state,
            ps.entry_datetime,
            ps.exit_datetime,
            p.name AS parking_name
        FROM vehicles v
        LEFT JOIN parkings_sessions ps ON v.id_vehicle = ps.id_vehicle
        LEFT JOIN parkings p ON p.id_parking = ps.id_parking
        WHERE v.license_plate ILIKE $1
        ORDER BY ps.entry_datetime DESC NULLS LAST;
    ";
    $results = pg_query_params($conn, $sqlSearch, ['%' . $plate . '%']);
    if (!$results) {
        $error = "Erreur lors de la recherche.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recherche véhicule</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">

    <header class="hero">
        <h1>Recherche de véhicule</h1>
        <p>Rechercher l’historique de stationnement d’un véhicule par immatriculation.</p>

        <nav class="nav">
            <a href="index.php" class="nav-link">Parkings</a>
            <a href="vehicle_search.php" class="nav-link nav-link-active">Recherche véhicule</a>
        </nav>
    </header>

    <main class="main">

        <section>
            <h2>Formulaire de recherche</h2>
            <form method="get" class="filter-form">
                <label>
                    Immatriculation (complète ou partielle)
                    <input type="text" name="plate" value="<?= htmlspecialchars($plate) ?>">
                </label>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-secondary">Rechercher</button>
                    <a href="vehicle_search.php" class="btn btn-link">Effacer</a>
                </div>
            </form>
        </section>

        <section>
            <h2>Résultats</h2>
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php elseif ($plate === ''): ?>
                <p class="empty">Entrez une immatriculation pour lancer la recherche.</p>
            <?php else: ?>
                <?php if (!$results || pg_num_rows($results) === 0): ?>
                    <p class="empty">Aucun véhicule trouvé pour cette immatriculation.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Immatriculation</th>
                            <th>Modèle</th>
                            <th>Parking</th>
                            <th>Entrée</th>
                            <th>Sortie</th>
                            <th>État</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = pg_fetch_assoc($results)) : ?>
                            <tr>
                                <td><?= htmlspecialchars($row['license_plate']) ?></td>
                                <td><?= htmlspecialchars($row['model']) ?></td>
                                <td><?= htmlspecialchars($row['parking_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['entry_datetime'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['exit_datetime'] ?? '-') ?></td>
                                <td>
                                    <span class="tag">
                                        <?= htmlspecialchars($row['vehicle_state']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </section>

    </main>

    <footer class="footer">
        Projet SGBD – ENSEIRB-MATMECA – <?= date('Y') ?>
    </footer>

</div>
</body>
</html>
