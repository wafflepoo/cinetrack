<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

$user_id = $user['id'];

// Collect profile fields
$prenom = trim($_POST['prenom']);
$nom    = trim($_POST['nom']);
$pseudo = trim($_POST['pseudo']);
$email  = trim($_POST['email']);



// ----------- UPDATE PASSWORD IF PROVIDED -----------
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$old_password = $_POST['old_password'] ?? '';

if (!empty($new_password)) {
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
        header("Location: profile.php");
        exit;
    }

    // Get current hashed password
    $stmt = $mysqli->prepare("SELECT mot_de_passe FROM UTILISATEUR WHERE id_utilisateur = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($old_password, $hashed_password)) {
        $_SESSION['error'] = "Mot de passe actuel incorrect.";
        header("Location: profile.php");
        exit;
    }

    // Hash new password
    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare("UPDATE UTILISATEUR SET mot_de_passe = ? WHERE id_utilisateur = ?");
    $stmt->bind_param("si", $new_hashed_password, $user_id);
    $stmt->execute();
    $stmt->close();
}





$_SESSION['success'] = "Mot de passe mis à jour avec succès !";

header("Location: profile.php");
exit;
?>
