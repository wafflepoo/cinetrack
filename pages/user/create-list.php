<?php
// create-list.php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    $_SESSION['error'] = 'Non connecté';
    header('Location: liste.php'); // redirection vers la page des listes
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $list_name = trim($_POST['list_name'] ?? '');
    $list_type = trim($_POST['list_type'] ?? '');

    if (!$list_name || !$list_type) {
        $_SESSION['error'] = 'Données invalides';
        header('Location: liste.php');
        exit();
    }

    try {
        // Vérifier si une liste du même nom existe pour cet utilisateur
        $check_query = "SELECT id_liste FROM LISTE WHERE id_utilisateur = ? AND nom_liste = ?";
        $stmt = $mysqli->prepare($check_query);
        $stmt->bind_param("is", $user_id, $list_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error'] = 'Une liste avec ce nom existe déjà';
            header('Location: liste.php');
            exit();
        }
        $stmt->close();

        // Insertion de la nouvelle liste
        $insert_query = "INSERT INTO LISTE (nom_liste, type, date_creation, id_utilisateur) VALUES (?, ?, NOW(), ?)";
        $stmt = $mysqli->prepare($insert_query);
        $stmt->bind_param("ssi", $list_name, $list_type, $user_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Liste créée avec succès';
        } else {
            $_SESSION['error'] = 'Erreur base de données: ' . $stmt->error;
        }
        $stmt->close();

        header('Location: liste.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
        header('Location: liste.php');
        exit();
    }
} else {
    $_SESSION['error'] = 'Méthode non autorisée';
    header('Location: lists.php');
    exit();
}
?>
