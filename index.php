<?php
session_start();
include 'includes/config.conf';

// Function to fetch trending movies from TMDb API
function fetchTrendingMovies() {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'trending/movie/week?api_key=' . $api_key . '&language=fr-FR';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . TMDB_ACCESS_TOKEN,
            'Content-Type: application/json;charset=utf-8'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        return array_slice($data['results'] ?? [], 0, 8); // Get first 4 trending movies
    }
    
    return []; // Return empty if API fails
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