<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();
$user = getCurrentUser();

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $prenom = trim($_POST['prenom']);
    $nom = trim($_POST['nom']);
    $pseudo = trim($_POST['pseudo']);
    $email = trim($_POST['email']);
    
    try {
        // Check if pseudo or email already exists (excluding current user)
        $check_stmt = $pdo->prepare("
            SELECT id_utilisateur FROM UTILISATEUR 
            WHERE (pseudo = :pseudo OR email = :email) AND id_utilisateur != :user_id
        ");
        $check_stmt->execute(['pseudo' => $pseudo, 'email' => $email, 'user_id' => $user['id']]);
        $existing = $check_stmt->fetch();
        
        if ($existing) {
            $error = "Le pseudo ou l'email est déjà utilisé par un autre utilisateur";
        } else {
            $update_stmt = $pdo->prepare("
                UPDATE UTILISATEUR 
                SET prenom = :prenom, nom = :nom, pseudo = :pseudo, email = :email 
                WHERE id_utilisateur = :user_id
            ");
            $update_stmt->execute([
                'prenom' => $prenom,
                'nom' => $nom,
                'pseudo' => $pseudo,
                'email' => $email,
                'user_id' => $user['id']
            ]);
            
            // Update session
            $_SESSION['user_pseudo'] = $pseudo;
            $_SESSION['user_nom'] = $nom;
            $_SESSION['user_prenom'] = $prenom;
            $_SESSION['user_email'] = $email;
            
            $success = "Profil mis à jour avec succès!";
        }
    } catch (Exception $e) {
        $error = "Erreur lors de la mise à jour: " . $e->getMessage();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    try {
        // Verify current password
        $user_stmt = $pdo->prepare("SELECT mot_de_passe FROM UTILISATEUR WHERE id_utilisateur = :user_id");
        $user_stmt->execute(['user_id' => $user['id']]);
        $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($current_password, $user_data['mot_de_passe'])) {
            $error = "Mot de passe actuel incorrect";
        } elseif ($new_password !== $confirm_password) {
            $error = "Les nouveaux mots de passe ne correspondent pas";
        } elseif (strlen($new_password) < 6) {
            $error = "Le mot de passe doit contenir au moins 6 caractères";
        } else {
            $update_stmt = $pdo->prepare("
                UPDATE UTILISATEUR SET mot_de_passe = :password WHERE id_utilisateur = :user_id
            ");
            $update_stmt->execute([
                'password' => password_hash($new_password, PASSWORD_DEFAULT),
                'user_id' => $user['id']
            ]);
            
            $success = "Mot de passe changé avec succès!";
        }
    } catch (Exception $e) {
        $error = "Erreur lors du changement de mot de passe: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
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
            
            <!-- Notifications -->
            <?php if ($success): ?>
                <div class="bg-green-500/20 border border-green-500/50 text-green-200 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500/50 text-red-200 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
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
                            <a href="#notifications" class="settings-nav">
                                <i class="fas fa-bell mr-3"></i>Notifications
                            </a>
                        </nav>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Profile Settings -->
                    <section id="profile" class="settings-section">
                        <div class="glass p-6 rounded-2xl">
                            <h2 class="text-2xl font-bold mb-6">Informations du profil</h2>
                            
                            <form method="POST">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Prénom</label>
                                        <input type="text" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" 
                                               class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Nom</label>
                                        <input type="text" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" 
                                               class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                                    </div>
                                </div>
                                
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Pseudo</label>
                                    <input type="text" name="pseudo" value="<?php echo htmlspecialchars($user['pseudo']); ?>" 
                                           class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                                </div>
                                
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                           class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                                </div>
                                
                                <button type="submit" name="update_profile" value="1" 
                                        class="btn-primary px-6 py-3 rounded-lg font-semibold">
                                    <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                                </button>
                            </form>
                        </div>
                    </section>
                    
                    <!-- Security Settings -->
                    <section id="security" class="settings-section hidden">
                        <div class="glass p-6 rounded-2xl">
                            <h2 class="text-2xl font-bold mb-6">Sécurité du compte</h2>
                            
                            <form method="POST">
                                <div class="space-y-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Mot de passe actuel</label>
                                        <input type="password" name="current_password" required
                                               class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Nouveau mot de passe</label>
                                        <input type="password" name="new_password" required
                                               class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Confirmer le nouveau mot de passe</label>
                                        <input type="password" name="confirm_password" required
                                               class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                                    </div>
                                    
                                    <button type="submit" name="change_password" value="1" 
                                            class="btn-primary px-6 py-3 rounded-lg font-semibold">
                                        <i class="fas fa-key mr-2"></i>Changer le mot de passe
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>
                    
                    <!-- Privacy Settings -->
                    <section id="privacy" class="settings-section hidden">
                        <div class="glass p-6 rounded-2xl">
                            <h2 class="text-2xl font-bold mb-6">Confidentialité</h2>
                            
                            <div class="space-y-6">
                                <div class="flex items-center justify-between p-4 rounded-lg bg-gray-800/30">
                                    <div>
                                        <h3 class="font-semibold">Profil public</h3>
                                        <p class="text-gray-400 text-sm">Rendre votre profil visible par tous</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                                    </label>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 rounded-lg bg-gray-800/30">
                                    <div>
                                        <h3 class="font-semibold">Activité publique</h3>
                                        <p class="text-gray-400 text-sm">Afficher mes activités récentes</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                                    </label>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 rounded-lg bg-gray-800/30">
                                    <div>
                                        <h3 class="font-semibold">Listes publiques</h3>
                                        <p class="text-gray-400 text-sm">Rendre mes listes visibles par tous</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                                    </label>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 rounded-lg bg-gray-800/30">
                                    <div>
                                        <h3 class="font-semibold">Critiques publiques</h3>
                                        <p class="text-gray-400 text-sm">Afficher mes critiques à tous</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                                    </label>
                                </div>
                            </div>
                            
                            <button class="btn-primary px-6 py-3 rounded-lg font-semibold mt-6">
                                <i class="fas fa-save mr-2"></i>Enregistrer les préférences
                            </button>
                        </div>
                    </section>
                    
                    <!-- Notification Settings -->
                    <section id="notifications" class="settings-section hidden">
                        <div class="glass p-6 rounded-2xl">
                            <h2 class="text-2xl font-bold mb-6">Préférences de notification</h2>
                            
                            <div class="space-y-6">
                                <div class="flex items-center justify-between p-4 rounded-lg bg-gray-800/30">
                                    <div>
                                        <h3 class="font-semibold">Notifications par email</h3>
                                        <p class="text-gray-400 text-sm">Recevoir des emails pour les nouvelles fonctionnalités</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                                    </label>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 rounded-lg bg-gray-800/30">
                                    <div>
                                        <h3 class="font-semibold">Nouveautés</h3>
                                        <p class="text-gray-400 text-sm">Être notifié des nouveaux films et séries</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                                    </label>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 rounded-lg bg-gray-800/30">
                                    <div>
                                        <h3 class="font-semibold">Rappels de watchlist</h3>
                                        <p class="text-gray-400 text-sm">Rappels pour les contenus non terminés</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                                    </label>
                                </div>
                            </div>
                            
                            <button class="btn-primary px-6 py-3 rounded-lg font-semibold mt-6">
                                <i class="fas fa-save mr-2"></i>Enregistrer les préférences
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Settings navigation
        const navLinks = document.querySelectorAll('.settings-nav');
        const sections = document.querySelectorAll('.settings-section');
        
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
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    });
    </script>
    
    <style>
    .settings-nav {
        @apply flex items-center px-4 py-3 text-gray-400 rounded-lg transition;
    }
    .settings-nav:hover,
    .settings-nav.active {
        @apply bg-orange-500/10 text-orange-500;
    }
    
    .settings-section {
        @apply transition-opacity duration-300;
    }
    </style>
</body>
</html>