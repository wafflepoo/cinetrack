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
        .settings-nav {
            display: flex; align-items: center;
            padding: 12px 16px;
            border-radius: 8px;
            color: #9ca3af;
            cursor: pointer;
            transition: 0.2s;
            font-weight: 500;
        }
        .settings-nav:hover { background: rgba(255,130,0,0.08); color: #ff8c00; }
        .settings-nav.active { background: rgba(255,130,0,0.12); color: #ff8c00; font-weight: 600; }

        .btn-primary {
            background: linear-gradient(135deg,#ff8c00,#ff6b00);
            padding: 10px 22px;
            color: white;
            border-radius: 10px;
            transition: .2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255,140,0,0.35);
        }
    </style>
</head>

<body class="gradient-bg text-white min-h-screen">

<?php include '../../includes/header.php'; ?>

<main class="pt-32 pb-16">
    <div class="max-w-6xl mx-auto px-6">

        <h1 class="text-4xl font-black mb-2">Paramètres du compte</h1>
        <p class="text-gray-400 text-lg mb-10">Gérez vos informations personnelles et de sécurité</p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

            <!-- SIDEBAR -->
            <aside class="glass p-6 rounded-2xl h-fit sticky top-32">
                <nav class="space-y-3">
                    <div class="settings-nav active" data-target="profile">
                        <i class="fas fa-user mr-3"></i> Profil
                    </div>

                    <div class="settings-nav" data-target="security">
                        <i class="fas fa-lock mr-3"></i> Sécurité
                    </div>
                </nav>
            </aside>

            <!-- CONTENT -->
            <section class="lg:col-span-2 space-y-10">

                <!-- PROFILE TAB -->
                <div id="profile" class="glass p-6 rounded-2xl">

                    <h2 class="text-2xl font-bold mb-6">Informations du profil</h2>

                    <form action="update-profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">

                        <!-- Avatar -->
                        <div class="flex items-center gap-6">
                            <img src="<?= $user['avatar'] ? '/' . $user['avatar'] :
                                'https://cdn-icons-png.flaticon.com/512/149/149071.png' ?>"
                                class="w-24 h-24 rounded-full object-cover border border-gray-600 shadow-lg">

                            <div>
                                <label class="block text-sm text-gray-300 mb-1">Changer la photo</label>
                                <input type="file" name="avatar" accept="image/*" class="text-gray-300">
                            </div>
                        </div>

                        <!-- Names -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="text-sm text-gray-300 mb-1 block">Prénom</label>
                                <input name="prenom" type="text" value="<?= htmlspecialchars($user['prenom']); ?>"
                                    class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg">
                            </div>

                            <div>
                                <label class="text-sm text-gray-300 mb-1 block">Nom</label>
                                <input name="nom" type="text" value="<?= htmlspecialchars($user['nom']); ?>"
                                    class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg">
                            </div>
                        </div>

                        <!-- Pseudo -->
                        <div>
                            <label class="text-sm text-gray-300 mb-1">Pseudo</label>
                            <input name="pseudo" type="text" value="<?= htmlspecialchars($user['pseudo']); ?>"
                                class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg">
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="text-sm text-gray-300 mb-1">Email</label>
                            <input name="email" type="email" value="<?= htmlspecialchars($user['email']); ?>"
                                class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg">
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save mr-2"></i>Enregistrer
                        </button>
                    </form>
                </div>

                <!-- SECURITY TAB -->
                <div id="security" class="glass p-6 rounded-2xl hidden">
                    <h2 class="text-2xl font-bold mb-6">Sécurité du compte</h2>

                    <form action="update-password.php" method="POST" class="space-y-6">

                        <div>
                            <label class="text-sm text-gray-300 mb-1 block">Mot de passe actuel</label>
                            <input type="password" name="old_password"
                                class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg">
                        </div>

                        <div>
                            <label class="text-sm text-gray-300 mb-1 block">Nouveau mot de passe</label>
                            <input type="password" name="new_password"
                                class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg">
                        </div>

                        <div>
                            <label class="text-sm text-gray-300 mb-1 block">Confirmer le mot de passe</label>
                            <input type="password" name="confirm_password"
                                class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg">
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-key mr-2"></i>Changer le mot de passe
                        </button>
                    </form>

                </div>

            </section>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>

<script>
document.querySelectorAll(".settings-nav").forEach(btn => {
    btn.addEventListener("click", () => {

        document.querySelectorAll(".settings-nav").forEach(x => x.classList.remove("active"));
        btn.classList.add("active");

        const target = btn.dataset.target;

        document.querySelectorAll("section > div").forEach(sec => sec.classList.add("hidden"));
        document.getElementById(target).classList.remove("hidden");
    });
});
</script>

</body>
</html>
