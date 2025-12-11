<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$list_id = intval($_POST['list_id']);

if (!$list_id) {
    echo json_encode(["status"=>"error"]);
    exit;
}

// Remove entries from SELECTION_LISTE
$mysqli->query("DELETE FROM SELECTION_LISTE WHERE id_liste = $list_id");

// Delete list
$stmt = $mysqli->prepare("DELETE FROM LISTE WHERE id_liste = ? AND id_utilisateur = ?");
$stmt->bind_param("ii", $list_id, $user['id']);
$stmt->execute();
$stmt->close();

echo json_encode(["status"=>"success"]);
exit;
