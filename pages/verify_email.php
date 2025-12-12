<?php
session_start();
require_once '../includes/config.conf';

// Vérifier si le token est présent dans l'URL
if (!isset($_GET['token']) || empty($_GET['token'])) {
    header('Location: ../index.php');
    exit;
}

$token = $_GET['token'];
$success = false;
$message = '';

try {
    // Connexion à la base de données
    if (class_exists('mysqli')) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception('Erreur de connexion à la base de données');
        }
        
        // Vérifier si le token existe et n'est pas expiré
        $stmt = $conn->prepare("SELECT id_utilisateur, pseudo, email, token_expiry FROM UTILISATEUR WHERE verification_token = ? AND est_verifie = 0");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $now = date('Y-m-d H:i:s');
            
            // Vérifier si le token n'est pas expiré
            if ($user['token_expiry'] > $now) {
                // Activer le compte
                $stmt = $conn->prepare("UPDATE UTILISATEUR SET est_verifie = 1, verification_token = NULL, token_expiry = NULL WHERE id_utilisateur = ?");
                $stmt->bind_param("i", $user['id_utilisateur']);
                
                if ($stmt->execute()) {
                    $success = true;
                    $message = "Votre compte a été activé avec succès ! Vous pouvez maintenant vous connecter.";
                    
                    // Connecter automatiquement l'utilisateur
                    $_SESSION['user_id'] = $user['id_utilisateur'];
                    $_SESSION['username'] = $user['pseudo'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['logged_in'] = true;
                } else {
                    $success = false;
                    $message = "Erreur lors de l'activation du compte. Veuillez réessayer.";
                }
            } else {
                $success = false;
                $message = "Le lien de confirmation a expiré. Veuillez vous réinscrire.";
                
                // Supprimer le compte expiré
                $conn->query("DELETE FROM UTILISATEUR WHERE id_utilisateur = " . $user['id_utilisateur']);
            }
        } else {
            $success = false;
            $message = "Lien de confirmation invalide ou compte déjà activé.";
        }
        
        $stmt->close();
        $conn->close();
    } else {
        // Version PDO (fallback)
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Vérifier si le token existe et n'est pas expiré
        $stmt = $conn->prepare("SELECT id_utilisateur, pseudo, email, token_expiry FROM UTILISATEUR WHERE verification_token = ? AND est_verifie = 0");
        $stmt->execute([$token]);
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $now = date('Y-m-d H:i:s');
            
            // Vérifier si le token n'est pas expiré
            if ($user['token_expiry'] > $now) {
                // Activer le compte
                $stmt = $conn->prepare("UPDATE UTILISATEUR SET est_verifie = 1, verification_token = NULL, token_expiry = NULL WHERE id_utilisateur = ?");
                
                if ($stmt->execute([$user['id_utilisateur']])) {
                    $success = true;
                    $message = "Votre compte a été activé avec succès ! Vous pouvez maintenant vous connecter.";
                    
                    // Connecter automatiquement l'utilisateur
                    $_SESSION['user_id'] = $user['id_utilisateur'];
                    $_SESSION['username'] = $user['pseudo'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['logged_in'] = true;
                } else {
                    $success = false;
                    $message = "Erreur lors de l'activation du compte. Veuillez réessayer.";
                }
            } else {
                $success = false;
                $message = "Le lien de confirmation a expiré. Veuillez vous réinscrire.";
                
                // Supprimer le compte expiré
                $conn->query("DELETE FROM UTILISATEUR WHERE id_utilisateur = " . $user['id_utilisateur']);
            }
        } else {
            $success = false;
            $message = "Lien de confirmation invalide ou compte déjà activé.";
        }
    }
} catch (Exception $e) {
    $success = false;
    $message = "Erreur de connexion à la base de données.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineTrack - Confirmation de compte</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #0a0e14 0%, #05080d 100%); }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-gray-800/50 backdrop-blur-lg rounded-2xl p-8 shadow-2xl border border-gray-700">
        <div class="text-center mb-6">
            <div class="flex items-center justify-center space-x-2 mb-4">
                <div class="text-3xl text-orange-500"><i class="fas fa-film"></i></div>
                <span class="text-2xl font-bold">
                    <span class="text-orange-500">Cine</span><span class="text-white">Track</span>
                </span>
            </div>
            <h1 class="text-2xl font-bold">Confirmation de compte</h1>
        </div>
        
        <div class="text-center">
            <?php if ($success): ?>
                <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?php echo $message; ?></span>
                    </div>
                </div>
                <p class="text-gray-300 mb-6">Vous allez être redirigé vers la page d'accueil...</p>
              <script>
    setTimeout(() => {
        // Redirection absolue vers votre domaine
        window.location.href = 'https://cinetrack.alwaysdata.net/index.php';
    }, 3000);
</script>
            <?php else: ?>
                <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo $message; ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="mt-6">
                <a href="../index.php" class="inline-block bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-6 rounded-lg transition-all duration-300 transform hover:-translate-y-1">
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</body>
</html>