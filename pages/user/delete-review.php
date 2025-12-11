<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();

$user = getCurrentUser();

$type = $_POST['type'] ?? null;   // "film" or "serie"
$id_review = intval($_POST['id_review'] ?? 0);

if (!$type || !$id_review) {
    echo json_encode(["status" => "error"]);
    exit;
}

if ($type === "film") {
    $stmt = $mysqli->prepare("DELETE FROM CRITIQUE_FILM WHERE id_critique = ? AND id_utilisateur = ?");
} else {
    $stmt = $mysqli->prepare("DELETE FROM CRITIQUE_SERIE WHERE id_critique = ? AND id_utilisateur = ?");
}

$stmt->bind_param("ii", $id_review, $user['id']);
$stmt->execute();
$stmt->close();

echo json_encode(["status" => "success"]);
exit;
