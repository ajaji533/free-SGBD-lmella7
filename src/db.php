<?php


function db_connect() {
    static $conn = null;

    if ($conn === null) {
        
        $host = "localhost";
        $dbname = "parking_db";
        $user = "votre_utilisateur";  
        $password = "votre_mot_de_passe"; 

        $conn = pg_connect("host=$host dbname=$dbname user=$user password=$password");
        if (!$conn) {
            die("Erreur de connexion à PostgreSQL : " . pg_last_error());
        }
    }

    return $conn;
}
