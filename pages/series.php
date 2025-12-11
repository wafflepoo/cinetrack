<?php
// series.php - TMDb API Integration for TV Shows with AJAX
ob_start();
session_start();
include '../includes/config.conf.php';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Function to fetch TV shows from TMDb API
function fetchTVShowsFromAPI($search = '', $genre = '', $year = '', $page = 1) {
    $api_key = TMDB_API_KEY;
    $base_url = TMDB_BASE_URL;
    
    if (!empty($search)) {
        $url = $base_url . 'search/tv?api_key=' . $api_key . '&query=' . urlencode($search) . '&language=fr-FR&page=' . $page;
    } else {
        $url = $base_url . 'discover/tv?api_key=' . $api_key . '&language=fr-FR&sort_by=popularity.desc&page=' . $page;
        
        if (!empty($genre)) {
            $url .= '&with_genres=' . urlencode($genre);
        }
        
        if (!empty($year)) {
            $url .= '&first_air_date_year=' . urlencode($year);
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
            'tv_shows' => $data['results'] ?? [],
            'total_pages' => $data['total_pages'] ?? 1,
            'total_results' => $data['total_results'] ?? 0
        ];
    }
    
    error_log("TMDb TV API Error: HTTP $http_code - URL: $url");
    return ['tv_shows' => [], 'total_pages' => 1, 'total_results' => 0];
}

// Function to get TV genre list from TMDb
function fetchTVGenresFromAPI() {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'genre/tv/list?api_key=' . $api_key . '&language=fr-FR';
    
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
    
    error_log("TMDb TV Genres API Error: HTTP $http_code");
    return getDefaultTVGenres();
}

function getDefaultTVGenres() {
    return [
        ['id' => 10759, 'name' => 'Action & Aventure'],
        ['id' => 16, 'name' => 'Animation'],
        ['id' => 35, 'name' => 'Comédie'],
        ['id' => 80, 'name' => 'Crime'],
        ['id' => 99, 'name' => 'Documentaire'],
        ['id' => 18, 'name' => 'Drame'],
        ['id' => 10751, 'name' => 'Famille'],
        ['id' => 10762, 'name' => 'Kids'],
        ['id' => 9648, 'name' => 'Mystère'],
        ['id' => 10763, 'name' => 'News'],
        ['id' => 10764, 'name' => 'Reality'],
        ['id' => 10765, 'name' => 'Sci-Fi & Fantastique'],
        ['id' => 10766, 'name' => 'Soap'],
        ['id' => 10767, 'name' => 'Talk'],
        ['id' => 10768, 'name' => 'War & Politics'],
        ['id' => 37, 'name' => 'Western']
    ];
}

// Fetch data
$apiData = fetchTVShowsFromAPI($search_query, $genre_filter, $year_filter, $page);
$apiTVShows = $apiData['tv_shows'];
$total_pages = $apiData['total_pages'];
$total_results = $apiData['total_results'];

$apiGenres = fetchTVGenresFromAPI();

$series = [];
foreach ($apiTVShows as $tv) {
    if (empty($tv['poster_path'])) continue;
    
    $series[] = [
        'id_serie' => $tv['id'],
        'titre' => $tv['name'] ?? 'Titre non disponible',
        'poster' => TMDB_IMAGE_BASE_URL . 'w500' . $tv['poster_path'],
        'date_premiere' => $tv['first_air_date'] ?? 'Date inconnue',
        'description' => $tv['overview'] ?? 'Description non disponible.',
        'note_moyenne' => $tv['vote_average'] ? round($tv['vote_average'], 1) : 0,
        'nb_critiques' => $tv['vote_count'] ?? 0,
        'nb_saisons' => $tv['number_of_seasons'] ?? 0,
        'nb_episodes' => $tv['number_of_episodes'] ?? 0,
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

$total_series = count($series);
$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Séries TV - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/animations.css">
    <style>
        /* COULEURS SÉRIES (pour différencier des films) */
        :root {
            --serie-primary: #8b5cf6;  /* Violet */
            --serie-secondary: #6366f1; /* Indigo */
            --serie-accent: #a855f7;    /* Violet plus clair */
            --serie-gradient: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
        }
        
        .series-hero {
            background: linear-gradient(135deg, 
                rgba(139, 92, 246, 0.15) 0%, 
                rgba(99, 102, 241, 0.15) 50%, 
                rgba(168, 85, 247, 0.1) 100%);
            padding: 120px 0 40px;
            position: relative;
            overflow: hidden;
        }
        
        .series-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(139, 92, 246, 0.2) 0%, transparent 60%),
                radial-gradient(circle at 80% 20%, rgba(99, 102, 241, 0.2) 0%, transparent 60%);
            filter: blur(80px);
            z-index: -1;
        }
        
        .hero-content h1 {
            background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 50%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
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
            border-color: rgba(139, 92, 246, 0.3);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.2);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
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
            border: 2px solid rgba(139, 92, 246, 0.2);
            border-radius: 50px;
            padding: 5px;
            transition: all 0.3s ease;
        }
        
        .search-input-wrapper:focus-within {
            border-color: #8b5cf6;
            box-shadow: 0 0 30px rgba(139, 92, 246, 0.4);
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
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
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
            box-shadow: 0 5px 20px rgba(139, 92, 246, 0.5);
        }
        
        #search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(17, 24, 39, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(139, 92, 246, 0.2);
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
            background: rgba(139, 92, 246, 0.1);
            border-left: 3px solid #8b5cf6;
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
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
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
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            border: none;
            color: white;
        }
        
        .apply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(139, 92, 246, 0.3);
        }
        
        .series-section {
            padding: 3rem 0;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(139, 92, 246, 0.2);
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
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
        
        .series-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .serie-card {
            background: rgba(30, 30, 40, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
        }
        
        .serie-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 50px rgba(139, 92, 246, 0.25);
            border-color: rgba(139, 92, 246, 0.3);
        }
        
        .serie-poster {
            position: relative;
            overflow: hidden;
            aspect-ratio: 2/3;
        }
        
        .serie-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .serie-card:hover .serie-poster img {
            transform: scale(1.1);
        }
        
        .serie-overlay {
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
        
        .serie-card:hover .serie-overlay {
            opacity: 1;
        }
        
        .serie-actions {
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
            background: #8b5cf6;
            transform: scale(1.1);
        }
        
        .serie-rating {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.9) 0%, rgba(168, 85, 247, 0.9) 100%);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: white;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(139, 92, 246, 0.3);
        }
        
        .serie-info {
            padding: 1.2rem;
        }
        
        .serie-title {
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
        
        .serie-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #9ca3af;
            font-size: 0.8rem;
        }
        
        .serie-year {
            background: rgba(139, 92, 246, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            color: #a855f7;
            font-weight: 600;
        }
        
        .serie-seasons {
            background: rgba(139, 92, 246, 0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 8px;
            font-size: 0.75rem;
            color: #8b5cf6;
            font-weight: 600;
        }
        
        .no-series {
            text-align: center;
            padding: 4rem 2rem;
            color: #9ca3af;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            border: 2px dashed rgba(255, 255, 255, 0.1);
        }
        
        .no-series i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
            background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
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
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            border-color: #8b5cf6;
            transform: translateY(-2px);
        }
        
        .pagination .current {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            border-color: #8b5cf6;
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .series-hero {
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
            
            .series-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .series-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .stat {
                min-width: 100%;
            }
        }
        
        .serie-card {
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
    
    <main class="series-main">
        <!-- Hero Section -->
        <section class="series-hero">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="hero-content text-center">
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-black mb-4">📺 TOUTES LES SÉRIES</h1>
                    <p class="text-lg md:text-xl text-gray-300 mb-8 max-w-3xl mx-auto">
                        Découvrez des milliers de séries TV. Trouvez votre prochaine série préférée.
                    </p>
                    <div class="hero-stats">
                        <div class="stat">
                            <span class="stat-number"><?php echo number_format($total_results); ?></span>
                            <span class="stat-label">Séries Totales</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number"><?php echo number_format($total_series); ?></span>
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
                               placeholder="Rechercher une série par titre..."
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
                            <a href="series.php" class="clear-btn">
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

        <!-- Series Section -->
        <section class="series-section">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="section-header">
                    <h2 class="section-title">SÉRIES <?php echo $search_query ? 'TROUVÉES' : 'POPULAIRES'; ?></h2>
                    <div class="results-count">
                        <?php if($search_query): ?>
                            Résultats pour "<?php echo htmlspecialchars($search_query); ?>"
                        <?php else: ?>
                            <?php echo number_format($total_results); ?> séries disponibles
                        <?php endif; ?>
                    </div>
                </div>
                
                <div id="series-container">
                    <?php if(!empty($series)): ?>
                        <div class="series-grid">
                            <?php foreach($series as $index => $serie): ?>
                                <div class="serie-card" style="animation-delay: <?php echo $index * 0.1; ?>s" 
                                     onclick="viewSerieDetails(<?php echo $serie['id_serie']; ?>)">
                                    <div class="serie-poster">
                                        <img src="<?php echo $serie['poster']; ?>" 
                                             alt="<?php echo htmlspecialchars($serie['titre']); ?>"
                                             loading="lazy"
                                             onerror="this.src='https://images.unsplash.com/photo-1592417817098-8fd3d9eb14a5?w=400&fit=crop'">
                                        <div class="serie-overlay">
                                            <div class="serie-actions">
                                                <button class="action-btn favorite-btn" 
                                                        onclick="event.stopPropagation(); addToWatchlist(<?php echo $serie['id_serie']; ?>, '<?php echo addslashes($serie['titre']); ?>', '<?php echo $serie['poster']; ?>', 'series')">
                                                    <i class="far fa-heart"></i>
                                                </button>
                                                <button class="action-btn play-btn" 
                                                        onclick="event.stopPropagation(); viewSerieDetails(<?php echo $serie['id_serie']; ?>)">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            </div>
                                            <div class="serie-rating">
                                                <i class="fas fa-star"></i>
                                                <span><?php echo number_format($serie['note_moyenne'], 1); ?>/10</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="serie-info">
                                        <h3 class="serie-title"><?php echo htmlspecialchars($serie['titre']); ?></h3>
                                        <div class="serie-meta">
                                            <span class="serie-year">
                                                <?php echo !empty($serie['date_premiere']) ? date('Y', strtotime($serie['date_premiere'])) : 'N/A'; ?>
                                            </span>
                                            <span class="serie-seasons">
                                                <?php echo $serie['nb_saisons']; ?> saison<?php echo $serie['nb_saisons'] > 1 ? 's' : ''; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-series">
                            <i class="fas fa-tv"></i>
                            <h3 class="text-2xl font-bold mb-2">Aucune série trouvée</h3>
                            <p class="text-gray-400">
                                <?php if($search_query): ?>
                                    Aucun résultat pour "<?php echo htmlspecialchars($search_query); ?>"
                                <?php else: ?>
                                    Aucune série disponible pour le moment
                                <?php endif; ?>
                            </p>
                            <?php if($search_query || $genre_filter || $year_filter): ?>
                                <a href="series.php" class="inline-block mt-4 px-6 py-3 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg font-semibold hover:opacity-90 transition-opacity">
                                    Voir toutes les séries
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
    // Recherche AJAX pour séries
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
        fetch(`../api/search-series.php?query=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) throw new Error('Erreur réseau');
                return response.json();
            })
            .then(results => {
                displaySearchResults(results, query);
            })
            .catch(error => {
                console.error('Erreur:', error);
                searchResults.innerHTML = '<div class="p-4 text-center text-gray-400">Service temporairement indisponible</div>';
                searchResults.style.display = 'block';
            });
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
        results.slice(0, 8).forEach(serie => {
            const year = serie.first_air_date ? serie.first_air_date.substring(0, 4) : 'N/A';
            const rating = serie.vote_average ? serie.vote_average.toFixed(1) : 'N/A';
            const poster = serie.poster_path ? 
                'https://image.tmdb.org/t/p/w200' + serie.poster_path : 
                'https://images.unsplash.com/photo-1592417817098-8fd3d9eb14a5?w=200&fit=crop';
            
            html += `
                <div class="search-result-item" onclick="viewSerieDetails(${serie.id})">
                    <img src="${poster}" alt="${serie.name}" loading="lazy">
                    <div class="search-result-info">
                        <h4>${serie.name}</h4>
                        <p>${year} • ⭐ ${rating}/10</p>
                    </div>
                </div>
            `;
        });
        
        searchResults.innerHTML = html;
        searchResults.style.display = 'block';
    }
    
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
    
    function performSearch() {
        const query = searchInput.value.trim();
        if (query) {
            window.location.href = `?search=${encodeURIComponent(query)}`;
        }
    }
    
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
    
    function viewSerieDetails(serieId) {
        window.location.href = 'serie-details.php?id=' + serieId;
    }
    
    function addToWatchlist(serieId, serieTitle, seriePoster, type = 'series') {
        fetch('../api/add-to-watchlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `serie_id=${serieId}&serie_title=${encodeURIComponent(serieTitle)}&serie_poster=${encodeURIComponent(seriePoster)}&type=${type}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Série ajoutée à votre watchlist!', 'success');
            } else {
                showNotification('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Erreur réseau', 'error');
        });
    }
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg font-semibold shadow-lg ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        }`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.serie-card').forEach(card => {
            observer.observe(card);
        });
    });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>