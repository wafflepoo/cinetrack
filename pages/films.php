<?php
// films.php - TMDb API Integration avec recherche AJAX
include '../includes/config.conf.php';
include '../includes/auth.php';
// Get parameters for filtering
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Function to fetch movies from TMDb API
function fetchMoviesFromAPI($search = '', $genre = '', $year = '', $page = 1) {
    $api_key = TMDB_API_KEY;
    $base_url = TMDB_BASE_URL;
    
    if (!empty($search)) {
        $url = $base_url . 'search/movie?api_key=' . $api_key . '&query=' . urlencode($search) . '&language=fr-FR&page=' . $page;
    } else {
        $url = $base_url . 'discover/movie?api_key=' . $api_key . '&language=fr-FR&sort_by=popularity.desc&page=' . $page;
        
        if (!empty($genre)) {
            $url .= '&with_genres=' . urlencode($genre);
        }
        
        if (!empty($year)) {
            $url .= '&primary_release_year=' . urlencode($year);
        }
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
        return [
            'movies' => $data['results'] ?? [],
            'total_pages' => $data['total_pages'] ?? 1,
            'total_results' => $data['total_results'] ?? 0
        ];
    }
    
    error_log("TMDb API Error: HTTP $http_code - URL: $url");
    return ['movies' => [], 'total_pages' => 1, 'total_results' => 0];
}

// Function to get genre list from TMDb
function fetchGenresFromAPI() {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'genre/movie/list?api_key=' . $api_key . '&language=fr-FR';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
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
        ['id' => 99, 'name' => 'Documentaire'],
        ['id' => 10751, 'name' => 'Familial'],
        ['id' => 36, 'name' => 'Historique'],
        ['id' => 10402, 'name' => 'Musique'],
        ['id' => 9648, 'name' => 'Mystère'],
        ['id' => 10752, 'name' => 'Guerre'],
        ['id' => 37, 'name' => 'Western']
    ];
}

// Fetch data
$apiData = fetchMoviesFromAPI($search_query, $genre_filter, $year_filter, $page);
$apiMovies = $apiData['movies'];
$total_pages = $apiData['total_pages'];
$total_results = $apiData['total_results'];

$apiGenres = fetchGenresFromAPI();

$films = [];
foreach ($apiMovies as $movie) {
    if (empty($movie['poster_path'])) continue;
    
    $films[] = [
        'id_film' => $movie['id'],
        'titre' => $movie['title'] ?? 'Titre non disponible',
        'poster' => TMDB_IMAGE_BASE_URL . 'w500' . $movie['poster_path'],
        'date_sortie' => $movie['release_date'] ?? 'Date inconnue',
        'description' => $movie['overview'] ?? 'Description non disponible.',
        'note_moyenne' => $movie['vote_average'] ? round($movie['vote_average'], 1) : 0,
        'nb_critiques' => $movie['vote_count'] ?? 0,
        'duree' => null,
        'realisateur' => 'Information non disponible',
        'genres' => []
    ];
}

$genres = [];
foreach ($apiGenres as $genre) {
    $genres[] = [
        'id' => $genre['id'],
        'name' => $genre['name']
    ];
}

$total_films = count($films);

// Get current year for year dropdown
$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Films - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        .films-hero {
            background: linear-gradient(135deg, 
                rgba(255, 140, 0, 0.15) 0%, 
                rgba(120, 0, 255, 0.15) 50%, 
                rgba(0, 100, 255, 0.1) 100%);
            padding: 120px 0 40px;
            position: relative;
            overflow: hidden;
        }
        .toast {
            min-width: 280px;
            max-width: 360px;
            padding: 14px 18px;
            border-radius: 14px;
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.45);
            backdrop-filter: blur(12px);
            animation: toast-in 0.35s ease forwards;
        }

        .toast-success {
            background: linear-gradient(135deg, #16a34a, #22c55e);
        }

        .toast-error {
            background: linear-gradient(135deg, #dc2626, #ef4444);
        }

        .toast-info {
            background: linear-gradient(135deg, #f59e0b, #f97316);
        }

        .toast i {
            font-size: 1.3rem;
        }

        @keyframes toast-in {
            from {
                opacity: 0;
                transform: translateX(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes toast-out {
            to {
                opacity: 0;
                transform: translateX(40px) scale(0.9);
            }
        }

        .films-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 140, 0, 0.2) 0%, transparent 60%),
                radial-gradient(circle at 80% 20%, rgba(120, 0, 255, 0.2) 0%, transparent 60%);
            filter: blur(80px);
            z-index: -1;
        }
        
        .hero-content h1 {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 50%, #ffa500 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 5px 15px rgba(255, 140, 0, 0.3);
        }
        
        .hero-stats {
            display: flex;
            gap: 3rem;
            margin-top: 2rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .stat {
            text-align: center;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            min-width: 150px;
            transition: all 0.3s ease;
        }
        
        .stat:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 140, 0, 0.3);
            box-shadow: 0 10px 30px rgba(255, 140, 0, 0.2);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #ff8c00 0%, #ffa500 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            line-height: 1;
        }
        
        .stat-label {
            color: #9ca3af;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 0.5rem;
        }
        
        .search-section {
            background: rgba(17, 24, 39, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem 0;
            position: sticky;
            top: 70px;
            z-index: 40;
        }
        
        .search-container {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .search-input-wrapper {
            position: relative;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 140, 0, 0.2);
            border-radius: 50px;
            padding: 5px;
            transition: all 0.3s ease;
        }
        
        .search-input-wrapper:focus-within {
            border-color: #ff8c00;
            box-shadow: 0 0 30px rgba(255, 140, 0, 0.4);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .search-input-wrapper input {
            width: 100%;
            padding: 1rem 1.5rem;
            background: transparent;
            border: none;
            color: white;
            font-size: 1.1rem;
            outline: none;
        }
        
        .search-input-wrapper input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .search-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .search-btn:hover {
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 5px 20px rgba(255, 140, 0, 0.5);
        }
        
        #search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(17, 24, 39, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 140, 0, 0.2);
            border-radius: 15px;
            margin-top: 10px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 100;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .search-result-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .search-result-item:hover {
            background: rgba(255, 140, 0, 0.1);
            border-left: 3px solid #ff8c00;
        }
        
        .search-result-item img {
            width: 50px;
            height: 75px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .search-result-info h4 {
            color: white;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .search-result-info p {
            color: #9ca3af;
            font-size: 0.8rem;
        }
        
        .filters-section {
            padding: 2rem 0;
            background: rgba(30, 41, 59, 0.3);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            color: #9ca3af;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .filter-select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: #ff8c00;
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1);
        }
        
        .filter-select option {
            background: #1f2937;
            color: white;
            padding: 10px;
        }
        
        .filter-actions {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }
        
        .clear-btn, .apply-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .clear-btn {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        .clear-btn:hover {
            background: rgba(239, 68, 68, 0.2);
        }
        
        .apply-btn {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            border: none;
            color: white;
        }
        
        .apply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 140, 0, 0.3);
        }
        
        .movies-section {
            padding: 3rem 0;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 140, 0, 0.2);
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ff8c00 0%, #ffa500 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .results-count {
            color: #9ca3af;
            font-size: 0.9rem;
            background: rgba(255, 255, 255, 0.05);
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        @media (min-width: 1024px) {
            .movies-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 2rem;
            }
        }
        
        .movie-card {
            background: rgba(30, 30, 40, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
        }
        
        .movie-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 50px rgba(255, 140, 0, 0.25);
            border-color: rgba(255, 140, 0, 0.3);
        }
        
        .movie-poster {
            position: relative;
            overflow: hidden;
            aspect-ratio: 2/3;
        }
        
        .movie-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .movie-card:hover .movie-poster img {
            transform: scale(1.1);
        }
        
        .movie-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, 
                rgba(0,0,0,0.3) 0%, 
                transparent 30%, 
                rgba(0,0,0,0.9) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1rem;
        }
        
        .movie-card:hover .movie-overlay {
            opacity: 1;
        }
        
        .movie-actions {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .action-btn {
            background: rgba(0, 0, 0, 0.8);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        
        .action-btn:hover {
            background: #ff8c00;
            transform: scale(1.1);
        }
        
        .movie-rating {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.9) 0%, rgba(255, 165, 0, 0.9) 100%);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: white;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 215, 0, 0.3);
        }
        
        .movie-info {
            padding: 1.2rem;
        }
        
        .movie-title {
            font-size: 1rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .movie-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #9ca3af;
            font-size: 0.8rem;
        }
        
        .movie-year {
            background: rgba(255, 140, 0, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            color: #ff8c00;
            font-weight: 600;
        }
        
        .movie-reviews {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .no-movies {
            text-align: center;
            padding: 4rem 2rem;
            color: #9ca3af;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            border: 2px dashed rgba(255, 255, 255, 0.1);
        }
        
        .no-movies i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
            background: linear-gradient(135deg, #ff8c00 0%, #ffa500 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 3rem;
            flex-wrap: wrap;
        }
        
        .pagination a, .pagination span {
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            min-width: 45px;
            text-align: center;
            font-weight: 600;
        }
        
        .pagination a:hover:not(.disabled) {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            border-color: #ff8c00;
            transform: translateY(-2px);
        }
        
        .pagination .current {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            border-color: #ff8c00;
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        @media (max-width: 768px) {
            .films-hero {
                padding: 100px 0 30px;
            }
            
            .hero-stats {
                gap: 1rem;
            }
            
            .stat {
                min-width: 120px;
                padding: 1rem 1.5rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .search-section {
                padding: 1.5rem 0;
                top: 60px;
            }
            
            .search-input-wrapper {
                border-radius: 25px;
            }
        
            .search-input-wrapper input {
                padding: 0.875rem 1rem;
                font-size: 1rem;
            }
        
            .search-btn {
                width: 45px;
                height: 45px;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .filter-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .movies-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .movies-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .stat {
                min-width: 100%;
            }
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
            color: #ff8c00;
        }
        
        .loading-spinner i {
            animation: spin 1s linear infinite;
            font-size: 2rem;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Animation pour l'apparition des cartes */
        .movie-card {
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="gradient-bg text-white">
    <?php include '../includes/header.php'; ?>    
    <main class="films-main">
        <!-- Hero Section -->
        <section class="films-hero">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="hero-content text-center">
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-black mb-4">🎬 TOUS LES FILMS</h1>
                    <p class="text-lg md:text-xl text-gray-300 mb-8 max-w-3xl mx-auto">
                        Explorez notre collection complète de films. Trouvez vos prochains coups de cœur cinématographiques.
                    </p>
                    <div class="hero-stats">
                        <div class="stat">
                            <span class="stat-number"><?php echo number_format($total_results); ?></span>
                            <span class="stat-label">Films Totaux</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number"><?php echo number_format($total_films); ?></span>
                            <span class="stat-label">Disponibles Maintenant</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number"><?php echo count($genres); ?></span>
                            <span class="stat-label">Genres</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Search Section -->
        <section class="search-section">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="search-container">
                    <div class="search-input-wrapper">
                        <input type="text" 
                               id="live-search"
                               placeholder="Rechercher un film par titre, acteur ou réalisateur..."
                               value="<?php echo htmlspecialchars($search_query); ?>"
                               autocomplete="off">
                        <button type="button" class="search-btn" onclick="performSearch()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div id="search-results"></div>
                </div>
            </div>
        </section>

        <!-- Filters Section -->
        <section class="filters-section">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <form method="GET" action="" id="filters-form" class="filters-grid">
                    <div class="filter-group">
                        <label for="genre-select"><i class="fas fa-tag mr-2"></i>Genre</label>
                        <select name="genre" id="genre-select" class="filter-select">
                            <option value="">Tous les genres</option>
                            <?php foreach($genres as $genre): ?>
                                <option value="<?php echo $genre['id']; ?>" 
                                    <?php echo $genre_filter == $genre['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($genre['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="year-select"><i class="fas fa-calendar-alt mr-2"></i>Année</label>
                        <select name="year" id="year-select" class="filter-select">
                            <option value="">Toutes les années</option>
                            <?php for($year = $current_year; $year >= 1900; $year--): ?>
                                <option value="<?php echo $year; ?>" 
                                    <?php echo $year_filter == $year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <?php if($search_query || $genre_filter || $year_filter): ?>
                            <a href="films.php" class="clear-btn">
                                <i class="fas fa-times"></i> Effacer tout
                            </a>
                        <?php endif; ?>
                        <button type="submit" class="apply-btn">
                            <i class="fas fa-filter"></i> Appliquer
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Movies Section -->
        <section class="movies-section">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="section-header">
                    <h2 class="section-title">FILMS <?php echo $search_query ? 'TROUVÉS' : 'POPULAIRES'; ?></h2>
                    <div class="results-count">
                        <?php if($search_query): ?>
                            Résultats pour "<?php echo htmlspecialchars($search_query); ?>"
                        <?php else: ?>
                            <?php echo number_format($total_results); ?> films disponibles
                        <?php endif; ?>
                    </div>
                </div>
                
                <div id="movies-container">
                    <?php if(!empty($films)): ?>
                        <div class="movies-grid">
                            <?php foreach($films as $index => $film): ?>
                                <div class="movie-card" style="animation-delay: <?php echo $index * 0.1; ?>s" 
                                     onclick="viewMovieDetails(<?php echo $film['id_film']; ?>)">
                                    <div class="movie-poster">
                                        <img src="<?php echo $film['poster']; ?>" 
                                             alt="<?php echo htmlspecialchars($film['titre']); ?>"
                                             loading="lazy"
                                             onerror="this.src='https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=400&fit=crop'">
                                        <div class="movie-overlay">
                                           <div class="movie-actions">
    <div class="flex gap-2">
        <!-- Bouton Watchlist -->
        <button class="action-btn watchlist-btn" 
                onclick="event.stopPropagation(); addToWatchlist(<?php echo $film['id_film']; ?>, '<?php echo addslashes($film['titre']); ?>', '<?php echo $film['poster']; ?>', '<?php echo $film['date_sortie'] ?? ''; ?>')"
                title="Ajouter à la watchlist">
            <i class="fas fa-bookmark"></i>
        </button>
        
        <!-- Bouton Ajouter à une liste -->
        <button class="action-btn list-btn" 
                onclick="event.stopPropagation(); addToList(<?php echo $film['id_film']; ?>, '<?php echo addslashes($film['titre']); ?>', '<?php echo $film['poster']; ?>', '<?php echo $film['date_sortie'] ?? ''; ?>')"
                title="Ajouter à une liste">
            <i class="fas fa-list"></i>
        </button>
        
        <!-- Bouton Détails -->
        <button class="action-btn play-btn" 
                onclick="event.stopPropagation(); viewMovieDetails(<?php echo $film['id_film']; ?>)"
                title="Voir les détails">
            <i class="fas fa-play"></i>
        </button>
    </div>
</div>
                                            <div class="movie-rating">
                                                <i class="fas fa-star"></i>
                                                <span><?php echo number_format($film['note_moyenne'], 1); ?>/10</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="movie-info">
                                        <h3 class="movie-title"><?php echo htmlspecialchars($film['titre']); ?></h3>
                                        <div class="movie-meta">
                                            <span class="movie-year">
                                                <?php echo !empty($film['date_sortie']) ? date('Y', strtotime($film['date_sortie'])) : 'N/A'; ?>
                                            </span>
                                            <span class="movie-reviews">
                                                <i class="fas fa-comment"></i>
                                                <?php echo number_format($film['nb_critiques']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-movies">
                            <i class="fas fa-film"></i>
                            <h3 class="text-2xl font-bold mb-2">Aucun film trouvé</h3>
                            <p class="text-gray-400">
                                <?php if($search_query): ?>
                                    Aucun résultat pour "<?php echo htmlspecialchars($search_query); ?>"
                                <?php else: ?>
                                    Aucun film disponible pour le moment
                                <?php endif; ?>
                            </p>
                            <?php if($search_query || $genre_filter || $year_filter): ?>
                                <a href="films.php" class="inline-block mt-4 px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg font-semibold hover:opacity-90 transition-opacity">
                                    Voir tous les films
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
<?php if($total_pages > 1): ?>
    <div class="pagination">
        <?php if($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?><?php echo $search_query ? '&search='.urlencode($search_query) : ''; ?><?php echo $genre_filter ? '&genre='.$genre_filter : ''; ?><?php echo $year_filter ? '&year='.$year_filter : ''; ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php else: ?>
            <span class="disabled"><i class="fas fa-chevron-left"></i></span>
        <?php endif; ?>
        
        <?php 
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        
        if($start > 1) {
            echo '<a href="?page=1' . ($search_query ? '&search='.urlencode($search_query) : '') . ($genre_filter ? '&genre='.$genre_filter : '') . ($year_filter ? '&year='.$year_filter : '') . '">1</a>';
            if($start > 2) echo '<span class="disabled">...</span>';
        }
        
        for($i = $start; $i <= $end; $i++): ?>
            <?php if($i == $page): ?>
                <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?><?php echo $search_query ? '&search='.urlencode($search_query) : ''; ?><?php echo $genre_filter ? '&genre='.$genre_filter : ''; ?><?php echo $year_filter ? '&year='.$year_filter : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php
        // CORRECTION ICI : Syntaxe PHP correcte
        if($end < $total_pages) {
            if($end < $total_pages - 1) echo '<span class="disabled">...</span>';
            echo '<a href="?page=' . $total_pages . ($search_query ? '&search='.urlencode($search_query) : '') . ($genre_filter ? '&genre='.$genre_filter : '') . ($year_filter ? '&year='.$year_filter : '') . '">' . $total_pages . '</a>';
        }
        ?>
        
        <?php if($page < $total_pages): ?>
            <a href="?page=<?php echo $page+1; ?><?php echo $search_query ? '&search='.urlencode($search_query) : ''; ?><?php echo $genre_filter ? '&genre='.$genre_filter : ''; ?><?php echo $year_filter ? '&year='.$year_filter : ''; ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php else: ?>
            <span class="disabled"><i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>
    </div>
<?php endif; ?>
            </div>
        </section>
    </main>
<?php include '../includes/footer.php'; ?>
    <script>
    // Recherche en temps réel avec AJAX
    let searchTimeout;
    const searchInput = document.getElementById('live-search');
    const searchResults = document.getElementById('search-results');
    
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performLiveSearch(query);
        }, 300);
    });
    
    function performLiveSearch(query) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '../api/search-movies.php?query=' + encodeURIComponent(query), true);
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    const results = JSON.parse(xhr.responseText);
                    displaySearchResults(results, query);
                } else {
                    searchResults.innerHTML = '<div class="p-4 text-center text-gray-400">Erreur de recherche</div>';
                    searchResults.style.display = 'block';
                }
            }
        };
        
        xhr.send();
    }
    
    function displaySearchResults(results, query) {
        if (results.length === 0) {
            searchResults.innerHTML = `
                <div class="p-4 text-center">
                    <i class="fas fa-search mb-2 text-gray-500"></i>
                    <p class="text-gray-400">Aucun résultat pour "${query}"</p>
                </div>
            `;
            searchResults.style.display = 'block';
            return;
        }
        
        let html = '';
        results.slice(0, 8).forEach(movie => {
            const year = movie.release_date ? movie.release_date.substring(0, 4) : 'N/A';
            const rating = movie.vote_average ? movie.vote_average.toFixed(1) : 'N/A';
            const poster = movie.poster_path ? 
                'https://image.tmdb.org/t/p/w200' + movie.poster_path : 
                'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=200&fit=crop';
            
            html += `
                <div class="search-result-item" onclick="viewMovieDetails(${movie.id})">
                    <img src="${poster}" alt="${movie.title}" loading="lazy">
                    <div class="search-result-info">
                        <h4>${movie.title}</h4>
                        <p>${year} • ⭐ ${rating}/10</p>
                    </div>
                </div>
            `;
        });
        
        searchResults.innerHTML = html;
        searchResults.style.display = 'block';
    }
    
    // Fermer les résultats de recherche en cliquant ailleurs
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
    
    // Recherche soumise via formulaire
    function performSearch() {
        const query = searchInput.value.trim();
        if (query) {
            window.location.href = `?search=${encodeURIComponent(query)}`;
        }
    }
    
    // Permettre la touche Entrée pour la recherche
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });



function viewMovieDetails(movieId) {
    window.location.href = 'movie-details.php?id=' + movieId;
}

function addToWatchlist(movieId, movieTitle, moviePoster, releaseDate = '') {
    const movieData = {
        movie_id: movieId,
        movie_title: movieTitle,
        movie_poster: moviePoster,
        release_date: releaseDate || null
    };
    
    fetch('../api/add-to-watchlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(movieData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(' Film ajouté à votre watchlist', 'success');
        } else {
            showToast(data.message, data.message === 'Déjà dans la watchlist' ? 'info' : 'error');
        }
    })
    .catch(error => {
        showToast('Erreur réseau', 'error');
    });
}
    
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const icon = {
        success: 'fa-check-circle',
        error: 'fa-xmark-circle',
        info: 'fa-heart'
    }[type];

    toast.innerHTML = `
        <i class="fas ${icon}"></i>
        <span>${message}</span>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'toast-out 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 2600);
}


    // Fonction pour ajouter à une liste
function addToList(movieId, movieTitle, moviePoster, releaseDate = '') {
    // Vérifier si l'utilisateur est connecté
    <?php if (isLoggedIn()): ?>
        // Utilisateur connecté - charger les listes
        loadUserLists(movieId, movieTitle, moviePoster, releaseDate);
    <?php else: ?>
        // Non connecté - rediriger
        showToast('Veuillez vous connecter pour ajouter à une liste', 'info');
        setTimeout(() => {
            window.location.href = 'connexion.php?redirect=' + encodeURIComponent(window.location.href);
        }, 1500);
    <?php endif; ?>
}

// Charger les listes de l'utilisateur
function loadUserLists(movieId, movieTitle, moviePoster, releaseDate) {
    fetch('../lists/get-user-lists.php')
        .then(response => response.json())
        .then(lists => {
            showListSelectionModal(lists, movieId, movieTitle, moviePoster, releaseDate);
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur lors du chargement des listes', 'error');
        });
}

// Afficher la modal de sélection de liste
function showListSelectionModal(lists, movieId, movieTitle, moviePoster, releaseDate) {
    const modalId = 'listModal-' + movieId;
    
    let modalHTML = `
        <div id="${modalId}" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-[100]">
            <div class="bg-gray-900 border border-orange-500/30 rounded-xl p-6 max-w-md w-full mx-4 shadow-2xl">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-white">Ajouter à une liste</h3>
                    <button onclick="closeListModal('${modalId}')" class="text-gray-400 hover:text-white text-2xl">&times;</button>
                </div>
                
                <p class="text-gray-300 mb-6">
                    Choisissez une liste pour "<strong class="text-orange-400">${movieTitle}</strong>"
                </p>
                
                <div id="listsContainer-${movieId}" class="space-y-3 max-h-60 overflow-y-auto mb-6 pr-2">
                    ${lists.length === 0 ? 
                        '<div class="text-center py-4 text-gray-400">' +
                            '<i class="fas fa-list mb-2 text-2xl"></i>' +
                            '<p>Vous n\'avez aucune liste</p>' +
                            '<a href="../lists/list.php" class="text-orange-500 hover:text-orange-400 mt-2 inline-block">Créer une liste</a>' +
                        '</div>' 
                        : ''}
                </div>
                
                <div class="flex gap-3">
                    <button onclick="createNewList(${movieId}, '${movieTitle.replace(/'/g, "\\'")}', '${moviePoster.replace(/'/g, "\\'")}', '${releaseDate.replace(/'/g, "\\'")}', '${modalId}')"
                            class="flex-1 py-3 bg-orange-600 hover:bg-orange-700 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-plus mr-2"></i>Nouvelle liste
                    </button>
                    <button onclick="closeListModal('${modalId}')"
                            class="flex-1 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg font-semibold transition-colors">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Afficher les listes si elles existent
    if (lists.length > 0) {
        const container = document.getElementById(`listsContainer-${movieId}`);
        lists.forEach(list => {
            const listItem = document.createElement('div');
            listItem.className = 'p-4 bg-gray-800/50 rounded-lg hover:bg-gray-700/50 cursor-pointer transition-colors border border-gray-700/50 hover:border-orange-500/30';
            listItem.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <span class="font-medium text-white">${list.nom_liste}</span>
                    <span class="text-sm text-gray-400">${list.item_count || 0} élément(s)</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-400 capitalize">${list.type_liste || 'mixte'}</span>
                    <button onclick="addToSpecificList(${list.id_liste}, ${movieId}, '${movieTitle.replace(/'/g, "\\'")}', '${moviePoster.replace(/'/g, "\\'")}', '${releaseDate.replace(/'/g, "\\'")}', '${modalId}')"
                            class="px-3 py-1 bg-orange-600 hover:bg-orange-700 rounded-lg text-sm font-medium">
                        Ajouter
                    </button>
                </div>
            `;
            container.appendChild(listItem);
        });
    }
}

// Fermer la modal
function closeListModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.remove();
}

// Ajouter à une liste spécifique
function addToSpecificList(listId, movieId, movieTitle, moviePoster, releaseDate, modalId) {
    // D'abord ajouter le film à la base de données
    const movieData = {
        movie_id: movieId,
        movie_title: movieTitle,
        movie_poster: moviePoster,
        release_date: releaseDate || null
    };
    
    fetch('../api/add-to-watchlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(movieData)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success && data.message !== 'Déjà dans la watchlist') {
            showToast('Erreur: ' + data.message, 'error');
            return;
        }
        
        // Puis ajouter à la liste
        const formData = new FormData();
        formData.append('list_id', listId);
        formData.append('movie_id', movieId);
        formData.append('movie_title', movieTitle);
        formData.append('movie_poster', moviePoster);
        
        fetch('../lists/add-to-list.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            closeListModal(modalId);
            if (result.status === 'success') {
                showToast(`Ajouté à "${result.list_name}"`, 'success');
            } else if (result.status === 'exists') {
                showToast('Déjà dans cette liste', 'info');
            } else {
                showToast('Erreur: ' + result.message, 'error');
            }
        })
        .catch(error => {
            showToast('Erreur réseau', 'error');
        });
    })
    .catch(error => {
        showToast('Erreur d\'ajout', 'error');
    });
}

// Créer une nouvelle liste
function createNewList(movieId, movieTitle, moviePoster, releaseDate, modalId) {
    const listName = prompt('Nom de la nouvelle liste :');
    if (!listName || listName.trim() === '') return;
    
    // D'abord ajouter le film
    const movieData = {
        movie_id: movieId,
        movie_title: movieTitle,
        movie_poster: moviePoster,
        release_date: releaseDate || null
    };
    
    fetch('../api/add-to-watchlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(movieData)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success && data.message !== 'Déjà dans la watchlist') {
            showToast('Erreur: ' + data.message, 'error');
            return;
        }
        
        // Créer la liste
        const formData = new FormData();
        formData.append('list_name', listName);
        formData.append('list_type', 'films');
        formData.append('movie_id', movieId);
        formData.append('movie_title', movieTitle);
        formData.append('movie_poster', moviePoster);
        
        fetch('../lists/create-list-with-item.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            closeListModal(modalId);
            if (result.status === 'success') {
                showToast(`Liste "${listName}" créée`, 'success');
            } else {
                showToast('Erreur: ' + result.message, 'error');
            }
        })
        .catch(error => {
            showToast('Erreur de création', 'error');
        });
    })
    .catch(error => {
        showToast('Erreur d\'ajout', 'error');
    });
}

    
    // Animation pour le scroll
    document.addEventListener('DOMContentLoaded', function() {
        const observerOptions = {
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observer les cartes de film
        document.querySelectorAll('.movie-card').forEach(card => {
            observer.observe(card);
        });
    });
    </script>
</body>
</html>