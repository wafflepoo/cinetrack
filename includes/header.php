<!-- Navigation -->
<nav class="fixed top-0 w-full z-50 bg-gray-900/90 backdrop-blur-lg border-b border-gray-800 transition-all duration-500">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center space-x-2">
                <div class="text-3xl text-orange-500"><i class="fas fa-film"></i></div>
                <span class="text-2xl font-bold">
                    <span class="text-orange-500">Cine</span><span class="text-white">Track</span>
                </span>
            </div>
            
            <div class="hidden md:flex items-center space-x-8">
                <a href="includes/components/films.php" class="text-gray-300 hover:text-orange-500 transition-all duration-300 nav-link">
                    Films
                </a>
                <a href="#" class="text-gray-300 hover:text-orange-500 transition-all duration-300 nav-link">
                    Séries
                </a>
                <a href="#" class="text-gray-300 hover:text-orange-500 transition-all duration-300 nav-link">
                    Critiques
                </a>
                <a href="#" class="text-gray-300 hover:text-orange-500 transition-all duration-300 nav-link">
                    Listes
                </a>
            </div>
            
            <div class="flex items-center space-x-4">
                <button class="text-gray-300 hover:text-orange-500 transition-all duration-300 transform hover:scale-110">
                    <i class="fas fa-search text-lg"></i>
                </button>
                <div class="flex items-center space-x-2">
                    <span class="text-gray-300 text-sm">Sombre</span>
                    <div class="dark-mode-toggle">
                        <div class="dark-mode-toggle-inner"></div>
                    </div>
                    <span class="text-gray-300 text-sm">Clair</span>
                </div>
                
                <!-- Boutons de connexion -->
                <div class="flex items-center space-x-3">
                    <button class="px-4 py-2 text-gray-300 hover:text-orange-500 transition-all duration-300 font-medium">
                        Se connecter
                    </button>
                    <button class="px-6 py-2 bg-orange-500 hover:bg-orange-600 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105">
                        S'inscrire
                    </button>
                </div>
            </div>
        </div>
    </div>
</nav>