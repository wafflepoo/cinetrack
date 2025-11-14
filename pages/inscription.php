<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

$error = '';
$success = '';

// Clés reCAPTCHA - À remplacer par vos vraies clés
define('RECAPTCHA_SITE_KEY', '6LeJbwwsAAAAALqLThIVd0_DDSIZiYEWVxD5KSGZ');
define('RECAPTCHA_SECRET_KEY', '6LeJbwwsAAAAABkId0c2JU1PiDwkzFG_QQFHQvsq');

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $pseudo = trim($_POST['pseudo'] ?? '');
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    // Validation
    if (empty($prenom) || empty($nom) || empty($email) || empty($password) || empty($confirm_password) || empty($pseudo)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif (empty($recaptcha_response)) {
        $error = 'Veuillez vérifier que vous n\'êtes pas un robot.';
    } else {
        // Vérification du captcha
        $recaptcha_verified = verifyRecaptcha($recaptcha_response);
        
        if (!$recaptcha_verified) {
            $error = 'Échec de la vérification du captcha. Veuillez réessayer.';
        } else {
            // Simulation d'inscription - En production, enregistrer dans la base de données
            $success = 'Inscription réussie ! Vous allez être redirigé vers la page de connexion.';
            
            // En production, vous enregistreriez l'utilisateur ici
            // et peut-être le connecteriez directement
            
            header('Refresh: 3; URL=connexion.php');
        }
    }
}

// Fonction de vérification reCAPTCHA
function verifyRecaptcha($recaptcha_response) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result, true);
    
    return $response['success'] ?? false;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - CineTrack</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <?php include '../header.php'; ?>
    
    <main>
        <section class="auth-section">
            <div class="container">
                <div class="auth-container">
                    <div class="auth-card">
                        <div class="auth-header">
                            <h1>Inscription</h1>
                            <p>Rejoignez la communauté CineTrack</p>
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

                        <form method="POST" action="inscription.php" class="auth-form" id="inscriptionForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="prenom">Prénom *</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-user"></i>
                                        <input type="text" 
                                               id="prenom" 
                                               name="prenom" 
                                               value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>" 
                                               placeholder="Votre prénom"
                                               required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="nom">Nom *</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-user"></i>
                                        <input type="text" 
                                               id="nom" 
                                               name="nom" 
                                               value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" 
                                               placeholder="Votre nom"
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Adresse email *</label>
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
                                <label for="pseudo">Pseudo *</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-at"></i>
                                    <input type="text" 
                                           id="pseudo" 
                                           name="pseudo" 
                                           value="<?php echo htmlspecialchars($_POST['pseudo'] ?? ''); ?>" 
                                           placeholder="Votre pseudo"
                                           required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Mot de passe *</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Minimum 6 caractères"
                                           required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength" id="password-strength"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirmer le mot de passe *</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           placeholder="Retapez votre mot de passe"
                                           required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Ajout du CAPTCHA -->
                            <div class="form-group">
                                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                            </div>
                            
                            <div class="form-options">
                                <label class="checkbox-container">
                                    <input type="checkbox" name="newsletter" checked>
                                    <span class="checkmark"></span>
                                    Recevoir les nouveautés et recommandations
                                </label>
                                
                                <label class="checkbox-container">
                                    <input type="checkbox" name="terms" required>
                                    <span class="checkmark"></span>
                                    J'accepte les <a href="#">conditions d'utilisation</a> et la <a href="#">politique de confidentialité</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-full" id="submitBtn">
                                <i class="fas fa-user-plus"></i>
                                Créer mon compte
                            </button>
                        </form>
                        
                        <div class="auth-divider">
                            <span>Ou</span>
                        </div>
                        
                        <div class="social-auth">
                            <button class="btn btn-social btn-google">
                                <i class="fab fa-google"></i>
                                S'inscrire avec Google
                            </button>
                            <button class="btn btn-social btn-facebook">
                                <i class="fab fa-facebook"></i>
                                S'inscrire avec Facebook
                            </button>
                        </div>
                        
                        <div class="auth-footer">
                            <p>Déjà un compte ? 
                                <a href="connexion.php" class="auth-link">Se connecter</a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="auth-features">
                        <div class="feature">
                            <i class="fas fa-star"></i>
                            <h3>Noter et critiquer</h3>
                            <p>Partagez votre avis sur les films et séries que vous regardez</p>
                        </div>
                        <div class="feature">
                            <i class="fas fa-heart"></i>
                            <h3>Listes personnalisées</h3>
                            <p>Créez des listes thématiques et partagez-les</p>
                        </div>
                        <div class="feature">
                            <i class="fas fa-robot"></i>
                            <h3>Recommandations intelligentes</h3>
                            <p>Découvrez du contenu adapté à vos goûts</p>
                        </div>
                        <div class="feature">
                            <i class="fas fa-users"></i>
                            <h3>Communauté active</h3>
                            <p>Échangez avec d'autres passionnés de cinéma</p>
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
    
    // Vérification de la force du mot de passe
    document.getElementById('password')?.addEventListener('input', function() {
        const password = this.value;
        const strengthDiv = document.getElementById('password-strength');
        
        if (!strengthDiv) return;
        
        let strength = 0;
        let message = '';
        let color = '';
        
        if (password.length >= 6) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/\d/)) strength++;
        if (password.match(/[^a-zA-Z\d]/)) strength++;
        
        switch(strength) {
            case 0:
            case 1:
                message = 'Faible';
                color = 'var(--error-color)';
                break;
            case 2:
                message = 'Moyen';
                color = 'var(--warning-color)';
                break;
            case 3:
                message = 'Bon';
                color = 'var(--success-color)';
                break;
            case 4:
                message = 'Fort';
                color = 'var(--success-color)';
                break;
        }
        
        if (password.length > 0) {
            strengthDiv.innerHTML = `<span style="color: ${color}">${message}</span>`;
            strengthDiv.style.display = 'block';
        } else {
            strengthDiv.style.display = 'none';
        }
    });

    // Validation du formulaire côté client pour le captcha
    document.getElementById('inscriptionForm')?.addEventListener('submit', function(e) {
        const recaptchaResponse = grecaptcha.getResponse();
        if (recaptchaResponse.length === 0) {
            e.preventDefault();
            alert('Veuillez vérifier que vous n\'êtes pas un robot.');
            return false;
        }
    });
    </script>
</body>
</html>