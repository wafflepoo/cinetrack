<?php
// SMART HEADER - Adapts to login status with FIXED paths (NO AUTH)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

$currentUser = $isLoggedIn ? [
    'id' => $_SESSION['user_id'],
    'pseudo' => $_SESSION['user_pseudo'] ?? '',
    'email' => $_SESSION['user_email'] ?? '',
    'nom' => $_SESSION['user_nom'] ?? '',
    'prenom' => $_SESSION['user_prenom'] ?? '',
    'avatar' => $_SESSION['user_avatar'] ?? null
] : null;

// Detect current directory for proper links
$current_file = $_SERVER['PHP_SELF'];
$is_in_pages = strpos($current_file, '/pages/') !== false;
$is_in_user = strpos($current_file, '/user/') !== false;

// Calculate base path
if ($is_in_user) {
    $base_path = '../../';
} elseif ($is_in_pages) {
    $base_path = '../';
} else {
    $base_path = '';
}
?>

<header class="fixed top-0 w-full bg-gray-900/95 backdrop-blur-lg border-b border-gray-800 z-50">
    <nav class="max-w-7xl mx-auto px-6 py-4">
        <div class="flex items-center justify-between">
            
            <!-- Logo -->
            <div class="flex items-center space-x-8">
                <a href="<?= $base_path; ?>index.php" class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-film text-white text-lg"></i>
                    </div>
                    <span class="text-xl font-black">CineTrack</span>
                </a>

                <!-- Navigation Links -->
                <?php if ($isLoggedIn && $is_in_user): ?>
                    <div class="hidden md:flex items-center space-x-6">
                        <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                        <a href="watchlist.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'watchlist.php' ? 'active' : ''; ?>">
                            <i class="fas fa-bookmark mr-2"></i>Watchlist
                        </a>
                        <a href="reviews.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
                            <i class="fas fa-star mr-2"></i>Critiques
                        </a>
                        <a href="lists.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'lists.php' ? 'active' : ''; ?>">
                            <i class="fas fa-list mr-2"></i>Listes
                        </a>
                    </div>
                <?php else: ?>
                    <div class="hidden md:flex items-center space-x-6">
                        <a href="<?= $base_path; ?>index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Accueil</a>
                        <a href="<?= $base_path; ?>pages/films.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'films.php' ? 'active' : ''; ?>">Films</a>
                        <a href="<?= $base_path; ?>pages/series.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'series.php' ? 'active' : ''; ?>">Séries</a>
                        <a href="<?= $base_path; ?>pages/cinemas.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'cinemas.php' ? 'active' : ''; ?>">
                            <i class="fas fa-film mr-1"></i> Cinémas
                        </a>

                        <?php if ($isLoggedIn): ?>
                            <a href="<?= $base_path; ?>pages/user/dashboard.php" class="nav-link">
                                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- User Menu -->
            <div class="flex items-center space-x-4">
                <?php if ($isLoggedIn && $currentUser): ?>

                    <?php if (!$is_in_user): ?>
                        <a href="<?= $base_path; ?>pages/user/dashboard.php" 
                           class="bg-gray-700/50 hover:bg-gray-600/50 px-4 py-2 rounded-lg border border-gray-600">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?= $base_path; ?>pages/films.php" 
                           class="bg-gray-700/50 hover:bg-gray-600/50 px-4 py-2 rounded-lg border border-gray-600">
                            <i class="fas fa-search mr-2"></i>Découvrir
                        </a>
                    <?php endif; ?>

                    <!-- Avatar Dropdown -->
                    <div class="relative group">

                        <button class="flex items-center space-x-3 bg-gray-800/50 hover:bg-gray-700/50 px-4 py-2 rounded-lg">

                            <!-- Avatar circle -->
                            <div class="w-8 h-8 rounded-full overflow-hidden border border-gray-700">
                                <?php if (!empty($currentUser['avatar'])): ?>
                                    <img src="<?= $base_path . $currentUser['avatar'] ?>" 
                                         class="w-full h-full object-cover" />
                                <?php else: ?>
                                    <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" 
                                         class="w-full h-full object-cover" />
                                <?php endif; ?>
                            </div>

                            <span class="hidden md:block"><?= htmlspecialchars($currentUser['pseudo']); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>

                        <!-- Dropdown -->
                        <div class="absolute right-0 top-full mt-2 w-48 bg-gray-800/95 border border-gray-700 rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                            <div class="p-2">
                                <a href="<?= $base_path; ?>pages/user/profile.php" class="dropdown-item">
                                    <i class="fas fa-user mr-3"></i>Mon Profil
                                </a>
                                <a href="<?= $base_path; ?>pages/user/settings.php" class="dropdown-item">
                                    <i class="fas fa-cog mr-3"></i>Paramètres
                                </a>
                                <div class="border-t border-gray-700 my-2"></div>
                                <a href="<?= $base_path; ?>pages/logout.php" class="dropdown-item text-red-400 hover:text-red-300">
                                    <i class="fas fa-sign-out-alt mr-3"></i>Déconnexion
                                </a>
                            </div>
                        </div>

                    </div>

                <?php else: ?>

                    <!-- Guest -->
                    <a href="<?= $base_path; ?>pages/connexion.php" class="text-gray-300 hover:text-orange-500 font-medium">
                        Connexion
                    </a>
                    <a href="<?= $base_path; ?>pages/inscription.php" 
                       class="bg-orange-500 hover:bg-orange-600 px-4 py-2 rounded-xl text-white font-medium">
                        S'inscrire
                    </a>

                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<style>
.nav-link { @apply text-gray-400 hover:text-white px-3 py-2 rounded-lg transition; }
.nav-link.active { @apply text-orange-500 bg-orange-500/10; }
.dropdown-item { @apply flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700/50 rounded-lg transition; }
</style>
