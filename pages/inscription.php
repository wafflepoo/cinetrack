<?php
session_start();
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
            display: none;
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
                        
                        <form id="signup-form" class="space-y-6 stagger-animation">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-300 mb-2">Pseudo</label>
                                <input type="text" id="username" class="w-full px-4 py-3 form-input rounded-lg" placeholder="Veuillez renseigner votre pseudo" required>
                                <div class="error-message" id="username-error"></div>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                                <input type="email" id="email" class="w-full px-4 py-3 form-input rounded-lg" placeholder="votre@email.com" required>
                                <div class="error-message" id="email-error"></div>
                            </div>
                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Mot de passe</label>
                                <input type="password" id="password" class="w-full px-4 py-3 form-input rounded-lg" placeholder="••••••••" required>
                                <div class="password-strength mt-2 text-sm" id="password-strength"></div>
                                <div class="error-message" id="password-error"></div>
                            </div>
                            
                            <div>
                                <label for="confirm-password" class="block text-sm font-medium text-gray-300 mb-2">Confirmer le mot de passe</label>
                                <input type="password" id="confirm-password" class="w-full px-4 py-3 form-input rounded-lg" placeholder="••••••••" required>
                                <div class="error-message" id="confirm-password-error"></div>
                            </div>
                            
                            <!-- reCAPTCHA -->
                            <div class="captcha-container flex justify-center my-4">
                                <div class="g-recaptcha" data-sitekey="6LcrXhMsAAAAAKrE4f5EtzyYoqsYbY9yZtTOdlHU"></div>
                            </div>
                            <div class="error-message text-center" id="captcha-error"></div>
                            
                            <div class="flex items-start space-x-3">
                                <input type="checkbox" id="terms" class="mt-1 form-checkbox rounded bg-gray-700 border-gray-600 text-orange-500 focus:ring-orange-500 focus:ring-offset-gray-800" required>
                                <label for="terms" class="text-sm text-gray-300">
                                    J'accepte les <a href="#" class="terms-link">conditions d'utilisation</a>
                                </label>
                            </div>
                            <div class="error-message" id="terms-error"></div>
                            
                            <button type="submit" class="w-full py-3 btn-primary rounded-lg font-semibold text-white transition-all duration-300" id="submit-btn">
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
            const form = document.getElementById('signup-form');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm-password');
            const passwordStrength = document.getElementById('password-strength');
            const submitBtn = document.getElementById('submit-btn');
            
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
            
            // Validation du formulaire
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Réinitialiser les erreurs
                document.querySelectorAll('.error-message').forEach(el => {
                    el.style.display = 'none';
                    el.textContent = '';
                });
                
                let isValid = true;
                
                // Validation du pseudo
                const username = document.getElementById('username').value;
                if (username.length < 3) {
                    showError('username-error', 'Le pseudo doit contenir au moins 3 caractères');
                    isValid = false;
                }
                
                // Validation de l'email
                const email = document.getElementById('email').value;
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    showError('email-error', 'Veuillez entrer une adresse email valide');
                    isValid = false;
                }
                
                // Validation du mot de passe
                const password = passwordInput.value;
                if (password.length < 6) {
                    showError('password-error', 'Le mot de passe doit contenir au moins 6 caractères');
                    isValid = false;
                }
                
                // Validation de la confirmation du mot de passe
                if (password !== confirmPasswordInput.value) {
                    showError('confirm-password-error', 'Les mots de passe ne correspondent pas');
                    isValid = false;
                }
                
                // Validation reCAPTCHA
                const recaptchaResponse = grecaptcha.getResponse();
                if (recaptchaResponse.length === 0) {
                    showError('captcha-error', 'Veuillez valider le reCAPTCHA');
                    isValid = false;
                }
                
                // Validation des conditions
                if (!document.getElementById('terms').checked) {
                    showError('terms-error', 'Vous devez accepter les conditions d\'utilisation');
                    isValid = false;
                }
                
                if (isValid) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Création du compte...';
                    
                    try {
                        // Simulation d'envoi au serveur
                        await new Promise(resolve => setTimeout(resolve, 2000));
                        
                        // Ici, vous enverriez les données à votre serveur
                        // const response = await fetch('/includes/register.php', {
                        //     method: 'POST',
                        //     headers: { 'Content-Type': 'application/json' },
                        //     body: JSON.stringify({
                        //         username: username,
                        //         email: email,
                        //         password: password,
                        //         recaptcha: recaptchaResponse
                        //     })
                        // });
                        
                        alert('Votre compte a été créé avec succès ! Bienvenue dans la communauté CineTrack.');
                        form.reset();
                        grecaptcha.reset();
                        
                        // Redirection vers la page de connexion
                        setTimeout(() => {
                            window.location.href = 'connexion.php';
                        }, 1500);
                        
                    } catch (error) {
                        alert('Une erreur est survenue lors de la création du compte. Veuillez réessayer.');
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Créer mon compte';
                    }
                }
            });
            
            function showError(elementId, message) {
                const errorElement = document.getElementById(elementId);
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }

            // Animation pour les éléments avec délai
            const staggerElements = document.querySelectorAll('.stagger-animation > *');
            staggerElements.forEach((element, index) => {
                element.style.animationDelay = `${0.1 + (index * 0.1)}s`;
            });
        });
    </script>
</body>
</html>