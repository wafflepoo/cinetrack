<!-- Genres Section -->
<section class="py-20 px-4 bg-gray-900/30">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16 slide-in-right">
            <h2 class="text-4xl font-bold mb-4">
                Explorez par <span class="text-orange-500">Genre</span>
            </h2>
            <p class="text-gray-400 text-lg">Plongez dans des univers cinématographiques uniques</p>
        </div>

        <!-- Filtres de catégorie -->
        <div class="flex justify-center mb-12 fade-in">
            <div class="glass rounded-2xl p-2 flex space-x-2">
                <button class="filter-btn active px-6 py-3 rounded-xl transition-all duration-300 flex items-center space-x-2" data-filter="all">
                    <i class="fas fa-layer-group text-orange-500"></i>
                    <span>Tous les genres</span>
                </button>
                <button class="filter-btn px-6 py-3 rounded-xl transition-all duration-300 flex items-center space-x-2" data-filter="movies">
                    <i class="fas fa-film text-orange-500"></i>
                    <span>Films</span>
                </button>
                <button class="filter-btn px-6 py-3 rounded-xl transition-all duration-300 flex items-center space-x-2" data-filter="series">
                    <i class="fas fa-tv text-orange-500"></i>
                    <span>Séries</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 stagger-animation genres-container">
            <!-- Action -->
            <div class="genre-card group relative h-80 rounded-2xl overflow-hidden cursor-pointer transform transition-all duration-700 hover:scale-105" data-category="movies series">
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent z-10"></div>
                <img 
                    src="https://images.unsplash.com/photo-1594909122845-11baa439b7bf?ixlib=rb-4.0.3&w=400&h=500&fit=crop" 
                    alt="Action" 
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                >
                <div class="absolute bottom-0 left-0 right-0 p-6 z-20 transform transition-all duration-500 group-hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl text-orange-500">
                            <i class="fas fa-explosion"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Action</h3>
                    <div class="flex space-x-2 mb-2 transform translate-y-2">
                        <span class="bg-orange-500/20 text-orange-500 px-2 py-1 rounded-full text-xs font-semibold">
                            1.8K films
                        </span>
                        <span class="bg-orange-500/20 text-orange-500 px-2 py-1 rounded-full text-xs font-semibold">
                            700 séries
                        </span>
                    </div>
                    <p class="text-gray-300 text-sm opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-500 delay-200">
                        Adrénaline pure et scènes spectaculaires
                    </p>
                </div>
                <div class="absolute inset-0 border-2 border-transparent group-hover:border-orange-500/50 rounded-2xl transition-all duration-500 z-30"></div>
            </div>

            <!-- Drame -->
            <div class="genre-card group relative h-80 rounded-2xl overflow-hidden cursor-pointer transform transition-all duration-700 hover:scale-105" data-category="movies series">
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent z-10"></div>
                <img 
                    src="https://images.unsplash.com/photo-1485846234645-a62644f84728?ixlib=rb-4.0.3&w=400&h=500&fit=crop" 
                    alt="Drame" 
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                >
                <div class="absolute bottom-0 left-0 right-0 p-6 z-20 transform transition-all duration-500 group-hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl text-purple-500">
                            <i class="fas fa-masks-theater"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Drame</h3>
                    <div class="flex space-x-2 mb-2 transform translate-y-2">
                        <span class="bg-purple-500/20 text-purple-500 px-2 py-1 rounded-full text-xs font-semibold">
                            1.2K films
                        </span>
                        <span class="bg-purple-500/20 text-purple-500 px-2 py-1 rounded-full text-xs font-semibold">
                            600 séries
                        </span>
                    </div>
                    <p class="text-gray-300 text-sm opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-500 delay-200">
                        Émotions intenses et histoires poignantes
                    </p>
                </div>
                <div class="absolute inset-0 border-2 border-transparent group-hover:border-purple-500/50 rounded-2xl transition-all duration-500 z-30"></div>
            </div>

            <!-- Comédie -->
            <div class="genre-card group relative h-80 rounded-2xl overflow-hidden cursor-pointer transform transition-all duration-700 hover:scale-105" data-category="movies series">
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent z-10"></div>
                <img 
                    src="https://images.unsplash.com/photo-1542204165-65bf26472b9b?ixlib=rb-4.0.3&w=400&h=500&fit=crop" 
                    alt="Comédie" 
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                >
                <div class="absolute bottom-0 left-0 right-0 p-6 z-20 transform transition-all duration-500 group-hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl text-yellow-500">
                            <i class="fas fa-face-laugh-beam"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Comédie</h3>
                    <div class="flex space-x-2 mb-2 transform translate-y-2">
                        <span class="bg-yellow-500/20 text-yellow-500 px-2 py-1 rounded-full text-xs font-semibold">
                            1.5K films
                        </span>
                        <span class="bg-yellow-500/20 text-yellow-500 px-2 py-1 rounded-full text-xs font-semibold">
                            600 séries
                        </span>
                    </div>
                    <p class="text-gray-300 text-sm opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-500 delay-200">
                        Rires garantis et moments légers
                    </p>
                </div>
                <div class="absolute inset-0 border-2 border-transparent group-hover:border-yellow-500/50 rounded-2xl transition-all duration-500 z-30"></div>
            </div>

            <!-- Romance -->
            <div class="genre-card group relative h-80 rounded-2xl overflow-hidden cursor-pointer transform transition-all duration-700 hover:scale-105" data-category="movies series">
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent z-10"></div>
                <img 
                    src="https://images.unsplash.com/photo-1534447677768-be436bb09401?ixlib=rb-4.0.3&w=400&h=500&fit=crop" 
                    alt="Romance" 
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                >
                <div class="absolute bottom-0 left-0 right-0 p-6 z-20 transform transition-all duration-500 group-hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl text-pink-500">
                            <i class="fas fa-heart"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Romance</h3>
                    <div class="flex space-x-2 mb-2 transform translate-y-2">
                        <span class="bg-pink-500/20 text-pink-500 px-2 py-1 rounded-full text-xs font-semibold">
                            1.1K films
                        </span>
                        <span class="bg-pink-500/20 text-pink-500 px-2 py-1 rounded-full text-xs font-semibold">
                            400 séries
                        </span>
                    </div>
                    <p class="text-gray-300 text-sm opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-500 delay-200">
                        Histoires d'amour et passions dévorantes
                    </p>
                </div>
                <div class="absolute inset-0 border-2 border-transparent group-hover:border-pink-500/50 rounded-2xl transition-all duration-500 z-30"></div>
            </div>

            <!-- Aventure -->
            <div class="genre-card group relative h-80 rounded-2xl overflow-hidden cursor-pointer transform transition-all duration-700 hover:scale-105" data-category="movies series">
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent z-10"></div>
                <img 
                    src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&w=400&h=500&fit=crop" 
                    alt="Aventure" 
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                >
                <div class="absolute bottom-0 left-0 right-0 p-6 z-20 transform transition-all duration-500 group-hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl text-blue-500">
                            <i class="fas fa-mountain-sun"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Aventure</h3>
                    <div class="flex space-x-2 mb-2 transform translate-y-2">
                        <span class="bg-blue-500/20 text-blue-500 px-2 py-1 rounded-full text-xs font-semibold">
                            1.3K films
                        </span>
                        <span class="bg-blue-500/20 text-blue-500 px-2 py-1 rounded-full text-xs font-semibold">
                            600 séries
                        </span>
                    </div>
                    <p class="text-gray-300 text-sm opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-500 delay-200">
                        Voyages épiques et découvertes extraordinaires
                    </p>
                </div>
                <div class="absolute inset-0 border-2 border-transparent group-hover:border-blue-500/50 rounded-2xl transition-all duration-500 z-30"></div>
            </div>

            <!-- Horreur -->
            <div class="genre-card group relative h-80 rounded-2xl overflow-hidden cursor-pointer transform transition-all duration-700 hover:scale-105" data-category="movies series">
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent z-10"></div>
                <img 
                    src="https://images.unsplash.com/photo-1509248961154-6c975d1301c4?ixlib=rb-4.0.3&w=400&h=500&fit=crop" 
                    alt="Horreur" 
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                >
                <div class="absolute bottom-0 left-0 right-0 p-6 z-20 transform transition-all duration-500 group-hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl text-red-600">
                            <i class="fas fa-ghost"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Horreur</h3>
                    <div class="flex space-x-2 mb-2 transform translate-y-2">
                        <span class="bg-red-600/20 text-red-600 px-2 py-1 rounded-full text-xs font-semibold">
                            800 films
                        </span>
                        <span class="bg-red-600/20 text-red-600 px-2 py-1 rounded-full text-xs font-semibold">
                            400 séries
                        </span>
                    </div>
                    <p class="text-gray-300 text-sm opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-500 delay-200">
                        Frissons et suspense au rendez-vous
                    </p>
                </div>
                <div class="absolute inset-0 border-2 border-transparent group-hover:border-red-600/50 rounded-2xl transition-all duration-500 z-30"></div>
            </div>

            <!-- Sci-Fi -->
            <div class="genre-card group relative h-80 rounded-2xl overflow-hidden cursor-pointer transform transition-all duration-700 hover:scale-105" data-category="movies series">
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent z-10"></div>
                <img 
                    src="https://images.unsplash.com/photo-1446776653964-20c1d3a81b06?ixlib=rb-4.0.3&w=400&h=500&fit=crop" 
                    alt="Sci-Fi" 
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                >
                <div class="absolute bottom-0 left-0 right-0 p-6 z-20 transform transition-all duration-500 group-hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl text-indigo-500">
                            <i class="fas fa-rocket"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Sci-Fi</h3>
                    <div class="flex space-x-2 mb-2 transform translate-y-2">
                        <span class="bg-indigo-500/20 text-indigo-500 px-2 py-1 rounded-full text-xs font-semibold">
                            1.1K films
                        </span>
                        <span class="bg-indigo-500/20 text-indigo-500 px-2 py-1 rounded-full text-xs font-semibold">
                            500 séries
                        </span>
                    </div>
                    <p class="text-gray-300 text-sm opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-500 delay-200">
                        Futur, technologie et univers infinis
                    </p>
                </div>
                <div class="absolute inset-0 border-2 border-transparent group-hover:border-indigo-500/50 rounded-2xl transition-all duration-500 z-30"></div>
            </div>

            <!-- Fantastique -->
            <div class="genre-card group relative h-80 rounded-2xl overflow-hidden cursor-pointer transform transition-all duration-700 hover:scale-105" data-category="movies series">
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent z-10"></div>
                <img 
                    src="https://images.unsplash.com/photo-1460881680858-30d872d5b530?ixlib=rb-4.0.3&w=400&h=500&fit=crop" 
                    alt="Fantastique" 
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                >
                <div class="absolute bottom-0 left-0 right-0 p-6 z-20 transform transition-all duration-500 group-hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl text-purple-400">
                            <i class="fas fa-hat-wizard"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Fantastique</h3>
                    <div class="flex space-x-2 mb-2 transform translate-y-2">
                        <span class="bg-purple-400/20 text-purple-400 px-2 py-1 rounded-full text-xs font-semibold">
                            900 films
                        </span>
                        <span class="bg-purple-400/20 text-purple-400 px-2 py-1 rounded-full text-xs font-semibold">
                            500 séries
                        </span>
                    </div>
                    <p class="text-gray-300 text-sm opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-500 delay-200">
                        Magie, créatures et mondes imaginaires
                    </p>
                </div>
                <div class="absolute inset-0 border-2 border-transparent group-hover:border-purple-400/50 rounded-2xl transition-all duration-500 z-30"></div>
            </div>
        </div>

        <!-- Navigation entre les genres -->
        <div class="flex justify-center mt-12 fade-in">
            <button class="px-8 py-3 glass hover:bg-gray-700/30 rounded-xl transition-all duration-300 flex items-center space-x-2 transform hover:-translate-y-1">
                <i class="fas fa-compass text-orange-500"></i>
                <span>Explorer tous les genres</span>
                <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
            </button>
        </div>
    </div>
</section>

<style>
.filter-btn {
    background: transparent;
    color: #9CA3AF;
    transition: all 0.3s ease;
}

.filter-btn.active,
.filter-btn:hover {
    background: rgba(255, 140, 0, 0.1);
    color: #FF8C00;
    transform: translateY(-2px);
}

.genre-card {
    transition: all 0.5s ease;
}

.genre-card.hidden {
    display: none;
    opacity: 0;
    transform: scale(0.8);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const genreCards = document.querySelectorAll('.genre-card');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            
            // Filtrer les cartes
            genreCards.forEach(card => {
                if (filter === 'all') {
                    card.classList.remove('hidden');
                } else {
                    const categories = card.getAttribute('data-category');
                    if (categories.includes(filter)) {
                        card.classList.remove('hidden');
                    } else {
                        card.classList.add('hidden');
                    }
                }
            });
        });
    });
});
</script>