<?php
// series.php - TMDb API Integration for TV Shows
include '../includes/config.conf.php';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Function to fetch TV shows from TMDb API
function fetchTVShowsFromAPI($search = '', $genre = '', $year = '', $page = 1) {
    $api_key = TMDB_API_KEY;
    $base_url = TMDB_BASE_URL;
    
    // Build the API URL based on filters
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
        .series-hero {
            background: linear-gradient(135deg, rgba(120, 0, 255, 0.1) 0%, rgba(255, 140, 0, 0.1) 100%);
            padding: 120px 0 60px;
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
                radial-gradient(circle at 20% 80%, rgba(120, 0, 255, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 140, 0, 0.15) 0%, transparent 50%);
            filter: blur(60px);
            z-index: -1;
        }
        
        .hero-stats {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            background: linear-gradient(135deg, #7800ff 0%, #ff8c00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block;
        }
        
        .stat-label {
            color: #9ca3af;
            font-size: 0.875rem;
        }
        
        .filters-section {
            background: rgba(30, 30, 40, 0.25);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem 0;
        }
        
        .filters-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .search-box {
            position: relative;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .search-box input {
            width: 100%;
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            color: white;
            font-size: 1rem;
            backdrop-filter: blur(10px);
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #7800ff;
            box-shadow: 0 0 20px rgba(120, 0, 255, 0.3);
        }
        
        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: #7800ff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            background: #5a00cc;
            transform: translateY(-50%) scale(1.1);
        }
        
        .filter-container {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: white;
            backdrop-filter: blur(10px);
            min-width: 150px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: #7800ff;
        }
        
        .clear-filters {
            padding: 0.75rem 1rem;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 10px;
            color: #ef4444;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .clear-filters:hover {
            background: rgba(239, 68, 68, 0.3);
        }
        
        .series-section {
            padding: 4rem 0;
        }
        
        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: bold;
            color: white;
        }
        
        .results-count {
            color: #9ca3af;
            font-size: 0.875rem;
        }
        
        .series-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
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
        }
        
        .serie-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 50px rgba(120, 0, 255, 0.25);
            border-color: rgba(120, 0, 255, 0.3);
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
            transition: transform 0.3s ease;
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
            background: linear-gradient(180deg, rgba(0,0,0,0.3) 0%, transparent 50%, rgba(0,0,0,0.8) 100%);
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
        
        .favorite-btn, .play-btn {
            background: rgba(0, 0, 0, 0.7);
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
        }
        
        .favorite-btn:hover {
            background: #ef4444;
            transform: scale(1.1);
        }
        
        .play-btn:hover {
            background: #7800ff;
            transform: scale(1.1);
        }
        
        .serie-rating {
            background: rgba(0, 0, 0, 0.8);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: #ffd700;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .serie-info {
            padding: 1.5rem;
        }
        
        .serie-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: white;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .serie-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #9ca3af;
            font-size: 0.875rem;
        }
        
        .serie-seasons {
            background: rgba(120, 0, 255, 0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 8px;
            font-size: 0.75rem;
        }
        
        .no-series {
            text-align: center;
            padding: 4rem 2rem;
            color: #9ca3af;
        }
        
        .no-series i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 3rem;
        }
        
        .pagination a, .pagination span {
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background: #7800ff;
            border-color: #7800ff;
        }
        
        .pagination .current {
            background: #7800ff;
            border-color: #7800ff;
        }
        
        @media (max-width: 768px) {
            .series-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }
            
            .filter-container {
                flex-direction: column;
                align-items: center;
            }
            
            .filter-select {
                width: 100%;
                max-width: 300px;
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
                    <h1 class="text-5xl md:text-7xl font-black mb-6">📺 Séries TV</h1>
                    <p class="text-xl text-gray-300 mb-8">Plongez dans l'univers des séries télévisées</p>
                    <div class="hero-stats justify-center">
                        <div class="stat">
                            <span class="stat-number"><?php echo $total_results; ?></span>
                            <span class="stat-label">Séries</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number"><?php echo $total_series; ?></span>
                            <span class="stat-label">Disponibles</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Filters Section -->
        <section class="filters-section">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <form method="GET" action="" class="filters-form">
                    <div class="search-container">
                        <div class="search-box">
                            <input type="text" 
                                   name="search" 
                                   placeholder="Rechercher une série..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="filter-container">
                        <select name="genre" class="filter-select">
                            <option value="">Tous les genres</option>
                            <?php foreach($genres as $genre): ?>
                                <option value="<?php echo $genre['id']; ?>" 
                                    <?php echo $genre_filter == $genre['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($genre['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="year" class="filter-select">
                            <option value="">Toutes les années</option>
                            <?php for($year = date('Y'); $year >= 2000; $year--): ?>
                                <option value="<?php echo $year; ?>" 
                                    <?php echo $year_filter == $year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        
                        <?php if($search_query || $genre_filter || $year_filter): ?>
                            <a href="series.php" class="clear-filters">
                                <i class="fas fa-times"></i> Effacer
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>

        <!-- Series Grid Section -->
        <section class="series-section">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="section-header">
                    <h2 class="section-title">
                        <?php if($search_query): ?>
                            Résultats pour "<?php echo htmlspecialchars($search_query); ?>"
                        <?php elseif($genre_filter): ?>
                            <?php 
                            $genre_name = 'Genre';
                            foreach($genres as $genre) {
                                if($genre['id'] == $genre_filter) {
                                    $genre_name = $genre['name'];
                                    break;
                                }
                            }
                            ?>
                            Séries <?php echo htmlspecialchars($genre_name); ?>
                        <?php else: ?>
                            Séries Populaires
                        <?php endif; ?>
                    </h2>
                    <div class="results-count">
                        <?php echo $total_results; ?> série<?php echo $total_results > 1 ? 's' : ''; ?> trouvée<?php echo $total_results > 1 ? 's' : ''; ?>
                    </div>
                </div>

                <?php if(empty($series)): ?>
                    <div class="no-series">
                        <i class="fas fa-tv"></i>
                        <h3 class="text-2xl font-bold mb-4">Aucune série trouvée</h3>
                        <p class="text-gray-400 mb-6">Essayez de modifier vos critères de recherche</p>
                        <a href="series.php" class="btn-primary px-6 py-3 rounded-xl font-semibold">Voir toutes les séries</a>
                    </div>
                <?php else: ?>
                    <div class="series-grid">
                        <?php foreach($series as $serie): ?>
                            <div class="serie-card" onclick="viewSerieDetails(<?php echo $serie['id_serie']; ?>)">
                                <div class="serie-poster">
                                    <img src="<?php echo $serie['poster']; ?>" 
                                         alt="<?php echo htmlspecialchars($serie['titre']); ?>"
                                         loading="lazy">
                                    <div class="serie-overlay">
                                        <div class="flex justify-between">
                                            <button class="favorite-btn" onclick="event.stopPropagation(); addToWatchlist(<?php echo $serie['id_serie']; ?>, '<?php echo addslashes($serie['titre']); ?>', '<?php echo $serie['poster']; ?>', 'series')">
                                                <i class="far fa-heart"></i>
                                            </button>
                                            <button class="play-btn" onclick="event.stopPropagation(); viewSerieDetails(<?php echo $serie['id_serie']; ?>)">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </div>
                                        <div class="serie-rating">
                                            <i class="fas fa-star"></i>
                                            <span><?php echo number_format($serie['note_moyenne'], 1); ?></span>
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

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <?php if($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
    function viewSerieDetails(serieId) {
        window.location.href = 'serie-details.php?id=' + serieId;
    }
    
    function addToWatchlist(serieId, serieTitle, seriePoster) {
        const data = {
            serie_id: serieId,
            serie_title: serieTitle,
            serie_poster: seriePoster
        };

        fetch('add-to-watchlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Série ajoutée à votre watchlist!');
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue');
        });
    }
    
    // Auto-submit form when filters change
    document.addEventListener('DOMContentLoaded', function() {
        const filterSelects = document.querySelectorAll('select[name="genre"], select[name="year"]');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                document.querySelector('.filters-form').submit();
            });
        });
    });
    </script>
</body>
</html>