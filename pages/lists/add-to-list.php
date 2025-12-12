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
$list_id = intval($_POST['list_id'] ?? 0);
$movie_id = intval($_POST['movie_id'] ?? 0);
$serie_id = intval($_POST['serie_id'] ?? 0);
$movie_title = trim($_POST['movie_title'] ?? '');
$serie_title = trim($_POST['serie_title'] ?? '');
$movie_poster = trim($_POST['movie_poster'] ?? '');
$serie_poster = trim($_POST['serie_poster'] ?? '');
$content_type = trim($_POST['content_type'] ?? 'movie'); // 'movie' ou 'series'

// Utiliser soit movie_id soit serie_id
$content_id = $movie_id ?: $serie_id;
$content_title = $movie_title ?: $serie_title;
$content_poster = $movie_poster ?: $serie_poster;

if (!$list_id || !$content_id) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Données manquantes']);
    exit;
}

try {
    // Vérifier que la liste appartient à l'utilisateur
    $check_list = "SELECT id_liste, nom_liste FROM LISTE WHERE id_liste = ? AND id_utilisateur = ?";
    $stmt = $mysqli->prepare($check_list);
    $stmt->bind_param("ii", $list_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Liste non trouvée']);
        exit;
    }
    
    $list_data = $result->fetch_assoc();
    $list_name = $list_data['nom_liste'];
    $stmt->close();
    
    // Vérifier si la table LISTE_CONTENU existe
    $check_table = $mysqli->query("SHOW TABLES LIKE 'LISTE_CONTENU'");
    if ($check_table && $check_table->num_rows > 0) {
        // Utiliser LISTE_CONTENU (SANS content_type car la colonne n'existe pas)
        $check_exists = "SELECT id_contenu FROM LISTE_CONTENU WHERE id_liste = ? AND id_film = ?";
        $stmt = $mysqli->prepare($check_exists);
        $stmt->bind_param("ii", $list_id, $content_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'exists', 'message' => 'Déjà dans la liste', 'list_name' => $list_name]);
            exit;
        }
        $stmt->close();
        
        // Ajouter à LISTE_CONTENU (SANS content_type)
        $insert_query = "INSERT INTO LISTE_CONTENU (id_liste, id_film, titre_film, poster_url, date_ajout) 
                         VALUES (?, ?, ?, ?, NOW())";
        $stmt = $mysqli->prepare($insert_query);
        $stmt->bind_param("iiss", $list_id, $content_id, $content_title, $content_poster);
    } else {
        // Fallback: utiliser SELECTION_LISTE (pour films seulement)
        if ($content_type !== 'movie') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Les séries ne sont pas supportées avec cette table']);
            exit;
        }
        
        $check_exists = "SELECT id_selection FROM SELECTION_LISTE WHERE id_liste = ? AND id_film = ?";
        $stmt = $mysqli->prepare($check_exists);
        $stmt->bind_param("ii", $list_id, $content_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'exists', 'message' => 'Déjà dans la liste', 'list_name' => $list_name]);
            exit;
        }
        $stmt->close();
        
        // Ajouter à SELECTION_LISTE
        $insert_query = "INSERT INTO SELECTION_LISTE (id_liste, id_film, titre, poster_url, date_ajout) 
                         VALUES (?, ?, ?, ?, NOW())";
        $stmt = $mysqli->prepare($insert_query);
        $stmt->bind_param("iiss", $list_id, $content_id, $content_title, $content_poster);
    }
    
    header('Content-Type: application/json');
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Ajouté à la liste',
            'list_name' => $list_name
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erreur d\'ajout: ' . $stmt->error]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>