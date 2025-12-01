<?php
require_once 'db.php';

$conn = db_connect();

// Récupérer l'id du parking
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die("Parking invalide.");
}

$reservation_success = false;
$reservation_error = null;
$checkout_success = null;
$checkout_error = null;

// Variables pour l'estimation
$estimated_price = null;
$estimated_hours = null;

// Traitement des formulaires (réservation ou sortie véhicule)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    // 1) Sortie d'un véhicule (retirer du parking)
    if ($action === 'checkout') {
        $session_id = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);

        if (!$session_id) {
            $checkout_error = "Session invalide.";
        } else {
            $sqlCheckout = "
                UPDATE parkings_sessions
                SET exit_datetime = NOW()
                WHERE id_parking_session = $1
                  AND exit_datetime IS NULL;
            ";
            $resCheckout = pg_query_params($conn, $sqlCheckout, [$session_id]);

            if (!$resCheckout) {
                $checkout_error = "Erreur lors de la sortie du véhicule.";
            } else {
                if (pg_affected_rows($resCheckout) > 0) {
                    $checkout_success = "Le véhicule a été retiré du parking.";
                } else {
                    $checkout_error = "Impossible de retirer ce véhicule (peut-être déjà sorti).";
                }
            }
        }
    }

    // 2) Réservation d'une place
    if ($action === 'reserve') {
        $plate = trim($_POST['license_plate'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $entry_dt_str = trim($_POST['entry_dt'] ?? '');
        $exit_dt_str  = trim($_POST['exit_dt'] ?? '');

        if ($plate === '') {
            $reservation_error = "L'immatriculation est obligatoire pour réserver.";
        }

        // Vérification des dates / heures
        if (!$reservation_error) {
            if ($entry_dt_str === '' || $exit_dt_str === '') {
                $reservation_error = "Veuillez renseigner la date/heure d'entrée et de sortie.";
            } else {
                $entry_dt = DateTime::createFromFormat('Y-m-d\TH:i', $entry_dt_str);
                $exit_dt  = DateTime::createFromFormat('Y-m-d\TH:i', $exit_dt_str);

                if (!$entry_dt || !$exit_dt || $exit_dt <= $entry_dt) {
                    $reservation_error = "Dates d'entrée/sortie invalides (la sortie doit être après l'entrée).";
                } else {
                    // Calcul de la durée en heures (arrondie à l'heure supérieure)
                    $seconds = $exit_dt->getTimestamp() - $entry_dt->getTimestamp();
                    $hours = max(1, (int)ceil($seconds / 3600));
                    $estimated_hours = $hours;

                    // Recherche du prix horaire dans fees pour l'heure d'entrée
                    $timeStr = $entry_dt->format('H:i:s');

                    $sqlPrice = "
                        SELECT price
                        FROM fees
                        WHERE id_parking = $1
                          AND $2::time >= entry_time
                          AND $2::time < exit_time
                        ORDER BY entry_time
                        LIMIT 1;
                    ";
                    $resPrice = pg_query_params($conn, $sqlPrice, [$id, $timeStr]);

                    if ($resPrice && pg_num_rows($resPrice) > 0) {
                        $rowPrice = pg_fetch_assoc($resPrice);
                        $hourly = (float)$rowPrice['price'];
                        $estimated_price = $hourly * $hours;
                    } else {
                        // Si aucune tranche ne correspond, on prend le premier tarif du parking
                        $sqlFallback = "
                            SELECT price
                            FROM fees
                            WHERE id_parking = $1
                            ORDER BY entry_time
                            LIMIT 1;
                        ";
                        $resFallback = pg_query_params($conn, $sqlFallback, [$id]);
                        if ($resFallback && pg_num_rows($resFallback) > 0) {
                            $rowPrice = pg_fetch_assoc($resFallback);
                            $hourly = (float)$rowPrice['price'];
                            $estimated_price = $hourly * $hours;
                        }
                    }
                }
            }
        }

        if (!$reservation_error) {
            // Chercher ou créer le véhicule (mais attention aux véhicules déjà garés)
            $sqlVeh = "SELECT id_vehicle FROM vehicles WHERE license_plate = $1";
            $resVeh = pg_query_params($conn, $sqlVeh, [$plate]);

            if (!$resVeh) {
                $reservation_error = "Erreur lors de la recherche du véhicule.";
            } else {
                if (pg_num_rows($resVeh) > 0) {
                    $vehRow = pg_fetch_assoc($resVeh);
                    $id_vehicle = (int)$vehRow['id_vehicle'];
                } else {
                    $sqlInsVeh = "
                        INSERT INTO vehicles(license_plate, model, kilometers, vehicle_state, date_of_circulation)
                        VALUES ($1, $2, 0, 'Inconnu', CURRENT_DATE)
                        RETURNING id_vehicle;
                    ";
                    $resInsVeh = pg_query_params($conn, $sqlInsVeh, [$plate, $model]);
                    if (!$resInsVeh) {
                        $reservation_error = "Erreur lors de la création du véhicule.";
                    } else {
                        $vehRow = pg_fetch_assoc($resInsVeh);
                        $id_vehicle = (int)$vehRow['id_vehicle'];
                    }
                }

                // Vérifier que ce véhicule n'est pas déjà garé (session active)
                if (!$reservation_error) {
                    $sqlActive = "
                        SELECT 1
                        FROM parkings_sessions
                        WHERE id_vehicle = $1
                          AND exit_datetime IS NULL
                        LIMIT 1;
                    ";
                    $resActive = pg_query_params($conn, $sqlActive, [$id_vehicle]);
                    if ($resActive && pg_num_rows($resActive) > 0) {
                        $reservation_error = "Ce véhicule est déjà garé dans un parking (session active).";
                    }
                }

                if (!$reservation_error) {
                    // Démarrer transaction et verrouiller la ligne du parking
                    $resBegin = pg_query($conn, "BEGIN");
                    if (!$resBegin) {
                        $reservation_error = "Erreur interne (transaction).";
                    } else {
                        // Verrouiller la ligne du parking (FOR UPDATE) — pas de GROUP BY ici
                        $sqlLockRow = "
                            SELECT capacity
                            FROM parkings
                            WHERE id_parking = $1
                            FOR UPDATE
                        ";
                        $resLockRow = pg_query_params($conn, $sqlLockRow, [$id]);
                        if (!$resLockRow || pg_num_rows($resLockRow) === 0) {
                            pg_query($conn, "ROLLBACK");
                            $reservation_error = "Impossible de vérifier la disponibilité du parking.";
                        } else {
                            $row = pg_fetch_assoc($resLockRow);
                            $capacity = (int)$row['capacity'];

                            // Compter les sessions actives séparément
                            $sqlCount = "
                                SELECT COUNT(*) AS occupied
                                FROM parkings_sessions
                                WHERE id_parking = $1
                                  AND exit_datetime IS NULL
                            ";
                            $resCount = pg_query_params($conn, $sqlCount, [$id]);
                            if (!$resCount) {
                                pg_query($conn, "ROLLBACK");
                                $reservation_error = "Impossible de vérifier les sessions actives.";
                            } else {
                                $countRow = pg_fetch_assoc($resCount);
                                $occupied = (int)$countRow['occupied'];
                                $freeSlots = $capacity - $occupied;

                                if ($freeSlots <= 0) {
                                    pg_query($conn, "ROLLBACK");
                                    $reservation_error = "Ce parking est complet : aucune nouvelle session n'est autorisée.";
                                } else {
                                    // Chercher une place libre et insérer la session (toujours dans la transaction)
                                    $sqlFreePlace = "
                                        SELECT pl.id_place
                                        FROM places pl
                                        LEFT JOIN parkings_sessions ps
                                          ON pl.id_parking = ps.id_parking
                                         AND pl.id_place = ps.id_place
                                         AND ps.exit_datetime IS NULL
                                        WHERE pl.id_parking = $1
                                          AND ps.id_parking_session IS NULL
                                        ORDER BY pl.id_place
                                        LIMIT 1;
                                    ";
                                    $resFree = pg_query_params($conn, $sqlFreePlace, [$id]);
                                    if (!$resFree || pg_num_rows($resFree) === 0) {
                                        pg_query($conn, "ROLLBACK");
                                        $reservation_error = "Aucune place libre disponible pour ce parking.";
                                    } else {
                                        $freeRow = pg_fetch_assoc($resFree);
                                        $id_place = (int)$freeRow['id_place'];
                                        $entry_for_sql = $entry_dt->format('Y-m-d H:i:s');

                                        $sqlInsertSession = "
                                           INSERT INTO parkings_sessions(id_parking, id_place, id_vehicle, entry_datetime)
                                           VALUES ($1, $2, $3, $4::timestamp)
                                           RETURNING id_parking_session;
                                        ";
                                        $resInsSess = pg_query_params($conn, $sqlInsertSession, [$id, $id_place, $id_vehicle, $entry_for_sql]);
                                        if (!$resInsSess) {
                                            pg_query($conn, "ROLLBACK");
                                            $reservation_error = "Erreur lors de la réservation de la place.";
                                        } else {
                                            pg_query($conn, "COMMIT");
                                            $reservation_success = true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}


$sqlParking = "
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
    WHERE p.id_parking = $1
    GROUP BY 
        p.id_parking, p.name, p.type, p.address, p.capacity,
        m.name, m.postal_code;
";

$resParking = pg_query_params($conn, $sqlParking, [$id]);
if (!$resParking || pg_num_rows($resParking) === 0) {
    die("Parking introuvable.");
}
$parking = pg_fetch_assoc($resParking);

$capacity = (int)$parking['capacity'];
$occupied = (int)$parking['occupied'];
$free     = max(0, (int)$parking['free']);
$rate     = $capacity > 0 ? round($occupied * 100 / $capacity) : 0;

// Badge de taux d’occupation
$label = "Fluide";
$labelClass = "tag-green";

if ($rate >= 90) {
    $label = "Saturé";
    $labelClass = "tag-red";
} elseif ($rate >= 50) {
    $label = "Chargé";
    $labelClass = "tag-orange";
}

// Véhicules actuellement garés dans ce parking
$sqlCurrent = "
    SELECT 
        ps.id_parking_session,
        v.license_plate,
        v.model,
        v.kilometers,
        v.vehicle_state,
        ps.entry_datetime,
        ps.id_place
    FROM parkings_sessions ps
    JOIN vehicles v 
        ON v.id_vehicle = ps.id_vehicle
    WHERE ps.id_parking = $1
      AND ps.exit_datetime IS NULL
    ORDER BY ps.entry_datetime DESC;
";
$resCurrent = pg_query_params($conn, $sqlCurrent, [$id]);

// Historique des 10 dernières sessions
$sqlHistory = "
    SELECT 
        v.license_plate,
        v.model,
        ps.entry_datetime,
        ps.exit_datetime,
        ps.id_place
    FROM parkings_sessions ps
    JOIN vehicles v 
        ON v.id_vehicle = ps.id_vehicle
    WHERE ps.id_parking = $1
    ORDER BY ps.entry_datetime DESC
    LIMIT 10;
";
$resHistory = pg_query_params($conn, $sqlHistory, [$id]);

// Tarifs pour ce parking
$sqlFees = "
    SELECT entry_time, exit_time, price
    FROM fees
    WHERE id_parking = $1
    ORDER BY entry_time;
";
$resFees = pg_query_params($conn, $sqlFees, [$id]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail parking – <?= htmlspecialchars($parking['name']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">

    <header class="hero">
        <h1><?= htmlspecialchars($parking['name']) ?></h1>
        <p>
            <?= htmlspecialchars($parking['city']) ?> 
            (<?= htmlspecialchars($parking['postal_code']) ?>) – 
            <?= htmlspecialchars($parking['type']) ?>
        </p>

        <nav class="nav">
            <a href="index.php" class="nav-link">Parkings</a>
            <a href="vehicle_search.php" class="nav-link">Recherche véhicule</a>
        </nav>
    </header>

    <main class="main">

        <a class="btn btn-secondary" href="index.php">&larr; Retour à la liste des parkings</a>

        <?php if ($reservation_success): ?>
            <div class="alert alert-success">
                Réservation enregistrée : une place a été attribuée à votre véhicule.
            </div>
        <?php endif; ?>

        <?php if ($reservation_error): ?>
            <div class="alert alert-error">
                 <?= htmlspecialchars($reservation_error) ?>
            </div>
        <?php endif; ?>

        <?php if ($checkout_success): ?>
            <div class="alert alert-success">
                 <?= htmlspecialchars($checkout_success) ?>
            </div>
        <?php endif; ?>

        <?php if ($checkout_error): ?>
            <div class="alert alert-error">
                 <?= htmlspecialchars($checkout_error) ?>
            </div>
        <?php endif; ?>

        <section class="parking-details">
            <h2>Informations générales</h2>
            <p class="parking-address">
                <strong>Adresse :</strong> <?= htmlspecialchars($parking['address']) ?>
            </p>

            <div class="parking-stats">
                <span><strong>Capacité :</strong> <?= $capacity ?> places</span>
                <span class="busy"><strong>Occupées :</strong> <?= $occupied ?></span>
                <span class="free"><strong>Libres :</strong> <?= $free ?></span>
                <span><strong>Taux d’occupation :</strong> <?= $rate ?>%</span>
            </div>

            <div class="parking-occupation">
                <span class="tag <?= $labelClass ?>">
                    <?= $label ?> (<?= $rate ?>%)
                </span>
            </div>
        </section>

        <section>
            <h2>Réserver une place</h2>
            <form method="post" class="reservation-form">
                <input type="hidden" name="action" value="reserve">
                <div class="field-row">
                    <label>Immatriculation *</label>
                    <input type="text" name="license_plate" required>
                </div>
                <div class="field-row">
                    <label>Modèle du véhicule (optionnel)</label>
                    <input type="text" name="model">
                </div>
                <div class="field-row">
                    <label>Date et heure d’entrée souhaitée *</label>
                    <input type="datetime-local" name="entry_dt" required>
                </div>
                <div class="field-row">
                    <label>Date et heure de sortie souhaitée *</label>
                    <input type="datetime-local" name="exit_dt" required>
                </div>
                <button type="submit" class="btn btn-primary">Réserver une place</button>
            </form>
            <p class="help-text">
                Une place libre sera automatiquement attribuée à votre véhicule si le parking n’est pas complet.
            </p>

            <?php if ($estimated_price !== null && $estimated_hours !== null && !$reservation_error): ?>
                <p class="estimate">
                    Tarif estimé pour la période choisie (~ <?= (int)$estimated_hours ?> h) :
                    <strong><?= number_format($estimated_price, 2, ',', ' ') ?> €</strong>
                </p>
            <?php endif; ?>
        </section>

        <section>
            <h2>Tarifs</h2>
            <?php if (!$resFees || pg_num_rows($resFees) === 0): ?>
                <p class="empty">Aucun tarif défini pour ce parking.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>De</th>
                        <th>À</th>
                        <th>Prix horaire</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = pg_fetch_assoc($resFees)) : ?>
                        <tr>
                            <td><?= htmlspecialchars($row['entry_time']) ?></td>
                            <td><?= htmlspecialchars($row['exit_time']) ?></td>
                            <td><?= htmlspecialchars($row['price']) ?> € / h</td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section>
            <h2>Véhicules actuellement garés</h2>
            <?php if (!$resCurrent || pg_num_rows($resCurrent) === 0): ?>
                <p class="empty">Aucun véhicule n’est actuellement garé dans ce parking.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Immatriculation</th>
                        <th>Modèle</th>
                        <th>Place</th>
                        <th>Date / heure d’entrée</th>
                        <th>État véhicule</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = pg_fetch_assoc($resCurrent)) : ?>
                        <tr>
                            <td><?= htmlspecialchars($row['license_plate']) ?></td>
                            <td><?= htmlspecialchars($row['model']) ?></td>
                            <td><?= (int)$row['id_place'] ?></td>
                            <td><?= htmlspecialchars($row['entry_datetime']) ?></td>
                            <td>
                                <span class="tag">
                                    <?= htmlspecialchars($row['vehicle_state']) ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="checkout">
                                    <input type="hidden" name="session_id" value="<?= (int)$row['id_parking_session'] ?>">
                                    <button type="submit" class="btn btn-secondary">Retirer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section>
            <h2>Historique récent (10 dernières sessions)</h2>
            <?php if (!$resHistory || pg_num_rows($resHistory) === 0): ?>
                <p class="empty">Aucun historique pour ce parking.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Immatriculation</th>
                        <th>Modèle</th>
                        <th>Place</th>
                        <th>Entrée</th>
                        <th>Sortie</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = pg_fetch_assoc($resHistory)) : ?>
                        <tr>
                            <td><?= htmlspecialchars($row['license_plate']) ?></td>
                            <td><?= htmlspecialchars($row['model']) ?></td>
                            <td><?= (int)$row['id_place'] ?></td>
                            <td><?= htmlspecialchars($row['entry_datetime']) ?></td>
                            <td><?= htmlspecialchars($row['exit_datetime'] ?? '-') ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    </main>

    <footer class="footer">
        Projet SGBD – ENSEIRB-MATMECA – <?= date('Y') ?>
    </footer>

</div>
</body>
</html>
