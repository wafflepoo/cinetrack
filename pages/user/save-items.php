<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(["status" => "error", "message" => "Non connecté"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$list_id = $_POST['list_id'] ?? null;
$items   = $_POST['items'] ?? [];

if (!$list_id || empty($items)) {
    echo json_encode(["status" => "error", "message" => "Aucun élément sélectionné"]);
    exit();
}

foreach ($items as $media) {

    list($media_id, $media_type) = explode("|", $media);

    $sql = "INSERT INTO SELECTION (type_status, favori, id_utilisateur, id_film, id_serie)
            VALUES ('plan_to_watch', 0, ?, ?, ?)";

    $stmt = $mysqli->prepare($sql);

    if ($media_type === "film") {
        $film_id = $media_id;
        $serie_id = null;
    } else {
        $film_id = null;
        $serie_id = $media_id;
    }

    $stmt->bind_param("iss", $user_id, $film_id, $serie_id);
    $stmt->execute();

    $new_selection_id = $stmt->insert_id;

    // Link to list
    $sql2 = "INSERT INTO SELECTION_LISTE (id_selection, id_liste) VALUES (?, ?)";
    $stmt2 = $mysqli->prepare($sql2);
    $stmt2->bind_param("ii", $new_selection_id, $list_id);
    $stmt2->execute();
}

echo json_encode([
    "status" => "success",
    "message" => "Éléments ajoutés avec succès"
]);
exit();
?>
