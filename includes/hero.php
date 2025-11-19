<!-- Hero Section avec votre image -->
<section class="relative pt-32 pb-20 px-4 overflow-hidden hero-with-background hero-vignette">
    <!-- Effets visuels -->
    <div class="absolute inset-0 opacity-20 z-0">
        <div class="absolute top-20 left-10 w-72 h-72 bg-orange-500 rounded-full filter blur-3xl float-animation"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500 rounded-full filter blur-3xl float-animation" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-blue-500 rounded-full filter blur-3xl float-animation" style="animation-delay: 4s;"></div>
    </div>
    
    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto relative z-10">
        <div class="text-center mb-12 fade-in">
            <h1 class="text-5xl md:text-7xl font-black mb-6 leading-tight hero-title">
                Découvrez votre<br/>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-yellow-500">
                    Prochaine Obsession
                </span>
            </h1>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-8 leading-relaxed">
                Films, séries, critiques et recommandations personnalisées par IA. 
                Rejoignez la communauté des cinéphiles passionnés.
            </p>
        </div>

        <!-- Search Bar -->
        <div class="max-w-4xl mx-auto mb-8 fade-in" style="transition-delay: 0.2s;">
            <div class="relative">
                <input 
                    type="text" 
                    placeholder="Rechercher un film, une série, un réalisateur..." 
                    class="w-full px-6 py-5 rounded-2xl glass text-white placeholder-gray-400 focus:outline-none search-glow transition-all duration-300"
                />
                <button class="absolute right-3 top-1/2 -translate-y-1/2 px-8 py-3 btn-primary rounded-xl font-semibold shadow-lg search-button">
                    <i class="fas fa-search mr-2"></i>Rechercher
                </button>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap justify-center gap-4 mb-12 fade-in" style="transition-delay: 0.4s;">
            <button class="px-6 py-3 glass hover:bg-gray-700/30 rounded-xl transition-all duration-300 flex items-center space-x-2 transform hover:-translate-y-1">
                <i class="fas fa-chart-line text-orange-500"></i>
                <span>Tendances</span>
            </button>
            <button class="px-6 py-3 glass hover:bg-gray-700/30 rounded-xl transition-all duration-300 flex items-center space-x-2 transform hover:-translate-y-1">
                <i class="fas fa-robot text-orange-500"></i>
                <span>Recommandations IA</span>
            </button>
            <button class="px-6 py-3 glass hover:bg-gray-700/30 rounded-xl transition-all duration-300 flex items-center space-x-2 transform hover:-translate-y-1">
                <i class="fas fa-users text-orange-500"></i>
                <span>Communauté</span>
            </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto fade-in stats-grid" style="transition-delay: 0.6s;">
            <div class="text-center glass p-6 rounded-2xl transform hover:-translate-y-2 transition-all duration-300">
                <div class="text-5xl font-black stat-number mb-2">50K+</div>
                <div class="text-gray-300">Films & Séries</div>
            </div>
            <div class="text-center glass p-6 rounded-2xl transform hover:-translate-y-2 transition-all duration-300">
                <div class="text-5xl font-black stat-number mb-2">100K+</div>
                <div class="text-gray-300">Critiques</div>
            </div>
            <div class="text-center glass p-6 rounded-2xl transform hover:-translate-y-2 transition-all duration-300">
                <div class="text-5xl font-black stat-number mb-2">25K+</div>
                <div class="text-gray-300">Membres</div>
            </div>
        </div>
    </div>
</section>