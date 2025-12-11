<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();

$user = getCurrentUser();

$selection_id = intval($_POST['selection_id'] ?? 0);
$list_id = intval($_POST['list_id'] ?? 0);

if (!$selection_id || !$list_id) {
    $_SESSION['error'] = "Requête invalide.";
    header("Location: list-detail.php?id=$list_id");
    exit();
}

// 1. Delete link from SELECTION_LISTE
$stmt = $mysqli->prepare("DELETE FROM SELECTION_LISTE WHERE id_selection = ? AND id_liste = ?");
$stmt->bind_param("ii", $selection_id, $list_id);
$stmt->execute();
$stmt->close();

// 2. Check if selection is still used by another list
$check = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM SELECTION_LISTE WHERE id_selection = ?");
$check->bind_param("i", $selection_id);
$check->execute();
$count = $check->get_result()->fetch_assoc()['cnt'];
$check->close();

// 3. If unused → delete from SELECTION
if ($count == 0) {
    $del = $mysqli->prepare("DELETE FROM SELECTION WHERE id_selection = ?");
    $del->bind_param("i", $selection_id);
    $del->execute();
    $del->close();
}

$_SESSION['success'] = "Élément retiré de la liste.";
header("Location: list-detail.php?id=$list_id");
exit();
