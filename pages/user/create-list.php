<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(["status" => "error", "msg" => "not logged in"]);
    exit();
}

$user_id = $_SESSION["user_id"];
$name = trim($_POST["list_name"]);
$type = trim($_POST["list_type"]);

if (!$name || !$type) {
    echo json_encode(["status" => "error", "msg" => "invalid fields"]);
    exit();
}

$stmt = $mysqli->prepare("INSERT INTO LISTE (nom_liste, type, date_creation, id_utilisateur) 
                          VALUES (?, ?, NOW(), ?)");
$stmt->bind_param("ssi", $name, $type, $user_id);
$stmt->execute();

echo json_encode([
    "status" => "success",
    "list_id" => $stmt->insert_id,
    "list_name" => $name,
    "list_type" => $type
]);
exit();
