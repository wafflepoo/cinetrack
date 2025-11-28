<?php
session_start();
include 'includes/config.conf.php'; // Changez .conf en .php

// Function to fetch trending movies from TMDb API - CORRIGÉE
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
        // SUPPRIMEZ les lignes Authorization qui utilisent TMDB_ACCESS_TOKEN
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

// Fonction pour récupérer les genres depuis l'API TMDb
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

// Mettez à jour la partie qui génère les genres avec counts
$genres_to_display = array_slice($apiGenres, 0, 8);
$genres_with_counts = [];

foreach ($genres_to_display as $genre) {
    $movie_count = fetchMovieCountByGenre($genre['id']);
    $series_count = fetchSeriesCountByGenre($genre['id']); // Utilisez la vraie fonction maintenant
    
    $genres_with_counts[] = [
        'id' => $genre['id'],
        'name' => $genre['name'],
        'movie_count' => $movie_count,
        'series_count' => $series_count,
        'image' => $genre_images[$genre['id']] ?? 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=400',
        'icon' => $genre_styles[$genre['id']]['icon'] ?? 'fa-film',
        'color' => $genre_styles[$genre['id']]['color'] ?? 'from-gray-900 to-gray-700',
        'hover_color' => $genre_styles[$genre['id']]['hover_color'] ?? 'border-gray-500/50'
    ];
}
// Récupérer les genres depuis l'API
$apiGenres = fetchGenresFromAPI();

// Images d'arrière-plan par genre (vous pouvez les personnaliser)
$genre_images = [
    28 => 'https://images.unsplash.com/photo-1594909122845-11baa439b7bf?ixlib=rb-4.0.3&w=400&h=500&fit=crop', // Action
    12 => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&w=400&h=500&fit=crop', // Aventure
    35 => 'https://images.unsplash.com/photo-1542204165-65bf26472b9b?ixlib=rb-4.0.3&w=400&h=500&fit=crop', // Comédie
    18 => 'https://images.unsplash.com/photo-1485846234645-a62644f84728?ixlib=rb-4.0.3&w=400&h=500&fit=crop', // Drame
    878 => 'https://images.unsplash.com/photo-1446776653964-20c1d3a81b06?ixlib=rb-4.0.3&w=400&h=500&fit=crop', // Sci-Fi
    14 => 'https://images.unsplash.com/photo-1460881680858-30d872d5b530?ixlib=rb-4.0.3&w=400&h=500&fit=crop', // Fantastique
    27 => 'https://images.unsplash.com/photo-1509248961154-6c975d1301c4?ixlib=rb-4.0.3&w=400&h=500&fit=crop', // Horreur
    10749 => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?ixlib=rb-4.0.3&w=400&h=500&fit=crop', // Romance
    53 => 'https://images.unsplash.com/photo-1489599510024-f0e02f06dfb1?ixlib=rb-4.0.3&w=400&h=500&fit=crop', // Thriller
    16 => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?ixlib=rb-4.0.3&w=400&h=500&fit=crop', // Animation
    80 => 'https://images.unsplash.com/photo-1544216717-3bbf52512659?ixlib=rb-4.0.3&w=400&h=500&fit=crop', // Crime
    99 => 'https://images.unsplash.com/photo-1582719471384-894fbb16e074?ixlib=rb-4.0.3&w=400&h=500&fit=crop' // Documentaire
];

// Icônes et couleurs par genre
$genre_styles = [
    28 => ['icon' => 'fa-gun', 'color' => 'from-red-900 to-red-700', 'hover_color' => 'border-orange-500/50'],
    12 => ['icon' => 'fa-mountain-sun', 'color' => 'from-blue-900 to-blue-700', 'hover_color' => 'border-blue-500/50'],
    35 => ['icon' => 'fa-face-laugh-beam', 'color' => 'from-yellow-900 to-yellow-700', 'hover_color' => 'border-yellow-500/50'],
    18 => ['icon' => 'fa-masks-theater', 'color' => 'from-purple-900 to-purple-700', 'hover_color' => 'border-purple-500/50'],
    878 => ['icon' => 'fa-rocket', 'color' => 'from-indigo-900 to-indigo-700', 'hover_color' => 'border-indigo-500/50'],
    14 => ['icon' => 'fa-hat-wizard', 'color' => 'from-purple-900 to-purple-600', 'hover_color' => 'border-purple-400/50'],
    27 => ['icon' => 'fa-ghost', 'color' => 'from-gray-900 to-gray-700', 'hover_color' => 'border-red-600/50'],
    10749 => ['icon' => 'fa-heart', 'color' => 'from-pink-900 to-pink-700', 'hover_color' => 'border-pink-500/50'],
    53 => ['icon' => 'fa-user-secret', 'color' => 'from-orange-900 to-orange-700', 'hover_color' => 'border-orange-500/50'],
    16 => ['icon' => 'fa-film', 'color' => 'from-green-900 to-green-700', 'hover_color' => 'border-green-500/50'],
    80 => ['icon' => 'fa-shield', 'color' => 'from-gray-800 to-gray-600', 'hover_color' => 'border-gray-500/50'],
    99 => ['icon' => 'fa-camera', 'color' => 'from-blue-800 to-blue-600', 'hover_color' => 'border-blue-400/50']
];

// Récupérer les comptes de films par genre (limité aux 8 premiers pour les performances)
$genres_to_display = array_slice($apiGenres, 0, 8);
$genres_with_counts = [];

foreach ($genres_to_display as $genre) {
    $movie_count = fetchMovieCountByGenre($genre['id']);
    $series_count = round($movie_count * 0.4); // Estimation pour les séries
    
    $genres_with_counts[] = [
        'id' => $genre['id'],
        'name' => $genre['name'],
        'movie_count' => $movie_count,
        'series_count' => $series_count,
        'image' => $genre_images[$genre['id']] ?? 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=400',
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
    <title>CineTrack - Découvrez votre Prochaine Obsession</title>
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
    <?php include 'includes/cta.php'; ?>
    <?php include 'includes/footer.php'; ?>

    <script src="js/script.js"></script>
</body>
</html>