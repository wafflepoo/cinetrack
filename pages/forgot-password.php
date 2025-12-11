<?php include '../includes/header.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/animations.css">
    <style>
        .auth-hero {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(255, 140, 0, 0.1) 100%);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        .auth-hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(239, 68, 68, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 140, 0, 0.15) 0%, transparent 50%);
            filter: blur(60px);
            z-index: -1;
        }
        
        .auth-container {
            background: rgba(30, 30, 40, 0.25);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
        }
        
        .auth-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .auth-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #ef4444;
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);
        }
        
        .success-message {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
    </style>
    <!-- EmailJS -->
    <script src="https://cdn.jsdelivr.net/npm/emailjs-com@3/dist/email.min.js"></script>
    <script>
        (function() {
            emailjs.init("nquGdbCR5gVZrLsZ4");  
        })();
    </script>
</head>
<body class="gradient-bg text-white">

<main class="auth-hero pt-20">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="auth-container p-8 w-full max-w-md shadow-2xl">

            <!-- Header -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2">Mot de passe oublié ?</h1>
                <p class="text-gray-400">Entrez votre email pour réinitialiser votre mot de passe</p>
            </div>

            <!-- Success Message -->
            <div class="success-message rounded-xl p-4 mb-6 hidden" id="success-message">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    <div>
                        <p class="text-green-400 font-semibold">Email envoyé !</p>
                        <p class="text-green-300 text-sm">Vérifiez votre boîte mail pour les instructions</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form class="space-y-6" id="reset-form">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <div class="relative">
                        <input 
                            id="email"
                            type="email" 
                            name="email"  
                            placeholder="votre@email.com"
                            class="auth-input w-full px-4 py-3 rounded-xl text-white placeholder-gray-500 focus:outline-none pl-12"
                            required
                        >
                        <i class="fas fa-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>

                <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white py-3 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg">
                    Envoyer les instructions
                </button>
            </form>

            <!-- Back to Login -->
            <div class="text-center mt-6">
                <a href="connexion.php" class="text-red-500 hover:text-red-400 font-semibold transition flex items-center justify-center space-x-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Retour à la connexion</span>
                </a>
            </div>

            <!-- Help Text -->
            <div class="mt-6 p-4 bg-gray-800/50 rounded-xl">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-info-circle text-blue-400 mt-1"></i>
                    <div>
                        <p class="text-sm text-gray-300">
                            Vous recevrez un email avec un lien pour réinitialiser votre mot de passe. 
                            Le lien expirera dans 1 heure pour des raisons de sécurité.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reset-form');
    const successMessage = document.getElementById('success-message');

    form.addEventListener("submit", async function(e) {
        e.preventDefault();

        const email = document.getElementById("email").value;
        const formData = new FormData();
        formData.append("email", email);

        const response = await fetch("https://cinetrack.alwaysdata.net/pages/reset_request.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.error) {
            alert(result.error);
            return;
        }

        // Envoi EmailJS
        emailjs.send("service_g7mou2i", "template_2ease97", {
            email: result.email,
            link: result.link
        }).then(() => {
            successMessage.classList.remove("hidden");
        }).catch(err => {
            alert("Erreur EmailJS : " + err);
        });
    });
});
</script>

</body>
</html>
