<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

$error = '';
$success = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // Simulation de connexion - En production, vérifier dans la base de données
        $valid_email = 'utilisateur@cinetrack.com';
        $valid_password = 'password123';
        
        if ($email === $valid_email && $password === $valid_password) {
            // Connexion réussie
            $_SESSION['user_id'] = 1;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = 'Utilisateur CineTrack';
            $_SESSION['role'] = 'user';
            
            $success = 'Connexion réussie ! Redirection...';
            header('Refresh: 2; URL=../index.php');
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - CineTrack</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../header.php'; ?>
    
    <main>
        <section class="auth-section">
            <div class="container">
                <div class="auth-container">
                    <div class="auth-card">
                        <div class="auth-header">
                            <h1>Connexion</h1>
                            <p>Accédez à votre compte CineTrack</p>
                        </div>
                        
                        <?php if($error): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="connexion.php" class="auth-form">
                            <div class="form-group">
                                <label for="email">Adresse email</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                           placeholder="votre@email.com"
                                           required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Mot de passe</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Votre mot de passe"
                                           required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-options">
                                <label class="checkbox-container">
                                    <input type="checkbox" name="remember">
                                    <span class="checkmark"></span>
                                    Se souvenir de moi
                                </label>
                                <a href="mot-de-passe-oublie.php" class="forgot-password">
                                    Mot de passe oublié ?
                                </a>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-full">
                                <i class="fas fa-sign-in-alt"></i>
                                Se connecter
                            </button>
                        </form>
                        
                        <div class="auth-divider">
                            <span>Ou</span>
                        </div>
                        
                        <div class="social-auth">
                            <button class="btn btn-social btn-google">
                                <i class="fab fa-google"></i>
                                Continuer avec Google
                            </button>
                            <button class="btn btn-social btn-facebook">
                                <i class="fab fa-facebook"></i>
                                Continuer avec Facebook
                            </button>
                        </div>
                        
                        <div class="auth-footer">
                            <p>Pas encore de compte ? 
                                <a href="inscription.php" class="auth-link">Créer un compte</a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="auth-features">
                        <div class="feature">
                            <i class="fas fa-film"></i>
                            <h3>Accédez à tout le catalogue</h3>
                            <p>Des milliers de films et séries à découvrir</p>
                        </div>
                        <div class="feature">
                            <i class="fas fa-comments"></i>
                            <h3>Rejoignez la communauté</h3>
                            <p>Partagez vos critiques et discutez avec d'autres passionnés</p>
                        </div>
                        <div class="feature">
                            <i class="fas fa-list"></i>
                            <h3>Créez vos listes</h3>
                            <p>Organisez vos films et séries préférés</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include '../footer.php'; ?>
    
    <script src="../js/script.js"></script>
    <script>
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.parentNode.querySelector('.password-toggle i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }
    </script>
</body>
</html>