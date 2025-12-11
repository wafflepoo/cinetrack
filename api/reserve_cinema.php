<?php
// api/reserve_cinema.php - Version avec auth.php
session_start();
require_once '../includes/config.conf.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Log pour debugging
error_log("=== RESERVATION CINEMA API CALLED ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . print_r($_POST, true));
error_log("Session User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    error_log("User not logged in");
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour réserver',
        'redirect' => 'login'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Récupérer et valider les données POST
    $cinema_id = trim($_POST['cinema_id'] ?? '');
    $cinema_name = trim($_POST['cinema_name'] ?? '');
    $cinema_address = trim($_POST['cinema_address'] ?? '');
    $film_id = trim($_POST['film_id'] ?? '');
    $film_title = trim($_POST['film_title'] ?? '');
    $film_poster = trim($_POST['film_poster'] ?? '');
    $reservation_date = trim($_POST['reservation_date'] ?? '');
    $reservation_time = trim($_POST['reservation_time'] ?? '');
    $number_tickets = intval($_POST['number_tickets'] ?? 1);
    $total_price = floatval($_POST['total_price'] ?? 0);
    
    error_log("Processing reservation: User $user_id, Cinema $cinema_id - $cinema_name, Film $film_id - $film_title");
    
    // Validation des champs requis
    if (empty($cinema_id) || empty($cinema_name) || empty($film_id) || empty($film_title) || 
        empty($reservation_date) || empty($reservation_time)) {
        error_log("Validation failed: missing required fields");
        echo json_encode([
            'success' => false,
            'message' => 'Tous les champs sont requis'
        ]);
        exit();
    }
    
    // Validation du nombre de tickets
    if ($number_tickets < 1 || $number_tickets > 10) {
        error_log("Invalid ticket number: $number_tickets");
        echo json_encode([
            'success' => false,
            'message' => 'Nombre de places invalide (1-10)'
        ]);
        exit();
    }
    
    // Validation de la date (ne peut pas être dans le passé)
    $today = new DateTime();
    $reservationDate = new DateTime($reservation_date);
    if ($reservationDate < $today->setTime(0, 0, 0)) {
        error_log("Invalid date: reservation date is in the past");
        echo json_encode([
            'success' => false,
            'message' => 'La date de réservation ne peut pas être dans le passé'
        ]);
        exit();
    }
    
    // Générer un code de réservation unique
    $reservation_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    
    try {
        // Vérifier si la table cinema_reservations existe, sinon la créer
        $check_table = "SHOW TABLES LIKE 'cinema_reservations'";
        $result = $mysqli->query($check_table);
        
        if ($result->num_rows === 0) {
            error_log("Table cinema_reservations not found, creating it...");
            $create_table = "CREATE TABLE cinema_reservations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                cinema_id VARCHAR(50) NOT NULL,
                cinema_name VARCHAR(255) NOT NULL,
                cinema_address TEXT,
                film_id INT NOT NULL,
                film_title VARCHAR(255) NOT NULL,
                film_poster TEXT,
                reservation_date DATE NOT NULL,
                reservation_time TIME NOT NULL,
                number_tickets INT NOT NULL,
                total_price DECIMAL(10, 2) NOT NULL,
                reservation_code VARCHAR(20) NOT NULL UNIQUE,
                status VARCHAR(20) DEFAULT 'confirmed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES UTILISATEUR(id_utilisateur) ON DELETE CASCADE
            )";
            
            if ($mysqli->query($create_table)) {
                error_log("Table cinema_reservations created successfully");
            } else {
                throw new Exception("Failed to create table: " . $mysqli->error);
            }
        }
        
        // Insérer la réservation
        $insert_reservation = "INSERT INTO cinema_reservations 
            (user_id, cinema_id, cinema_name, cinema_address, film_id, film_title, film_poster, 
             reservation_date, reservation_time, number_tickets, total_price, reservation_code) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $mysqli->prepare($insert_reservation);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $mysqli->error);
        }
        
        $stmt->bind_param(
            "issssssssiis",
            $user_id,
            $cinema_id,
            $cinema_name,
            $cinema_address,
            $film_id,
            $film_title,
            $film_poster,
            $reservation_date,
            $reservation_time,
            $number_tickets,
            $total_price,
            $reservation_code
        );
        
        if ($stmt->execute()) {
            $reservation_id = $stmt->insert_id;
            error_log("SUCCESS: Reservation created with ID $reservation_id, code $reservation_code");
            
            echo json_encode([
                'success' => true,
                'reservation_code' => $reservation_code,
                'reservation_id' => $reservation_id,
                'message' => 'Réservation confirmée avec succès',
                'details' => [
                    'cinema' => $cinema_name,
                    'film' => $film_title,
                    'date' => $reservation_date,
                    'time' => $reservation_time,
                    'tickets' => $number_tickets,
                    'total' => $total_price . '€'
                ]
            ]);
        } else {
            throw new Exception("Failed to execute: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("EXCEPTION in reservation: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la réservation. Veuillez réessayer.',
            'error_detail' => $e->getMessage()
        ]);
    }
} else {
    error_log("Invalid method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
}

error_log("=== RESERVATION CINEMA API FINISHED ===");
?>