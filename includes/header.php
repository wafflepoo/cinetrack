<!-- includes/header.php - SMART HEADER -->
<?php
// Detect current directory and set base path
$current_file = $_SERVER['PHP_SELF'];
$is_in_pages = strpos($current_file, '/pages/') !== false;
$base_path = $is_in_pages ? '../' : '';
?>
<nav class="fixed top-0 w-full z-50 bg-gray-900/90 backdrop-blur-lg border-b border-gray-800 transition-all duration-500">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center space-x-2">
                <a href="<?php echo $base_path; ?>index.php" class="flex items-center space-x-2">
                    <div class="text-3xl">🎬</div>
                    <span class="text-2xl font-bold">
                        <span class="text-orange-500">Cine</span><span class="text-white">Track</span>
                    </span>
                </a>
            </div>
            
            <div class="hidden md:flex items-center space-x-8">
                <a href="<?php echo $base_path; ?>index.php" class="text-gray-300 hover:text-orange-500 transition-all duration-300 nav-link">
                    Accueil
                </a>
                <a href="<?php echo $base_path; ?>pages/films.php" class="text-gray-300 hover:text-orange-500 transition-all duration-300 nav-link">
                    Films
                </a>
                <a href="<?php echo $base_path; ?>pages/series.php" class="text-gray-300 hover:text-orange-500 transition-all duration-300 nav-link">
                    Séries
                </a>

            </div>
            
            <div class="flex items-center space-x-4">
                <button class="text-gray-300 hover:text-orange-500 transition-all duration-300 transform hover:scale-110">
                    <i class="fas fa-search text-lg"></i>
                </button>
                
                <div class="flex items-center space-x-3">
                    <a href="<?php echo $base_path; ?>pages/connexion.php" class="text-gray-300 hover:text-orange-500 transition-all duration-300 font-medium">
                        Connexion
                    </a>
                    <a href="<?php echo $base_path; ?>pages/inscription.php" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-xl font-medium transition-all duration-300 transform hover:scale-105 shadow-lg">
                        S'inscrire
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>