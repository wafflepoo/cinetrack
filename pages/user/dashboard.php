<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Récupérer les statistiques de l'utilisateur
$total_watchlist = 0;
$watching_count = 0;
$completed_count = 0;
$plan_to_watch_count = 0;
$review_count = 0;
$list_count = 0;

// Stats watchlist
$query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN type_status = 'watching' THEN 1 ELSE 0 END) as watching,
    SUM(CASE WHEN type_status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN type_status = 'plan_to_watch' THEN 1 ELSE 0 END) as plan_to_watch
    FROM SELECTION WHERE id_utilisateur = ?";
    
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
if ($stats = $result->fetch_assoc()) {
    $total_watchlist = $stats['total'] ?? 0;
    $watching_count = $stats['watching'] ?? 0;
    $completed_count = $stats['completed'] ?? 0;
    $plan_to_watch_count = $stats['plan_to_watch'] ?? 0;
}
$stmt->close();

// Stats critiques
$query = "SELECT 
    (SELECT COUNT(*) FROM CRITIQUE_FILM WHERE id_utilisateur = ?) +
    (SELECT COUNT(*) FROM CRITIQUE_SERIE WHERE id_utilisateur = ?) as total_reviews";
    
$stmt = $mysqli->prepare($query);
$stmt->bind_param("ii", $user['id'], $user['id']);
$stmt->execute();
$result = $stmt->get_result();
if ($reviews = $result->fetch_assoc()) {
    $review_count = $reviews['total_reviews'] ?? 0;
}
$stmt->close();

// Stats listes
$query = "SELECT COUNT(*) as total FROM LISTE WHERE id_utilisateur = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
if ($lists = $result->fetch_assoc()) {
    $list_count = $lists['total'] ?? 0;
}
$stmt->close();

// Récupérer quelques recommandations pour le dashboard
function getDashboardRecommendationsPreview($user_id) {
    global $mysqli;
    
    // Récupérer quelques films de la watchlist
    $query = "SELECT f.id_film FROM SELECTION s 
              JOIN FILM f ON s.id_film = f.id_film 
              WHERE s.id_utilisateur = ? 
              LIMIT 3";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $movie_ids = [];
    while ($row = $result->fetch_assoc()) {
        $movie_ids[] = $row['id_film'];
    }
    $stmt->close();
    
    if (empty($movie_ids)) {
        return getPopularMoviesPreview();
    }
    
    // Prendre un film au hasard et chercher des similaires
    $random_movie_id = $movie_ids[array_rand($movie_ids)];
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "movie/$random_movie_id/similar?api_key=$api_key&language=fr-FR&page=1";
    
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
            $count = 0;
            foreach ($data['results'] as $movie) {
                if ($count >= 6) break;
                if (!empty($movie['poster_path'])) {
                    $movies[] = [
                        'id' => $movie['id'],
                        'title' => $movie['title'] ?? 'Titre inconnu',
                        'poster' => TMDB_IMAGE_BASE_URL . 'w500' . $movie['poster_path'],
                        'vote_average' => $movie['vote_average'] ?? 0
                    ];
                    $count++;
                }
            }
        }
    }
    
    // Si pas assez de résultats, compléter avec des films populaires
    if (count($movies) < 6) {
        $popular = getPopularMoviesPreview();
        foreach ($popular as $movie) {
            if (count($movies) >= 6) break;
            $movies[] = $movie;
        }
    }
    
    return array_slice($movies, 0, 6);
}

function getPopularMoviesPreview() {
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
    
    $movies = [];
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['results'])) {
            $count = 0;
            foreach ($data['results'] as $movie) {
                if ($count >= 6) break;
                if (!empty($movie['poster_path'])) {
                    $movies[] = [
                        'id' => $movie['id'],
                        'title' => $movie['title'] ?? 'Titre inconnu',
                        'poster' => TMDB_IMAGE_BASE_URL . 'w500' . $movie['poster_path'],
                        'vote_average' => $movie['vote_average'] ?? 0
                    ];
                    $count++;
                }
            }
        }
    }
    
    return $movies;
}

$recommendations_preview = getDashboardRecommendationsPreview($user['id']);
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
        .nav-link {
            @apply text-gray-400 hover:text-white px-3 py-2 rounded-lg transition;
        }
        .nav-link.active {
            @apply text-orange-500 bg-orange-500/10;
        }
        .dropdown-item {
            @apply flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-700/50 rounded-lg transition;
        }
        .movie-card {
            transition: all 0.3s ease;
        }
        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 140, 0, 0.2);
        }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen">
    <!-- User Header -->
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
                        <a href="dashboard.php" class="nav-link active">
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
                        <a href="recommendations.php" class="nav-link">
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
                    <a href="dashboard.php" class="flex flex-col items-center text-orange-500 p-2 rounded-lg bg-orange-500/10">
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
                    <a href="recommendations.php" class="flex flex-col items-center text-gray-400 p-2 rounded-lg transition">
                        <i class="fas fa-magic"></i>
                        <span class="text-xs mt-1">Recommandations</span>
                    </a>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="pt-32 pb-16">
        <div class="max-w-7xl mx-auto px-6">
            <!-- Welcome Section -->
            <div class="mb-12">
                <h1 class="text-5xl font-black mb-4">
                    Bonjour, <span class="text-orange-500"><?php echo htmlspecialchars($user['pseudo']); ?></span>
                </h1>
                <p class="text-xl text-gray-400">Bienvenue sur votre dashboard personnel</p>
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
                </div>
                
                <div class="stat-card p-6 rounded-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-4xl">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <span class="text-3xl font-black text-green-500"><?php echo $completed_count; ?></span>
                    </div>
                    <h3 class="text-gray-400 text-sm uppercase tracking-wider">Complétés</h3>
                </div>
                
                <div class="stat-card p-6 rounded-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-4xl">
                            <i class="fas fa-star text-yellow-500"></i>
                        </div>
                        <span class="text-3xl font-black text-yellow-500"><?php echo $review_count; ?></span>
                    </div>
                    <h3 class="text-gray-400 text-sm uppercase tracking-wider">Critiques</h3>
                </div>
                
                <div class="stat-card p-6 rounded-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-4xl">
                            <i class="fas fa-list text-purple-500"></i>
                        </div>
                        <span class="text-3xl font-black text-purple-500"><?php echo $list_count; ?></span>
                    </div>
                    <h3 class="text-gray-400 text-sm uppercase tracking-wider">Listes</h3>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <a href="watchlist.php" class="stat-card p-6 rounded-2xl text-center block hover:scale-105 transition">
                    <i class="fas fa-bookmark text-5xl text-orange-500 mb-4"></i>
                    <h3 class="text-xl font-bold">Ma Watchlist</h3>
                    <p class="text-gray-400 text-sm mt-2">Gérer mes films et séries</p>
                </a>
                
                <a href="reviews.php" class="stat-card p-6 rounded-2xl text-center block hover:scale-105 transition">
                    <i class="fas fa-pen text-5xl text-yellow-500 mb-4"></i>
                    <h3 class="text-xl font-bold">Mes Critiques</h3>
                    <p class="text-gray-400 text-sm mt-2">Voir mes avis</p>
                </a>
                
                <a href="lists.php" class="stat-card p-6 rounded-2xl text-center block hover:scale-105 transition">
                    <i class="fas fa-folder text-5xl text-purple-500 mb-4"></i>
                    <h3 class="text-xl font-bold">Mes Listes</h3>
                    <p class="text-gray-400 text-sm mt-2">Créer et organiser</p>
                </a>
            </div>
            
            <!-- ===================================================================== -->
            <!-- SECTION RECOMMANDATIONS COMPLÈTE (incluse directement) -->
            <!-- ===================================================================== -->
            
            <!-- Inclure le fichier recommendations.php -->
            <?php
            // Récupérer le contenu du fichier recommendations.php
            // Mais attention, nous devons éviter de redéclarer les sessions et includes
            // Créons une version adaptée pour l'inclusion
            ob_start();
            ?>
            
            <div class="mt-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-3xl font-bold">
                        <i class="fas fa-magic text-orange-500 mr-2"></i>
                        Recommandations personnalisées
                    </h2>
                    <a href="recommendations.php" class="btn-primary px-6 py-3 rounded-lg font-semibold flex items-center">
                        <i class="fas fa-expand mr-2"></i>Voir toutes
                    </a>
                </div>
                
                <!-- Info section -->
                <div class="glass rounded-2xl p-6 mb-8">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-orange-500/20 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-info-circle text-orange-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold mb-2">Basé sur votre watchlist</h3>
                            <p class="text-gray-400">
                                Nous avons analysé vos films préférés pour vous suggérer des contenus similaires.
                               
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Movies Grid Preview -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <?php foreach ($recommendations_preview as $movie): ?>
                        <div class="movie-card glass rounded-xl overflow-hidden group cursor-pointer"
                             onclick="viewMovieDetails(<?= $movie['id'] ?>)">
                            <div class="aspect-[2/3] relative">
                                <img src="<?= $movie['poster'] ?>" 
                                     alt="<?= htmlspecialchars($movie['title']) ?>"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                
                                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 p-3 flex flex-col justify-end">
                                    <div class="mb-2">
                                        <div class="flex items-center mb-1">
                                            <i class="fas fa-star text-yellow-400 text-xs mr-1"></i>
                                            <span class="font-bold text-sm"><?= number_format($movie['vote_average'], 1) ?></span>
                                        </div>
                                    </div>
                                    
                                    <button onclick="event.stopPropagation(); addToWatchlist(<?= $movie['id'] ?>, '<?= addslashes($movie['title']) ?>', '<?= $movie['poster'] ?>')" 
                                            class="w-full bg-orange-500 hover:bg-orange-600 text-white py-1 rounded text-xs font-semibold transition">
                                        <i class="fas fa-plus mr-1"></i> Watchlist
                                    </button>
                                </div>
                                
                                <!-- Rating badge -->
                                <div class="absolute top-2 left-2 bg-black/80 text-yellow-400 px-2 py-1 rounded text-xs font-bold">
                                    <i class="fas fa-star mr-1"></i>
                                    <?= number_format($movie['vote_average'], 1) ?>
                                </div>
                            </div>
                            
                            <div class="p-3">
                                <h3 class="font-semibold text-sm truncate" title="<?= htmlspecialchars($movie['title']) ?>">
                                    <?= htmlspecialchars($movie['title']) ?>
                                </h3>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Call to Action -->
                <div class="mt-8 text-center">
                    <a href="recommendations.php" class="btn-primary inline-block px-8 py-3 rounded-lg font-semibold">
                        <i class="fas fa-magic mr-2"></i>Voir plus de recommandations
                    </a>
                </div>
            </div>
            
            <?php
            $recommendations_content = ob_get_clean();
            echo $recommendations_content;
            ?>
            
            <!-- ===================================================================== -->
            <!-- FIN SECTION RECOMMANDATIONS -->
            <!-- ===================================================================== -->
            
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