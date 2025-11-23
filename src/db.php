<?php
// src/db.php
require_once __DIR__ . '/config.php';

function get_db()
{
    static $conn = null;

    if ($conn === null) {
        $conn = pg_connect(
            "host=" . DB_HOST .
            " dbname=" . DB_NAME .
            " user=" . DB_USER .
            " password=" . DB_PASS
        );
        if (!$conn) {
            die("Erreur de connexion à la base de données.");
        }
    }
    return $conn;
}
