<?php
session_start();
require_once '../includes/config.conf';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';
    $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
    $terms = isset($_POST['terms']);

    $errors = [];

    // Validation
    if (empty($username) || strlen($username) < 3) {
        $errors['username'] = 'Le pseudo doit contenir au moins 3 caractères';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Veuillez entrer une adresse email valide';
    }

    if (empty($password) || strlen($password) < 6) {
        $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères';
    }

    if ($password !== $confirm_password) {
        $errors['confirm-password'] = 'Les mots de passe ne correspondent pas';
    }

    if (empty($recaptcha_token)) {
        $errors['captcha'] = 'Veuillez valider le reCAPTCHA';
    }

    if (!$terms) {
        $errors['terms'] = 'Vous devez accepter les conditions d\'utilisation';
    }

    // Verify reCAPTCHA (with cURL to avoid HTTPS wrapper issue)
    if (empty($errors) && !empty($recaptcha_token)) {
        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
        $recaptcha_data = [
            'secret' => RECAPTCHA_SECRET_KEY,
            'response' => $recaptcha_token
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $recaptcha_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($recaptcha_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $recaptcha_response = curl_exec($ch);
        curl_close($ch);
        
        $recaptcha_result = json_decode($recaptcha_response);
        
        if (!$recaptcha_result || !$recaptcha_result->success) {
            $errors['captcha'] = 'Échec de la vérification reCAPTCHA';
        }
    }

    // If no errors, register user
    if (empty($errors)) {
        // Generate verification token
        $verification_token = bin2hex(random_bytes(32));
        $token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Try MySQLi first, fallback to PDO if MySQLi not available
        try {
            // Try MySQLi
            if (class_exists('mysqli')) {
                $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                
                if ($conn->connect_error) {
                    throw new Exception('Erreur de connexion à la base de données');
                }
                
                // Check if user already exists
                $stmt = $conn->prepare("SELECT id_utilisateur FROM UTILISATEUR WHERE email = ? OR pseudo = ?");
                $stmt->bind_param("ss", $email, $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $errors['general'] = 'Un utilisateur avec ce pseudo ou email existe déjà';
                } else {
                    // Hash password and insert user with verification token
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO UTILISATEUR (pseudo, email, mot_de_passe, nom, prenom, verification_token, token_expiry, est_verifie) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $nom = $username;
                    $prenom = $username;
                    $est_verifie = 0;
                    $stmt->bind_param("sssssssi", $username, $email, $password_hash, $nom, $prenom, $verification_token, $token_expiry, $est_verifie);
                    
                    if ($stmt->execute()) {
                        $user_id = $conn->insert_id;
                        
                        // Send verification email
                        if (sendVerificationEmail($email, $username, $verification_token)) {
                            $success = true;
                            $email_sent = true;
                        } else {
                            // If email fails, delete the user and show error
                            $conn->query("DELETE FROM UTILISATEUR WHERE id_utilisateur = $user_id");
                            $errors['general'] = 'Erreur lors de l\'envoi de l\'email de vérification. Veuillez réessayer.';
                        }
                    } else {
                        $errors['general'] = 'Erreur lors de la création du compte';
                    }
                }
                $stmt->close();
                $conn->close();
            } else {
                // Fallback to PDO
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if user already exists
                $stmt = $conn->prepare("SELECT id_utilisateur FROM UTILISATEUR WHERE email = ? OR pseudo = ?");
                $stmt->execute([$email, $username]);
                
                if ($stmt->rowCount() > 0) {
                    $errors['general'] = 'Un utilisateur avec ce pseudo ou email existe déjà';
                } else {
                    // Hash password and insert user with verification token
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $nom = $username;
                    $prenom = $username;
                    $est_verifie = 0;
                    
                    $stmt = $conn->prepare("INSERT INTO UTILISATEUR (pseudo, email, mot_de_passe, nom, prenom, verification_token, token_expiry, est_verifie) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    if ($stmt->execute([$username, $email, $password_hash, $nom, $prenom, $verification_token, $token_expiry, $est_verifie])) {
                        $user_id = $conn->lastInsertId();
                        
                        // Send verification email
                        if (sendVerificationEmail($email, $username, $verification_token)) {
                            $success = true;
                            $email_sent = true;
                        } else {
                            // If email fails, delete the user and show error
                            $conn->query("DELETE FROM UTILISATEUR WHERE id_utilisateur = $user_id");
                            $errors['general'] = 'Erreur lors de l\'envoi de l\'email de vérification. Veuillez réessayer.';
                        }
                    } else {
                        $errors['general'] = 'Erreur lors de la création du compte';
                    }
                }
            }
        } catch (Exception $e) {
            $errors['general'] = 'Erreur de connexion à la base de données';
        }
    }
}

// Function to send verification email - VERSION POUR DÉVELOPPEMENT LOCAL
// Function to send verification email - VERSION POUR PRODUCTION
// Function to send verification email - VERSION RÉELLE
function sendVerificationEmail($email, $username, $token) {
    // URL ABSOLUE pour votre hébergement AlwaysData
    $verification_link = "https://cinetrack.alwaysdata.net/pages/verify_email.php?token=" . $token;
    
    // Sujet de l'email
    $subject = "Activez votre compte CineTrack";
    
    // Corps de l'email en HTML
    $message = "
    <html>
    <head>
        <title>Activation de votre compte CineTrack</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
            .container { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; }
            .header { text-align: center; color: #ff8c00; }
            .button { background: #ff8c00; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
            .footer { margin-top: 30px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>CineTrack</h1>
            </div>
            <h2>Bonjour $username,</h2>
            <p>Merci de vous être inscrit sur <strong>CineTrack</strong> !</p>
            <p>Pour activer votre compte et commencer à explorer notre univers cinématographique, veuillez cliquer sur le bouton ci-dessous :</p>
            
            <div style='text-align: center;'>
                <a href='$verification_link' class='button'>Activer mon compte</a>
            </div>
            
            <p>Ou copiez-collez ce lien dans votre navigateur :</p>
            <p style='word-break: break-all; background: #f8f8f8; padding: 10px; border-radius: 5px;'>$verification_link</p>
            
            <p><strong>Attention :</strong> Ce lien expirera dans 24 heures.</p>
            
            <div class='footer'>
                <p>Si vous n'avez pas créé de compte sur CineTrack, veuillez ignorer cet email.</p>
                <p>© 2024 CineTrack. Tous droits réservés.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Version texte pour les clients email qui ne supportent pas HTML
    $message_text = "
    Bonjour $username,
    
    Merci de vous être inscrit sur CineTrack !
    
    Pour activer votre compte, veuillez cliquer sur le lien suivant :
    
    $verification_link
    
    Ce lien expirera dans 24 heures.
    
    Si vous n'avez pas créé de compte sur CineTrack, veuillez ignorer cet email.
    
    Cordialement,
    L'équipe CineTrack
    ";
    
    // En-têtes de l'email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@cinetrack.alwaysdata.net" . "\r\n";
    $headers .= "Reply-To: no-reply@cinetrack.alwaysdata.net" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Envoi de l'email
    $email_sent = mail($email, $subject, $message, $headers);
    
    // Log pour débogage
    error_log("=== TENTATIVE D'ENVOI D'EMAIL ===");
    error_log("Destinataire: " . $email);
    error_log("Lien de vérification: " . $verification_link);
    error_log("Email envoyé: " . ($email_sent ? "OUI" : "NON"));
    error_log("=====================================");
    
    return $email_sent;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineTrack - Créer un compte</title>
    <!-- reCAPTCHA API -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        * {
            font-family: 'Inter', sans-serif;
            scroll-behavior: smooth;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #0a0e14 0%, #05080d 100%);
        }

        .glass {
            background: rgba(30, 30, 40, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #ff9d1a 0%, #ff7c1a 100%);
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(255, 140, 0, 0.4);
        }

        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 0.8s ease forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-in-left {
            opacity: 0;
            transform: translateX(-30px);
            animation: slideInLeft 0.8s ease forwards;
        }

        @keyframes slideInLeft {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .slide-in-right {
            opacity: 0;
            transform: translateX(30px);
            animation: slideInRight 0.8s ease forwards;
        }

        @keyframes slideInRight {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .stagger-animation > * {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 0.8s ease forwards;
        }

        .stagger-animation > *:nth-child(1) { animation-delay: 0.1s; }
        .stagger-animation > *:nth-child(2) { animation-delay: 0.2s; }
        .stagger-animation > *:nth-child(3) { animation-delay: 0.3s; }
        .stagger-animation > *:nth-child(4) { animation-delay: 0.4s; }
        .stagger-animation > *:nth-child(5) { animation-delay: 0.5s; }
        .stagger-animation > *:nth-child(6) { animation-delay: 0.6s; }

        .strength-weak { color: #e74c3c; }
        .strength-medium { color: #f39c12; }
        .strength-strong { color: #27ae60; }

        .form-input {
            background: rgba(30, 30, 40, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            border-color: #ff8c00;
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.2);
        }

        .form-input::placeholder {
            color: #9CA3AF;
        }

        .terms-link {
            color: #ff8c00;
            text-decoration: none;
            font-weight: 600;
        }

        .terms-link:hover {
            text-decoration: underline;
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.85rem;
            margin-top: 5px;
        }

        .success-message {
            color: #27ae60;
            font-size: 0.85rem;
            margin-top: 5px;
        }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen flex flex-col">
    
    <!-- Header -->
    <?php include '../includes/header.php'; ?>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center py-16 px-4">
        <div class="container max-w-4xl w-full">
            <div class="glass rounded-2xl overflow-hidden shadow-2xl">
                <div class="grid grid-cols-1 lg:grid-cols-2">
                    <!-- Brand Section -->
                    <div class="p-8 lg:p-12 bg-gradient-to-br from-gray-900 to-gray-800 relative overflow-hidden">
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute top-0 left-0 w-64 h-64 bg-orange-500 rounded-full -translate-x-32 -translate-y-32"></div>
                            <div class="absolute bottom-0 right-0 w-64 h-64 bg-purple-500 rounded-full translate-x-32 translate-y-32"></div>
                        </div>
                        
                        <div class="relative z-10">
                            <div class="flex items-center space-x-2 mb-6">
                                <div class="text-4xl text-orange-500"><i class="fas fa-film"></i></div>
                                <span class="text-3xl font-bold">
                                    <span class="text-orange-500">Cine</span><span class="text-white">Track</span>
                                </span>
                            </div>
                            
                            <h1 class="text-3xl font-bold mb-4 slide-in-left">Rejoignez notre communauté de cinéphiles</h1>
                            <p class="text-gray-300 mb-8 slide-in-left" style="animation-delay: 0.1s;">
                                Découvrez un univers infini de films, séries et documentaires.
                            </p>
                            
                            <div class="space-y-4 stagger-animation">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-orange-500/20 flex items-center justify-center">
                                        <i class="fas fa-check text-orange-500"></i>
                                    </div>
                                    <span class="text-gray-300">Accédez à des recommandations personnalisées</span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-orange-500/20 flex items-center justify-center">
                                        <i class="fas fa-check text-orange-500"></i>
                                    </div>
                                    <span class="text-gray-300">Créez vos listes de films préférés</span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-orange-500/20 flex items-center justify-center">
                                        <i class="fas fa-check text-orange-500"></i>
                                    </div>
                                    <span class="text-gray-300">Partagez vos critiques avec la communauté</span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-orange-500/20 flex items-center justify-center">
                                        <i class="fas fa-check text-orange-500"></i>
                                    </div>
                                    <span class="text-gray-300">Ne ratez plus aucune sortie cinéma</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Section -->
                    <div class="p-8 lg:p-12 bg-gray-800/50">
                        <div class="form-header mb-8 slide-in-right">
                            <h2 class="text-2xl font-bold mb-2">Créer un compte</h2>
                            <p class="text-gray-400">Rejoignez notre communauté de cinéphiles</p>
                        </div>

                        <?php if (isset($success) && $success && isset($email_sent) && $email_sent): ?>
    <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-6">
        <div class="flex items-center">
            <i class="fas fa-envelope-circle-check mr-2"></i>
            <span>
                <strong>Compte créé avec succès !</strong><br>
                Un email de vérification a été envoyé à <strong><?php echo htmlspecialchars($email); ?></strong><br>
                <small class="text-gray-400">Veuillez consulter votre boîte mail et cliquer sur le lien pour activer votre compte.</small>
            </span>
        </div>
    </div>
<?php endif; ?>
<?php if (isset($success) && $success && isset($email_sent) && !$email_sent): ?>
    <div class="bg-yellow-500/20 border border-yellow-500 text-yellow-300 px-4 py-3 rounded-lg mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <span>
                <strong>Compte créé mais problème d'envoi d'email</strong><br>
                <small class="text-gray-400">Veuillez contacter le support pour activer votre compte.</small>
            </span>
        </div>
    </div>
<?php endif; ?>

                        <?php if (isset($errors['general'])): ?>
                            <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-6">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                    <span><?php echo htmlspecialchars($errors['general']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="space-y-6 stagger-animation">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-300 mb-2">Pseudo</label>
                                <input type="text" id="username" name="username" class="w-full px-4 py-3 form-input rounded-lg" placeholder="Veuillez renseigner votre pseudo" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                                <?php if (isset($errors['username'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['username']); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                                <input type="email" id="email" name="email" class="w-full px-4 py-3 form-input rounded-lg" placeholder="votre@email.com" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Mot de passe</label>
                                <input type="password" id="password" name="password" class="w-full px-4 py-3 form-input rounded-lg" placeholder="••••••••" required>
                                <div class="password-strength mt-2 text-sm" id="password-strength"></div>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label for="confirm-password" class="block text-sm font-medium text-gray-300 mb-2">Confirmer le mot de passe</label>
                                <input type="password" id="confirm-password" name="confirm-password" class="w-full px-4 py-3 form-input rounded-lg" placeholder="••••••••" required>
                                <?php if (isset($errors['confirm-password'])): ?>
                                    <div class="error-message"><?php echo htmlspecialchars($errors['confirm-password']); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- reCAPTCHA -->
                            <div class="captcha-container flex justify-center my-4">
                                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                            </div>
                            <?php if (isset($errors['captcha'])): ?>
                                <div class="error-message text-center"><?php echo htmlspecialchars($errors['captcha']); ?></div>
                            <?php endif; ?>
                            
                            <div class="flex items-start space-x-3">
                                <input type="checkbox" id="terms" name="terms" class="mt-1 form-checkbox rounded bg-gray-700 border-gray-600 text-orange-500 focus:ring-orange-500 focus:ring-offset-gray-800" 
                                       <?php echo isset($_POST['terms']) ? 'checked' : ''; ?> required>
                                <label for="terms" class="text-sm text-gray-300">
                                    J'accepte les <a href="#" class="terms-link">conditions d'utilisation</a>
                                </label>
                            </div>
                            <?php if (isset($errors['terms'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['terms']); ?></div>
                            <?php endif; ?>
                            
                            <button type="submit" class="w-full py-3 btn-primary rounded-lg font-semibold text-white transition-all duration-300">
                                Créer mon compte
                            </button>
                            
                            <div class="text-center text-gray-400 mt-6">
                                Déjà membre ? <a href="connexion.php" class="text-orange-500 hover:text-orange-400 font-medium transition-all duration-300">Se connecter</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const passwordStrength = document.getElementById('password-strength');
            
            // Vérification de la force du mot de passe
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = '';
                let strengthClass = '';
                
                if (password.length === 0) {
                    strength = '';
                } else if (password.length < 6) {
                    strength = 'Faible';
                    strengthClass = 'strength-weak';
                } else if (password.length < 8) {
                    strength = 'Moyen';
                    strengthClass = 'strength-medium';
                } else {
                    // Vérification de complexité
                    const hasUpperCase = /[A-Z]/.test(password);
                    const hasLowerCase = /[a-z]/.test(password);
                    const hasNumbers = /\d/.test(password);
                    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                    
                    const complexity = [hasUpperCase, hasLowerCase, hasNumbers, hasSpecialChar].filter(Boolean).length;
                    
                    if (complexity >= 3) {
                        strength = 'Fort';
                        strengthClass = 'strength-strong';
                    } else {
                        strength = 'Moyen';
                        strengthClass = 'strength-medium';
                    }
                }
                
                if (strength) {
                    passwordStrength.textContent = `Force du mot de passe: ${strength}`;
                    passwordStrength.className = `password-strength ${strengthClass}`;
                } else {
                    passwordStrength.textContent = '';
                }
            });

            // Animation pour les éléments avec délai
            const staggerElements = document.querySelectorAll('.stagger-animation > *');
            staggerElements.forEach((element, index) => {
                element.style.animationDelay = `${0.1 + (index * 0.1)}s`;
            });
        });
    </script>
</body>
</html>