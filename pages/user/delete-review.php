<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'] ?? null;

$type = $_POST['type'] ?? null;   // "film" or "serie"
$id = intval($_POST['id'] ?? 0);  // id_film or id_serie

if (!$user_id || !$type || !$id) {
    echo json_encode(["status" => "error", "msg" => "Invalid request"]);
    exit;
}

if ($type === "film") {

    // Delete review ONLY by correct PK
    $stmt = $mysqli->prepare("
        DELETE FROM CRITIQUE_FILM
        WHERE id_utilisateur = ? AND id_film = ?
    ");
    $stmt->bind_param("ii", $user_id, $id);

} elseif ($type === "serie") {

    $stmt = $mysqli->prepare("
        DELETE FROM CRITIQUE_SERIE
        WHERE id_utilisateur = ? AND id_serie = ?
    ");
    $stmt->bind_param("ii", $user_id, $id);

} else {
    echo json_encode(["status" => "error", "msg" => "Invalid type"]);
    exit;
}

$stmt->execute();
$stmt->close();

echo json_encode(["status" => "success"]);
exit;
