<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

// =====================================================================
// RÉCUPÉRATION DES STATISTIQUES
// =====================================================================

// Compter les films et séries séparément
$query_movies = "SELECT COUNT(*) as count FROM SELECTION WHERE id_utilisateur = ? AND id_film IS NOT NULL";
$stmt = $mysqli->prepare($query_movies);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$movie_count = $result->fetch_assoc()['count'] ?? 0;
$stmt->close();

$query_series = "SELECT COUNT(*) as count FROM SELECTION WHERE id_utilisateur = ? AND id_serie IS NOT NULL";
$stmt = $mysqli->prepare($query_series);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$serie_count = $result->fetch_assoc()['count'] ?? 0;
$stmt->close();

$total_watchlist = $movie_count + $serie_count;

// Stats par statut
$status_stats = [
    'watching' => 0,
    'completed' => 0,
    'plan_to_watch' => 0
];

$query = "SELECT type_status, COUNT(*) as count 
          FROM SELECTION 
          WHERE id_utilisateur = ? 
          GROUP BY type_status";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $status_stats[$row['type_status']] = $row['count'];
}
$stmt->close();

// Stats critiques
$query = "SELECT 
    (SELECT COUNT(*) FROM CRITIQUE_FILM WHERE id_utilisateur = ?) as film_reviews,
    (SELECT COUNT(*) FROM CRITIQUE_SERIE WHERE id_utilisateur = ?) as serie_reviews";
    
$stmt = $mysqli->prepare($query);
$stmt->bind_param("ii", $user['id'], $user['id']);
$stmt->execute();
$result = $stmt->get_result();
if ($reviews = $result->fetch_assoc()) {
    $film_reviews = $reviews['film_reviews'] ?? 0;
    $serie_reviews = $reviews['serie_reviews'] ?? 0;
    $total_reviews = $film_reviews + $serie_reviews;
}
$stmt->close();

// Stats listes
$query = "SELECT COUNT(*) as total FROM LISTE WHERE id_utilisateur = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$list_count = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// =====================================================================
// RÉCUPÉRATION DES RECOMMANDATIONS POUR LE DASHBOARD
// =====================================================================
// =====================================================================
// RÉCUPÉRATION DES RECOMMANDATIONS POUR LE DASHBOARD (CORRIGÉ)
// =====================================================================

function getDashboardRecommendations($user_id) {
    global $mysqli;
    
    // Récupérer 2 films de la watchlist (version corrigée)
    $query_movies = "SELECT f.id_film as id, 'movie' as type, f.titre, f.poster 
                     FROM SELECTION s 
                     JOIN FILM f ON s.id_film = f.id_film 
                     WHERE s.id_utilisateur = ? AND s.id_film IS NOT NULL 
                     ORDER BY s.date_ajout DESC 
                     LIMIT 2";
    
    $stmt = $mysqli->prepare($query_movies);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $watchlist_items = [];
    while ($row = $result->fetch_assoc()) {
        $watchlist_items[] = $row;
    }
    $stmt->close();
    
    // Récupérer 2 séries de la watchlist (version corrigée)
    $query_series = "SELECT se.id_serie as id, 'serie' as type, se.titre, se.poster 
                     FROM SELECTION s 
                     JOIN SERIE se ON s.id_serie = se.id_serie 
                     WHERE s.id_utilisateur = ? AND s.id_serie IS NOT NULL 
                     ORDER BY s.date_ajout DESC 
                     LIMIT 2";
    
    $stmt = $mysqli->prepare($query_series);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $watchlist_items[] = $row;
    }
    $stmt->close();
    
    if (empty($watchlist_items)) {
        return getPopularContentPreview();
    }
    
    // Pour chaque élément, chercher des recommandations
    $recommendations = [];
    foreach ($watchlist_items as $item) {
        if ($item['type'] === 'movie') {
            $similar = getSimilarMovies($item['id']);
            $recommendations = array_merge($recommendations, array_slice($similar, 0, 2));
        } else {
            $similar = getSimilarSeries($item['id']);
            $recommendations = array_merge($recommendations, array_slice($similar, 0, 2));
        }
    }
    
    // Si pas assez de recommandations, compléter avec du contenu populaire
    if (count($recommendations) < 6) {
        $popular = getPopularContentPreview();
        foreach ($popular as $item) {
            if (count($recommendations) >= 6) break;
            $recommendations[] = $item;
        }
    }
    
    // Mélanger et limiter à 6
    shuffle($recommendations);
    return array_slice($recommendations, 0, 6);
}

function getSimilarMovies($movie_id) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "movie/$movie_id/similar?api_key=$api_key&language=fr-FR&page=1";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
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
                        'vote_average' => $movie['vote_average'] ?? 0,
                        'type' => 'movie'
                    ];
                }
            }
        }
    }
    
    return $movies;
}

function getSimilarSeries($serie_id) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "tv/$serie_id/similar?api_key=$api_key&language=fr-FR&page=1";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
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
                        'vote_average' => $serie['vote_average'] ?? 0,
                        'type' => 'serie'
                    ];
                }
            }
        }
    }
    
    return $series;
}

function getPopularContentPreview() {
    // Récupérer 3 films populaires
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "movie/popular?api_key=$api_key&language=fr-FR&page=1";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $content = [];
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['results'])) {
            $count = 0;
            foreach ($data['results'] as $movie) {
                if ($count >= 3) break;
                if (!empty($movie['poster_path'])) {
                    $content[] = [
                        'id' => $movie['id'],
                        'title' => $movie['title'] ?? 'Titre inconnu',
                        'poster' => TMDB_IMAGE_BASE_URL . 'w500' . $movie['poster_path'],
                        'vote_average' => $movie['vote_average'] ?? 0,
                        'type' => 'movie'
                    ];
                    $count++;
                }
            }
        }
    }
    
    // Récupérer 3 séries populaires
    $url = TMDB_BASE_URL . "tv/popular?api_key=$api_key&language=fr-FR&page=1";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['results'])) {
            $count = 0;
            foreach ($data['results'] as $serie) {
                if ($count >= 3) break;
                if (!empty($serie['poster_path'])) {
                    $content[] = [
                        'id' => $serie['id'],
                        'title' => $serie['name'] ?? 'Titre inconnu',
                        'poster' => TMDB_IMAGE_BASE_URL . 'w500' . $serie['poster_path'],
                        'vote_average' => $serie['vote_average'] ?? 0,
                        'type' => 'serie'
                    ];
                    $count++;
                }
            }
        }
    }
    
    return $content;
}

$recommendations_preview = getDashboardRecommendations($user['id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Gardez vos styles CSS existants */
        .gradient-bg {
            background: linear-gradient(135deg, #0a0e14 0%, #05080d 100%);
        }
        .glass {
            background: rgba(30, 30, 40, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .stat-card {
            background: linear-gradient(135deg, rgba(30, 30, 45, 0.6) 0%, rgba(18, 18, 28, 0.8) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 140, 0, 0.3);
            box-shadow: 0 15px 40px rgba(255, 140, 0, 0.15);
        }
        .btn-primary {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 140, 0, 0.3);
        }
        .content-card {
            transition: all 0.3s ease;
        }
        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 140, 0, 0.2);
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
        <div class="max-w-7xl mx-auto px-6">
            <!-- Welcome Section -->
            <div class="mb-12">
                <h1 class="text-5xl font-black mb-4">
                    Bonjour, <span class="text-orange-500"><?php echo htmlspecialchars($user['pseudo']); ?></span>
                </h1>
                <p class="text-xl text-gray-400">
                    Bienvenue sur votre espace personnel CineTrack
                    <?php if ($total_watchlist > 0): ?>
                        <span class="text-green-500 font-semibold ml-2">
                            (Votre profil a été analysé avec succès!)
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div class="stat-card p-6 rounded-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-4xl">
                            <i class="fas fa-film text-orange-500"></i>
                        </div>
                        <span class="text-3xl font-black text-orange-500"><?php echo $total_watchlist; ?></span>
                    </div>
                    <h3 class="text-gray-400 text-sm uppercase tracking-wider">Total Watchlist</h3>
                    <?php if ($movie_count > 0 || $serie_count > 0): ?>
                        <p class="text-gray-500 text-xs mt-1">
                            <?php echo $movie_count; ?> film(s) • <?php echo $serie_count; ?> série(s)
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="stat-card p-6 rounded-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-4xl">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <span class="text-3xl font-black text-green-500"><?php echo $status_stats['completed']; ?></span>
                    </div>
                    <h3 class="text-gray-400 text-sm uppercase tracking-wider">Complétés</h3>
                    <?php if ($status_stats['watching'] > 0 || $status_stats['plan_to_watch'] > 0): ?>
                        <p class="text-gray-500 text-xs mt-1">
                            <?php echo $status_stats['watching']; ?> en cours • <?php echo $status_stats['plan_to_watch']; ?> à voir
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="stat-card p-6 rounded-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-4xl">
                            <i class="fas fa-star text-yellow-500"></i>
                        </div>
                        <span class="text-3xl font-black text-yellow-500"><?php echo $total_reviews; ?></span>
                    </div>
                    <h3 class="text-gray-400 text-sm uppercase tracking-wider">Critiques</h3>
                    <?php if ($film_reviews > 0 || $serie_reviews > 0): ?>
                        <p class="text-gray-500 text-xs mt-1">
                            <?php echo $film_reviews; ?> film(s) • <?php echo $serie_reviews; ?> série(s)
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="stat-card p-6 rounded-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-4xl">
                            <i class="fas fa-list text-purple-500"></i>
                        </div>
                        <span class="text-3xl font-black text-purple-500"><?php echo $list_count; ?></span>
                    </div>
                    <h3 class="text-gray-400 text-sm uppercase tracking-wider">Listes</h3>
                    <p class="text-gray-500 text-xs mt-1">
                        Organisez vos contenus
                    </p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <a href="watchlist.php" class="stat-card p-6 rounded-2xl text-center block hover:scale-105 transition">
                    <div class="flex justify-center mb-4">
                        <div class="relative">
                            <i class="fas fa-film text-5xl text-orange-500 absolute -top-2 -left-2"></i>
                            <i class="fas fa-tv text-4xl text-purple-500"></i>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold">Ma Watchlist</h3>
                    <p class="text-gray-400 text-sm mt-2">
                        <?php echo $movie_count; ?> film(s) • <?php echo $serie_count; ?> série(s)
                    </p>
                </a>
                
                <a href="reviews.php" class="stat-card p-6 rounded-2xl text-center block hover:scale-105 transition">
                    <i class="fas fa-pen text-5xl text-yellow-500 mb-4"></i>
                    <h3 class="text-xl font-bold">Mes Critiques</h3>
                    <p class="text-gray-400 text-sm mt-2">
                        <?php echo $total_reviews; ?> avis partagés
                    </p>
                </a>
                
                <a href="recommendations.php" class="stat-card p-6 rounded-2xl text-center block hover:scale-105 transition">
                    <i class="fas fa-magic text-5xl text-blue-500 mb-4"></i>
                    <h3 class="text-xl font-bold">Recommandations</h3>
                    <p class="text-gray-400 text-sm mt-2">
                        Découvrez des films et séries
                    </p>
                </a>
            </div>
            
            <!-- ===================================================================== -->
            <!-- NOUVELLE SECTION RECOMMANDATIONS AVEC SÉRIES -->
            <!-- ===================================================================== -->
            
            <div class="mt-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-3xl font-bold">
                        <i class="fas fa-magic text-orange-500 mr-2"></i>
                        Recommandations personnalisées
                    </h2>
                   
                </div>
                
                <!-- Info section améliorée -->
                <div class="glass rounded-2xl p-6 mb-8">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-purple-500 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-chart-line text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold mb-2">Analyses basées sur vos films ET séries</h3>
                            <p class="text-gray-400">
                                Nous analysons l'ensemble de votre watchlist (films et séries) pour comprendre 
                                vos préférences et vous proposer des recommandations adaptées.
                            </p>
                            
                            <?php if ($movie_count > 0 || $serie_count > 0): ?>
                                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-gray-800/30 rounded-lg p-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-orange-500/20 rounded-lg flex items-center justify-center mr-3">
                                                <i class="fas fa-film text-orange-500"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-300">Films analysés</p>
                                                <p class="text-lg font-bold text-orange-500"><?php echo $movie_count; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gray-800/30 rounded-lg p-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center mr-3">
                                                <i class="fas fa-tv text-purple-500"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-300">Séries analysées</p>
                                                <p class="text-lg font-bold text-purple-500"><?php echo $serie_count; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Content Grid Preview avec films ET séries -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <?php if (!empty($recommendations_preview)): ?>
                        <?php foreach ($recommendations_preview as $item): ?>
                            <div class="content-card glass rounded-xl overflow-hidden group cursor-pointer"
                                 onclick="viewContentDetails(<?= $item['id'] ?>, '<?= $item['type'] ?>')">
                                <div class="aspect-[2/3] relative">
                                    <img src="<?= $item['poster'] ?>" 
                                         alt="<?= htmlspecialchars($item['title']) ?>"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                    
                                    <!-- Type badge -->
                                    <div class="absolute top-2 left-2 <?= $item['type'] === 'movie' ? 'badge-movie' : 'badge-serie' ?> text-white px-2 py-1 rounded text-xs font-bold">
                                        <?= $item['type'] === 'movie' ? '🎬 Film' : '📺 Série' ?>
                                    </div>
                                    
                                    <!-- Hover overlay -->
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 p-3 flex flex-col justify-end">
                                        <div class="mb-2">
                                            <div class="flex items-center mb-1">
                                                <i class="fas fa-star text-yellow-400 text-xs mr-1"></i>
                                                <span class="font-bold text-sm"><?= number_format($item['vote_average'], 1) ?></span>
                                            </div>
                                        </div>
                                        
                                        <?php if ($item['type'] === 'movie'): ?>
                                            <button onclick="event.stopPropagation(); addMovieToWatchlist(<?= $item['id'] ?>, '<?= addslashes($item['title']) ?>', '<?= $item['poster'] ?>')" 
                                                    class="w-full bg-orange-500 hover:bg-orange-600 text-white py-1 rounded text-xs font-semibold transition">
                                                <i class="fas fa-plus mr-1"></i> Watchlist
                                            </button>
                                        <?php else: ?>
                                            <button onclick="event.stopPropagation(); addSerieToWatchlist(<?= $item['id'] ?>, '<?= addslashes($item['title']) ?>', '<?= $item['poster'] ?>')" 
                                                    class="w-full bg-purple-500 hover:bg-purple-600 text-white py-1 rounded text-xs font-semibold transition">
                                                <i class="fas fa-plus mr-1"></i> Watchlist
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Rating badge -->
                                    <div class="absolute top-2 right-2 bg-black/80 text-yellow-400 px-2 py-1 rounded text-xs font-bold">
                                        <i class="fas fa-star mr-1"></i>
                                        <?= number_format($item['vote_average'], 1) ?>
                                    </div>
                                </div>
                                
                                <div class="p-3">
                                    <h3 class="font-semibold text-sm truncate" title="<?= htmlspecialchars($item['title']) ?>">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </h3>
                                    <p class="text-gray-400 text-xs mt-1">
                                        <?= $item['type'] === 'movie' ? 'Film' : 'Série' ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-6 text-center py-8 text-gray-500">
                            <i class="fas fa-film text-4xl mb-2"></i>
                            <p>Ajoutez des films ou séries à votre watchlist pour voir des recommandations</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Call to Action -->
                <div class="mt-8 text-center">
                    <a href="recommendations.php" class="btn-primary inline-block px-8 py-3 rounded-lg font-semibold">
                        <i class="fas fa-magic mr-2"></i>Voir toutes les recommandations
                    </a>
                </div>
            </div>
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