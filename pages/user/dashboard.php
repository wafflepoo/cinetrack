<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Stats
$query_movies = "SELECT COUNT(*) as count FROM SELECTION WHERE id_utilisateur = ? AND id_film IS NOT NULL";
$stmt = $mysqli->prepare($query_movies);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$movie_count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
$stmt->close();

$query_series = "SELECT COUNT(*) as count FROM SELECTION WHERE id_utilisateur = ? AND id_serie IS NOT NULL";
$stmt = $mysqli->prepare($query_series);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$serie_count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
$stmt->close();

// Recommandations
function getDashboardRecommendations($user_id) {
    global $mysqli;
    
    $query_movies = "SELECT f.id_film as id, 'movie' as type, f.titre, f.poster 
                     FROM SELECTION s 
                     JOIN FILM f ON s.id_film = f.id_film 
                     WHERE s.id_utilisateur = ? AND s.id_film IS NOT NULL 
                     ORDER BY s.date_ajout DESC LIMIT 2";
    
    $stmt = $mysqli->prepare($query_movies);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $watchlist_items = [];
    while ($row = $result->fetch_assoc()) {
        $watchlist_items[] = $row;
    }
    $stmt->close();
    
    $query_series = "SELECT se.id_serie as id, 'serie' as type, se.titre, se.poster 
                     FROM SELECTION s 
                     JOIN SERIE se ON s.id_serie = se.id_serie 
                     WHERE s.id_utilisateur = ? AND s.id_serie IS NOT NULL 
                     ORDER BY s.date_ajout DESC LIMIT 2";
    
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
    
    $recommendations = [];
    foreach ($watchlist_items as $item) {
        if ($item['type'] === 'movie') {
            $similar = getSimilarMovies($item['id']);
            $recommendations = array_merge($recommendations, array_slice($similar, 0, 3));
        } else {
            $similar = getSimilarSeries($item['id']);
            $recommendations = array_merge($recommendations, array_slice($similar, 0, 3));
        }
    }
    
    if (count($recommendations) < 12) {
        $popular = getPopularContentPreview();
        foreach ($popular as $item) {
            if (count($recommendations) >= 12) break;
            $recommendations[] = $item;
        }
    }
    
    shuffle($recommendations);
    return array_slice($recommendations, 0, 12);
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
            foreach (array_slice($data['results'], 0, 6) as $movie) {
                if (!empty($movie['poster_path'])) {
                    $content[] = [
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
            foreach (array_slice($data['results'], 0, 6) as $serie) {
                if (!empty($serie['poster_path'])) {
                    $content[] = [
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
    
    return $content;
}

$recommendations = getDashboardRecommendations($user['id']);

// Réservations
$today = date('Y-m-d');
$query_reservations = "SELECT * FROM cinema_reservations 
                       WHERE user_id = ? AND reservation_date >= ?
                       ORDER BY reservation_date ASC, reservation_time ASC 
                       LIMIT 6";
$stmt = $mysqli->prepare($query_reservations);
$stmt->bind_param("is", $user['id'], $today);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
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
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #0a0e14 0%, #05080d 100%);
        }
        .content-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .content-card:hover {
            transform: translateY(-10px) scale(1.02);
        }
        .badge-movie {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
        }
        .badge-serie {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
        .slider-container {
            position: relative;
            overflow: hidden;
        }
        .slider-track {
            display: flex;
            transition: transform 0.5s ease;
        }
        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 140, 0, 0.9);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s;
        }
        .slider-btn:hover {
            background: rgba(255, 140, 0, 1);
            transform: translateY(-50%) scale(1.1);
        }
        .slider-btn.prev { left: 10px; }
        .slider-btn.next { right: 10px; }
        .quick-link {
            background: rgba(30, 30, 40, 0.5);
            border: 2px solid rgba(255, 140, 0, 0.3);
            transition: all 0.3s;
        }
        .quick-link:hover {
            border-color: rgba(255, 140, 0, 0.8);
            background: rgba(255, 140, 0, 0.1);
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen">
    <?php include '../../includes/header.php'; ?>
    
    <main class="pt-32 pb-16">
        <div class="max-w-7xl mx-auto px-6">
            
            <!-- Welcome -->
            <div class="mb-12 text-center">
                <h1 class="text-6xl font-black mb-4" style="background: linear-gradient(135deg, #ff8c00 0%, #7800ff 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    Bonjour, <?php echo htmlspecialchars($user['pseudo']); ?> 👋
                </h1>
                <p class="text-2xl text-gray-400">Votre espace cinéma personnalisé</p>
            </div>
            
            <!-- Recommandations -->
            <div class="mb-16">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-4xl font-black" style="background: linear-gradient(135deg, #ff8c00 0%, #ffa500 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        <i class="fas fa-sparkles mr-3"></i>Recommandations pour vous
                    </h2>
                    <a href="recommendations.php" class="text-orange-500 hover:text-orange-400 font-bold flex items-center gap-2 text-lg">
                        Tout voir
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                    <?php foreach ($recommendations as $item): ?>
                        <div class="content-card rounded-xl overflow-hidden cursor-pointer" 
                             style="background: rgba(30, 30, 40, 0.5); backdrop-filter: blur(10px); border: 1px solid rgba(255, 140, 0, 0.2);"
                             onclick="viewContent(<?= $item['id'] ?>, '<?= $item['type'] ?>')">
                            <div class="aspect-[2/3] relative group">
                                <img src="<?= $item['poster'] ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                
                                <div class="absolute top-2 left-2 <?= $item['type'] === 'movie' ? 'badge-movie' : 'badge-serie' ?> px-2 py-1 rounded text-xs font-bold">
                                    <?= $item['type'] === 'movie' ? '🎬' : '📺' ?>
                                </div>
                                
                                <div class="absolute top-2 right-2 bg-black/80 px-2 py-1 rounded text-xs font-bold text-yellow-400">
                                    <i class="fas fa-star mr-1"></i><?= number_format($item['vote_average'], 1) ?>
                                </div>
                                
                                <div class="absolute inset-0 bg-gradient-to-t from-black/90 to-transparent opacity-0 group-hover:opacity-100 transition-opacity p-3 flex flex-col justify-end">
                                    <button onclick="event.stopPropagation(); add<?= $item['type'] === 'movie' ? 'Movie' : 'Serie' ?>(<?= $item['id'] ?>, '<?= addslashes($item['title']) ?>', '<?= $item['poster'] ?>')" 
                                            class="w-full py-2 rounded-lg font-bold text-sm transition" 
                                            style="background: <?= $item['type'] === 'movie' ? 'linear-gradient(135deg, #ff8c00, #ff6b00)' : 'linear-gradient(135deg, #4f46e5, #7c3aed)' ?>">
                                        <i class="fas fa-plus mr-1"></i>Watchlist
                                    </button>
                                </div>
                            </div>
                            
                            <div class="p-3">
                                <h3 class="font-bold text-sm truncate"><?= htmlspecialchars($item['title']) ?></h3>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Réservations -->
            <div class="mb-16">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-4xl font-black" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        <i class="fas fa-ticket-alt mr-3"></i>Vos prochaines séances
                    </h2>
                    <a href="reservations.php" class="text-green-500 hover:text-green-400 font-bold flex items-center gap-2 text-lg">
                        Toutes les réservations
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <?php if (empty($reservations)): ?>
                    <div class="rounded-2xl p-12 text-center" style="background: rgba(30, 30, 40, 0.5); border: 2px dashed rgba(255, 140, 0, 0.3);">
                        <i class="fas fa-ticket-alt text-6xl text-gray-600 mb-4"></i>
                        <h3 class="text-2xl font-bold mb-2">Aucune réservation</h3>
                        <p class="text-gray-400 mb-6">Réservez votre prochaine séance dès maintenant</p>
                        <a href="../cinemas.php" class="inline-block px-8 py-4 rounded-xl font-bold text-lg" style="background: linear-gradient(135deg, #ff8c00, #ff6b00);">
                            <i class="fas fa-search mr-2"></i>Découvrir les cinémas
                        </a>
                    </div>
                <?php else: ?>
                    <div class="slider-container rounded-2xl" style="background: rgba(30, 30, 40, 0.3); padding: 2rem;">
                        <button class="slider-btn prev" onclick="slideReservations(-1)">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        
                        <div class="slider-track" id="reservationsSlider">
                            <?php foreach ($reservations as $res): 
                                $is_today = $res['reservation_date'] === $today;
                            ?>
                                <div class="min-w-[300px] md:min-w-[350px] mx-2">
                                    <div class="rounded-xl overflow-hidden" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 140, 0, 0.3);">
                                        <div class="relative h-64">
                                            <?php if (!empty($res['film_poster'])): ?>
                                                <img src="<?= htmlspecialchars($res['film_poster']) ?>" alt="<?= htmlspecialchars($res['film_title']) ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full bg-gray-800 flex items-center justify-center">
                                                    <i class="fas fa-film text-6xl text-gray-600"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($is_today): ?>
                                                <div class="absolute top-3 left-3 bg-red-500 px-3 py-1 rounded-full text-xs font-bold animate-pulse">
                                                    🔥 AUJOURD'HUI
                                                </div>
                                            <?php else: ?>
                                                <div class="absolute top-3 left-3 bg-green-500 px-3 py-1 rounded-full text-xs font-bold">
                                                    📅 <?= date('d/m', strtotime($res['reservation_date'])) ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="absolute top-3 right-3 bg-black/80 px-3 py-1 rounded-full text-xs font-bold text-orange-500">
                                                <?= htmlspecialchars($res['reservation_code']) ?>
                                            </div>
                                        </div>
                                        
                                        <div class="p-4">
                                            <h3 class="font-bold text-lg mb-2 truncate"><?= htmlspecialchars($res['film_title']) ?></h3>
                                            <p class="text-sm text-gray-400 mb-2 truncate">
                                                <i class="fas fa-building mr-1 text-orange-500"></i>
                                                <?= htmlspecialchars($res['cinema_name']) ?>
                                            </p>
                                            <div class="flex justify-between text-sm mb-3">
                                                <span><i class="fas fa-clock text-orange-500 mr-1"></i><?= $res['reservation_time'] ?></span>
                                                <span><i class="fas fa-users text-orange-500 mr-1"></i><?= $res['number_tickets'] ?> place(s)</span>
                                            </div>
                                            <button onclick="window.location.href='reservations.php'" class="w-full py-2 rounded-lg font-bold" style="background: linear-gradient(135deg, #ff8c00, #ff6b00);">
                                                <i class="fas fa-eye mr-2"></i>Voir détails
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button class="slider-btn next" onclick="slideReservations(1)">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Liens rapides -->
            <div>
                <h2 class="text-3xl font-black mb-6 text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    Accès rapides
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <a href="watchlist.php" class="quick-link rounded-xl p-6 text-center block">
                        <i class="fas fa-bookmark text-5xl mb-4 text-orange-500"></i>
                        <h3 class="text-xl font-bold mb-2">Watchlist</h3>
                        <p class="text-gray-400"><?= $movie_count + $serie_count ?> contenus</p>
                    </a>
                    
                    <a href="lists.php" class="quick-link rounded-xl p-6 text-center block">
                        <i class="fas fa-list text-5xl mb-4 text-purple-500"></i>
                        <h3 class="text-xl font-bold mb-2">Mes Listes</h3>
                        <p class="text-gray-400">Organisez vos contenus</p>
                    </a>
                    
                    <a href="reviews.php" class="quick-link rounded-xl p-6 text-center block">
                        <i class="fas fa-star text-5xl mb-4 text-yellow-500"></i>
                        <h3 class="text-xl font-bold mb-2">Critiques</h3>
                        <p class="text-gray-400">Partagez vos avis</p>
                    </a>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
        let currentSlide = 0;
        
        function slideReservations(direction) {
            const slider = document.getElementById('reservationsSlider');
            const slideWidth = 350 + 16; // width + margin
            const maxSlides = <?= count($reservations) ?> - Math.floor(window.innerWidth / slideWidth);
            
            currentSlide = Math.max(0, Math.min(maxSlides, currentSlide + direction));
            slider.style.transform = `translateX(-${currentSlide * slideWidth}px)`;
        }
        
        function viewContent(id, type) {
            window.location.href = type === 'movie' ? '../movie-details.php?id=' + id : '../serie-details.php?id=' + id;
        }
        
        function addMovie(id, title, poster) {
            fetch('../../add_to_watchlist.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({movie_id: id, movie_title: title, movie_poster: poster})
            })
            .then(r => r.json())
            .then(d => alert(d.success ? '✅ Ajouté!' : '❌ ' + d.message));
        }
        
        function addSerie(id, title, poster) {
            fetch('../../add_to_watchlist.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({serie_id: id, serie_title: title, serie_poster: poster})
            })
            .then(r => r.json())
            .then(d => alert(d.success ? '✅ Ajouté!' : '❌ ' + d.message));
        }
    </script>
</body>
</html>