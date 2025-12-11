<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();

$user = getCurrentUser();

$list_id = intval($_POST['list_id']);
$name = trim($_POST['list_name']);
$type = trim($_POST['list_type']);

if (!$list_id || !$name) {
    echo json_encode(["status"=>"error", "msg"=>"Invalid"]);
    exit;
}

$stmt = $mysqli->prepare("
    UPDATE LISTE 
    SET nom_liste = ?, type = ?
    WHERE id_liste = ? AND id_utilisateur = ?
");
$stmt->bind_param("ssii", $name, $type, $list_id, $user['id']);
$stmt->execute();
$stmt->close();

echo json_encode(["status"=>"success"]);
exit;
