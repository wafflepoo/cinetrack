<?php
// SMART HEADER - Adapts to login status with FIXED paths
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
    'prenom' => $_SESSION['user_prenom'] ?? ''
] : null;

// Detect current directory for proper links - FIXED VERSION
$current_file = $_SERVER['PHP_SELF'];
$is_in_pages = strpos($current_file, '/pages/') !== false;
$is_in_user = strpos($current_file, '/user/') !== false;
$is_in_auth = strpos($current_file, '/auth/') !== false;

// Calculate base path correctly
if ($is_in_user) {
    // When in /pages/user/ directory, go up 2 levels to reach root
    $base_path = '../../';
} elseif ($is_in_pages && !$is_in_user) {
    // When in /pages/ directory (but not in user), go up 1 level
    $base_path = '../';
} elseif ($is_in_auth) {
    // When in /auth/ directory
    $base_path = '../';
} else {
    // When in root directory
    $base_path = '';
}

// Debug paths (remove this in production)
// error_log("Current file: $current_file");
// error_log("Base path: $base_path");
// error_log("In pages: " . ($is_in_pages ? 'yes' : 'no'));
// error_log("In user: " . ($is_in_user ? 'yes' : 'no'));
?>

<header class="fixed top-0 w-full bg-gray-900/95 backdrop-blur-lg border-b border-gray-800 z-50">
    <nav class="max-w-7xl mx-auto px-6 py-4">
        <div class="flex items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center space-x-8">
                <a href="<?php echo $base_path; ?>index.php" class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-film text-white text-lg"></i>
                    </div>
                    <span class="text-xl font-black">CineTrack</span>
                </a>
                
                <!-- Navigation Links -->
                <?php if ($isLoggedIn && $is_in_user): ?>
                    <!-- USER DASHBOARD NAVIGATION -->
                    <div class="hidden md:flex items-center space-x-6">
                        <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                        <a href="watchlist.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'watchlist.php' ? 'active' : ''; ?>">
                            <i class="fas fa-bookmark mr-2"></i>Watchlist
                        </a>
                        <a href="reviews.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
                            <i class="fas fa-star mr-2"></i>Critiques
                        </a>
                        <a href="lists.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'lists.php' ? 'active' : ''; ?>">
                            <i class="fas fa-list mr-2"></i>Listes
                        </a>
                    </div>
                <?php else: ?>
                    <!-- PUBLIC NAVIGATION -->
                    <div class="hidden md:flex items-center space-x-6">
                        <a href="<?php echo $base_path; ?>index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            Accueil
                        </a>
                        <a href="<?php echo $base_path; ?>pages/films.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'films.php' ? 'active' : ''; ?>">
                            Films
                        </a>
                        <a href="<?php echo $base_path; ?>pages/series.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'series.php' ? 'active' : ''; ?>">
                            Séries
                        </a>
                        <?php if ($isLoggedIn): ?>
                            <a href="<?php echo $base_path; ?>pages/user/dashboard.php" class="nav-link">
                                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- User Menu / Auth Buttons -->
            <div class="flex items-center space-x-4">
                <?php if ($isLoggedIn && $currentUser): ?>
                    <!-- LOGGED IN USER MENU -->
                    <?php if (!$is_in_user): ?>
                        <!-- Browse button when in public pages -->
                        <a href="<?php echo $base_path; ?>pages/user/dashboard.php" class="bg-gray-700/50 hover:bg-gray-600/50 text-white px-4 py-2 rounded-lg font-semibold transition border border-gray-600">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                    <?php else: ?>
                        <!-- Discover button when in user area -->
                        <a href="<?php echo $base_path; ?>pages/films.php" class="bg-gray-700/50 hover:bg-gray-600/50 text-white px-4 py-2 rounded-lg font-semibold transition border border-gray-600">
                            <i class="fas fa-search mr-2"></i>Découvrir
                        </a>
                    <?php endif; ?>
                    
                    <!-- User Dropdown -->
                    <div class="relative group">
                        <button class="flex items-center space-x-3 bg-gray-800/50 hover:bg-gray-700/50 px-4 py-2 rounded-lg transition">
                            <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold text-sm">
                                    <?php echo strtoupper(substr($currentUser['pseudo'], 0, 1)); ?>
                                </span>
                            </div>
                            <span class="hidden md:block"><?php echo htmlspecialchars($currentUser['pseudo']); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 top-full mt-2 w-48 bg-gray-800/95 backdrop-blur-lg rounded-lg border border-gray-700 shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <div class="p-2">
                                <a href="<?php echo $base_path; ?>pages/user/profile.php" class="dropdown-item">
                                    <i class="fas fa-user mr-3"></i>Mon Profil
                                </a>
                                <a href="<?php echo $base_path; ?>pages/user/settings.php" class="dropdown-item">
                                    <i class="fas fa-cog mr-3"></i>Paramètres
                                </a>
                                <div class="border-t border-gray-700 my-2"></div>
                                <a href="<?php echo $base_path; ?>pages/logout.php" class="dropdown-item text-red-400 hover:text-red-300">
                                    <i class="fas fa-sign-out-alt mr-3"></i>Déconnexion
                                </a>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- GUEST AUTH BUTTONS -->
                    <div class="flex items-center space-x-3">
                        <a href="<?php echo $base_path; ?>pages/connexion.php" class="text-gray-300 hover:text-orange-500 transition-all duration-300 font-medium">
                            Connexion
                        </a>
                        <a href="<?php echo $base_path; ?>pages/inscription.php" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-xl font-medium transition-all duration-300 transform hover:scale-105 shadow-lg">
                            S'inscrire
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div class="md:hidden mt-4">
            <?php if ($isLoggedIn && $is_in_user): ?>
                <!-- USER MOBILE MENU -->
                <div class="flex justify-around">
                    <a href="dashboard.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span class="text-xs mt-1">Dashboard</span>
                    </a>
                    <a href="watchlist.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'watchlist.php' ? 'active' : ''; ?>">
                        <i class="fas fa-bookmark"></i>
                        <span class="text-xs mt-1">Watchlist</span>
                    </a>
                    <a href="reviews.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i>
                        <span class="text-xs mt-1">Critiques</span>
                    </a>
                    <a href="lists.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'lists.php' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i>
                        <span class="text-xs mt-1">Listes</span>
                    </a>
                </div>
            <?php else: ?>
                <!-- PUBLIC MOBILE MENU -->
                <div class="flex justify-around">
                    <a href="<?php echo $base_path; ?>index.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span class="text-xs mt-1">Accueil</span>
                    </a>
                    <a href="<?php echo $base_path; ?>pages/films.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'films.php' ? 'active' : ''; ?>">
                        <i class="fas fa-film"></i>
                        <span class="text-xs mt-1">Films</span>
                    </a>
                    <a href="<?php echo $base_path; ?>pages/series.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'series.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tv"></i>
                        <span class="text-xs mt-1">Séries</span>
                    </a>
                    <?php if ($isLoggedIn): ?>
                        <a href="<?php echo $base_path; ?>pages/user/dashboard.php" class="mobile-nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span class="text-xs mt-1">Dashboard</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </nav>
</header>

<style>
.nav-link {
    @apply text-gray-400 hover:text-white px-3 py-2 rounded-lg transition;
}
.nav-link.active {
    @apply text-orange-500 bg-orange-500/10;
}
.mobile-nav-link {
    @apply flex flex-col items-center text-gray-400 p-2 rounded-lg transition;
}
.mobile-nav-link.active {
    @apply text-orange-500 bg-orange-500/10;
}
.dropdown-item {
    @apply flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700/50 rounded-lg transition;
}
</style>