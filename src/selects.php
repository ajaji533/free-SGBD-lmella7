<?php
// ----------------------------------------
// Connexion PostgreSQL SANS mot de passe
// ----------------------------------------
// Important : lancer le serveur avec :
//   sudo -u postgres php -S localhost:8000 -t src
//
// Ainsi, le processus PHP tourne sous l'utilisateur système "postgres"
// et pg_connect("dbname=parking_db") utilise l'authentification "peer".

$conn = pg_connect("dbname=parking_db");
if (!$conn) {
    die("Erreur de connexion à PostgreSQL : " . pg_last_error());
}

// ----------------------------------------
// Lecture du fichier sql/select.sql
// ----------------------------------------
$selectFile = __DIR__ . '/../sql/select.sql';

if (!file_exists($selectFile)) {
    die("Fichier select.sql introuvable : " . htmlspecialchars($selectFile));
}

$lines = file($selectFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$queries = [];
$current = "";

// On reconstitue les requêtes complètes en se basant sur le ';'
foreach ($lines as $line) {
    $trim = trim($line);

    // Ignorer les commentaires commençant par --
    if ($trim === '' || str_starts_with($trim, '--')) {
        continue;
    }

    $current .= ' ' . $line;

    // Si la ligne se termine par ';', la requête est complète
    if (substr(rtrim($line), -1) === ';') {
        // On enlève le ';' final
        $queries[] = rtrim($current, ";\r\n\t ");
        $current = "";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats des requêtes (select.sql)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
        th, td { border: 1px solid #ccc; padding: 6px 10px; text-align: left; }
        th { background-color: #eee; }
        pre { background: #f8f8f8; padding: 8px; border: 1px solid #ddd; }
        .error { color: darkred; font-weight: bold; }
        .query-block { margin-bottom: 40px; }
    </style>
</head>
<body>

<h1>Résultats des requêtes définies dans <code>sql/select.sql</code></h1>

<?php if (empty($queries)): ?>
    <p>Aucune requête SELECT trouvée dans le fichier.</p>
<?php else: ?>
    <?php foreach ($queries as $index => $sql): ?>
        <div class="query-block">
            <h2>Requête <?= $index + 1 ?></h2>
            <pre><?= htmlspecialchars($sql) ?>;</pre>

            <?php
            $result = @pg_query($conn, $sql);
            if ($result === false) {
                echo '<p class="error">Erreur lors de l\'exécution de la requête :</p>';
                echo '<pre class="error">' . htmlspecialchars(pg_last_error($conn)) . '</pre>';
            } else {
                $numRows = pg_num_rows($result);
                $numCols = pg_num_fields($result);

                if ($numRows === 0) {
                    echo "<p>Aucun résultat.</p>";
                } else {
                    echo "<table><thead><tr>";
                    for ($col = 0; $col < $numCols; $col++) {
                        $colName = pg_field_name($result, $col);
                        echo "<th>" . htmlspecialchars($colName) . "</th>";
                    }
                    echo "</tr></thead><tbody>";

                    while ($row = pg_fetch_row($result)) {
                        echo "<tr>";
                        foreach ($row as $val) {
                            echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
                        }
                        echo "</tr>";
                    }

                    echo "</tbody></table>";
                }

                pg_free_result($result);
            }
            ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
