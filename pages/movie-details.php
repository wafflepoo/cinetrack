<?php
// movie-details.php - Updated with correct database tables
session_start();
include '../includes/config.conf.php';
include '../includes/auth.php';

// Get movie ID from URL
$movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$movie_id) {
    header('Location: films.php');
    exit();
}

// Fetch movie details from TMDb API
function fetchMovieDetails($movie_id) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'movie/' . $movie_id . '?api_key=' . $api_key . '&language=fr-FR&append_to_response=credits,videos,reviews';
    
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

// Fetch movie details
$movie = fetchMovieDetails($movie_id);

if (!$movie) {
    header('Location: films.php');
    exit();
}

// Check if movie is in user's watchlist
$in_watchlist = false;
$watchlist_status = '';
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $check_query = "SELECT type_status FROM SELECTION WHERE id_utilisateur = ? AND id_film = ?";
    $stmt = $mysqli->prepare($check_query);
    $stmt->bind_param("ii", $user_id, $movie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $in_watchlist = true;
        $watchlist_data = $result->fetch_assoc();
        $watchlist_status = $watchlist_data['type_status'];
    }
    $stmt->close();
}

// Fetch reviews from database using CRITIQUE_FILM table
$reviews = [];
try {
    $review_query = "SELECT cf.*, u.pseudo, u.email 
                     FROM CRITIQUE_FILM cf 
                     JOIN UTILISATEUR u ON cf.id_utilisateur = u.id_utilisateur 
                     WHERE cf.id_film = ? 
                     ORDER BY cf.date DESC 
                     LIMIT 10";
    $stmt = $mysqli->prepare($review_query);
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    // If there's an error with the reviews, just continue without them
    error_log("Error fetching reviews: " . $e->getMessage());
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
        if ($rating < 1 || $rating > 5) {
            $_SESSION['error'] = 'La note doit être entre 1 et 5.';
        }
        else {
            try {
                // First, ensure the movie exists in FILM table
                $check_film = "SELECT id_film FROM FILM WHERE id_film = ?";
                $stmt = $mysqli->prepare($check_film);
                $stmt->bind_param("i", $movie_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    // Insert movie into FILM table first
                    $insert_film = "INSERT INTO FILM (id_film, titre, poster, date_sortie, description) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $mysqli->prepare($insert_film);
                    $stmt->bind_param("issss", $movie_id, $movie['title'], $movie['poster_path'], $movie['release_date'], $movie['overview']);
                    $stmt->execute();
                }
                $stmt->close();
                
                // Check if user already reviewed this movie
                $check_review = "SELECT id_utilisateur FROM CRITIQUE_FILM WHERE id_utilisateur = ? AND id_film = ?";
                $stmt = $mysqli->prepare($check_review);
                $stmt->bind_param("ii", $user_id, $movie_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $_SESSION['error'] = 'Vous avez déjà posté une critique pour ce film.';
                } else {
                    // Insert review using CRITIQUE_FILM table
                    $insert_review = "INSERT INTO CRITIQUE_FILM (id_utilisateur, id_film, note, texte, date) 
                                     VALUES (?, ?, ?, ?, NOW())";
                    $stmt = $mysqli->prepare($insert_review);
                    $stmt->bind_param("iids", $user_id, $movie_id, $rating, $comment);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = 'Votre critique a été publiée!';
                        header('Location: movie-details.php?id=' . $movie_id);
                        exit();
                    } else {
                        $_SESSION['error'] = 'Erreur lors de la publication de la critique.';
                    }
                }
                $stmt->close();
                
            } catch (Exception $e) {
                $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
                error_log("Review submission error: " . $e->getMessage());
            }
        }
    }
}
// Get YouTube trailer
// Get YouTube trailer - improved search
$trailer_key = '';
if (isset($movie['videos']['results'])) {
    // Look for trailers first, then teasers, then any video
    foreach ($movie['videos']['results'] as $video) {
        if ($video['site'] === 'YouTube') {
            if ($video['type'] === 'Trailer') {
                $trailer_key = $video['key'];
                break;
            } elseif ($video['type'] === 'Teaser' && empty($trailer_key)) {
                $trailer_key = $video['key'];
            } elseif (empty($trailer_key)) {
                $trailer_key = $video['key']; // Fallback to any YouTube video
            }
        }
    }
}

// Debug trailer info
error_log("Movie ID: " . $movie_id . " - Trailer found: " . ($trailer_key ? 'Yes' : 'No'));
if ($trailer_key) {
    error_log("Trailer key: " . $trailer_key);
}

// Handle backdrop image
$backdrop_url = '';
if (!empty($movie['backdrop_path'])) {
    $backdrop_url = TMDB_IMAGE_BASE_URL . 'w1280' . $movie['backdrop_path'];
} else {
    // Fallback gradient background
    $backdrop_url = 'none';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico">
    <style>
        .star {
            cursor: pointer;
            font-size: 2rem;
            color: #555;
            transition: color .2s;
        }
        .star.active {
            color: #ffd700;
        }
        .star:hover {
            color: #ffd700;
        }

        .movie-hero {
            <?php if ($backdrop_url !== 'none'): ?>
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 100%), 
                        url('<?php echo $backdrop_url; ?>');
            background-size: cover;
            background-position: center;
            <?php else: ?>
            background: linear-gradient(135deg, #ff8c00 0%, #7800ff 100%);
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
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 140, 0, 0.3);
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
            scrollbar-color: #ff8c00 transparent;
        }
        
        .cast-scroll::-webkit-scrollbar {
            height: 6px;
        }
        
        .cast-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .cast-scroll::-webkit-scrollbar-thumb {
            background: #ff8c00;
            border-radius: 3px;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #0a0e14 0%, #05080d 100%);
            min-height: 100vh;
        }

        /* Notification styles */
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
        <!-- Hero Section with Movie Details -->
        <section class="movie-hero">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row gap-8 items-start">
                    <!-- Movie Poster -->
                    <div class="flex-shrink-0 mx-auto lg:mx-0">
                        <img src="<?php echo TMDB_IMAGE_BASE_URL . 'w500' . $movie['poster_path']; ?>" 
                             alt="<?php echo htmlspecialchars($movie['title']); ?>"
                             class="w-64 lg:w-80 rounded-2xl shadow-2xl">
                    </div>
                    
                    <!-- Movie Info -->
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-4xl lg:text-5xl font-black mb-4"><?php echo htmlspecialchars($movie['title']); ?></h1>
                        
                        <!-- Movie Metadata -->
                        <div class="flex flex-wrap gap-4 mb-6 text-gray-300 justify-center lg:justify-start">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-star text-yellow-400"></i>
                                <span><?php echo number_format($movie['vote_average'], 1); ?>/10</span>
                                <span class="text-sm">(<?php echo number_format($movie['vote_count']); ?> votes)</span>
                            </span>
                            <span><?php echo date('Y', strtotime($movie['release_date'])); ?></span>
                            <span><?php echo $movie['runtime'] ?? 'N/A'; ?> min</span>
                            <span>
                                <?php 
                                $genres = array_map(function($genre) {
                                    return $genre['name'];
                                }, $movie['genres']);
                                echo implode(', ', $genres);
                                ?>
                            </span>
                        </div>
                        
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
    
    <?php if ($trailer_key): ?>
        <button onclick="openTrailerModal()" class="glass px-6 py-3 rounded-lg font-semibold border border-orange-500/50 hover:bg-orange-500/20 transition">
            <i class="fas fa-play mr-2"></i>Voir la bande-annonce
        </button>
    <?php else: ?>
        <!-- Show disabled button if no trailer -->
        <button class="glass px-6 py-3 rounded-lg font-semibold border border-gray-500/50 opacity-50 cursor-not-allowed">
            <i class="fas fa-play mr-2"></i>Bande-annonce non disponible
        </button>
    <?php endif; ?>
</div>


                        <!-- Synopsis -->
                        <div class="mb-6">
                            <h3 class="text-xl font-bold mb-3">Synopsis</h3>
                            <p class="text-gray-300 leading-relaxed text-lg"><?php echo $movie['overview'] ?: 'Aucun synopsis disponible.'; ?></p>
                        </div>
                        
                        <!-- Cast -->
                        <?php if (isset($movie['credits']['cast']) && !empty($movie['credits']['cast'])): ?>
                        <div class="mb-6">
                            <h3 class="text-xl font-bold mb-3">Casting principal</h3>
                            <div class="flex gap-4 overflow-x-auto pb-4 cast-scroll">
                                <?php 
                                $cast = array_slice($movie['credits']['cast'], 0, 10);
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
                              <div class="mb-4">
    <label class="block text-sm font-medium mb-2">Note (1 à 5)</label>

    <div class="star-rating" id="starRating">
        <i class="fas fa-star star" data-value="1"></i>
        <i class="fas fa-star star" data-value="2"></i>
        <i class="fas fa-star star" data-value="3"></i>
        <i class="fas fa-star star" data-value="4"></i>
        <i class="fas fa-star star" data-value="5"></i>
    </div>

    <!-- Hidden input sent to PHP -->
    <input type="hidden" name="rating" id="ratingValue" value="0">
</div>

                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2">Commentaire</label>
                                <textarea name="comment" rows="4" 
                                          class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition"
                                          placeholder="Partagez votre opinion sur ce film..."
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
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-orange-500 to-purple-600 flex items-center justify-center">
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
                                                   <?php for ($i = 1; $i <= 5; $i++): ?>
    <i class="fas fa-star <?= $i <= $review['note'] ? 'text-yellow-400' : 'text-gray-600' ?>"></i>
<?php endfor; ?>

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
            <button onclick="closeTrailerModal()" class="absolute -top-12 right-0 text-white text-2xl hover:text-orange-500 transition z-10">
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
            iframe.src = iframeSrc; // This reloads the iframe, stopping the video
        }
    }

// Add to watchlist function - SIMPLIFIED VERSION
    function addToWatchlist() {
        console.log('=== addToWatchlist called ===');
        
        const movieData = {
            movie_id: <?php echo $movie_id; ?>,
            movie_title: '<?php echo addslashes($movie['title']); ?>',
            movie_poster: '<?php echo $movie['poster_path']; ?>',
            release_date: '<?php echo $movie['release_date']; ?>'
        };

        console.log('Movie data:', movieData);


        
        // Try the fetch request
        fetch('add-to-watchlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(movieData)
        })
        .then(response => {
            console.log('Response received:', response);
            console.log('Response status:', response.status, response.statusText);
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
                showNotification(' Film ajoutée à votre watchlist', 'success');

                // reload AFTER animation
                setTimeout(() => {
                    location.reload();
                }, 1500);

            } else {
                showNotification('❌ ' + data.message, 'error');
            }
        })
    }

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

    document.querySelectorAll('.star').forEach(star => {
        star.addEventListener('click', () => {
            let rating = star.getAttribute('data-value');
            document.getElementById('ratingValue').value = rating;

            // Highlight stars
            document.querySelectorAll('.star').forEach(s => {
                s.classList.toggle('active', s.getAttribute('data-value') <= rating);
            });
        });
    });

    </script>
</body>
</html>