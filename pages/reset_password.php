<?php
session_start();
require_once '../includes/config.conf'; // contient DB_HOST, DB_USER, DB_PASS, DB_NAME

// Vérifier si le token est présent
if (!isset($_GET['token'])) {
    die("Lien invalide");
}

$token = $_GET['token'];
$errors = [];
$success = false;

// Connexion à la base
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Erreur de connexion à la base de données");
}

// Vérifier le token et récupérer l'utilisateur correspondant
$stmt = $conn->prepare("SELECT id_utilisateur, email FROM UTILISATEUR WHERE verification_token = ? AND token_expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Token invalide ou expiré");
}

$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || strlen($new_password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    }

    if ($new_password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }

    if (empty($errors)) {
        // Hasher le mot de passe
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Mettre à jour le mot de passe et invalider le token
        $update_stmt = $conn->prepare("UPDATE UTILISATEUR SET mot_de_passe = ?, verification_token = NULL, token_expiry = NULL WHERE id_utilisateur = ?");
        $update_stmt->bind_param("si", $password_hash, $user['id_utilisateur']);
        if ($update_stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Erreur lors de la mise à jour du mot de passe";
        }
        $update_stmt->close();
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser mot de passe - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico">
    <style>
        .auth-hero { 
            background: linear-gradient(135deg, rgba(239,68,68,0.1) 0%, rgba(255,140,0,0.1) 100%);
            min-height: 100vh; position: relative; overflow: hidden; 
        }
        .auth-hero::before { 
            content: ''; position: absolute; top:0; left:0; right:0; bottom:0;
            background: radial-gradient(circle at 20% 80%, rgba(239,68,68,0.15) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(255,140,0,0.15) 0%, transparent 50%);
            filter: blur(60px); z-index:-1; 
        }
        .auth-container { 
            background: rgba(30,30,40,0.25); backdrop-filter: blur(20px); border:1px solid rgba(255,255,255,0.1); border-radius:20px; 
        }
        .auth-input { 
            background: rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); transition: all 0.3s ease; 
        }
        .auth-input:focus { 
            background: rgba(255,255,255,0.15); border-color:#ef4444; box-shadow: 0 0 20px rgba(239,68,68,0.2); 
        }
        .success-message { 
            background: rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.3); 
        }
        .error-message { 
            background: rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); padding:10px; border-radius:10px; margin-bottom:10px; 
        }
    </style>
</head>
<body class="gradient-bg text-white">

<main class="auth-hero pt-20">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="auth-container p-8 w-full max-w-md shadow-2xl">

            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2">Réinitialiser votre mot de passe</h1>
                <p class="text-gray-400">Entrez votre nouveau mot de passe ci-dessous</p>
            </div>

            <?php if (!empty($errors)) : ?>
                <div class="error-message">
                    <?php foreach ($errors as $error) : ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success) : ?>
                <div class="success-message rounded-xl p-4 mb-6">
                    <p class="text-green-400 font-semibold">Mot de passe réinitialisé avec succès !</p>
                    <p class="text-green-300 text-sm"><a href="connexion.php" class="underline">Retour à la connexion</a></p>
                </div>
            <?php else : ?>
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nouveau mot de passe</label>
                        <input type="password" name="new_password" class="auth-input w-full px-4 py-3 rounded-xl text-white placeholder-gray-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Confirmer le mot de passe</label>
                        <input type="password" name="confirm_password" class="auth-input w-full px-4 py-3 rounded-xl text-white placeholder-gray-500 focus:outline-none" required>
                    </div>
                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white py-3 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg">
                        Valider
                    </button>
                </form>
            <?php endif; ?>

        </div>
    </div>
</main>

</body>
</html>
