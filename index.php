<?php
session_start();
include 'includes/config.conf.php';

// Function to fetch trending movies from TMDb API
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
        error_log("TMDb API Error: HTTP $http_code - URL: $url");
        return [];
    }
}

// Fonction pour récupérer les genres films depuis l'API TMDb
function fetchGenresFromAPI() {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'genre/movie/list?api_key=' . $api_key . '&language=fr-FR';
    
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
        return $data['genres'] ?? [];
    }
    
    error_log("TMDb Genres API Error: HTTP $http_code");
    return getDefaultGenres();
}

// Fonction pour récupérer les genres séries depuis l'API TMDb
function fetchTVGenresFromAPI() {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'genre/tv/list?api_key=' . $api_key . '&language=fr-FR';
    
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
        return $data['genres'] ?? [];
    }
    
    return [];
}

// Fonction pour récupérer le nombre de films par genre
function fetchMovieCountByGenre($genre_id) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'discover/movie?api_key=' . $api_key . '&with_genres=' . $genre_id . '&language=fr-FR&page=1';
    
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
        return $data['total_results'] ?? 0;
    }
    
    return 0;
}

// Fonction pour récupérer le nombre de séries par genre
function fetchSeriesCountByGenre($genre_id) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'discover/tv?api_key=' . $api_key . '&with_genres=' . $genre_id . '&language=fr-FR&page=1';
    
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
        return $data['total_results'] ?? 0;
    }
    
    return 0;
}

// Fonction pour récupérer une image aléatoire pour un genre
function fetchGenreImageFromAPI($genre_id, $type = 'movie') {
    $api_key = TMDB_API_KEY;
    
    if ($type === 'movie') {
        $url = TMDB_BASE_URL . 'discover/movie?api_key=' . $api_key . '&with_genres=' . $genre_id . '&language=fr-FR&page=1';
    } else {
        $url = TMDB_BASE_URL . 'discover/tv?api_key=' . $api_key . '&with_genres=' . $genre_id . '&language=fr-FR&page=1';
    }
    
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
        $results = $data['results'] ?? [];
        
        // Prendre un film/série aléatoire avec poster
        $items_with_posters = array_filter($results, function($item) {
            return !empty($item['poster_path']);
        });
        
        if (!empty($items_with_posters)) {
            $random_item = $items_with_posters[array_rand($items_with_posters)];
            return TMDB_IMAGE_BASE_URL . 'w500' . $random_item['poster_path'];
        }
    }
    
    // Fallback image
    return 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=400';
}

function getDefaultGenres() {
    return [
        ['id' => 28, 'name' => 'Action'],
        ['id' => 12, 'name' => 'Aventure'],
        ['id' => 35, 'name' => 'Comédie'],
        ['id' => 18, 'name' => 'Drame'],
        ['id' => 878, 'name' => 'Science-Fiction'],
        ['id' => 14, 'name' => 'Fantastique'],
        ['id' => 27, 'name' => 'Horreur'],
        ['id' => 10749, 'name' => 'Romance'],
        ['id' => 53, 'name' => 'Thriller'],
        ['id' => 16, 'name' => 'Animation'],
        ['id' => 80, 'name' => 'Crime'],
        ['id' => 99, 'name' => 'Documentaire']
    ];
}

// Fonction pour formater les nombres (1.5K, 2.3K, etc.)
function formatCount($count) {
    if ($count >= 1000000) {
        return round($count / 1000000, 1) . 'M';
    } elseif ($count >= 1000) {
        return round($count / 1000, 1) . 'K';
    }
    return $count;
}

// Récupérer les genres depuis l'API
$movieGenres = fetchGenresFromAPI(); // Genres films
$tvGenres = fetchTVGenresFromAPI(); // Genres séries

// Combiner les genres uniques (films + séries)
$allGenreIds = [];
$genres_combined = [];

// Ajouter les genres films
foreach ($movieGenres as $genre) {
    $allGenreIds[$genre['id']] = $genre['name'];
    $genres_combined[$genre['id']] = [
        'id' => $genre['id'],
        'name' => $genre['name'],
        'has_movies' => true,
        'has_series' => false
    ];
}

// Ajouter/mettre à jour avec les genres séries
foreach ($tvGenres as $genre) {
    if (isset($genres_combined[$genre['id']])) {
        $genres_combined[$genre['id']]['has_series'] = true;
    } else {
        $genres_combined[$genre['id']] = [
            'id' => $genre['id'],
            'name' => $genre['name'],
            'has_movies' => false,
            'has_series' => true
        ];
    }
}

// Icônes et couleurs par genre ID
$genre_styles = [
    28 => ['icon' => 'fa-gun', 'color' => 'from-red-900 to-red-700', 'hover_color' => 'border-orange-500/50'],
    12 => ['icon' => 'fa-mountain-sun', 'color' => 'from-blue-900 to-blue-700', 'hover_color' => 'border-blue-500/50'],
    16 => ['icon' => 'fa-film', 'color' => 'from-green-900 to-green-700', 'hover_color' => 'border-green-500/50'],
    35 => ['icon' => 'fa-face-laugh-beam', 'color' => 'from-yellow-900 to-yellow-700', 'hover_color' => 'border-yellow-500/50'],
    80 => ['icon' => 'fa-shield', 'color' => 'from-gray-800 to-gray-600', 'hover_color' => 'border-gray-500/50'],
    99 => ['icon' => 'fa-camera', 'color' => 'from-blue-800 to-blue-600', 'hover_color' => 'border-blue-400/50'],
    18 => ['icon' => 'fa-masks-theater', 'color' => 'from-purple-900 to-purple-700', 'hover_color' => 'border-purple-500/50'],
    10751 => ['icon' => 'fa-house', 'color' => 'from-teal-900 to-teal-700', 'hover_color' => 'border-teal-500/50'],
    14 => ['icon' => 'fa-hat-wizard', 'color' => 'from-purple-900 to-purple-600', 'hover_color' => 'border-purple-400/50'],
    36 => ['icon' => 'fa-landmark', 'color' => 'from-amber-900 to-amber-700', 'hover_color' => 'border-amber-500/50'],
    27 => ['icon' => 'fa-ghost', 'color' => 'from-gray-900 to-gray-700', 'hover_color' => 'border-red-600/50'],
    10402 => ['icon' => 'fa-music', 'color' => 'from-pink-900 to-pink-700', 'hover_color' => 'border-pink-500/50'],
    9648 => ['icon' => 'fa-question', 'color' => 'from-indigo-900 to-indigo-700', 'hover_color' => 'border-indigo-500/50'],
    10749 => ['icon' => 'fa-heart', 'color' => 'from-pink-900 to-pink-700', 'hover_color' => 'border-pink-500/50'],
    878 => ['icon' => 'fa-rocket', 'color' => 'from-indigo-900 to-indigo-700', 'hover_color' => 'border-indigo-500/50'],
    10770 => ['icon' => 'fa-tv', 'color' => 'from-blue-900 to-blue-700', 'hover_color' => 'border-blue-500/50'],
    53 => ['icon' => 'fa-user-secret', 'color' => 'from-orange-900 to-orange-700', 'hover_color' => 'border-orange-500/50'],
    10752 => ['icon' => 'fa-helmet-battle', 'color' => 'from-red-800 to-red-600', 'hover_color' => 'border-red-500/50'],
    37 => ['icon' => 'fa-hat-cowboy', 'color' => 'from-yellow-800 to-yellow-600', 'hover_color' => 'border-yellow-500/50']
];

// Récupérer les données pour les 8 premiers genres combinés
$genres_to_display = array_slice($genres_combined, 0, 8);
$genres_with_counts = [];

foreach ($genres_to_display as $genre) {
    $movie_count = $genre['has_movies'] ? fetchMovieCountByGenre($genre['id']) : 0;
    $series_count = $genre['has_series'] ? fetchSeriesCountByGenre($genre['id']) : 0;
    
    // Récupérer une vraie image depuis l'API
    $image = fetchGenreImageFromAPI($genre['id'], 'movie');
    if (empty($image) || strpos($image, 'unsplash') !== false) {
        // Si pas d'image film, essayer avec les séries
        $image = fetchGenreImageFromAPI($genre['id'], 'tv');
    }
    
    $genres_with_counts[] = [
        'id' => $genre['id'],
        'name' => $genre['name'],
        'movie_count' => $movie_count,
        'series_count' => $series_count,
        'has_movies' => $genre['has_movies'],
        'has_series' => $genre['has_series'],
        'image' => $image,
        'icon' => $genre_styles[$genre['id']]['icon'] ?? 'fa-film',
        'color' => $genre_styles[$genre['id']]['color'] ?? 'from-gray-900 to-gray-700',
        'hover_color' => $genre_styles[$genre['id']]['hover_color'] ?? 'border-gray-500/50'
    ];
}

// Fetch trending movies from API
$api_trending_movies = fetchTrendingMovies();

// Format API data to match your existing structure
if (!empty($api_trending_movies)) {
    $trending_movies = [];
    foreach ($api_trending_movies as $movie) {
        $trending_movies[] = [
            'id' => $movie['id'],
            'title' => $movie['title'] ?? 'Titre inconnu',
            'rating' => $movie['vote_average'] ? round($movie['vote_average'], 1) : 0,
            'year' => !empty($movie['release_date']) ? date('Y', strtotime($movie['release_date'])) : '2024',
            'genre' => 'Action', // Default genre
            'image' => $movie['poster_path'] ? TMDB_IMAGE_BASE_URL . 'w400' . $movie['poster_path'] : 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=400'
        ];
    }
} else {
    // Fallback to sample data if API fails
    $trending_movies = [
        [
            'id' => 1,
            'title' => 'THRILLER SI RILLER',
            'rating' => 4.8,
            'year' => 2024,
            'genre' => 'Thriller',
            'image' => 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=400'
        ],
        [
            'id' => 2,
            'title' => 'Eternal Love',
            'rating' => 4.6,
            'year' => 2024,
            'genre' => 'Romance',
            'image' => 'https://images.unsplash.com/photo-1518676590629-3dcbd9c5a5c9?w=400'
        ],
        [
            'id' => 3,
            'title' => 'HARGE 3',
            'rating' => 4.7,
            'year' => 2024,
            'genre' => 'Action',
            'image' => 'https://images.unsplash.com/photo-1509347528160-9a9e33742cdb?w=400'
        ],
        [
            'id' => 4,
            'title' => 'HASMER',
            'rating' => 4.5,
            'year' => 2024,
            'genre' => 'Sci-Fi',
            'image' => 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=400'
        ]
    ];
}

// Keep everything else exactly the same
$genres = [
    ['name' => 'Action', 'count' => '2.5K', 'icon' => 'fa-gun', 'color' => 'from-red-900 to-red-700'],
    ['name' => 'Drame', 'count' => '1.8K', 'icon' => 'fa-masks-theater', 'color' => 'from-purple-900 to-purple-700'],
    ['name' => 'Comédie', 'count' => '2.1K', 'icon' => 'fa-face-laugh', 'color' => 'from-yellow-900 to-yellow-700'],
    ['name' => 'Romance', 'count' => '1.5K', 'icon' => 'fa-heart', 'color' => 'from-pink-900 to-pink-700'],
    ['name' => 'Aventure', 'count' => '1.9K', 'icon' => 'fa-mountain', 'color' => 'from-blue-900 to-blue-700'],
    ['name' => 'Horreur', 'count' => '1.2K', 'icon' => 'fa-ghost', 'color' => 'from-gray-900 to-gray-700'],
    ['name' => 'Sci-Fi', 'count' => '1.6K', 'icon' => 'fa-robot', 'color' => 'from-indigo-900 to-indigo-700'],
    ['name' => 'Fantastique', 'count' => '1.4K', 'icon' => 'fa-wand-magic-sparkles', 'color' => 'from-purple-900 to-purple-600']
];

$features = [
    [
        'title' => 'Recommandations IA',
        'description' => 'Notre IA analyse vos goûts et vous suggère des films et séries parfaitement adaptés à vos préférences.',
        'icon' => 'fa-brain'
    ],
    [
        'title' => 'Recherche Avancée',
        'description' => 'Filtrez par genre, année, réalisateur, acteurs et plateforme de streaming pour trouver exactement ce que vous cherchez.',
        'icon' => 'fa-search'
    ],
    [
        'title' => 'Communauté Active',
        'description' => 'Rejoignez une communauté de passionnés, partagez vos critiques et découvrez de nouveaux amis cinéphiles.',
        'icon' => 'fa-users'
    ],
    [
        'title' => 'Listes Personnalisées',
        'description' => 'Créez et organisez vos propres listes de films à regarder, favoris et collections thématiques.',
        'icon' => 'fa-list'
    ],
    [
        'title' => 'Statistiques Détaillées',
        'description' => 'Suivez votre progression, découvrez vos tendances de visionnage et partagez vos statistiques.',
        'icon' => 'fa-chart-bar'
    ],
    [
        'title' => 'Critiques & Notes',
        'description' => 'Lisez et écrivez des critiques détaillées, notez vos films et partagez votre opinion avec la communauté.',
        'icon' => 'fa-comments'
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineTrack - Vos Coups de Cœur Films et Séries</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
</head>
<body class="gradient-bg text-white">
    
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/hero.php'; ?>
    <?php include 'includes/trending.php'; ?>
    <?php include 'includes/genres.php'; ?>
    <?php include 'includes/features.php'; ?>
    <section class="py-16 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="glass p-8 md:p-12 rounded-3xl border border-gray-700 shadow-2xl">
            <div class="grid md:grid-cols-2 gap-8 items-center">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">
                        🎯 Testez vos connaissances cinéma
                    </h2>
                    <p class="text-gray-300 mb-6">
                        Notre quiz quotidien vous propose 10 questions sur les films, 
                        les acteurs et les réalisateurs. Défiez-vous et comparez votre 
                        score avec la communauté !
                    </p>
                    <div class="space-y-4 mb-6">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-stopwatch text-orange-500"></i>
                            <span>10 minutes chrono</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fas fa-question text-orange-500"></i>
                            <span>10 questions de difficulté variable</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fas fa-trophy text-orange-500"></i>
                            <span>Classement des meilleurs scores</span>
                        </div>
                    </div>
                    <a href="pages/quiz.php" 
                       class="btn-primary px-8 py-4 rounded-xl text-lg font-bold inline-flex items-center gap-3 hover:scale-105 transition-transform">
                        <i class="fas fa-play"></i>
                        Commencer le quiz du jour
                    </a>
                </div>
                <div class="hidden md:block">
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-orange-500 to-purple-600 rounded-2xl blur-xl opacity-30"></div>
                        <div class="relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-8 border border-gray-700">
                            <div class="text-center">
                                <div class="text-5xl mb-4">🎬</div>
                                <h3 class="text-2xl font-bold mb-2">Quiz du Jour</h3>
                                <p class="text-gray-400 mb-4">Thème : Films des années 2000</p>
                                <div class="flex justify-center gap-4 mb-6">
                                    <span class="px-3 py-1 bg-gray-700 rounded-full text-sm">Action</span>
                                    <span class="px-3 py-1 bg-gray-700 rounded-full text-sm">Comédie</span>
                                    <span class="px-3 py-1 bg-gray-700 rounded-full text-sm">Drame</span>
                                </div>
                                <p class="text-sm text-gray-500">Moyenne des joueurs : 6.5/10</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
    <?php include 'includes/cta.php'; ?>
    <?php include 'includes/footer.php'; ?>

    <script src="js/script.js"></script>
</body>
</html>