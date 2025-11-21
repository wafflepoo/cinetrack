<?php
session_start();
require_once '../includes/config.conf';

$token = $_GET['token'] ?? '';
$message = '';
$errors = [];

if (empty($token)) {
    $errors[] = "Lien de vérification manquant.";
} else {
    // Connexion BDD
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        $errors[] = "Erreur de base de données.";
    } else {
        // Rechercher l'utilisateur avec ce token
        $stmt = $conn->prepare("SELECT id_utilisateur, token_expires, is_verified FROM UTILISATEUR WHERE verification_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $errors[] = "Token invalide.";
        } else {
            $user = $result->fetch_assoc();
            if (intval($user['is_verified']) === 1) {
                $message = "Votre compte est déjà vérifié.";
            } else {
                $expires = strtotime($user['token_expires']);
                if ($expires < time()) {
                    $errors[] = "Le lien de vérification a expiré.";
                } else {
                    // Mettre à jour : compte vérifié
                    $stmt2 = $conn->prepare("UPDATE UTILISATEUR SET is_verified = 1, verification_token = NULL, token_expires = NULL WHERE id_utilisateur = ?");
                    $stmt2->bind_param("i", $user['id_utilisateur']);
                    if ($stmt2->execute()) {
                        $message = "Votre compte a bien été vérifié ! Vous pouvez maintenant vous connecter.";
                    } else {
                        $errors[] = "Erreur lors de la vérification du compte.";
                    }
                    $stmt2->close();
                }
            }
        }
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification du compte – CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="gradient-bg text-white min-h-screen flex items-center justify-center">
    <div class="glass p-8 rounded-lg max-w-md w-full text-center">
        <?php if (!empty($message)): ?>
            <p class="text-green-300 mb-4"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <p class="text-red-400 mb-2"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        <?php endif; ?>
        <a href="connexion.php" class="btn-primary px-4 py-2 rounded-lg text-white">Se connecter</a>
    </div>
</body>
</html>
