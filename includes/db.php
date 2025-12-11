<?php
// includes/db.php - Connexion à la base de données
include 'config.conf.php'; // Inclut tes constantes DB

// Crée la connexion MySQLi
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Vérifie la connexion
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    // En production, affiche un message générique
    die("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
}

// Définit le charset
$mysqli->set_charset("utf8mb4");

// Fonction pour exécuter des requêtes sécurisées
function db_query($mysqli, $sql, $params = [], $types = "") {
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $mysqli->error);
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}

// Fonction pour insérer une réservation
function insert_reservation($mysqli, $reservation_data) {
    $sql = "INSERT INTO cinema_reservations 
            (user_id, cinema_id, cinema_name, cinema_address, film_id, film_title, 
             film_poster, reservation_date, reservation_time, number_tickets, total_price, reservation_code)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $code = 'CINE-' . strtoupper(uniqid());
    
    $result = db_query($mysqli, $sql, [
        $reservation_data['user_id'],
        $reservation_data['cinema_id'],
        $reservation_data['cinema_name'],
        $reservation_data['cinema_address'],
        $reservation_data['film_id'],
        $reservation_data['film_title'],
        $reservation_data['film_poster'],
        $reservation_data['date'],
        $reservation_data['time'],
        $reservation_data['tickets'],
        $reservation_data['total_price'],
        $code
    ], "issssisssids");
    
    return $result ? $code : false;
}
?>