<!-- Hero Section avec votre image -->
<section class="relative pt-32 pb-20 px-4 overflow-hidden hero-with-background hero-vignette">
    
    <!-- Effets visuels -->
    <div class="absolute inset-0 opacity-20 z-0">
        <div class="absolute top-20 left-10 w-72 h-72 bg-orange-500 rounded-full filter blur-3xl float-animation"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500 rounded-full filter blur-3xl float-animation" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-blue-500 rounded-full filter blur-3xl float-animation" style="animation-delay: 4s;"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10">

   
        <!-- TITRE -->
<div class="text-center mb-12 fade-in">
    <h1 class="text-5xl md:text-7xl font-black mb-6 leading-tight hero-title">
        Découvrez vos<br/>
        <span class="gradient-text">
            films et séries préférés
        </span>
    </h1>
    <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-8 leading-relaxed">
        CineTrack vous permet de suivre vos films et séries, créer votre watchlist, 
        regarder les bandes-annonces et partager vos avis et critiques. 
        Recevez des recommandations personnalisées et rejoignez la communauté de cinéphiles passionnés.
    </p>
</div>


        <!-- BARRE DE RECHERCHE -->
        <div class="max-w-4xl mx-auto mb-8 fade-in search-wrapper" style="transition-delay: 0.2s;">
            <div class="relative">
                <input 
                    type="text" 
                    id="global-search-input"
                    placeholder="Rechercher un film, une série, un réalisateur..." 
                    class="w-full px-6 py-5 rounded-2xl glass text-white placeholder-gray-400 search-glow"
                />
                <button onclick="performGlobalSearch()" 
                    class="absolute right-3 top-1/2 -translate-y-1/2 px-8 py-3 btn-primary rounded-xl font-semibold shadow-lg">
                    <i class="fas fa-search mr-2"></i>Rechercher
                </button>

                <!-- Résultats -->
                <div id="search-results" class="absolute top-full left-0 right-0 mt-2 bg-gray-900/95 backdrop-blur-lg rounded-2xl shadow-2xl border border-gray-700/50 z-50 max-h-96 overflow-y-auto hidden">
                    <div class="p-4">
                        <div id="search-loading" class="hidden text-center py-8">
                            <i class="fas fa-spinner fa-spin text-orange-500 text-2xl mb-2"></i>
                            <p class="text-gray-400">Recherche en cours...</p>
                        </div>
                        <div id="search-results-content"></div>
                    </div>
                </div>

            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="quick-actions flex flex-wrap justify-center gap-4 mb-16 fade-in relative z-20" style="transition-delay: 0.4s;">
            <button onclick="window.location.href='pages/films.php'" class="px-8 py-4 glass-card rounded-xl transition-all flex items-center space-x-3">
                <i class="fas fa-film text-orange-500 text-lg"></i>
                <span class="font-semibold text-white">Films</span>
            </button>

            <button onclick="window.location.href='pages/series.php'" class="px-8 py-4 glass-card rounded-xl transition-all flex items-center space-x-3">
                <i class="fas fa-tv text-orange-500 text-lg"></i>
                <span class="font-semibold text-white">Séries</span>
            </button>

            <button class="px-8 py-4 glass-card rounded-xl transition-all flex items-center space-x-3">
                <i class="fas fa-robot text-orange-500 text-lg"></i>
                <span class="font-semibold text-white">Recommandations IA</span>
            </button>

            <button class="px-8 py-4 glass-card rounded-xl transition-all flex items-center space-x-3">
                <i class="fas fa-users text-orange-500 text-lg"></i>
                <span class="font-semibold text-white">Communauté</span>
            </button>
        </div>

        <!-- STATISTIQUES -->
        <div id="stats-section" class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto fade-in stats-grid relative z-10 mt-8" style="transition-delay: 0.6s;">

            <div class="text-center glass-card p-6 rounded-2xl hover-lift">
                <div class="text-5xl font-black stat-number mb-2">50K+</div>
                <div class="text-gray-300">Films & Séries</div>
            </div>

            <div class="text-center glass-card p-6 rounded-2xl hover-lift">
                <div class="text-5xl font-black stat-number mb-2">100K+</div>
                <div class="text-gray-300">Critiques</div>
            </div>

            <div class="text-center glass-card p-6 rounded-2xl hover-lift">
                <div class="text-5xl font-black stat-number mb-2">25K+</div>
                <div class="text-gray-300">Membres</div>
            </div>

        </div>

    </div>
</section>

<script>
let searchTimeout;
let isSearchOpen = false;

// Input event
document.getElementById('global-search-input').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();

    if (query.length >= 2) {
        if (!isSearchOpen) showSearchResults();
        searchTimeout = setTimeout(() => performRealTimeSearch(query), 400);
    } else hideSearchResults();
});

// Focus event
document.getElementById('global-search-input').addEventListener('focus', function() {
    if (this.value.trim().length >= 2 && !isSearchOpen) showSearchResults();
});


// === Affiche les résultats ===
function showSearchResults() {
    const results = document.getElementById('search-results');

    isSearchOpen = true;
    results.classList.remove('hidden');

    document.querySelector('.quick-actions').classList.add('search-active');
    document.getElementById('stats-section').classList.add('search-active');
    document.querySelector('.search-wrapper').classList.add('search-active');
}


// === Cache les résultats ===
function hideSearchResults() {
    isSearchOpen = false;

    document.getElementById('search-results').classList.add('hidden');
    document.querySelector('.quick-actions').classList.remove('search-active');
    document.getElementById('stats-section').classList.remove('search-active');
    document.querySelector('.search-wrapper').classList.remove('search-active');
}


// Lancer une recherche complète
function performGlobalSearch() {
    const query = document.getElementById('global-search-input').value.trim();
    if (query) window.location.href = `pages/films.php?search=${encodeURIComponent(query)}`;
}


// Recherche AJAX
function performRealTimeSearch(query) {
    const loading = document.getElementById('search-loading');
    const content = document.getElementById('search-results-content');

    loading.classList.remove('hidden');
    content.innerHTML = '';

    fetch(`includes/search.php?query=${encodeURIComponent(query)}&type=multi`)
        .then(res => res.json())
        .then(data => {
            loading.classList.add('hidden');
            if (data.success) displaySearchResults(data, query);
            else content.innerHTML = "<p class='text-center py-4 text-gray-400'>Erreur...</p>";
        })
        .catch(() => {
            loading.classList.add('hidden');
            content.innerHTML = "<p class='text-center py-4 text-gray-400'>Erreur serveur</p>";
        });
}


// Afficher les résultats
function displaySearchResults(data, query) {
    const content = document.getElementById('search-results-content');

    if (!data.results.length) {
        content.innerHTML = `
            <div class="text-center py-4 text-gray-400">
                Aucun résultat trouvé pour "${query}"
            </div>`;
        return;
    }

    let html = `
    <h3 class="text-lg font-semibold text-white mb-3">Résultats (${data.results.length})</h3>
    <div class="space-y-2">
    `;

    data.results.forEach(item => {
        html += `
        <div class="flex items-center space-x-3 p-3 hover:bg-gray-800/50 rounded-lg cursor-pointer"
             onclick="viewItemDetails(${item.id}, '${item.media_type}')">
            <img src="${item.poster_path}" class="w-12 h-16 object-cover rounded">
            <div class="flex-1 min-w-0">
                <h4 class="text-white text-sm truncate">${item.title}</h4>
                <p class="text-gray-400 text-xs">${item.media_type === 'tv' ? 'Série' : 'Film'}</p>
            </div>
        </div>`;
    });

    html += `</div>`;
    content.innerHTML = html;
}

function viewItemDetails(id, type) {
    if (type === "movie") window.location.href = `pages/movie-details.php?id=${id}`;
    else window.location.href = `pages/serie-details.php?id=${id}`;
}

// Click extérieur → fermer le dropdown
document.addEventListener('click', function(e) {
    const box = document.querySelector('.search-wrapper');
    if (!box.contains(e.target)) hideSearchResults();
});
</script>
