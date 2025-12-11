<?php
header("Content-Type: application/json");
require_once '../includes/config.conf'; // connexion DB et constantes

if (!isset($_POST['email'])) {
    echo json_encode(["error" => "Aucun email reçu"]);
    exit;
}

$email = trim($_POST['email']);

// Vérification simple
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["error" => "Email invalide"]);
    exit;
}

// Connexion MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(["error" => "Erreur de connexion à la base"]);
    exit;
}

// Vérifier si l'utilisateur existe
$stmt = $conn->prepare("SELECT id_utilisateur FROM UTILISATEUR WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["error" => "Aucun utilisateur trouvé avec cet email"]);
    exit;
}

$user = $result->fetch_assoc();
$user_id = $user['id_utilisateur'];

// Génération token + expiration
$token = bin2hex(random_bytes(32));
$expire = date("Y-m-d H:i:s", time() + 3600); // 1 heure

// Enregistrement du token dans la base
$update = $conn->prepare("UPDATE UTILISATEUR SET verification_token = ?, token_expiry = ? WHERE id_utilisateur = ?");
$update->bind_param("ssi", $token, $expire, $user_id);
$update->execute();
$update->close();
$conn->close();

// Lien envoyé au JS
$link = "https://cinetrack.alwaysdata.net/pages/reset_password.php?token=$token";

echo json_encode([
    "success" => true,
    "email" => $email,
    "link" => $link
]);
?>
