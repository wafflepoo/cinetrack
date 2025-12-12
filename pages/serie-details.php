<?php
// serie-details.php - Series Details Page
session_start();
include '../includes/config.conf.php';
include '../includes/auth.php';

// Get series ID from URL
$serie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$serie_id) {
    header('Location: series.php');
    exit();
}

// Fetch series details from TMDb API
function fetchSeriesDetails($serie_id) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'tv/' . $serie_id . '?api_key=' . $api_key . '&language=fr-FR&append_to_response=credits,videos,reviews';
    
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
        return json_decode($response, true);
    }
    
    return null;
}

// Fetch series details
$serie = fetchSeriesDetails($serie_id);

if (!$serie) {
    header('Location: series.php');
    exit();
}

// Check if series is in user's watchlist
$in_watchlist = false;
$watchlist_status = '';
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $check_query = "SELECT type_status FROM SELECTION WHERE id_utilisateur = ? AND id_serie = ?";
    $stmt = $mysqli->prepare($check_query);
    $stmt->bind_param("ii", $user_id, $serie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $in_watchlist = true;
        $watchlist_data = $result->fetch_assoc();
        $watchlist_status = $watchlist_data['type_status'];
    }
    $stmt->close();
}

// Fetch reviews from database using CRITIQUE_SERIE table
$reviews = [];
try {
    $review_query = "SELECT cs.*, u.pseudo, u.email 
                     FROM CRITIQUE_SERIE cs 
                     JOIN UTILISATEUR u ON cs.id_utilisateur = u.id_utilisateur 
                     WHERE cs.id_serie = ? 
                     ORDER BY cs.date DESC 
                     LIMIT 10";
    $stmt = $mysqli->prepare($review_query);
    $stmt->bind_param("i", $serie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    // If there's an error with the reviews, just continue without them
    error_log("Error fetching series reviews: " . $e->getMessage());
    $reviews = [];
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Vous devez être connecté pour poster une critique.';
    } else {
        $user_id = $_SESSION['user_id'];
        $rating = floatval($_POST['rating']);
        $comment = trim($_POST['comment']);
        
        // Validate rating
        if ($rating < 0 || $rating > 10) {
            $_SESSION['error'] = 'La note doit être entre 0 et 10.';
        } else {
            try {
                // First, ensure the series exists in SERIE table
                $check_serie = "SELECT id_serie FROM SERIE WHERE id_serie = ?";
                $stmt = $mysqli->prepare($check_serie);
                $stmt->bind_param("i", $serie_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    // Insert series into SERIE table first
                    $insert_serie = "INSERT INTO SERIE (id_serie, titre, poster, date_premiere, description) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $mysqli->prepare($insert_serie);
                    $stmt->bind_param("issss", $serie_id, $serie['name'], $serie['poster_path'], $serie['first_air_date'], $serie['overview']);
                    $stmt->execute();
                }
                $stmt->close();
                
                // Check if user already reviewed this series
                $check_review = "SELECT id_utilisateur FROM CRITIQUE_SERIE WHERE id_utilisateur = ? AND id_serie = ?";
                $stmt = $mysqli->prepare($check_review);
                $stmt->bind_param("ii", $user_id, $serie_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $_SESSION['error'] = 'Vous avez déjà posté une critique pour cette série.';
                } else {
                    // Insert review using CRITIQUE_SERIE table
                    $insert_review = "INSERT INTO CRITIQUE_SERIE (id_utilisateur, id_serie, note, texte, date) 
                                     VALUES (?, ?, ?, ?, NOW())";
                    $stmt = $mysqli->prepare($insert_review);
                    $stmt->bind_param("iids", $user_id, $serie_id, $rating, $comment);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = 'Votre critique a été publiée!';
                        header('Location: serie-details.php?id=' . $serie_id);
                        exit();
                    } else {
                        $_SESSION['error'] = 'Erreur lors de la publication de la critique.';
                    }
                }
                $stmt->close();
                
            } catch (Exception $e) {
                $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
                error_log("Series review submission error: " . $e->getMessage());
            }
        }
    }
}

// Get YouTube trailer
$trailer_key = '';
if (isset($serie['videos']['results'])) {
    foreach ($serie['videos']['results'] as $video) {
        if ($video['site'] === 'YouTube') {
            if ($video['type'] === 'Trailer') {
                $trailer_key = $video['key'];
                break;
            } elseif ($video['type'] === 'Teaser' && empty($trailer_key)) {
                $trailer_key = $video['key'];
            } elseif (empty($trailer_key)) {
                $trailer_key = $video['key'];
            }
        }
    }
}

// Handle backdrop image
$backdrop_url = '';
if (!empty($serie['backdrop_path'])) {
    $backdrop_url = TMDB_IMAGE_BASE_URL . 'w1280' . $serie['backdrop_path'];
} else {
    $backdrop_url = 'none';
}

// Format episode runtime
$episode_runtime = isset($serie['episode_run_time'][0]) ? $serie['episode_run_time'][0] . ' min' : 'N/A';

// Get status
$status = $serie['status'] ?? 'Inconnu';
$status_translations = [
    'Returning Series' => 'En cours',
    'Ended' => 'Terminée',
    'Canceled' => 'Annulée',
    'In Production' => 'En production'
];
$status_fr = $status_translations[$status] ?? $status;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($serie['name']); ?> - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico">
    <style>
        .serie-hero {
            <?php if ($backdrop_url !== 'none'): ?>
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 100%), 
                        url('<?php echo $backdrop_url; ?>');
            background-size: cover;
            background-position: center;
            <?php else: ?>
            background: linear-gradient(135deg, #7800ff 0%, #ff8c00 100%);
            <?php endif; ?>
            padding: 120px 0 60px;
            position: relative;
        }
        
        .glass {
            background: rgba(30, 30, 40, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #7800ff 0%, #5a00cc 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(120, 0, 255, 0.3);
        }
        
        .star-rating {
            display: flex;
            gap: 0.25rem;
        }
        
        .star {
            color: #6b7280;
            cursor: pointer;
            transition: color 0.2s;
            font-size: 1.5rem;
        }
        
        .star.active {
            color: #ffd700;
        }
        
        .star:hover {
            color: #ffd700;
        }
        
        .cast-scroll {
            scrollbar-width: thin;
            scrollbar-color: #7800ff transparent;
        }
        
        .cast-scroll::-webkit-scrollbar {
            height: 6px;
        }
        
        .cast-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .cast-scroll::-webkit-scrollbar-thumb {
            background: #7800ff;
            border-radius: 3px;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #0a0e14 0%, #05080d 100%);
            min-height: 100vh;
        }

        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            color: white;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .notification.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .season-card {
            background: rgba(30, 30, 40, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .season-card:hover {
            border-color: rgba(120, 0, 255, 0.3);
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="gradient-bg text-white">
    <?php include '../includes/header.php'; ?>
    
    <!-- Notifications -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="notification success show" id="successNotification">
            <i class="fas fa-check-circle mr-2"></i><?php echo $_SESSION['success']; ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="notification error show" id="errorNotification">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $_SESSION['error']; ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <main>
        <!-- Hero Section with Series Details -->
        <section class="serie-hero">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row gap-8 items-start">
                    <!-- Series Poster -->
                    <div class="flex-shrink-0 mx-auto lg:mx-0">
                        <img src="<?php echo TMDB_IMAGE_BASE_URL . 'w500' . $serie['poster_path']; ?>" 
                             alt="<?php echo htmlspecialchars($serie['name']); ?>"
                             class="w-64 lg:w-80 rounded-2xl shadow-2xl">
                    </div>
                    
                    <!-- Series Info -->
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-4xl lg:text-5xl font-black mb-4"><?php echo htmlspecialchars($serie['name']); ?></h1>
                        
                        <!-- Series Metadata -->
                        <div class="flex flex-wrap gap-4 mb-6 text-gray-300 justify-center lg:justify-start">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-star text-yellow-400"></i>
                                <span><?php echo number_format($serie['vote_average'], 1); ?>/10</span>
                                <span class="text-sm">(<?php echo number_format($serie['vote_count']); ?> votes)</span>
                            </span>
                            <span><?php echo date('Y', strtotime($serie['first_air_date'])); ?></span>
                            <span><?php echo $episode_runtime; ?></span>
                            <span><?php echo $status_fr; ?></span>
                            <span>
                                <?php 
                                $genres = array_map(function($genre) {
                                    return $genre['name'];
                                }, $serie['genres']);
                                echo implode(', ', $genres);
                                ?>
                            </span>
                        </div>
                        
                        <!-- Series Stats -->
                        <div class="flex flex-wrap gap-6 mb-6 justify-center lg:justify-start">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-400"><?php echo $serie['number_of_seasons']; ?></div>
                                <div class="text-sm text-gray-400">Saisons</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-400"><?php echo $serie['number_of_episodes']; ?></div>
                                <div class="text-sm text-gray-400">Épisodes</div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <!-- Action Buttons -->
<div class="flex flex-wrap gap-4 mb-6 justify-center lg:justify-start">
    <?php if ($in_watchlist): ?>
        <button class="btn-primary px-6 py-3 rounded-lg font-semibold opacity-70 cursor-not-allowed">
            <i class="fas fa-check mr-2"></i>Dans votre watchlist
        </button>
    <?php else: ?>
        <button onclick="addToWatchlist()" class="btn-primary px-6 py-3 rounded-lg font-semibold">
            <i class="fas fa-plus mr-2"></i>Ajouter à ma watchlist
        </button>
    <?php endif; ?>
    
    <!-- NOUVEAU BOUTON: Ajouter à une liste -->
    <button onclick="addToCustomList()" class="glass px-6 py-3 rounded-lg font-semibold border border-purple-500/50 hover:bg-purple-500/20 transition">
        <i class="fas fa-list mr-2"></i>Ajouter à une liste
    </button>
    
    <?php if ($trailer_key): ?>
        <button onclick="openTrailerModal()" class="glass px-6 py-3 rounded-lg font-semibold border border-purple-500/50 hover:bg-purple-500/20 transition">
            <i class="fas fa-play mr-2"></i>Voir la bande-annonce
        </button>
    <?php else: ?>
        <button class="glass px-6 py-3 rounded-lg font-semibold border border-gray-500/50 opacity-50 cursor-not-allowed">
            <i class="fas fa-play mr-2"></i>Bande-annonce non disponible
        </button>
    <?php endif; ?>
</div>
                        
                        <!-- Synopsis -->
                        <div class="mb-6">
                            <h3 class="text-xl font-bold mb-3">Synopsis</h3>
                            <p class="text-gray-300 leading-relaxed text-lg"><?php echo $serie['overview'] ?: 'Aucun synopsis disponible.'; ?></p>
                        </div>
                        
                        <!-- Cast -->
                        <?php if (isset($serie['credits']['cast']) && !empty($serie['credits']['cast'])): ?>
                        <div class="mb-6">
                            <h3 class="text-xl font-bold mb-3">Casting principal</h3>
                            <div class="flex gap-4 overflow-x-auto pb-4 cast-scroll">
                                <?php 
                                $cast = array_slice($serie['credits']['cast'], 0, 10);
                                foreach ($cast as $actor): 
                                ?>
                                    <div class="flex-shrink-0 text-center w-20">
                                        <div class="w-16 h-16 lg:w-20 lg:h-20 rounded-full bg-gray-700 mb-2 mx-auto overflow-hidden">
                                            <?php if ($actor['profile_path']): ?>
                                                <img src="<?php echo TMDB_IMAGE_BASE_URL . 'w185' . $actor['profile_path']; ?>" 
                                                     alt="<?php echo htmlspecialchars($actor['name']); ?>"
                                                     class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <i class="fas fa-user text-gray-400 text-xl"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm font-semibold truncate"><?php echo htmlspecialchars($actor['name']); ?></p>
                                        <p class="text-xs text-gray-400 truncate"><?php echo htmlspecialchars($actor['character']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Seasons Section -->
        <?php if (isset($serie['seasons']) && !empty($serie['seasons'])): ?>
        <section class="py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold mb-8 text-center lg:text-left">Saisons</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($serie['seasons'] as $season): ?>
                        <?php if ($season['season_number'] > 0): ?>
                            <div class="season-card p-6">
                                <div class="flex gap-4 items-start">
                                    <?php if ($season['poster_path']): ?>
                                        <img src="<?php echo TMDB_IMAGE_BASE_URL . 'w154' . $season['poster_path']; ?>" 
                                             alt="<?php echo htmlspecialchars($season['name']); ?>"
                                             class="w-20 h-30 object-cover rounded-lg">
                                    <?php else: ?>
                                        <div class="w-20 h-30 bg-gray-700 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-tv text-gray-400 text-xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($season['name']); ?></h3>
                                        <p class="text-sm text-gray-400 mb-2">
                                            <?php echo $season['episode_count']; ?> épisode<?php echo $season['episode_count'] > 1 ? 's' : ''; ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo !empty($season['air_date']) ? date('Y', strtotime($season['air_date'])) : 'Date inconnue'; ?>
                                        </p>
                                        <?php if ($season['overview']): ?>
                                            <p class="text-sm text-gray-300 mt-2 line-clamp-3"><?php echo $season['overview']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Reviews Section -->
        <section class="py-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold mb-8 text-center lg:text-left">Critiques</h2>
                
                <!-- Review Form -->
                <?php if (isLoggedIn()): ?>
                    <div class="glass p-6 rounded-2xl mb-8">
                        <h3 class="text-xl font-bold mb-4">Donnez votre avis</h3>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2">Note (0-10)</label>
                                <div class="flex items-center gap-4">
                                    <input type="range" name="rating" id="ratingSlider" 
                                           min="0" max="10" step="0.5" value="5" 
                                           class="w-full accent-purple-500"
                                           oninput="updateRatingValue(this.value)">
                                    <span id="ratingDisplay" class="text-lg font-bold text-purple-500 min-w-12">5.0</span>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2">Commentaire</label>
                                <textarea name="comment" rows="4" 
                                          class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-purple-500 transition"
                                          placeholder="Partagez votre opinion sur cette série..."
                                          required></textarea>
                            </div>
                            
                            <div class="text-center lg:text-left">
                                <button type="submit" name="submit_review" class="btn-primary px-6 py-3 rounded-lg font-semibold">
                                    <i class="fas fa-paper-plane mr-2"></i>Publier la critique
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="glass p-6 rounded-2xl mb-8 text-center">
                        <p class="text-gray-400 mb-4">Connectez-vous pour poster une critique</p>
                        <a href="../auth/login.php" class="btn-primary px-6 py-3 rounded-lg font-semibold inline-block">
                            <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Reviews List -->
                <div class="space-y-6">
                    <?php if (empty($reviews)): ?>
                        <div class="glass p-8 rounded-2xl text-center">
                            <i class="fas fa-comment-slash text-4xl text-gray-600 mb-4"></i>
                            <h3 class="text-xl font-bold text-gray-400 mb-2">Aucune critique</h3>
                            <p class="text-gray-500">Soyez le premier à donner votre avis!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="glass p-6 rounded-2xl">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-500 to-pink-600 flex items-center justify-center">
                                            <span class="font-bold text-white text-sm">
                                                <?php echo strtoupper(substr($review['pseudo'] ?: $review['email'], 0, 2)); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold"><?php echo htmlspecialchars($review['pseudo'] ?: explode('@', $review['email'])[0]); ?></h4>
                                            <div class="flex items-center gap-2 text-sm text-gray-400">
                                                <span><?php echo date('d/m/Y à H:i', strtotime($review['date'])); ?></span>
                                                <span>•</span>
                                                <div class="flex items-center gap-1">
                                                    <i class="fas fa-star text-yellow-400 text-xs"></i>
                                                    <span class="font-semibold"><?php echo number_format($review['note'], 1); ?>/10</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-gray-300 leading-relaxed"><?php echo nl2br(htmlspecialchars($review['texte'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Trailer Modal -->
    <?php if ($trailer_key): ?>
    <div id="trailerModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm flex items-center justify-center z-50 opacity-0 invisible transition-all duration-300">
        <div class="relative max-w-4xl w-full mx-4">
            <button onclick="closeTrailerModal()" class="absolute -top-12 right-0 text-white text-2xl hover:text-purple-500 transition z-10">
                <i class="fas fa-times"></i>
            </button>
            <div class="aspect-video bg-black rounded-2xl overflow-hidden">
                <iframe width="100%" height="100%" 
                        src="https://www.youtube.com/embed/<?php echo $trailer_key; ?>" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                </iframe>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include '../includes/footer.php'; ?>

    <script>
    // Rating slider functionality
    function updateRatingValue(value) {
        document.getElementById('ratingDisplay').textContent = parseFloat(value).toFixed(1);
    }

    // Auto-hide notifications
    document.addEventListener('DOMContentLoaded', function() {
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        });
    });

    // Trailer modal functions
    function openTrailerModal() {
        const modal = document.getElementById('trailerModal');
        modal.classList.remove('opacity-0', 'invisible');
        modal.classList.add('opacity-100', 'visible');
        document.body.style.overflow = 'hidden';
    }

    function closeTrailerModal() {
        const modal = document.getElementById('trailerModal');
        modal.classList.remove('opacity-100', 'visible');
        modal.classList.add('opacity-0', 'invisible');
        document.body.style.overflow = 'auto';
        
        // Stop the video
        const iframe = modal.querySelector('iframe');
        if (iframe) {
            const iframeSrc = iframe.src;
            iframe.src = iframeSrc;
        }
    }

    // Add to watchlist function for series
    function addToWatchlist() {
        console.log('=== addToWatchlist for series called ===');
        
        const serieData = {
            serie_id: <?php echo $serie_id; ?>,
            serie_title: '<?php echo addslashes($serie['name']); ?>',
            serie_poster: '<?php echo $serie['poster_path']; ?>',
            first_air_date: '<?php echo $serie['first_air_date']; ?>'
        };

        console.log('Series data:', serieData);

        fetch('add-to-watchlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(serieData)
        })
        .then(response => {
            console.log('Response received:', response);
            return response.text().then(text => {
                console.log('Raw response text:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    return {success: false, message: 'Invalid JSON response: ' + text};
                }
            });
        })
        .then(data => {
            if (data.success) {
                showNotification(' Série ajoutée à votre watchlist', 'success');

                // reload AFTER animation
                setTimeout(() => {
                    location.reload();
                }, 1500);

            } else {
                showNotification('❌ ' + data.message, 'error');
            }
        })
    }

    // Notification function
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');

        notification.className = `
            notification ${type}
            show
        `;

        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-xmark-circle'} mr-2"></i>
            ${message}
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
// Fonction pour ajouter à une liste (séries)
function addToCustomList() {
    const serieData = {
        serie_id: <?php echo $serie_id; ?>,
        serie_title: '<?php echo addslashes($serie['name']); ?>',
        serie_poster: '<?php echo $serie['poster_path']; ?>',
        first_air_date: '<?php echo $serie['first_air_date']; ?>'
    };
    
    // Vérifier si l'utilisateur est connecté
    <?php if (isLoggedIn()): ?>
        // Charger les listes de l'utilisateur
        loadUserListsForSeries(serieData);
    <?php else: ?>
        // Non connecté - rediriger
        showNotification('Veuillez vous connecter pour ajouter à une liste', 'error');
        setTimeout(() => {
            window.location.href = '../auth/login.php?redirect=' + encodeURIComponent(window.location.href);
        }, 1500);
    <?php endif; ?>
}

// Fonction pour charger les listes de l'utilisateur (séries)
function loadUserListsForSeries(serieData) {
    fetch('lists/get-user-lists.php') 
        .then(response => response.json())
        .then(lists => {
            showListSelectionModalSeries(lists, serieData);
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors du chargement des listes', 'error');
        });
}

// Fonction pour afficher la modal de sélection de liste (adaptée pour séries)
function showListSelectionModalSeries(lists, serieData) {
    const modalId = 'listModal-series-' + serieData.serie_id;
    
    let modalHTML = `
        <div id="${modalId}" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-[100]">
            <div class="bg-gray-900 border border-purple-500/30 rounded-xl p-6 max-w-md w-full mx-4 shadow-2xl">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-white">Ajouter à une liste</h3>
                    <button onclick="closeListModal('${modalId}')" class="text-gray-400 hover:text-white text-2xl">&times;</button>
                </div>
                
                <p class="text-gray-300 mb-6">
                    Choisissez une liste pour "<strong class="text-purple-400">${serieData.serie_title}</strong>"
                </p>
                
                <div id="listsContainer-series-${serieData.serie_id}" class="space-y-3 max-h-60 overflow-y-auto mb-6 pr-2">
                    ${lists.length === 0 ? 
                        '<div class="text-center py-4 text-gray-400">' +
                            '<i class="fas fa-list mb-2 text-2xl"></i>' +
                            '<p>Vous n\'avez aucune liste</p>' +
                            '<a href="../lists/list.php" class="text-purple-500 hover:text-purple-400 mt-2 inline-block">Créer une liste</a>' +
                        '</div>' 
                        : ''}
                </div>
                
                <div class="flex gap-3">
                    <button onclick="createNewListForSeries(${serieData.serie_id}, '${serieData.serie_title.replace(/'/g, "\\'")}', '${serieData.serie_poster.replace(/'/g, "\\'")}', '${serieData.first_air_date.replace(/'/g, "\\'")}', '${modalId}')"
                            class="flex-1 py-3 bg-purple-600 hover:bg-purple-700 rounded-lg font-semibold transition-colors">
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
        const container = document.getElementById(`listsContainer-series-${serieData.serie_id}`);
        lists.forEach(list => {
            const listItem = document.createElement('div');
            listItem.className = 'p-4 bg-gray-800/50 rounded-lg hover:bg-gray-700/50 cursor-pointer transition-colors border border-gray-700/50 hover:border-purple-500/30';
            listItem.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <span class="font-medium text-white">${list.nom_liste}</span>
                    <span class="text-sm text-gray-400">${list.item_count || 0} élément(s)</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-400 capitalize">${list.type_liste || 'mixte'}</span>
                    <button onclick="addToSpecificListForSeries(${list.id_liste}, ${serieData.serie_id}, '${serieData.serie_title.replace(/'/g, "\\'")}', '${serieData.serie_poster.replace(/'/g, "\\'")}', '${serieData.first_air_date.replace(/'/g, "\\'")}', '${modalId}')"
                            class="px-3 py-1 bg-purple-600 hover:bg-purple-700 rounded-lg text-sm font-medium">
                        Ajouter
                    </button>
                </div>
            `;
            container.appendChild(listItem);
        });
    }
}

// Fonction pour ajouter une série à une liste spécifique
function addToSpecificListForSeries(listId, serieId, serieTitle, seriePoster, firstAirDate, modalId) {
    // D'abord ajouter la série à la base de données (si pas déjà)
    const serieData = {
        serie_id: serieId,
        serie_title: serieTitle,
        serie_poster: seriePoster,
        first_air_date: firstAirDate || null
    };
    
    fetch('add-to-watchlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(serieData)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success && data.message !== 'Déjà dans la watchlist') {
            showNotification('Erreur: ' + data.message, 'error');
            return;
        }
        
        // Puis ajouter à la liste
        const formData = new FormData();
        formData.append('list_id', listId);
        formData.append('serie_id', serieId);  // Note: serie_id au lieu de movie_id
        formData.append('serie_title', serieTitle);  // serie_title au lieu de movie_title
        formData.append('serie_poster', seriePoster);  // serie_poster au lieu de movie_poster
        formData.append('content_type', 'series');  // Nouveau: indiquer que c'est une série
        
        fetch('lists/add-to-list.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            closeListModal(modalId);
            if (result.status === 'success') {
                showNotification(`Ajouté à "${result.list_name}"`, 'success');
            } else if (result.status === 'exists') {
                showNotification('Déjà dans cette liste', 'info');
            } else {
                showNotification('Erreur: ' + result.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Erreur réseau', 'error');
        });
    })
    .catch(error => {
        showNotification('Erreur d\'ajout', 'error');
    });
}

// Fonction pour créer une nouvelle liste pour une série
function createNewListForSeries(serieId, serieTitle, seriePoster, firstAirDate, modalId) {
    const listName = prompt('Nom de la nouvelle liste :');
    if (!listName || listName.trim() === '') return;
    
    // D'abord ajouter la série
    const serieData = {
        serie_id: serieId,
        serie_title: serieTitle,
        serie_poster: seriePoster,
        first_air_date: firstAirDate || null
    };
    
    fetch('add-to-watchlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(serieData)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success && data.message !== 'Déjà dans la watchlist') {
            showNotification('Erreur: ' + data.message, 'error');
            return;
        }
        
        // Créer la liste
        const formData = new FormData();
        formData.append('list_name', listName);
        formData.append('list_type', 'series');  // Type: series
        formData.append('serie_id', serieId);  // serie_id au lieu de movie_id
        formData.append('serie_title', serieTitle);  // serie_title au lieu de movie_title
        formData.append('serie_poster', seriePoster);  // serie_poster au lieu de movie_poster
        formData.append('content_type', 'series');  // Indiquer que c'est une série
        
        fetch('lists/create-list-with-item.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            closeListModal(modalId);
            if (result.status === 'success') {
                showNotification(`Liste "${listName}" créée`, 'success');
            } else {
                showNotification('Erreur: ' + result.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Erreur de création', 'error');
        });
    })
    .catch(error => {
        showNotification('Erreur d\'ajout', 'error');
    });
}

// Fonction pour fermer la modal
function closeListModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.remove();
}
    </script>
</body>
</html>