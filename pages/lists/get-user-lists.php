<?php
session_start();
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode([]);
    exit;
}

$user = getCurrentUser();

// Charger les listes de l'utilisateur
$stmt = $GLOBALS['mysqli']->prepare("
    SELECT l.*, COUNT(sl.id_selection) AS item_count
    FROM LISTE l
    LEFT JOIN SELECTION_LISTE sl ON l.id_liste = sl.id_liste
    WHERE l.id_utilisateur = ?
    GROUP BY l.id_liste
    ORDER BY l.date_creation DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$lists = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

header('Content-Type: application/json');
echo json_encode($lists);
?>