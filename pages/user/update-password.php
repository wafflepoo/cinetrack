<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

requireLogin();
$user = getCurrentUser();
$user_id = $user['id'];

// Récupérer les champs du formulaire
$old_password     = $_POST['old_password'] ?? '';
$new_password     = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['error_password'] = "Tous les champs sont obligatoires.";
    header("Location: profile.php#security");
    exit;
}

// Vérifier que le nouveau mot de passe correspond à la confirmation
if ($new_password !== $confirm_password) {
    $_SESSION['error_password'] = "Les nouveaux mots de passe ne correspondent pas.";
    header("Location: profile.php#security");
    exit;
}

// Récupérer le mot de passe actuel
$stmt = $mysqli->prepare("SELECT mot_de_passe FROM UTILISATEUR WHERE id_utilisateur = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($hashed_password);
$stmt->fetch();
$stmt->close();

// Vérifier le mot de passe actuel
if (!password_verify($old_password, $hashed_password)) {
    $_SESSION['error_password'] = "Mot de passe actuel incorrect.";
    header("Location: profile.php#security");
    exit;
}

// Mettre à jour le mot de passe
$new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare("UPDATE UTILISATEUR SET mot_de_passe = ? WHERE id_utilisateur = ?");
$stmt->bind_param("si", $new_hashed_password, $user_id);
$stmt->execute();
$stmt->close();

// Message de succès uniquement
$_SESSION['success_password'] = "Mot de passe mis à jour avec succès !";
header("Location: profile.php#security");
exit;
