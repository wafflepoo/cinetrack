<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Déterminer le type de recommandation
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$content_type = isset($_GET['content']) ? $_GET['content'] : 'both'; // 'movies', 'series', 'both'
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// =====================================================================
// FONCTIONS POUR LES RECOMMANDATIONS FILMS
// =====================================================================

function getUserWatchlistMovies($mysqli, $user_id) {
    $query = "SELECT f.id_film, f.titre, f.poster 
              FROM SELECTION s 
              JOIN FILM f ON s.id_film = f.id_film 
              WHERE s.id_utilisateur = ? 
              LIMIT 10";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $movies = [];
    while ($row = $result->fetch_assoc()) {
        $movies[] = $row;
    }
    $stmt->close();
    
    return $movies;
}

function getUserWatchlistSeries($mysqli, $user_id) {
    $query = "SELECT s.id_serie, s.titre, s.poster 
              FROM SELECTION sel 
              JOIN SERIE s ON sel.id_serie = s.id_serie 
              WHERE sel.id_utilisateur = ? 
              LIMIT 10";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $series = [];
    while ($row = $result->fetch_assoc()) {
        $series[] = $row;
    }
    $stmt->close();
    
    return $series;
}

// Fonctions TMDb pour films
function getMovieGenres($movie_id) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "movie/$movie_id?api_key=$api_key&language=fr-FR";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $genres = [];
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['genres'])) {
            foreach ($data['genres'] as $genre) {
                $genres[] = $genre['id'];
            }
        }
    }
    
    return $genres;
}

function getMovieActors($movie_id) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "movie/$movie_id/credits?api_key=$api_key";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $actors = [];
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['cast'])) {
            $main_cast = array_slice($data['cast'], 0, 5);
            foreach ($main_cast as $actor) {
                $actors[] = $actor['id'];
            }
        }
    }
    
    return $actors;
}

// =====================================================================
// FONCTIONS POUR LES RECOMMANDATIONS SÉRIES
// =====================================================================

function getSerieGenres($serie_id) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "tv/$serie_id?api_key=$api_key&language=fr-FR";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $genres = [];
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['genres'])) {
            foreach ($data['genres'] as $genre) {
                $genres[] = $genre['id'];
            }
        }
    }
    
    return $genres;
}

function getSerieActors($serie_id) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "tv/$serie_id/credits?api_key=$api_key";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $actors = [];
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['cast'])) {
            $main_cast = array_slice($data['cast'], 0, 5);
            foreach ($main_cast as $actor) {
                $actors[] = $actor['id'];
            }
        }
    }
    
    return $actors;
}

// =====================================================================
// FONCTIONS GÉNÉRALES TMDb
// =====================================================================

function fetchMoviesFromTMDB($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $movies = [];
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['results'])) {
            foreach ($data['results'] as $movie) {
                if (!empty($movie['poster_path'])) {
                    $movies[] = [
                        'id' => $movie['id'],
                        'title' => $movie['title'] ?? 'Titre inconnu',
                        'poster' => TMDB_IMAGE_BASE_URL . 'w500' . $movie['poster_path'],
                        'release_date' => $movie['release_date'] ?? '',
                        'overview' => $movie['overview'] ?? '',
                        'vote_average' => $movie['vote_average'] ?? 0,
                        'vote_count' => $movie['vote_count'] ?? 0,
                        'type' => 'movie'
                    ];
                }
            }
        }
    }
    
    return $movies;
}

function fetchSeriesFromTMDB($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $series = [];
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['results'])) {
            foreach ($data['results'] as $serie) {
                if (!empty($serie['poster_path'])) {
                    $series[] = [
                        'id' => $serie['id'],
                        'title' => $serie['name'] ?? 'Titre inconnu',
                        'poster' => TMDB_IMAGE_BASE_URL . 'w500' . $serie['poster_path'],
                        'first_air_date' => $serie['first_air_date'] ?? '',
                        'overview' => $serie['overview'] ?? '',
                        'vote_average' => $serie['vote_average'] ?? 0,
                        'vote_count' => $serie['vote_count'] ?? 0,
                        'type' => 'serie'
                    ];
                }
            }
        }
    }
    
    return $series;
}

// =====================================================================
// FONCTIONS DE RECOMMANDATIONS
// =====================================================================

function getCommonGenres($items, $is_movie = true) {
    $all_genres = [];
    
    foreach ($items as $item_id) {
        if ($is_movie) {
            $genres = getMovieGenres($item_id);
        } else {
            $genres = getSerieGenres($item_id);
        }
        $all_genres = array_merge($all_genres, $genres);
    }
    
    $genre_counts = array_count_values($all_genres);
    arsort($genre_counts);
    
    return $genre_counts;
}

function getCommonActors($items, $is_movie = true) {
    $all_actors = [];
    
    foreach (array_slice($items, 0, 5) as $item_id) {
        if ($is_movie) {
            $actors = getMovieActors($item_id);
        } else {
            $actors = getSerieActors($item_id);
        }
        $all_actors = array_merge($all_actors, $actors);
    }
    
    $actor_counts = array_count_values($all_actors);
    arsort($actor_counts);
    
    return $actor_counts;
}

function getMovieRecommendationsByGenres($movie_ids, $page = 1) {
    $genres = getCommonGenres($movie_ids, true);
    
    if (empty($genres)) {
        return [];
    }
    
    $genre_keys = array_keys($genres);
    $top_genres = array_slice($genre_keys, 0, 2);
    $genre_ids = implode(',', $top_genres);
    
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "discover/movie?api_key=$api_key&language=fr-FR&sort_by=popularity.desc&with_genres=$genre_ids&page=$page";
    
    return fetchMoviesFromTMDB($url);
}

function getSerieRecommendationsByGenres($serie_ids, $page = 1) {
    $genres = getCommonGenres($serie_ids, false);
    
    if (empty($genres)) {
        return [];
    }
    
    $genre_keys = array_keys($genres);
    $top_genres = array_slice($genre_keys, 0, 2);
    $genre_ids = implode(',', $top_genres);
    
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "discover/tv?api_key=$api_key&language=fr-FR&sort_by=popularity.desc&with_genres=$genre_ids&page=$page";
    
    return fetchSeriesFromTMDB($url);
}

function getMovieRecommendationsByActors($movie_ids, $page = 1) {
    $actors = getCommonActors($movie_ids, true);
    
    if (empty($actors)) {
        return [];
    }
    
    $actor_keys = array_keys($actors);
    $top_actors = array_slice($actor_keys, 0, 2);
    $actor_ids = implode(',', $top_actors);
    
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "discover/movie?api_key=$api_key&language=fr-FR&sort_by=popularity.desc&with_cast=$actor_ids&page=$page";
    
    return fetchMoviesFromTMDB($url);
}

function getSerieRecommendationsByActors($serie_ids, $page = 1) {
    $actors = getCommonActors($serie_ids, false);
    
    if (empty($actors)) {
        return [];
    }
    
    $actor_keys = array_keys($actors);
    $top_actors = array_slice($actor_keys, 0, 2);
    $actor_ids = implode(',', $top_actors);
    
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "discover/tv?api_key=$api_key&language=fr-FR&sort_by=popularity.desc&with_cast=$actor_ids&page=$page";
    
    return fetchSeriesFromTMDB($url);
}

function getSimilarMovies($movie_ids, $page = 1) {
    $all_similar = [];
    
    foreach (array_slice($movie_ids, 0, 3) as $movie_id) {
        $api_key = TMDB_API_KEY;
        $url = TMDB_BASE_URL . "movie/$movie_id/similar?api_key=$api_key&language=fr-FR&page=$page";
        
        $similar = fetchMoviesFromTMDB($url);
        $all_similar = array_merge($all_similar, $similar);
    }
    
    return $all_similar;
}

function getSimilarSeries($serie_ids, $page = 1) {
    $all_similar = [];
    
    foreach (array_slice($serie_ids, 0, 3) as $serie_id) {
        $api_key = TMDB_API_KEY;
        $url = TMDB_BASE_URL . "tv/$serie_id/similar?api_key=$api_key&language=fr-FR&page=$page";
        
        $similar = fetchSeriesFromTMDB($url);
        $all_similar = array_merge($all_similar, $similar);
    }
    
    return $all_similar;
}

function getPopularMovies($page = 1) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "movie/popular?api_key=$api_key&language=fr-FR&page=$page";
    
    return fetchMoviesFromTMDB($url);
}

function getPopularSeries($page = 1) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "tv/popular?api_key=$api_key&language=fr-FR&page=$page";
    
    return fetchSeriesFromTMDB($url);
}

// =====================================================================
// FONCTION PRINCIPALE
// =====================================================================

function getRecommendations($user_id, $type = 'all', $content_type = 'both', $page = 1) {
    global $mysqli;
    
    $recommendations = [];
    $watchlist_movie_ids = [];
    $watchlist_serie_ids = [];
    
    // Récupérer les IDs de la watchlist
    if ($content_type === 'movies' || $content_type === 'both') {
        $movies = getUserWatchlistMovies($mysqli, $user_id);
        $watchlist_movie_ids = array_column($movies, 'id_film');
    }
    
    if ($content_type === 'series' || $content_type === 'both') {
        $series = getUserWatchlistSeries($mysqli, $user_id);
        $watchlist_serie_ids = array_column($series, 'id_serie');
    }
    
    // Si pas de contenu dans la watchlist, retourner du populaire
    if (empty($watchlist_movie_ids) && empty($watchlist_serie_ids)) {
        if ($content_type === 'movies') {
            return getPopularMovies($page);
        } elseif ($content_type === 'series') {
            return getPopularSeries($page);
        } else {
            $movies = getPopularMovies($page);
            $series = getPopularSeries($page);
            return array_merge(array_slice($movies, 0, 10), array_slice($series, 0, 10));
        }
    }
    
    // Générer des recommandations selon le type et le contenu
    switch ($content_type) {
        case 'movies':
            $recommendations = getMovieRecommendations($watchlist_movie_ids, $type, $page);
            break;
            
        case 'series':
            $recommendations = getSerieRecommendations($watchlist_serie_ids, $type, $page);
            break;
            
        case 'both':
        default:
            $movie_recs = getMovieRecommendations($watchlist_movie_ids, $type, $page);
            $serie_recs = getSerieRecommendations($watchlist_serie_ids, $type, $page);
            $recommendations = array_merge($movie_recs, $serie_recs);
            break;
    }
    
    // Filtrer les doublons et ce qui est déjà en watchlist
    return filterRecommendations($recommendations, $watchlist_movie_ids, $watchlist_serie_ids);
}

function getMovieRecommendations($movie_ids, $type, $page) {
    $recommendations = [];
    
    switch ($type) {
        case 'genres':
            $recommendations = getMovieRecommendationsByGenres($movie_ids, $page);
            break;
            
        case 'actors':
            $recommendations = getMovieRecommendationsByActors($movie_ids, $page);
            break;
            
        case 'similar':
            $recommendations = getSimilarMovies($movie_ids, $page);
            break;
            
        case 'all':
        default:
            $genre_recs = getMovieRecommendationsByGenres($movie_ids, $page);
            $actor_recs = getMovieRecommendationsByActors($movie_ids, $page);
            $similar_recs = getSimilarMovies($movie_ids, $page);
            $recommendations = array_merge($genre_recs, $actor_recs, $similar_recs);
            break;
    }
    
    return $recommendations;
}

function getSerieRecommendations($serie_ids, $type, $page) {
    $recommendations = [];
    
    switch ($type) {
        case 'genres':
            $recommendations = getSerieRecommendationsByGenres($serie_ids, $page);
            break;
            
        case 'actors':
            $recommendations = getSerieRecommendationsByActors($serie_ids, $page);
            break;
            
        case 'similar':
            $recommendations = getSimilarSeries($serie_ids, $page);
            break;
            
        case 'all':
        default:
            $genre_recs = getSerieRecommendationsByGenres($serie_ids, $page);
            $actor_recs = getSerieRecommendationsByActors($serie_ids, $page);
            $similar_recs = getSimilarSeries($serie_ids, $page);
            $recommendations = array_merge($genre_recs, $actor_recs, $similar_recs);
            break;
    }
    
    return $recommendations;
}

function filterRecommendations($recommendations, $movie_ids, $serie_ids) {
    $unique_recommendations = [];
    $seen_ids = [];
    
    foreach ($recommendations as $item) {
        $item_id = $item['id'];
        
        // Vérifier si pas déjà vu et pas dans la watchlist
        if (!in_array($item_id, $seen_ids)) {
            if ($item['type'] === 'movie' && !in_array($item_id, $movie_ids)) {
                $seen_ids[] = $item_id;
                $unique_recommendations[] = $item;
            } elseif ($item['type'] === 'serie' && !in_array($item_id, $serie_ids)) {
                $seen_ids[] = $item_id;
                $unique_recommendations[] = $item;
            }
        }
    }
    
    // Compléter si nécessaire
    if (count($unique_recommendations) < 10) {
        $popular_movies = getPopularMovies(1);
        $popular_series = getPopularSeries(1);
        $popular_all = array_merge($popular_movies, $popular_series);
        
        foreach ($popular_all as $item) {
            if (count($unique_recommendations) >= 20) break;
            
            $item_id = $item['id'];
            if (!in_array($item_id, $seen_ids)) {
                if ($item['type'] === 'movie' && !in_array($item_id, $movie_ids)) {
                    $unique_recommendations[] = $item;
                    $seen_ids[] = $item_id;
                } elseif ($item['type'] === 'serie' && !in_array($item_id, $serie_ids)) {
                    $unique_recommendations[] = $item;
                    $seen_ids[] = $item_id;
                }
            }
        }
    }
    
    // Mélanger et limiter
    shuffle($unique_recommendations);
    return array_slice($unique_recommendations, 0, 20);
}

// =====================================================================
// RÉCUPÉRATION DES DONNÉES
// =====================================================================

$recommended_items = getRecommendations($user['id'], $type, $content_type, $page);
$watchlist_movies = getUserWatchlistMovies($mysqli, $user['id']);
$watchlist_series = getUserWatchlistSeries($mysqli, $user['id']);
$total_watchlist = count($watchlist_movies) + count($watchlist_series);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommandations - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #0a0e14 0%, #05080d 100%);
        }
        .glass {
            background: rgba(30, 30, 40, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 140, 0, 0.3);
        }
        .movie-card, .serie-card {
            transition: all 0.3s ease;
        }
        .movie-card:hover, .serie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 140, 0, 0.2);
        }
        .type-btn {
            padding: 0.5rem 1.5rem;
            border-radius: 9999px;
            transition: all 0.3s ease;
        }
        .type-btn.active {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
        }
        .content-btn {
            padding: 0.5rem 1.5rem;
            border-radius: 9999px;
            transition: all 0.3s ease;
        }
        .content-btn.active {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
        .nav-link {
            @apply text-gray-400 hover:text-white px-3 py-2 rounded-lg transition;
        }
        .nav-link.active {
            @apply text-orange-500 bg-orange-500/10;
        }
        .badge-movie {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
        }
        .badge-serie {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen">
   <?php include '../../includes/header.php'; ?>

    <main class="pt-32 pb-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-12">
                <h1 class="text-4xl md:text-5xl font-black mb-4">
                    <i class="fas fa-magic text-orange-500 mr-3"></i>
                    Recommandations personnalisées
                </h1>
                <p class="text-gray-400 text-lg">
                    Découvrez des films et séries que vous allez adorer, sélectionnés spécialement pour vous
                </p>
                
                <!-- Type filters -->
                <div class="flex flex-wrap gap-3 mt-6 mb-4">
                    <a href="recommendations.php?type=all&content=<?= $content_type ?>" 
                       class="type-btn glass <?= $type === 'all' ? 'active' : '' ?>">
                       <i class="fas fa-star mr-2"></i>Toutes
                    </a>
                    <a href="recommendations.php?type=genres&content=<?= $content_type ?>" 
                       class="type-btn glass <?= $type === 'genres' ? 'active' : '' ?>">
                       <i class="fas fa-tags mr-2"></i>Par genres
                    </a>
                    <a href="recommendations.php?type=actors&content=<?= $content_type ?>" 
                       class="type-btn glass <?= $type === 'actors' ? 'active' : '' ?>">
                       <i class="fas fa-users mr-2"></i>Par acteurs
                    </a>
                    <a href="recommendations.php?type=similar&content=<?= $content_type ?>" 
                       class="type-btn glass <?= $type === 'similar' ? 'active' : '' ?>">
                       <i class="fas fa-film mr-2"></i>Similaires
                    </a>
                </div>
                
                <!-- Content type filters -->
                <div class="flex flex-wrap gap-3 mt-4">
                    <a href="recommendations.php?type=<?= $type ?>&content=both" 
                       class="content-btn glass <?= $content_type === 'both' ? 'active' : '' ?>">
                       <i class="fas fa-film mr-2"></i><i class="fas fa-tv mr-2"></i>Films & Séries
                    </a>
                    <a href="recommendations.php?type=<?= $type ?>&content=movies" 
                       class="content-btn glass <?= $content_type === 'movies' ? 'active' : '' ?>">
                       <i class="fas fa-film mr-2"></i>Films seulement
                    </a>
                    <a href="recommendations.php?type=<?= $type ?>&content=series" 
                       class="content-btn glass <?= $content_type === 'series' ? 'active' : '' ?>">
                       <i class="fas fa-tv mr-2"></i>Séries seulement
                    </a>
                </div>
            </div>
            
            <!-- Info section -->
            <div class="glass rounded-2xl p-6 mb-8">
                <div class="flex items-start">
                    <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-purple-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-info-circle text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-2">Comment fonctionnent nos recommandations ?</h3>
                        <p class="text-gray-400">
                            Nous analysons les films et séries de votre watchlist pour comprendre vos préférences en termes de genres, 
                            d'acteurs et de styles. Ensuite, nous utilisons l'API TMDb pour vous suggérer des contenus similaires.
                        </p>
                        <?php if ($total_watchlist > 0): ?>
                            <p class="text-gray-400 mt-2">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Nous avons analysé <?= count($watchlist_movies) ?> film(s) et <?= count($watchlist_series) ?> série(s) de votre watchlist
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Content Grid -->
            <?php if (empty($recommended_items)): ?>
                <div class="text-center py-16 glass rounded-2xl">
                    <i class="fas fa-film text-6xl text-gray-600 mb-4"></i>
                    <h3 class="text-2xl font-bold text-gray-400 mb-2">Aucune recommandation trouvée</h3>
                    <p class="text-gray-500 mb-6">
                        Ajoutez des films ou séries à votre watchlist pour obtenir des recommandations personnalisées !
                    </p>
                    <a href="/index.php" class="btn-primary inline-block px-6 py-3 rounded-lg font-semibold">
                        <i class="fas fa-search mr-2"></i>Découvrir des contenus
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                    <?php foreach ($recommended_items as $item): ?>
                        <div class="movie-card glass rounded-xl overflow-hidden group cursor-pointer"
                             onclick="viewContentDetails(<?= $item['id'] ?>, '<?= $item['type'] ?>')">
                            <div class="aspect-[2/3] relative">
                                <img src="<?= $item['poster'] ?>" 
                                     alt="<?= htmlspecialchars($item['title']) ?>"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                
                                <!-- Type badge -->
                                <div class="absolute top-3 left-3 <?= $item['type'] === 'movie' ? 'badge-movie' : 'badge-serie' ?> text-white px-2 py-1 rounded text-xs font-bold">
                                    <?= $item['type'] === 'movie' ? '🎬 Film' : '📺 Série' ?>
                                </div>
                                
                                <!-- Rating badge -->
                                <div class="absolute top-3 right-3 bg-black/80 text-yellow-400 px-2 py-1 rounded-lg text-sm font-bold">
                                    <i class="fas fa-star mr-1"></i>
                                    <?= number_format($item['vote_average'], 1) ?>
                                </div>
                                
                                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 p-4 flex flex-col justify-end">
                                    <div class="mb-3">
                                        <div class="flex items-center mb-2">
                                            <i class="fas fa-star text-yellow-400 mr-1"></i>
                                            <span class="font-bold"><?= number_format($item['vote_average'], 1) ?></span>
                                            <span class="text-gray-400 text-sm ml-2">(<?= $item['vote_count'] ?> votes)</span>
                                        </div>
                                        <?php if (isset($item['release_date']) && $item['release_date']): ?>
                                            <p class="text-gray-300 text-sm">
                                                <i class="far fa-calendar mr-1"></i>
                                                <?= date('Y', strtotime($item['release_date'])) ?>
                                            </p>
                                        <?php elseif (isset($item['first_air_date']) && $item['first_air_date']): ?>
                                            <p class="text-gray-300 text-sm">
                                                <i class="far fa-calendar mr-1"></i>
                                                <?= date('Y', strtotime($item['first_air_date'])) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($item['type'] === 'movie'): ?>
                                        <button onclick="event.stopPropagation(); addMovieToWatchlist(<?= $item['id'] ?>, '<?= addslashes($item['title']) ?>', '<?= $item['poster'] ?>')" 
                                                class="w-full bg-orange-500 hover:bg-orange-600 text-white py-2 rounded-lg text-sm font-semibold transition">
                                            <i class="fas fa-plus mr-1"></i> Ajouter à ma watchlist
                                        </button>
                                    <?php else: ?>
                                        <button onclick="event.stopPropagation(); addSerieToWatchlist(<?= $item['id'] ?>, '<?= addslashes($item['title']) ?>', '<?= $item['poster'] ?>')" 
                                                class="w-full bg-purple-500 hover:bg-purple-600 text-white py-2 rounded-lg text-sm font-semibold transition">
                                            <i class="fas fa-plus mr-1"></i> Ajouter à ma watchlist
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="p-3">
                                <h3 class="font-semibold truncate" title="<?= htmlspecialchars($item['title']) ?>">
                                    <?= htmlspecialchars($item['title']) ?>
                                </h3>
                                <p class="text-gray-400 text-sm mt-1 truncate" title="<?= htmlspecialchars($item['overview']) ?>">
                                    <?= strlen($item['overview']) > 50 ? substr($item['overview'], 0, 50) . '...' : $item['overview'] ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <div class="mt-8 flex justify-center">
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                            <a href="recommendations.php?type=<?= $type ?>&content=<?= $content_type ?>&page=<?= $page - 1 ?>" 
                               class="px-4 py-2 glass rounded-lg hover:bg-gray-700/50 transition">
                                <i class="fas fa-chevron-left mr-2"></i>Précédent
                            </a>
                        <?php endif; ?>
                        
                        <a href="recommendations.php?type=<?= $type ?>&content=<?= $content_type ?>&page=<?= $page + 1 ?>" 
                           class="px-4 py-2 btn-primary rounded-lg font-semibold">
                            Suivant <i class="fas fa-chevron-right ml-2"></i>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include '../../includes/footer.php'; ?>
    
    <script>
    function viewContentDetails(contentId, type) {
        if (type === 'movie') {
            window.location.href = '../movie-details.php?id=' + contentId;
        } else {
            window.location.href = '../serie-details.php?id=' + contentId;
        }
    }
    
    function addMovieToWatchlist(movieId, movieTitle, moviePoster) {
        const data = {
            movie_id: movieId,
            movie_title: movieTitle,
            movie_poster: moviePoster
        };
        
        fetch('../../add_to_watchlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Film ajouté à votre watchlist!', 'success');
            } else {
                showNotification('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erreur lors de l\'ajout', 'error');
        });
    }
    
    function addSerieToWatchlist(serieId, serieTitle, seriePoster) {
        const data = {
            serie_id: serieId,
            serie_title: serieTitle,
            serie_poster: seriePoster
        };
        
        fetch('../../add_to_watchlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
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
            console.error('Error:', error);
            showNotification('Erreur lors de l\'ajout', 'error');
        });
    }
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-600' : 
            type === 'error' ? 'bg-red-600' : 
            'bg-blue-600'
        }`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} mr-3"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    </script>
</body>
</html>