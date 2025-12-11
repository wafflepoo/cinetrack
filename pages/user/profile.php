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
        .gradient-bg { background: linear-gradient(135deg,#0a0e14,#05080d); }
        .glass { background: rgba(30,30,40,0.25); backdrop-filter: blur(10px); border:1px solid rgba(255,255,255,0.1); }
        .settings-nav { @apply flex items-center px-4 py-3 text-gray-400 rounded-lg transition; }
        .settings-nav:hover, .settings-nav.active { @apply bg-orange-500/10 text-orange-500; }
        .btn-primary { background:linear-gradient(135deg,#ff8c00,#ff6b00); }
    </style>
</head>

<body class="gradient-bg text-white min-h-screen">
<?php include '../../includes/header.php'; ?>

<main class="pt-32 pb-16">
    <div class="max-w-4xl mx-auto px-6">

        <h1 class="text-4xl font-black mb-2">Paramètres du compte</h1>
        <p class="text-gray-400 text-lg mb-8">Gérez vos informations personnelles</p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Sidebar -->
            <div class="glass p-6 rounded-2xl sticky top-32">
                <nav class="space-y-2">
                    <a href="#profile" class="settings-nav active"><i class="fas fa-user mr-3"></i>Profil</a>
                    <a href="#security" class="settings-nav"><i class="fas fa-lock mr-3"></i>Sécurité</a>
                </nav>
            </div>

            <!-- Content -->
            <div class="lg:col-span-2 space-y-8">

                <!-- Profile -->
                <section id="profile" class="glass p-6 rounded-2xl">
                    <h2 class="text-2xl font-bold mb-6">Informations du profil</h2>

                    <?php if (!empty($_SESSION['success'])): ?>
                        <p class="text-green-400 mb-4"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
                    <?php endif; ?>

                    <form action="update-profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">

                        <!-- Avatar -->
                        <div class="flex items-center gap-6">
                            <img src="<?= $user['avatar'] ? '/' . $user['avatar'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png' ?>"
                                 class="w-20 h-20 rounded-full object-cover border border-gray-600">

                            <div>
                                <label class="block text-sm mb-1 text-gray-300">Changer la photo</label>
                                <input type="file" name="avatar" accept="image/*" class="text-gray-300">
                            </div>
                        </div>

                        <!-- Names -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Prénom</label>
                                <input name="prenom" type="text" value="<?= htmlspecialchars($user['prenom']); ?>"
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Nom</label>
                                <input name="nom" type="text" value="<?= htmlspecialchars($user['nom']); ?>"
                                       class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg">
                            </div>
                        </div>

                        <!-- Pseudo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Pseudo</label>
                            <input name="pseudo" type="text" value="<?= htmlspecialchars($user['pseudo']); ?>"
                                   class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg">
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                            <input name="email" type="email" value="<?= htmlspecialchars($user['email']); ?>"
                                   class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg">
                        </div>

                        <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-semibold">
                            <i class="fas fa-save mr-2"></i>Enregistrer
                        </button>
                    </form>
                </section>

                <!-- Security -->
                <section id="security" class="glass p-6 rounded-2xl hidden">
                    <h2 class="text-2xl font-bold mb-6">Sécurité</h2>
                    <p class="text-gray-400">Fonction à venir.</p>
                </section>

            </div>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('.settings-nav');
    const sections = document.querySelectorAll('section');

    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            links.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            sections.forEach(sec => sec.classList.add('hidden'));
            document.querySelector(this.getAttribute('href')).classList.remove('hidden');
        });
    });
});
</script>

</body>
</html>
