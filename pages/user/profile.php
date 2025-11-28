<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #0a0e14 0%, #05080d 100%);
        }
        .glass {
            background: rgba(30, 30, 40, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .settings-nav {
            @apply flex items-center px-4 py-3 text-gray-400 rounded-lg transition;
        }
        .settings-nav:hover,
        .settings-nav.active {
            @apply bg-orange-500/10 text-orange-500;
        }
        .btn-primary {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 140, 0, 0.3);
        }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen">
    <?php include '../../includes/header.php'; ?>    
    
    <main class="pt-32 pb-16">
        <div class="max-w-4xl mx-auto px-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-black mb-2">Paramètres du compte</h1>
                <p class="text-gray-400 text-lg">Gérez vos informations personnelles et préférences</p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Navigation -->
                <div class="lg:col-span-1">
                    <div class="glass p-6 rounded-2xl sticky top-32">
                        <nav class="space-y-2">
                            <a href="#profile" class="settings-nav active">
                                <i class="fas fa-user mr-3"></i>Profil
                            </a>
                            <a href="#security" class="settings-nav">
                                <i class="fas fa-lock mr-3"></i>Sécurité
                            </a>
                            <a href="#privacy" class="settings-nav">
                                <i class="fas fa-shield-alt mr-3"></i>Confidentialité
                            </a>
                        </nav>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Profile Settings -->
                    <section id="profile" class="glass p-6 rounded-2xl">
                        <h2 class="text-2xl font-bold mb-6">Informations du profil</h2>
                        
                        <form class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Prénom</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['prenom']); ?>" 
                                           class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Nom</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['nom']); ?>" 
                                           class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Pseudo</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['pseudo']); ?>" 
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                            </div>
                            
                            <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-semibold">
                                <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                            </button>
                        </form>
                    </section>
                    
                    <!-- Security Settings -->
                    <section id="security" class="glass p-6 rounded-2xl hidden">
                        <h2 class="text-2xl font-bold mb-6">Sécurité du compte</h2>
                        
                        <form class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Mot de passe actuel</label>
                                <input type="password" 
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Nouveau mot de passe</label>
                                <input type="password" 
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Confirmer le nouveau mot de passe</label>
                                <input type="password" 
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                            </div>
                            
                            <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-semibold">
                                <i class="fas fa-key mr-2"></i>Changer le mot de passe
                            </button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
     <?php include '../../includes/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Settings navigation
        const navLinks = document.querySelectorAll('.settings-nav');
        const sections = document.querySelectorAll('section');
        
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href').substring(1);
                
                // Update active nav
                navLinks.forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');
                
                // Show target section
                sections.forEach(section => {
                    section.classList.add('hidden');
                    if (section.id === targetId) {
                        section.classList.remove('hidden');
                    }
                });
            });
        });
    });
    </script>
</body>
</html>