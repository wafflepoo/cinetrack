<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Get user statistics using MySQLi
$total_watchlist = 0;
$watching_count = 0;
$completed_count = 0;
$plan_to_watch_count = 0;
$review_count = 0;
$list_count = 0;

// Get watchlist stats
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

// Get review count
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

// Get list count
$query = "SELECT COUNT(*) as total FROM LISTE WHERE id_utilisateur = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
if ($lists = $result->fetch_assoc()) {
    $list_count = $lists['total'] ?? 0;
}
$stmt->close();
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
                                <a href="../auth/logout.php" class="dropdown-item text-red-400 hover:text-red-300">
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
            <!-- ... Après la section Quick Actions dans dashboard.php ... -->

<!-- Section Recommandations -->
<div class="mt-12">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-3xl font-bold">🎬 Recommandations personnalisées</h2>
        <a href="recommendations.php" class="btn-primary px-6 py-3 rounded-lg font-semibold flex items-center">
            <i class="fas fa-magic mr-2"></i>Voir toutes les recommandations
        </a>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Carte basée sur les genres -->
        <div class="glass p-6 rounded-2xl border-l-4 border-orange-500">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-orange-500/20 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-tags text-orange-500 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold">Basé sur vos genres</h3>
                    <p class="text-gray-400">Films similaires à votre watchlist</p>
                </div>
            </div>
            <a href="recommendations.php?type=genres" class="text-orange-500 hover:text-orange-400 font-semibold inline-flex items-center">
                Découvrir <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        
        <!-- Carte basée sur les acteurs -->
        <div class="glass p-6 rounded-2xl border-l-4 border-purple-500">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-users text-purple-500 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold">Avec vos acteurs favoris</h3>
                    <p class="text-gray-400">Films avec les acteurs que vous aimez</p>
                </div>
            </div>
            <a href="recommendations.php?type=actors" class="text-purple-500 hover:text-purple-400 font-semibold inline-flex items-center">
                Explorer <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
    
    <!-- Preview rapide des recommandations -->
    <div class="glass rounded-2xl p-6">
        <h3 class="text-xl font-bold mb-4">🎭 Nos suggestions du moment</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php
            // Afficher quelques films populaires comme preview
            $preview_movies = getPopularPreview();
            foreach ($preview_movies as $movie): 
            ?>
            <div class="cursor-pointer group" onclick="viewMovieDetails(<?= $movie['id'] ?>)">
                <div class="aspect-[2/3] rounded-lg overflow-hidden mb-2">
                    <img src="<?= $movie['poster'] ?>" 
                         alt="<?= htmlspecialchars($movie['title']) ?>"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                </div>
                <p class="text-sm font-medium truncate" title="<?= htmlspecialchars($movie['title']) ?>">
                    <?= htmlspecialchars($movie['title']) ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
// Fonction pour obtenir quelques films populaires (prévisualisation)
function getPopularPreview() {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . "movie/popular?api_key=$api_key&language=fr-FR&page=1";
    
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
            // Prendre 6 films maximum
            $count = 0;
            foreach ($data['results'] as $movie) {
                if ($count >= 6) break;
                
                if (!empty($movie['poster_path'])) {
                    $movies[] = [
                        'id' => $movie['id'],
                        'title' => $movie['title'] ?? 'Titre inconnu',
                        'poster' => TMDB_IMAGE_BASE_URL . 'w500' . $movie['poster_path'],
                        'release_date' => $movie['release_date'] ?? ''
                    ];
                    $count++;
                }
            }
        }
    }
    
    return $movies;
}
?>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include '../../includes/footer.php'; ?>

</body>
</html>