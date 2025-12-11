<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$isLoggedIn = isset($_SESSION['user_id']);

$currentUser = $isLoggedIn ? [
    'id'     => $_SESSION['user_id'],
    'pseudo' => $_SESSION['user_pseudo'] ?? '',
    'email'  => $_SESSION['user_email'] ?? '',
    'nom'    => $_SESSION['user_nom'] ?? '',
    'prenom' => $_SESSION['user_prenom'] ?? '',
    'avatar' => $_SESSION['user_avatar'] ?? null
] : null;

// PATH FIX
$current_file = $_SERVER['PHP_SELF'];
$is_in_pages = str_contains($current_file, "/pages/");
$is_in_user  = str_contains($current_file, "/user/");
$base_path = $is_in_user ? "../../" : ($is_in_pages ? "../" : "");
?>

<!-- FAVICON -->
<link rel="icon" type="image/png" href="<?= $base_path ?>images/favicon.png">

<!-- OVERLAY -->
<div id="overlay" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-[9000]"></div>

<!-- =======================================================
     MOBILE SLIDE PANEL
======================================================= -->
<div id="mobilePanel"
     class="fixed top-0 left-0 h-full w-72 bg-gray-900 border-r border-gray-800 
            transform -translate-x-full transition-all duration-300 z-[9999]">

    <div class="p-6 relative">

        <!-- Close -->
        <button id="closeMenu" class="absolute top-4 right-4 text-gray-300 text-xl hover:text-white">
            <i class="fas fa-times"></i>
        </button>

        <h2 class="text-2xl font-bold mb-8">Menu</h2>

        <!-- MENU -->
        <nav class="space-y-4">

            <a href="<?= $base_path ?>index.php" class="block text-gray-200 hover:text-white text-lg">
                Accueil
            </a>

            <?php if ($isLoggedIn): ?>
                <a href="<?= $base_path ?>pages/user/dashboard.php" class="block text-gray-200 hover:text-white text-lg">Dashboard</a>
                <a href="<?= $base_path ?>pages/user/watchlist.php" class="block text-gray-200 hover:text-white text-lg">Watchlist</a>
                <a href="<?= $base_path ?>pages/user/reviews.php" class="block text-gray-200 hover:text-white text-lg">Critiques</a>
                <a href="<?= $base_path ?>pages/user/lists.php" class="block text-gray-200 hover:text-white text-lg">Listes</a>
                <a href="<?= $base_path ?>pages/user/recommendations.php" class="block text-gray-200 hover:text-white text-lg">Recommandations</a>

                <hr class="border-gray-700 my-4">

                <a href="<?= $base_path ?>pages/user/profile.php" class="block text-gray-200 hover:text-white text-lg">Mon Profil</a>
                <a href="<?= $base_path ?>pages/logout.php" class="block text-red-400 hover:text-red-300 text-lg font-semibold">Déconnexion</a>

            <?php else: ?>
                <a href="<?= $base_path ?>pages/films.php" class="block text-gray-200 hover:text-white text-lg">Films</a>
                <a href="<?= $base_path ?>pages/series.php" class="block text-gray-200 hover:text-white text-lg">Séries</a>
                <a href="<?= $base_path ?>pages/cinemas.php" class="block text-gray-200 hover:text-white text-lg">Cinémas</a>

                <hr class="border-gray-700 my-4">

                <!-- LOGIN / SIGNUP MOBILE -->
                <a href="<?= $base_path ?>pages/connexion.php"
                   class="block bg-gray-800 px-4 py-2 rounded-lg text-center hover:bg-gray-700">
                    Connexion
                </a>
                <a href="<?= $base_path ?>pages/inscription.php"
                   class="block bg-orange-500 px-4 py-2 rounded-lg text-center hover:bg-orange-600">
                    S'inscrire
                </a>
            <?php endif; ?>

        </nav>
    </div>
</div>


<!-- =======================================================
     DESKTOP HEADER
======================================================= -->
<header class="fixed top-0 w-full bg-gray-900/95 backdrop-blur-lg border-b border-gray-800 z-[8000]">
    <nav class="max-w-7xl mx-auto px-6 py-3">
        <div class="flex items-center justify-between">

            <!-- LEFT -->
            <div class="flex items-center space-x-4">

                <!-- LOGO -->
                <a href="<?= $base_path ?>index.php" class="flex items-center space-x-2">
                    <img src="<?= $base_path ?>images/logo.png" class="w-14 h-14">
                    <span class="text-2xl font-black">CineTrack</span>
                </a>

                <!-- DESKTOP NAV -->
                <div class="hidden md:flex items-center space-x-6">

                    <a href="<?= $base_path ?>index.php"
                       class="nav-link <?= basename($current_file)=='index.php'?'active':'' ?>">
                       Accueil
                    </a>

                    <?php if ($isLoggedIn): ?>
                        <a href="<?= $base_path ?>pages/user/dashboard.php" class="nav-link">Dashboard</a>
                        <a href="<?= $base_path ?>pages/user/watchlist.php" class="nav-link">Watchlist</a>
                        <a href="<?= $base_path ?>pages/user/reviews.php" class="nav-link">Critiques</a>
                        <a href="<?= $base_path ?>pages/user/lists.php" class="nav-link">Listes</a>
                        <a href="<?= $base_path ?>pages/user/recommendations.php" class="nav-link">Recommandations</a>

                    <?php else: ?>
                        <a href="<?= $base_path ?>pages/films.php" class="nav-link">Films</a>
                        <a href="<?= $base_path ?>pages/series.php" class="nav-link">Séries</a>
                        <a href="<?= $base_path ?>pages/cinemas.php" class="nav-link">Cinémas</a>
                    <?php endif; ?>

                </div>
            </div>

            <!-- RIGHT -->
            <div class="flex items-center space-x-6">

                <!-- MOBILE BURGER -->
                <button id="burger" class="text-white text-3xl md:hidden">
                    <i class="fas fa-bars"></i>
                </button>

                <?php if (!$isLoggedIn): ?>

                    <a href="<?= $base_path ?>pages/connexion.php" class="hidden md:inline text-gray-300 hover:text-white">
                        Connexion
                    </a>

                    <a href="<?= $base_path ?>pages/inscription.php"
                       class="hidden md:inline bg-orange-500 hover:bg-orange-600 px-4 py-2 rounded-xl text-white font-semibold shadow">
                        S'inscrire
                    </a>

                <?php else: ?>

                    <!-- AVATAR DROPDOWN (unchanged) -->
                    <div class="relative group hidden md:block">

                        <button class="flex items-center space-x-3 bg-gray-800/50 hover:bg-gray-700/60
                                       px-4 py-2 rounded-xl border border-gray-700 transition">

                            <div class="w-9 h-9 rounded-full overflow-hidden border border-gray-600">
                                <img src="<?= $currentUser['avatar']
                                    ? $base_path.$currentUser['avatar']
                                    : 'https://cdn-icons-png.flaticon.com/512/149/149071.png' ?>"
                                     class="w-full h-full object-cover">
                            </div>

                            <span><?= htmlspecialchars($currentUser['pseudo']) ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>

                        <div class="absolute right-0 top-full mt-2 w-56 bg-gray-900 border border-gray-700
                                    rounded-xl shadow-xl opacity-0 invisible group-hover:opacity-100
                                    group-hover:visible transition-all duration-200 overflow-hidden">

                            <a href="<?= $base_path ?>pages/user/profile.php"
                               class="flex items-center px-4 py-3 text-gray-200 hover:bg-gray-800 transition">
                                <i class="fas fa-user-circle mr-3 text-orange-400"></i>
                                <span>Gérer mon profil</span>
                            </a>

                            <div class="border-t border-gray-700 my-1"></div>

                            <a href="<?= $base_path ?>pages/logout.php"
                               class="flex items-center px-4 py-3 text-red-400 hover:bg-red-500/20 transition">
                                <i class="fas fa-sign-out-alt mr-3"></i>
                                <span>Déconnexion</span>
                            </a>
                        </div>

                    </div>

                <?php endif; ?>
            </div>

        </div>
    </nav>
</header>


<!-- MOBILE MENU SCRIPT -->
<script>
const burger  = document.getElementById("burger");
const panel   = document.getElementById("mobilePanel");
const overlay = document.getElementById("overlay");
const closeBtn = document.getElementById("closeMenu");

burger.onclick = () => {
    panel.classList.remove("-translate-x-full");
    overlay.classList.remove("hidden");
};

closeBtn.onclick = overlay.onclick = () => {
    panel.classList.add("-translate-x-full");
    overlay.classList.add("hidden");
};
</script>

<style>
.nav-link {
    position: relative;
    padding-bottom: 4px;
    color: #c5c9d2;
    transition: all 0.25s ease;
    font-size: 1.05rem;
}

.nav-link::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: -1px;
    height: 2px;
    width: 0;
    background: linear-gradient(to right, #ff8c00, #ff4800);
    transition: width 0.25s ease;
}

.nav-link:hover {
    color: white;
    transform: translateY(-1px);
}
.nav-link:hover::after {
    width: 100%;
}

.nav-link.active {
    color: #ff8c00 !important;
    font-weight: 700;
}
.nav-link.active::after {
    width: 100%;
}
</style>
