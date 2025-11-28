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
            <?php foreach($genres_with_counts as $genre): ?>
            <div class="genre-card group relative h-80 rounded-2xl overflow-hidden cursor-pointer transform transition-all duration-700 hover:scale-105" 
                 data-category="movies series"
                 onclick="navigateToGenre('<?php echo $genre['id']; ?>', '<?php echo urlencode($genre['name']); ?>')">
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent z-10"></div>
                <img 
                    src="<?php echo $genre['image']; ?>" 
                    alt="<?php echo htmlspecialchars($genre['name']); ?>" 
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                    loading="lazy"
                >
                <div class="absolute bottom-0 left-0 right-0 p-6 z-20 transform transition-all duration-500 group-hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-3xl text-orange-500">
                            <i class="fas <?php echo $genre['icon']; ?>"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4"><?php echo htmlspecialchars($genre['name']); ?></h3>
                    <div class="flex space-x-2 mb-2 transform translate-y-2">
                        <span class="bg-orange-500/20 text-orange-500 px-2 py-1 rounded-full text-xs font-semibold movies-count">
                            <?php echo formatCount($genre['movie_count']); ?> films
                        </span>
                        <span class="bg-orange-500/20 text-orange-500 px-2 py-1 rounded-full text-xs font-semibold series-count">
                            <?php echo formatCount($genre['series_count']); ?> séries
                        </span>
                    </div>
                    <p class="text-gray-300 text-sm opacity-0 transform translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-500 delay-200">
                        Découvrez notre collection <?php echo htmlspecialchars($genre['name']); ?>
                    </p>
                </div>
                <div class="absolute inset-0 border-2 border-transparent group-hover:<?php echo $genre['hover_color']; ?> rounded-2xl transition-all duration-500 z-30"></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Navigation entre les genres -->
        <div class="flex justify-center mt-12 fade-in">
            <a href="pages/films.php?genre=all" class="px-8 py-3 glass hover:bg-gray-700/30 rounded-xl transition-all duration-300 flex items-center space-x-2 transform hover:-translate-y-1 group" id="explore-all-btn">
                <i class="fas fa-compass text-orange-500"></i>
                <span>Explorer tous les genres</span>
                <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
            </a>
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

/* Masquer les comptes selon le filtre */
.genre-card.filter-movies .series-count,
.genre-card.filter-series .movies-count {
    opacity: 0.3;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const genreCards = document.querySelectorAll('.genre-card');
    const exploreAllBtn = document.getElementById('explore-all-btn');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            
            // Mettre à jour le bouton "Explorer tous les genres"
            updateExploreAllButton(filter);
            
            // Filtrer les cartes
            genreCards.forEach(card => {
                if (filter === 'all') {
                    card.classList.remove('hidden');
                    card.classList.remove('filter-movies', 'filter-series');
                } else {
                    const categories = card.getAttribute('data-category');
                    if (categories.includes(filter)) {
                        card.classList.remove('hidden');
                        card.classList.add('filter-' + filter);
                    } else {
                        card.classList.add('hidden');
                    }
                }
            });
        });
    });
    
    function updateExploreAllButton(filter) {
        let href = 'pages/films.php?genre=all';
        let text = 'Explorer tous les genres';
        
        if (filter === 'series') {
            href = 'pages/series.php?genre=all';
            text = 'Explorer toutes les séries';
        } else if (filter === 'movies') {
            text = 'Explorer tous les films';
        }
        
        exploreAllBtn.href = href;
        exploreAllBtn.querySelector('span').textContent = text;
    }
});

function navigateToGenre(genreId, genreName) {
    // Déterminer la page cible en fonction du filtre actif
    const activeFilter = document.querySelector('.filter-btn.active');
    let targetPage = 'pages/films.php';
    
    if (activeFilter) {
        const filterType = activeFilter.getAttribute('data-filter');
        if (filterType === 'series') {
            targetPage = 'pages/series.php';
        }
    }
    
    console.log('Navigation vers:', targetPage + '?genre=' + genreId);
    window.location.href = targetPage + '?genre=' + genreId;
}

function formatCount(count) {
    if (count >= 1000) {
        return (count / 1000).toFixed(1) + 'K';
    }
    return count.toString();
}
</script>