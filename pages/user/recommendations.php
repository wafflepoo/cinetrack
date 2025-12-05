<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Déterminer le type de recommandation
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Récupérer les films de la watchlist de l'utilisateur
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

// Fonction pour obtenir les genres d'un film depuis TMDb
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

// Fonction pour obtenir les acteurs d'un film
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
            // Prendre les 5 acteurs principaux
            $main_cast = array_slice($data['cast'], 0, 5);
            foreach ($main_cast as $actor) {
                $actors[] = $actor['id'];
            }
        }
    }
    
    return $actors;
}

// Fonction pour récupérer des films depuis TMDb
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
                        'vote_count' => $movie['vote_count'] ?? 0
                    ];
                }
            }
        }
    }
    
    return $movies;
}

// Obtenir les genres communs des films
function getCommonGenres($movie_ids) {
    $all_genres = [];
    
    foreach ($movie_ids as $movie_id) {
        $genres = getMovieGenres($movie_id);
        $all_genres = array_merge($all_genres, $genres);
    }
    
    // Compter les occurrences
    $genre_counts = array_count_values($all_genres);
    arsort($genre_counts);
    
    return $genre_counts;
}

// Obtenir les acteurs communs
function getCommonActors($movie_ids) {
    $all_actors = [];
    
    foreach (array_slice($movie_ids, 0, 5) as $movie_id) {
        $actors = getMovieActors($movie_id);
        $all_actors = array_merge($all_actors, $actors);
    }
    
    // Compter les occurrences
    $actor_counts = array_count_values($all_actors);
    arsort($actor_counts);
    
    return $actor_counts;
}

// Obtenir des recommandations par genres
function getRecommendationsByGenres($movie_ids, $page = 1) {
    // Analyser les genres des films
    $genres = getCommonGenres($movie_ids);
    
    if (empty($genres)) {
        return [];
    }
    
    // Prendre les 2 genres les plus communs
    $genre_keys = array_keys($genres);
    $top_genres = array_slice($genre_keys, 0, 2);
    $genre_ids = implode(',', $top_genres);
    
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "discover/movie?api_key=$api_key&language=fr-FR&sort_by=popularity.desc&with_genres=$genre_ids&page=$page";
    
    return fetchMoviesFromTMDB($url);
}

// Obtenir des recommandations par acteurs
function getRecommendationsByActors($movie_ids, $page = 1) {
    // Obtenir les acteurs communs
    $actors = getCommonActors($movie_ids);
    
    if (empty($actors)) {
        return [];
    }
    
    // Prendre les 2 acteurs les plus communs
    $actor_keys = array_keys($actors);
    $top_actors = array_slice($actor_keys, 0, 2);
    $actor_ids = implode(',', $top_actors);
    
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "discover/movie?api_key=$api_key&language=fr-FR&sort_by=popularity.desc&with_cast=$actor_ids&page=$page";
    
    return fetchMoviesFromTMDB($url);
}

// Obtenir des films similaires
function getSimilarMovies($movie_ids, $page = 1) {
    $all_similar = [];
    
    // Pour chaque film dans la watchlist, chercher des films similaires
    foreach (array_slice($movie_ids, 0, 3) as $movie_id) {
        $api_key = TMDB_API_KEY;
        $url = TMDB_BASE_URL . "movie/$movie_id/similar?api_key=$api_key&language=fr-FR&page=$page";
        
        $similar = fetchMoviesFromTMDB($url);
        $all_similar = array_merge($all_similar, $similar);
    }
    
    return $all_similar;
}

// Obtenir des films populaires
function getPopularMovies($page = 1) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "movie/popular?api_key=$api_key&language=fr-FR&page=$page";
    
    return fetchMoviesFromTMDB($url);
}

// Fonction principale pour obtenir des recommandations
function getRecommendations($user_id, $type = 'all', $page = 1) {
    global $mysqli;
    
    $recommendations = [];
    
    // 1. Obtenir les films de la watchlist
    $watchlist_movies = getUserWatchlistMovies($mysqli, $user_id);
    
    if (empty($watchlist_movies)) {
        // Si pas de watchlist, retourner des films populaires
        return getPopularMovies($page);
    }
    
    // 2. Extraire les IDs des films
    $movie_ids = array_column($watchlist_movies, 'id_film');
    
    // 3. Obtenir des recommandations selon le type
    switch ($type) {
        case 'genres':
            $recommendations = getRecommendationsByGenres($movie_ids, $page);
            break;
            
        case 'actors':
            $recommendations = getRecommendationsByActors($movie_ids, $page);
            break;
            
        case 'similar':
            $recommendations = getSimilarMovies($movie_ids, $page);
            break;
            
        case 'all':
        default:
            $genre_recs = getRecommendationsByGenres($movie_ids, $page);
            $actor_recs = getRecommendationsByActors($movie_ids, $page);
            $similar_recs = getSimilarMovies($movie_ids, $page);
            $recommendations = array_merge($genre_recs, $actor_recs, $similar_recs);
            break;
    }
    
    // 4. Filtrer les doublons et les films déjà en watchlist
    $unique_recommendations = [];
    $seen_ids = [];
    
    foreach ($recommendations as $movie) {
        if (!in_array($movie['id'], $seen_ids) && !in_array($movie['id'], $movie_ids)) {
            $seen_ids[] = $movie['id'];
            $unique_recommendations[] = $movie;
        }
    }
    
    // 5. Si pas assez de recommandations, ajouter des films populaires
    if (count($unique_recommendations) < 10) {
        $popular = getPopularMovies($page);
        foreach ($popular as $movie) {
            if (!in_array($movie['id'], $seen_ids) && !in_array($movie['id'], $movie_ids)) {
                $unique_recommendations[] = $movie;
            }
        }
    }
    
    // 6. Limiter à 20 résultats
    return array_slice($unique_recommendations, 0, 20);
}

// Récupérer les recommandations
$recommended_movies = getRecommendations($user['id'], $type, $page);
$watchlist_count = count(getUserWatchlistMovies($mysqli, $user['id']));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommandations - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .movie-card {
            transition: all 0.3s ease;
        }
        .movie-card:hover {
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
        .nav-link {
            @apply text-gray-400 hover:text-white px-3 py-2 rounded-lg transition;
        }
        .nav-link.active {
            @apply text-orange-500 bg-orange-500/10;
        }
        .dropdown-item {
            @apply flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700/50 rounded-lg transition;
        }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen">
    <!-- User Header (copié de dashboard.php pour consistance) -->
    <header class="fixed top-0 w-full bg-gray-900/95 backdrop-blur-lg border-b border-gray-800 z-50">
        <nav class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center space-x-8">
                    <a href="dashboard.php" class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-film text-white text-lg"></i>
                        </div>
                        <span class="text-xl font-black">CineTrack</span>
                    </a>
                    
                    <!-- Navigation -->
                    <div class="hidden md:flex items-center space-x-6">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                        <a href="watchlist.php" class="nav-link">
                            <i class="fas fa-bookmark mr-2"></i>Watchlist
                        </a>
                        <a href="reviews.php" class="nav-link">
                            <i class="fas fa-star mr-2"></i>Critiques
                        </a>
                        <a href="lists.php" class="nav-link">
                            <i class="fas fa-list mr-2"></i>Listes
                        </a>
                        <a href="recommendations.php" class="nav-link active">
                            <i class="fas fa-magic mr-2"></i>Recommandations
                        </a>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <a href="/index.php" class="bg-gray-700/50 hover:bg-gray-600/50 text-white px-4 py-2 rounded-lg font-semibold transition border border-gray-600">
                        <i class="fas fa-search mr-2"></i>Découvrir
                    </a>
                    
                    <div class="relative group">
                        <button class="flex items-center space-x-3 bg-gray-800/50 hover:bg-gray-700/50 px-4 py-2 rounded-lg transition">
                            <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold text-sm">
                                    <?php echo strtoupper(substr($user['pseudo'], 0, 1)); ?>
                                </span>
                            </div>
                            <span class="hidden md:block"><?php echo htmlspecialchars($user['pseudo']); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 top-full mt-2 w-48 bg-gray-800/95 backdrop-blur-lg rounded-lg border border-gray-700 shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <div class="p-2">
                                <a href="profile.php" class="dropdown-item">
                                    <i class="fas fa-user mr-3"></i>Mon Profil
                                </a>
                                <a href="settings.php" class="dropdown-item">
                                    <i class="fas fa-cog mr-3"></i>Paramètres
                                </a>
                                <div class="border-t border-gray-700 my-2"></div>
                                <a href="../../auth/logout.php" class="dropdown-item text-red-400 hover:text-red-300">
                                    <i class="fas fa-sign-out-alt mr-3"></i>Déconnexion
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Menu -->
            <div class="md:hidden mt-4">
                <div class="flex justify-around">
                    <a href="dashboard.php" class="flex flex-col items-center text-gray-400 p-2 rounded-lg transition">
                        <i class="fas fa-home"></i>
                        <span class="text-xs mt-1">Dashboard</span>
                    </a>
                    <a href="watchlist.php" class="flex flex-col items-center text-gray-400 p-2 rounded-lg transition">
                        <i class="fas fa-bookmark"></i>
                        <span class="text-xs mt-1">Watchlist</span>
                    </a>
                    <a href="reviews.php" class="flex flex-col items-center text-gray-400 p-2 rounded-lg transition">
                        <i class="fas fa-star"></i>
                        <span class="text-xs mt-1">Critiques</span>
                    </a>
                    <a href="lists.php" class="flex flex-col items-center text-gray-400 p-2 rounded-lg transition">
                        <i class="fas fa-list"></i>
                        <span class="text-xs mt-1">Listes</span>
                    </a>
                    <a href="recommendations.php" class="flex flex-col items-center text-orange-500 p-2 rounded-lg bg-orange-500/10">
                        <i class="fas fa-magic"></i>
                        <span class="text-xs mt-1">Recommandations</span>
                    </a>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="pt-32 pb-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-12">
                <h1 class="text-4xl md:text-5xl font-black mb-4">
                    <i class="fas fa-magic text-orange-500 mr-3"></i>
                    Recommandations personnalisées
                </h1>
                <p class="text-gray-400 text-lg">
                    Découvrez des films que vous allez adorer, sélectionnés spécialement pour vous
                </p>
                
                <!-- Type filters -->
                <div class="flex flex-wrap gap-3 mt-6">
                    <a href="recommendations.php?type=all" 
                       class="type-btn glass <?= $type === 'all' ? 'active' : '' ?>">
                       <i class="fas fa-star mr-2"></i>Toutes
                    </a>
                    <a href="recommendations.php?type=genres" 
                       class="type-btn glass <?= $type === 'genres' ? 'active' : '' ?>">
                       <i class="fas fa-tags mr-2"></i>Par genres
                    </a>
                    <a href="recommendations.php?type=actors" 
                       class="type-btn glass <?= $type === 'actors' ? 'active' : '' ?>">
                       <i class="fas fa-users mr-2"></i>Par acteurs
                    </a>
                    <a href="recommendations.php?type=similar" 
                       class="type-btn glass <?= $type === 'similar' ? 'active' : '' ?>">
                       <i class="fas fa-film mr-2"></i>Similaires
                    </a>
                </div>
            </div>
            
            <!-- Info section -->
            <div class="glass rounded-2xl p-6 mb-8">
                <div class="flex items-start">
                    <div class="w-12 h-12 bg-orange-500/20 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-info-circle text-orange-500 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-2">Comment fonctionnent nos recommandations ?</h3>
                        <p class="text-gray-400">
                            Nous analysons les films de votre watchlist pour comprendre vos préférences en termes de genres, 
                            d'acteurs et de styles. Ensuite, nous utilisons l'API TMDb pour vous suggérer des films similaires 
                            que vous pourriez aimer.
                        </p>
                        <?php if ($watchlist_count > 0): ?>
                            <p class="text-gray-400 mt-2">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                Nous avons analysé <?= $watchlist_count ?> film(s) de votre watchlist
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Movies Grid -->
            <?php if (empty($recommended_movies)): ?>
                <div class="text-center py-16 glass rounded-2xl">
                    <i class="fas fa-film text-6xl text-gray-600 mb-4"></i>
                    <h3 class="text-2xl font-bold text-gray-400 mb-2">Aucune recommandation trouvée</h3>
                    <p class="text-gray-500 mb-6">
                        Ajoutez des films à votre watchlist pour obtenir des recommandations personnalisées !
                    </p>
                    <a href="/index.php" class="btn-primary inline-block px-6 py-3 rounded-lg font-semibold">
                        <i class="fas fa-search mr-2"></i>Découvrir des films
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                    <?php foreach ($recommended_movies as $movie): ?>
                        <div class="movie-card glass rounded-xl overflow-hidden group cursor-pointer"
                             onclick="viewMovieDetails(<?= $movie['id'] ?>)">
                            <div class="aspect-[2/3] relative">
                                <img src="<?= $movie['poster'] ?>" 
                                     alt="<?= htmlspecialchars($movie['title']) ?>"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                
                                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 p-4 flex flex-col justify-end">
                                    <div class="mb-3">
                                        <div class="flex items-center mb-2">
                                            <i class="fas fa-star text-yellow-400 mr-1"></i>
                                            <span class="font-bold"><?= number_format($movie['vote_average'], 1) ?></span>
                                            <span class="text-gray-400 text-sm ml-2">(<?= $movie['vote_count'] ?> votes)</span>
                                        </div>
                                        <?php if ($movie['release_date']): ?>
                                            <p class="text-gray-300 text-sm">
                                                <i class="far fa-calendar mr-1"></i>
                                                <?= date('Y', strtotime($movie['release_date'])) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button onclick="event.stopPropagation(); addToWatchlist(<?= $movie['id'] ?>, '<?= addslashes($movie['title']) ?>', '<?= $movie['poster'] ?>')" 
                                            class="w-full bg-orange-500 hover:bg-orange-600 text-white py-2 rounded-lg text-sm font-semibold transition">
                                        <i class="fas fa-plus mr-1"></i> Ajouter à ma watchlist
                                    </button>
                                </div>
                                
                                <!-- Rating badge -->
                                <div class="absolute top-3 left-3 bg-black/80 text-yellow-400 px-2 py-1 rounded-lg text-sm font-bold">
                                    <i class="fas fa-star mr-1"></i>
                                    <?= number_format($movie['vote_average'], 1) ?>
                                </div>
                            </div>
                            
                            <div class="p-3">
                                <h3 class="font-semibold truncate" title="<?= htmlspecialchars($movie['title']) ?>">
                                    <?= htmlspecialchars($movie['title']) ?>
                                </h3>
                                <p class="text-gray-400 text-sm mt-1 truncate" title="<?= htmlspecialchars($movie['overview']) ?>">
                                    <?= strlen($movie['overview']) > 50 ? substr($movie['overview'], 0, 50) . '...' : $movie['overview'] ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <div class="mt-8 flex justify-center">
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                            <a href="recommendations.php?type=<?= $type ?>&page=<?= $page - 1 ?>" 
                               class="px-4 py-2 glass rounded-lg hover:bg-gray-700/50 transition">
                                <i class="fas fa-chevron-left mr-2"></i>Précédent
                            </a>
                        <?php endif; ?>
                        
                        <a href="recommendations.php?type=<?= $type ?>&page=<?= $page + 1 ?>" 
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
    function viewMovieDetails(movieId) {
        window.location.href = 'movie-details.php?id=' + movieId;
    }
    
    function addToWatchlist(movieId, movieTitle, moviePoster) {
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
    
    function showNotification(message, type = 'info') {
        // Créer une notification
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
        
        // Supprimer après 3 secondes
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    </script>
</body>
</html>