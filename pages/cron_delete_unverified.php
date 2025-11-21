<?php
require_once '../includes/config.conf';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    exit("Erreur BDD\n");
}

$stmt = $conn->prepare("DELETE FROM UTILISATEUR WHERE is_verified = 0 AND token_expires < NOW()");
$stmt->execute();
$stmt->close();
$conn->close();
