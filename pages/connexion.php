<?php
session_start();
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        * {
            font-family: 'Inter', sans-serif;
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

        .slide-in-up {
            opacity: 0;
            transform: translateY(30px);
            animation: slideInUp 0.8s ease forwards;
        }

        @keyframes slideInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen flex flex-col">
    
    <!-- Header -->
    <?php include '../includes/header.php'; ?>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center py-16 px-4">
        <!-- Form Section -->
        <section class="w-full max-w-md slide-in-up">
            <div class="glass p-8 rounded-2xl border border-gray-700 shadow-2xl">
                <!-- Logo -->
                <div class="text-center mb-8 fade-in">
                    <div class="flex items-center justify-center space-x-2 mb-4">
                        <div class="text-4xl text-orange-500"><i class="fas fa-film"></i></div>
                        <span class="text-3xl font-bold">
                            <span class="text-orange-500">Cine</span><span class="text-white">Track</span>
                        </span>
                    </div>
                    <h1 class="text-3xl font-bold">Connexion</h1>
                    <p class="text-gray-400 mt-2">Accédez à votre compte</p>
                </div>

                <!-- Formulaire -->
                <form class="space-y-6 stagger-animation">
                    <div class="fade-in" style="animation-delay: 0.1s;">
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email"
                            class="w-full px-4 py-3 form-input rounded-lg text-white placeholder-gray-400 focus:outline-none transition-all duration-300"
                            placeholder="votre@email.com"
                            required
                        >
                    </div>

                    <div class="fade-in" style="animation-delay: 0.2s;">
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Mot de passe</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password"
                            class="w-full px-4 py-3 form-input rounded-lg text-white placeholder-gray-400 focus:outline-none transition-all duration-300"
                            placeholder="Votre mot de passe"
                            required
                        >
                    </div>

                    <div class="flex items-center justify-between fade-in" style="animation-delay: 0.3s;">
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded bg-gray-700 border-gray-600 text-orange-500 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-300">Se souvenir de moi</span>
                        </label>
                        <a href="/forgotPassword.php" class="text-sm text-orange-500 hover:text-orange-400 transition-all duration-300">
                            Mot de passe oublié ?
                        </a>
                    </div>

                    <button 
                        type="submit"
                        class="w-full py-3 btn-primary rounded-lg font-semibold text-white transition-all duration-300 fade-in"
                        style="animation-delay: 0.4s;"
                    >
                        Se connecter
                    </button>
                </form>

                <!-- Lien vers inscription -->
                <div class="text-center mt-6 fade-in" style="animation-delay: 0.5s;">
                    <p class="text-gray-400">
                        Pas encore de compte ? 
                        <a href="inscription.php" class="text-orange-500 hover:text-orange-400 transition-all duration-300 font-medium">
                            S'inscrire
                        </a>
                    </p>
                </div>

                <!-- Retour à l'accueil -->
                <div class="text-center mt-4 fade-in" style="animation-delay: 0.6s;">
                    <a href="../index.php" class="text-gray-400 hover:text-orange-500 transition-all duration-300 text-sm inline-flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>Retour à l'accueil
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <script>
        // Animation pour les éléments avec délai
        document.addEventListener('DOMContentLoaded', function() {
            const staggerElements = document.querySelectorAll('.stagger-animation > *');
            staggerElements.forEach((element, index) => {
                element.style.animationDelay = `${0.1 + (index * 0.1)}s`;
            });
        });

        // Gestion de la soumission du formulaire
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            // Simulation de connexion
            submitBtn.textContent = 'Connexion...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                // Ici vous ajouteriez votre logique de connexion réelle
                // Pour l'instant, on simule une connexion réussie
                alert('Connexion réussie ! Redirection...');
                window.location.href = '../index.php';
                
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });
    </script>
</body>
</html>