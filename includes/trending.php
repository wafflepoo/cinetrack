<?php
// trending.php - Using TMDb API without redeclaring functions

// Check if functions are already declared to avoid redeclaration errors
if (!function_exists('fetchTrendingMovies')) {
    function fetchTrendingMovies() {
        $api_key = TMDB_API_KEY;
        $url = TMDB_BASE_URL . 'trending/movie/week?api_key=' . $api_key . '&language=fr-FR';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'CineTrack/1.0'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200 && $response) {
            $data = json_decode($response, true);
            return $data['results'] ?? [];
        } else {
            error_log("TMDb Trending Movies API Error: HTTP $http_code");
            return [];
        }
    }
}

if (!function_exists('fetchTrendingTVShows')) {
    function fetchTrendingTVShows() {
        $api_key = TMDB_API_KEY;
        $url = TMDB_BASE_URL . 'trending/tv/week?api_key=' . $api_key . '&language=fr-FR';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'CineTrack/1.0'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200 && $response) {
            $data = json_decode($response, true);
            return $data['results'] ?? [];
        } else {
            error_log("TMDb Trending TV API Error: HTTP $http_code");
            return [];
        }
    }
}

// Fetch trending data from API
$api_trending_movies = fetchTrendingMovies();
$api_trending_tv = fetchTrendingTVShows();

// Format movies data
$trending_movies = [];
if (!empty($api_trending_movies)) {
    foreach (array_slice($api_trending_movies, 0, 4) as $movie) {
        $trending_movies[] = [
            'id' => $movie['id'],
            'title' => $movie['title'] ?? 'Titre inconnu',
            'rating' => $movie['vote_average'] ? round($movie['vote_average'], 1) : 0,
            'year' => !empty($movie['release_date']) ? date('Y', strtotime($movie['release_date'])) : 'N/A',
            'genre' => 'Film',
            'image' => $movie['poster_path'] ? TMDB_IMAGE_BASE_URL . 'w400' . $movie['poster_path'] : 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=400',
            'type' => 'movie'
        ];
    }
}

// Format TV shows data
$trending_series = [];
if (!empty($api_trending_tv)) {
    foreach (array_slice($api_trending_tv, 0, 4) as $tv) {
        $trending_series[] = [
            'id' => $tv['id'],
            'title' => $tv['name'] ?? 'Titre inconnu',
            'rating' => $tv['vote_average'] ? round($tv['vote_average'], 1) : 0,
            'year' => !empty($tv['first_air_date']) ? date('Y', strtotime($tv['first_air_date'])) : 'N/A',
            'genre' => 'Série',
            'image' => $tv['poster_path'] ? TMDB_IMAGE_BASE_URL . 'w400' . $tv['poster_path'] : 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=400',
            'seasons' => $tv['number_of_seasons'] ?? 1,
            'type' => 'tv'
        ];
    }
}
?>

<!-- Trending Section -->
<section class="py-20 px-4">
    <div class="max-w-7xl mx-auto">
        <!-- Trending Movies -->
        <div class="mb-16">
            <div class="flex items-center justify-between mb-8 slide-in-left">
                <h2 class="text-4xl font-bold">
                    <span class="text-orange-500">Films</span> Tendances
                </h2>
                <a href="films.php" class="text-orange-500 hover:text-orange-400 transition-all duration-300 font-semibold group">
                    Voir tout <i class="fas fa-arrow-right ml-1 transform group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
            <p class="text-gray-400 mb-8 slide-in-left" style="transition-delay: 0.1s;">Les films les plus populaires cette semaine</p>

            <?php if (!empty($trending_movies)): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 stagger-animation">
                    <?php foreach ($trending_movies as $movie): ?>
                    <div class="card-hover glass rounded-2xl overflow-hidden cursor-pointer movie-card" 
                         onclick="window.location.href='/pages/movie-details.php?id=<?= $movie['id'] ?>'">
                        <div class="relative overflow-hidden">
                            <img src="<?= $movie['image'] ?>" alt="<?= htmlspecialchars($movie['title']) ?>" 
                                 class="w-full h-80 object-cover transition-transform duration-500"
                                 onerror="this.src='https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=400'">
                            <div class="absolute top-4 right-4 bg-orange-500 px-3 py-1 rounded-full flex items-center space-x-1 shadow-lg">
                                <i class="fas fa-star text-white"></i>
                                <span class="font-bold text-white"><?= $movie['rating'] ?></span>
                            </div>
                            <div class="absolute inset-0 hero-gradient opacity-0 hover:opacity-100 transition-opacity duration-300 flex items-end p-6">
                                <a href="/pages/movie-details.php?id=<?= $movie['id'] ?>" 
                                   class="w-full py-2 bg-orange-500 hover:bg-orange-600 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 text-center text-white">
                                    Voir détails
                                </a>
                            </div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2 hover:text-orange-400 transition-colors duration-300 line-clamp-2">
                                <a href="/pages/movie-details.php?id=<?= $movie['id'] ?>" class="hover:text-orange-400">
                                    <?= htmlspecialchars($movie['title']) ?>
                                </a>
                            </h3>
                            <div class="flex items-center space-x-4 text-sm text-gray-400">
                                <span class="flex items-center space-x-1">
                                    <i class="fas fa-star text-yellow-500"></i>
                                    <span><?= $movie['rating'] ?></span>
                                </span>
                                <span><?= $movie['year'] ?></span>
                                <span class="px-3 py-1 bg-orange-500/20 text-orange-500 rounded-full text-xs">
                                    Film
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 glass rounded-2xl">
                    <i class="fas fa-film text-4xl text-gray-600 mb-4"></i>
                    <p class="text-gray-400 text-lg">Aucun film tendance disponible pour le moment</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Trending Series Section -->
        <div class="mt-16">
            <div class="flex items-center justify-between mb-8 slide-in-left">
                <h2 class="text-4xl font-bold">
                    <span class="text-purple-500">Séries</span> Tendances
                </h2>
                <a href="series.php" class="text-purple-500 hover:text-purple-400 transition-all duration-300 font-semibold group">
                    Voir tout <i class="fas fa-arrow-right ml-1 transform group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
            <p class="text-gray-400 mb-8 slide-in-left" style="transition-delay: 0.1s;">Les séries les plus populaires cette semaine</p>

            <?php if (!empty($trending_series)): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 stagger-animation">
                    <?php foreach($trending_series as $series): ?>
                    <div class="card-hover glass rounded-2xl overflow-hidden cursor-pointer movie-card" 
                         onclick="window.location.href='/pages/serie-details.php?id=<?= $series['id'] ?>'">
                        <div class="relative overflow-hidden">
                            <img src="<?= $series['image'] ?>" alt="<?= htmlspecialchars($series['title']) ?>" 
                                 class="w-full h-80 object-cover transition-transform duration-500"
                                 onerror="this.src='https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=400'">
                            <div class="absolute top-4 right-4 bg-purple-500 px-3 py-1 rounded-full flex items-center space-x-1 shadow-lg">
                                <i class="fas fa-star text-white"></i>
                                <span class="font-bold text-white"><?= $series['rating'] ?></span>
                            </div>
                            <div class="absolute top-4 left-4 bg-blue-500 px-3 py-1 rounded-full flex items-center space-x-1 shadow-lg">
                                <i class="fas fa-tv text-white text-xs"></i>
                                <span class="font-bold text-white text-sm"><?= $series['seasons'] ?> saison<?= $series['seasons'] > 1 ? 's' : '' ?></span>
                            </div>
                            <div class="absolute inset-0 hero-gradient opacity-0 hover:opacity-100 transition-opacity duration-300 flex items-end p-6">
                                <a href="/pages/serie-details.php?id=<?= $series['id'] ?>" 
                                   class="w-full py-2 bg-purple-500 hover:bg-purple-600 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 text-center text-white">
                                    Voir détails
                                </a>
                            </div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2 hover:text-purple-400 transition-colors duration-300 line-clamp-2">
                                <a href="/pages/serie-details.php?id=<?= $series['id'] ?>" class="hover:text-purple-400">
                                    <?= htmlspecialchars($series['title']) ?>
                                </a>
                            </h3>
                            <div class="flex items-center space-x-4 text-sm text-gray-400">
                                <span class="flex items-center space-x-1">
                                    <i class="fas fa-star text-yellow-500"></i>
                                    <span><?= $series['rating'] ?></span>
                                </span>
                                <span><?= $series['year'] ?></span>
                                <span class="px-3 py-1 bg-purple-500/20 text-purple-500 rounded-full text-xs">
                                    Série
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 glass rounded-2xl">
                    <i class="fas fa-tv text-4xl text-gray-600 mb-4"></i>
                    <p class="text-gray-400 text-lg">Aucune série tendance disponible pour le moment</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.card-hover {
    transition: all 0.3s ease;
}

.card-hover:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.hero-gradient {
    background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.5) 50%, transparent 100%);
}

.movie-card {
    cursor: pointer;
}

.movie-card:hover img {
    transform: scale(1.05);
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.movie-card:hover {
    border-color: rgba(255, 140, 0, 0.3);
}

.movie-card:nth-child(n+5):hover {
    border-color: rgba(147, 51, 234, 0.3);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.movie-card');
    cards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.tagName === 'A' || e.target.closest('a')) {
                return;
            }
            const link = this.querySelector('a');
            if (link) {
                window.location.href = link.href;
            }
        });
    });
});
</script>