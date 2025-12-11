<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

$user_id = $user['id'];

// Collect fields
$prenom = trim($_POST['prenom']);
$nom    = trim($_POST['nom']);
$pseudo = trim($_POST['pseudo']);
$email  = trim($_POST['email']);

$avatar_path = $user['avatar']; // default

// ----------- HANDLE AVATAR UPLOAD -----------
if (!empty($_FILES['avatar']['name'])) {

    $folder = "../../uploads/avatars/";

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $file_name = "avatar_" . $user_id . "_" . time() . "." . $ext;
    $full_path = $folder . $file_name;

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $full_path)) {
        // Path stored in DB must be relative to root
        $avatar_path = "uploads/avatars/" . $file_name;
    }
}

// ----------- UPDATE DATABASE -----------
$stmt = $mysqli->prepare("
    UPDATE UTILISATEUR
    SET prenom = ?, nom = ?, pseudo = ?, email = ?, avatar = ?
    WHERE id_utilisateur = ?
");
$stmt->bind_param("sssssi", $prenom, $nom, $pseudo, $email, $avatar_path, $user_id);
$stmt->execute();
$stmt->close();

// ----------- UPDATE SESSION -----------
$_SESSION['user_prenom'] = $prenom;
$_SESSION['user_nom'] = $nom;
$_SESSION['user_pseudo'] = $pseudo;
$_SESSION['user_email'] = $email;
$_SESSION['user_avatar'] = $avatar_path;

$_SESSION['success'] = "Profil mis à jour avec succès !";

header("Location: profile.php");
exit;
?>
