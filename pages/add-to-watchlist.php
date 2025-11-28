<?php
// add_to_watchlist.php - UPDATED WITH SERIES SUPPORT
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
    
    $user_id = $_SESSION['user_id'];
    
    try {
        // Check if it's a movie or series
        if (isset($input['movie_id'])) {
            // MOVIE LOGIC
            $movie_id = intval($input['movie_id']);
            $movie_title = $input['movie_title'];
            $movie_poster = $input['movie_poster'];
            $release_date = $input['release_date'] ?? null;
            
            error_log("Processing MOVIE: User $user_id, Movie $movie_id - $movie_title");
            
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
                    error_log("FAILED to add movie to watchlist: " . $stmt->error);
                    echo json_encode(['success' => false, 'message' => 'Erreur base de données: ' . $stmt->error]);
                }
            } else {
                error_log("Movie $movie_id already in watchlist for user $user_id");
                echo json_encode(['success' => false, 'message' => 'Déjà dans la watchlist']);
            }
            
        } elseif (isset($input['serie_id'])) {
            // SERIES LOGIC
            $serie_id = intval($input['serie_id']);
            $serie_title = $input['serie_title'];
            $serie_poster = $input['serie_poster'];
            $first_air_date = $input['first_air_date'] ?? null;
            
            error_log("Processing SERIES: User $user_id, Series $serie_id - $serie_title");
            
            // Check if series exists in SERIE table
            $check_serie = "SELECT id_serie FROM SERIE WHERE id_serie = ?";
            $stmt = $mysqli->prepare($check_serie);
            $stmt->bind_param("i", $serie_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                error_log("Series $serie_id not found, inserting...");
                // Insert series
                $insert_serie = "INSERT INTO SERIE (id_serie, titre, poster, date_premiere) VALUES (?, ?, ?, ?)";
                $stmt = $mysqli->prepare($insert_serie);
                $stmt->bind_param("isss", $serie_id, $serie_title, $serie_poster, $first_air_date);
                if ($stmt->execute()) {
                    error_log("Series $serie_id inserted successfully");
                } else {
                    error_log("Failed to insert series: " . $stmt->error);
                }
            } else {
                error_log("Series $serie_id already exists in database");
            }
            $stmt->close();
            
            // Check if already in watchlist
            $check_watchlist = "SELECT id_selection FROM SELECTION WHERE id_utilisateur = ? AND id_serie = ?";
            $stmt = $mysqli->prepare($check_watchlist);
            $stmt->bind_param("ii", $user_id, $serie_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                // Add to watchlist
                $insert_watchlist = "INSERT INTO SELECTION (id_utilisateur, id_serie, type_status, date_ajout) VALUES (?, ?, 'plan_to_watch', NOW())";
                $stmt = $mysqli->prepare($insert_watchlist);
                $stmt->bind_param("ii", $user_id, $serie_id);
                
                if ($stmt->execute()) {
                    error_log("SUCCESS: Series $serie_id added to watchlist for user $user_id");
                    echo json_encode(['success' => true, 'message' => 'Série ajoutée à la watchlist']);
                } else {
                    error_log("FAILED to add series to watchlist: " . $stmt->error);
                    echo json_encode(['success' => false, 'message' => 'Erreur base de données: ' . $stmt->error]);
                }
            } else {
                error_log("Series $serie_id already in watchlist for user $user_id");
                echo json_encode(['success' => false, 'message' => 'Déjà dans la watchlist']);
            }
        } else {
            error_log("Invalid data: neither movie_id nor serie_id provided");
            echo json_encode(['success' => false, 'message' => 'Données invalides: ID manquant']);
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