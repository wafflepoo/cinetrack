<?php
session_start();
require_once '../includes/config.conf.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs";
    } else {
        // Use MySQLi instead of PDO
        $stmt = $mysqli->prepare("SELECT * FROM UTILISATEUR WHERE email = ? AND est_verifie = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id_utilisateur'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_pseudo'] = $user['pseudo'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_role'] = $user['role'];
            
            // Redirect to intended page or dashboard
            $redirect = $_SESSION['redirect_after_login'] ?? '/pages/user/dashboard.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = "Email ou mot de passe incorrect";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="gradient-bg text-white min-h-screen flex flex-col">
    <?php include '../includes/header.php'; ?>
    
    <main class="flex-grow flex items-center justify-center py-16 px-4">
        <section class="w-full max-w-md">
            <div class="glass p-8 rounded-2xl border border-gray-700 shadow-2xl">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold">Connexion</h1>
                    <p class="text-gray-400 mt-2">Accédez à votre compte</p>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-500/20 border border-red-500/50 text-red-200 px-4 py-3 rounded-lg mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                    </div>

                    <div class="relative">
    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Mot de passe</label>

    <input type="password" id="password" name="password" required
        class="w-full px-4 py-3 pr-12 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">

    <!-- Icône œil -->
    <span onclick="togglePassword()" 
          class="absolute right-3 top-[42px] cursor-pointer text-gray-400 hover:text-gray-200">
        <i id="eyeIcon" class="fa-solid fa-eye"></i>
    </span>
</div>


                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="rounded bg-gray-700 border-gray-600 text-orange-500">
                            <span class="ml-2 text-sm text-gray-300">Se souvenir de moi</span>
                        </label>
                        <a href="forgot-password.php" class="text-sm text-orange-500 hover:text-orange-400">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" class="w-full py-3 btn-primary rounded-lg font-semibold text-white">
                        Se connecter
                    </button>
                </form>

                <div class="text-center mt-6">
                    <p class="text-gray-400">
                        Pas encore de compte ? 
                        <a href="inscription.php" class="text-orange-500 hover:text-orange-400 font-medium">S'inscrire</a>
                    </p>
                </div>
            </div>
        </section>
    </main>
    
    <script>
function togglePassword() {
    const input = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}
</script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>