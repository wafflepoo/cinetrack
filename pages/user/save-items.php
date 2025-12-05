<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$list_id = intval($_POST['id_liste']);
$selected = $_POST['media'] ?? [];

foreach ($selected as $media_id) {
    $media_id = intval($media_id);
    $stmt = $mysqli->prepare("INSERT INTO SELECTION_LISTE (id_liste, selection_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $list_id, $media_id);
    $stmt->execute();
    $stmt->close();
}

$_SESSION['success'] = 'Éléments ajoutés à la liste';
header('Location: lists.php');
exit();
