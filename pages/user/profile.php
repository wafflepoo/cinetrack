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

/* Messages animés */
.alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 10px;
    font-weight: 500;
    animation: fadein 0.5s, fadeout 0.5s 2.5s forwards;
}
.alert-success { background: rgba(16,185,129,0.15); color: #10B981; border-left: 4px solid #10B981; }
.alert-error { background: rgba(239,68,68,0.15); color: #EF4444; border-left: 4px solid #EF4444; }

@keyframes fadein { from {opacity: 0; transform: translateY(-10px);} to {opacity: 1; transform: translateY(0);} }
@keyframes fadeout { to {opacity: 0; transform: translateY(-10px);} }

/* Eye icon */
.toggle-password { cursor: pointer; }
.toggle-password.active { color: #ff8c00; }
</style>
</head>

<body class="gradient-bg text-white min-h-screen">

<?php include '../../includes/header.php'; ?>

<main class="pt-32 pb-16">
<div class="max-w-6xl mx-auto px-6">

<h1 class="text-4xl font-black mb-2">Paramètres du compte</h1>
<p class="text-gray-400 text-lg mb-10">Gérez vos informations personnelles et de sécurité</p>

<!-- -------- MESSAGES -------- -->
<?php 
$messages = [
    ['type'=>'error','text'=>$_SESSION['error_profile'] ?? null],
    ['type'=>'success','text'=>$_SESSION['success_profile'] ?? null],
    ['type'=>'error','text'=>$_SESSION['error_password'] ?? null],
    ['type'=>'success','text'=>$_SESSION['success_password'] ?? null]
];

foreach ($messages as $msg) {
    if ($msg['text']) {
        $class = $msg['type']=='success' ? 'alert-success' : 'alert-error';
        $icon = $msg['type']=='success' ? 'fa-check' : 'fa-xmark';
        echo "<div class='alert $class'><i class='fas $icon'></i><span>{$msg['text']}</span></div>";
    }
}

// Supprimer les messages
unset($_SESSION['error_profile'], $_SESSION['success_profile'], $_SESSION['error_password'], $_SESSION['success_password']);
?>

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
                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <img src="<?= $user['avatar'] ? '/' . $user['avatar'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png' ?>"
                         class="w-24 h-24 rounded-full object-cover border border-gray-600 shadow-lg">
                    <div class="w-full sm:w-auto">
                        <label class="block text-sm text-gray-300 mb-1">Changer la photo</label>
                        <input type="file" name="avatar" accept="image/*" class="text-gray-300 w-full sm:w-auto">
                    </div>
                </div>

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

                <div>
                    <label class="text-sm text-gray-300 mb-1">Pseudo</label>
                    <input name="pseudo" type="text" value="<?= htmlspecialchars($user['pseudo']); ?>"
                           class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg">
                </div>

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
                <?php 
                $password_fields = ['old_password','new_password','confirm_password'];
                foreach($password_fields as $field): ?>
                <div class="relative">
                    <label class="text-sm text-gray-300 mb-1 block"><?= ucfirst(str_replace('_',' ',$field)) ?></label>
                    <input type="password" name="<?= $field ?>"
                           class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg pr-10 toggle-input">
                    <i class="fas fa-eye absolute right-3 top-1/2 transform -translate-y-1/2 toggle-password text-gray-400 hover:text-white"></i>
                </div>
                <?php endforeach; ?>

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
// Onglets
document.querySelectorAll(".settings-nav").forEach(btn => {
    btn.addEventListener("click", () => {
        document.querySelectorAll(".settings-nav").forEach(x => x.classList.remove("active"));
        btn.classList.add("active");
        const target = btn.dataset.target;
        document.querySelectorAll("section > div").forEach(sec => sec.classList.add("hidden"));
        document.getElementById(target).classList.remove("hidden");
    });
});

// Ouvrir sécurité si message mot de passe
const securityMessages = <?= !empty($_SESSION['error_password']) || !empty($_SESSION['success_password']) ? 'true' : 'false' ?>;
if(securityMessages){
    document.querySelectorAll(".settings-nav").forEach(x => x.classList.remove("active"));
    document.querySelector('[data-target="security"]').classList.add("active");
    document.getElementById("profile").classList.add("hidden");
    document.getElementById("security").classList.remove("hidden");
}

// Toggle password
document.querySelectorAll(".toggle-password").forEach((eye,i)=>{
    eye.addEventListener("click", ()=>{
        const input = document.querySelectorAll(".toggle-input")[i];
        if(input.type==="password"){ input.type="text"; eye.classList.add("active"); }
        else { input.type="password"; eye.classList.remove("active"); }
    });
});
</script>

</body>
</html>
