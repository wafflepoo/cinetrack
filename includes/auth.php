<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'pseudo' => $_SESSION['user_pseudo'] ?? '',
        'nom' => $_SESSION['user_nom'] ?? '',
        'prenom' => $_SESSION['user_prenom'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user'
    ];
}

// Require login - redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /pages/connexion.php');
        exit;
    }
}

// Logout user
function logout() {
    session_unset();
    session_destroy();
    header('Location: /index.php');
    exit;
}

// Save or update film/series in database when user interacts with it
function saveMediaToDatabase($mysqli, $mediaData, $type = 'film') {
    try {
        if ($type === 'film') {
            $stmt = $mysqli->prepare("
                INSERT INTO FILM (id_film, titre, date_sortie, description, poster, id_api, realisateur)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                titre = VALUES(titre),
                poster = VALUES(poster),
                description = VALUES(description)
            ");
            $stmt->bind_param(
                "issssss", 
                $mediaData['id'],
                $mediaData['title'],
                $mediaData['release_date'],
                $mediaData['overview'],
                $mediaData['poster_path'],
                $mediaData['id'],
                $mediaData['director']
            );
        } else {
            $stmt = $mysqli->prepare("
                INSERT INTO SERIE (id_serie, titre, date_premiere, description, poster, id_api, nb_saisons)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                titre = VALUES(titre),
                poster = VALUES(poster),
                description = VALUES(description),
                nb_saisons = VALUES(nb_saisons)
            ");
            $stmt->bind_param(
                "isssssi", 
                $mediaData['id'],
                $mediaData['name'],
                $mediaData['first_air_date'],
                $mediaData['overview'],
                $mediaData['poster_path'],
                $mediaData['id'],
                $mediaData['number_of_seasons']
            );
        }
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (Exception $e) {
        error_log("Error saving media: " . $e->getMessage());
        return false;
    }
}
?>