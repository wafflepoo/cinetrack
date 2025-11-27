<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();
$user = getCurrentUser();

// Get user reviews
$film_reviews = [];
$serie_reviews = [];

// Film reviews
$query = "SELECT cf.*, f.titre, f.poster, f.date_sortie
          FROM CRITIQUE_FILM cf
          JOIN FILM f ON cf.id_film = f.id_film
          WHERE cf.id_utilisateur = ?
          ORDER BY cf.date DESC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$film_reviews = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Series reviews
$query = "SELECT cs.*, s.titre, s.poster, s.date_premiere
          FROM CRITIQUE_SERIE cs
          JOIN SERIE s ON cs.id_serie = s.id_serie
          WHERE cs.id_utilisateur = ?
          ORDER BY cs.date DESC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$serie_reviews = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Critiques - CineTrack</title>
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
        .review-card {
            @apply transition-transform hover:scale-[1.02] border border-transparent hover:border-orange-500/20;
        }
        .rating-circle {
            @apply w-12 h-12 rounded-full flex items-center justify-center font-bold text-sm border-2;
        }
        .btn-primary {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 140, 0, 0.3);
        }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen">
    <?php include '../../includes/header.php'; ?>    
    
    <main class="pt-32 pb-16">
        <div class="max-w-6xl mx-auto px-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-black mb-2">Mes Critiques</h1>
                <p class="text-gray-400 text-lg">Vos avis sur les films et séries</p>
            </div>
            
            <!-- Tabs -->
            <div class="glass p-6 rounded-2xl mb-8">
                <div class="flex space-x-1 bg-gray-800/50 p-1 rounded-lg w-fit">
                    <button class="tab-btn active px-6 py-3 rounded-lg text-gray-400 font-medium transition bg-orange-500 text-white">
                        Tous (<?php echo count($film_reviews) + count($serie_reviews); ?>)
                    </button>
                    <button class="tab-btn px-6 py-3 rounded-lg text-gray-400 font-medium transition">
                        Films (<?php echo count($film_reviews); ?>)
                    </button>
                    <button class="tab-btn px-6 py-3 rounded-lg text-gray-400 font-medium transition">
                        Séries (<?php echo count($serie_reviews); ?>)
                    </button>
                </div>
            </div>
            
            <!-- Reviews -->
            <div class="space-y-6">
                <?php if (empty($film_reviews) && empty($serie_reviews)): ?>
                    <div class="text-center py-16 glass rounded-2xl">
                        <i class="fas fa-star text-6xl text-gray-600 mb-4"></i>
                        <h3 class="text-2xl font-bold text-gray-400 mb-2">Aucune critique</h3>
                        <p class="text-gray-500 mb-6">Commencez à noter et critiquer vos films et séries!</p>
                        <a href="../pages/films.php" class="btn-primary px-6 py-3 rounded-lg font-semibold">
                            <i class="fas fa-search mr-2"></i>Découvrir des contenus
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Film Reviews -->
                    <?php foreach ($film_reviews as $review): ?>
                        <div class="review-card glass p-6 rounded-2xl">
                            <div class="flex flex-col md:flex-row gap-6">
                                <!-- Poster -->
                                <div class="flex-shrink-0">
                                    <?php if ($review['poster']): ?>
                                        <img src="<?php echo TMDB_IMAGE_BASE_URL . 'w300' . $review['poster']; ?>" 
                                             alt="<?php echo htmlspecialchars($review['titre']); ?>"
                                             class="w-24 rounded-lg shadow-lg">
                                    <?php else: ?>
                                        <div class="w-24 h-36 bg-gray-700 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-film text-gray-400 text-2xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Content -->
                                <div class="flex-grow">
                                    <div class="flex flex-wrap items-start justify-between mb-3">
                                        <div>
                                            <h3 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($review['titre']); ?></h3>
                                            <p class="text-gray-400 text-sm">
                                                Film • <?php echo date('Y', strtotime($review['date_sortie'])); ?>
                                            </p>
                                        </div>
                                        
                                        <!-- Rating -->
                                        <div class="text-center">
                                            <div class="rating-circle <?php echo getRatingColor($review['note']); ?>">
                                                <?php echo number_format($review['note'], 1); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Review Text -->
                                    <?php if (!empty($review['texte'])): ?>
                                        <div class="bg-gray-800/30 rounded-lg p-4 mb-3">
                                            <p class="text-gray-300 leading-relaxed"><?php echo nl2br(htmlspecialchars($review['texte'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Date -->
                                    <p class="text-gray-500 text-sm">
                                        Posté le <?php echo date('d/m/Y à H:i', strtotime($review['date'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Series Reviews -->
                    <?php foreach ($serie_reviews as $review): ?>
                        <div class="review-card glass p-6 rounded-2xl">
                            <div class="flex flex-col md:flex-row gap-6">
                                <!-- Poster -->
                                <div class="flex-shrink-0">
                                    <?php if ($review['poster']): ?>
                                        <img src="<?php echo TMDB_IMAGE_BASE_URL . 'w300' . $review['poster']; ?>" 
                                             alt="<?php echo htmlspecialchars($review['titre']); ?>"
                                             class="w-24 rounded-lg shadow-lg">
                                    <?php else: ?>
                                        <div class="w-24 h-36 bg-gray-700 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-tv text-gray-400 text-2xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Content -->
                                <div class="flex-grow">
                                    <div class="flex flex-wrap items-start justify-between mb-3">
                                        <div>
                                            <h3 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($review['titre']); ?></h3>
                                            <p class="text-gray-400 text-sm">
                                                Série • <?php echo date('Y', strtotime($review['date_premiere'])); ?>
                                            </p>
                                        </div>
                                        
                                        <!-- Rating -->
                                        <div class="text-center">
                                            <div class="rating-circle <?php echo getRatingColor($review['note']); ?>">
                                                <?php echo number_format($review['note'], 1); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Review Text -->
                                    <?php if (!empty($review['texte'])): ?>
                                        <div class="bg-gray-800/30 rounded-lg p-4 mb-3">
                                            <p class="text-gray-300 leading-relaxed"><?php echo nl2br(htmlspecialchars($review['texte'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Date -->
                                    <p class="text-gray-500 text-sm">
                                        Posté le <?php echo date('d/m/Y à H:i', strtotime($review['date'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-900/50 border-t border-gray-800 py-8">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <p class="text-gray-400">&copy; 2024 CineTrack. Tous droits réservés.</p>
        </div>
    </footer>
    
    <script>
    // Tab functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabBtns = document.querySelectorAll('.tab-btn');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Update buttons
                tabBtns.forEach(b => {
                    b.classList.remove('bg-orange-500', 'text-white');
                    b.classList.add('text-gray-400');
                });
                this.classList.add('bg-orange-500', 'text-white');
                this.classList.remove('text-gray-400');
            });
        });
    });
    </script>
</body>
</html>

<?php
// Helper function for rating colors
function getRatingColor($rating) {
    if ($rating >= 8) return 'border-green-500 text-green-400 bg-green-500/10';
    if ($rating >= 6) return 'border-yellow-500 text-yellow-400 bg-yellow-500/10';
    if ($rating >= 4) return 'border-orange-500 text-orange-400 bg-orange-500/10';
    return 'border-red-500 text-red-400 bg-red-500/10';
}
?>