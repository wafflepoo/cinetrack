<?php
// add_to_watchlist.php - DEBUG VERSION
session_start();
require_once '../includes/config.conf.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Log the request for debugging
error_log("=== WATCHLIST API CALLED ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . file_get_contents('php://input'));
error_log("Session User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));

if (!isLoggedIn()) {
    error_log("User not logged in");
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    error_log("Received JSON data: " . print_r($input, true));
    
    if (!$input) {
        error_log("No JSON data received");
        echo json_encode(['success' => false, 'message' => 'Données invalides']);
        exit();
    }
    
    $movie_id = intval($input['movie_id']);
    $movie_title = $input['movie_title'];
    $movie_poster = $input['movie_poster'];
    $release_date = $input['release_date'] ?? null;
    $user_id = $_SESSION['user_id'];
    
    error_log("Processing: User $user_id, Movie $movie_id - $movie_title");
    
    try {
        // Check if movie exists in FILM table
        $check_film = "SELECT id_film FROM FILM WHERE id_film = ?";
        $stmt = $mysqli->prepare($check_film);
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            error_log("Movie $movie_id not found, inserting...");
            // Insert movie
            $insert_film = "INSERT INTO FILM (id_film, titre, poster, date_sortie) VALUES (?, ?, ?, ?)";
            $stmt = $mysqli->prepare($insert_film);
            $stmt->bind_param("isss", $movie_id, $movie_title, $movie_poster, $release_date);
            if ($stmt->execute()) {
                error_log("Movie $movie_id inserted successfully");
            } else {
                error_log("Failed to insert movie: " . $stmt->error);
            }
        } else {
            error_log("Movie $movie_id already exists in database");
        }
        $stmt->close();
        
        // Check if already in watchlist
        $check_watchlist = "SELECT id_selection FROM SELECTION WHERE id_utilisateur = ? AND id_film = ?";
        $stmt = $mysqli->prepare($check_watchlist);
        $stmt->bind_param("ii", $user_id, $movie_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Add to watchlist
            $insert_watchlist = "INSERT INTO SELECTION (id_utilisateur, id_film, type_status, date_ajout) VALUES (?, ?, 'plan_to_watch', NOW())";
            $stmt = $mysqli->prepare($insert_watchlist);
            $stmt->bind_param("ii", $user_id, $movie_id);
            
            if ($stmt->execute()) {
                error_log("SUCCESS: Movie $movie_id added to watchlist for user $user_id");
                echo json_encode(['success' => true, 'message' => 'Film ajouté à la watchlist']);
            } else {
                error_log("FAILED to add to watchlist: " . $stmt->error);
                echo json_encode(['success' => false, 'message' => 'Erreur base de données: ' . $stmt->error]);
            }
        } else {
            error_log("Movie $movie_id already in watchlist for user $user_id");
            echo json_encode(['success' => false, 'message' => 'Déjà dans la watchlist']);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("EXCEPTION in watchlist: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
} else {
    error_log("Invalid method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}

error_log("=== WATCHLIST API FINISHED ===");
?>