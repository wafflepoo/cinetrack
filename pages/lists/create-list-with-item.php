<?php
session_start();
require_once __DIR__ . '/../../includes/config.conf.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Non connecté']);
    exit;
}

$user_id = $_SESSION['user_id'];
$list_name = trim($_POST['list_name'] ?? '');
$list_type = trim($_POST['list_type'] ?? 'films');
$movie_id = intval($_POST['movie_id'] ?? 0);
$movie_title = trim($_POST['movie_title'] ?? '');
$movie_poster = trim($_POST['movie_poster'] ?? '');

if (empty($list_name)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Nom de liste requis']);
    exit;
}

try {
    // 1. Créer la liste - UTILISEZ 'type' PAS 'type_liste'
    $create_list = "INSERT INTO LISTE (id_utilisateur, nom_liste, type, date_creation) 
                VALUES (?, ?, ?, NOW())";
    
    $stmt = $mysqli->prepare($create_list);
    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Erreur préparation: ' . $mysqli->error]);
        exit;
    }
    
    $stmt->bind_param("iss", $user_id, $list_name, $list_type);
    
    if (!$stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Erreur création liste: ' . $stmt->error]);
        exit;
    }
    
    $new_list_id = $mysqli->insert_id;
    $stmt->close();
    
    // 2. Si un film est spécifié, l'ajouter à la nouvelle liste
    if ($movie_id > 0 && !empty($movie_title)) {
        // Essayez d'abord LISTE_CONTENU (si vous l'avez créée)
        // Sinon, essayez SELECTION_LISTE
        
        // Option A: LISTE_CONTENU (recommandé si la table existe)
        $add_movie = "INSERT INTO LISTE_CONTENU (id_liste, id_film, titre_film, poster_url, date_ajout) 
                      VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $mysqli->prepare($add_movie);
        if ($stmt) {
            $stmt->bind_param("iiss", $new_list_id, $movie_id, $movie_title, $movie_poster);
            $stmt->execute();
            $stmt->close();
        } else {
            // Option B: SELECTION_LISTE (fallback)
            // Note: Vous devrez peut-être d'abord ajouter à SELECTION
            $add_movie = "INSERT INTO SELECTION_LISTE (id_liste, id_film, titre, poster_url, date_ajout) 
                          VALUES (?, ?, ?, ?, NOW())";
            
            $stmt = $mysqli->prepare($add_movie);
            if ($stmt) {
                $stmt->bind_param("iiss", $new_list_id, $movie_id, $movie_title, $movie_poster);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success', 
        'message' => 'Liste créée avec succès',
        'list_id' => $new_list_id,
        'list_name' => $list_name
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>