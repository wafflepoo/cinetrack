<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.conf.php'; // ensure mysqli exists

// ------------------------------
// Check login
// ------------------------------
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// ------------------------------
// Load full user data from DB
// ------------------------------
function getCurrentUser() {
    global $mysqli;

    if (!isLoggedIn()) {
        return null;
    }

    $id = $_SESSION['user_id'];

    $stmt = $mysqli->prepare("
        SELECT 
            id_utilisateur AS id,
            email,
            pseudo,
            nom,
            prenom,
            avatar
        FROM UTILISATEUR
        WHERE id_utilisateur = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

// ------------------------------
// Require login
// ------------------------------
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /pages/connexion.php');
        exit;
    }
}

// ------------------------------
// Logout
// ------------------------------
function logout() {
    session_unset();
    session_destroy();
    header('Location: /index.php');
    exit;
}

// ------------------------------
// Save films or series to DB
// ------------------------------
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
