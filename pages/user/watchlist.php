<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();
$user = getCurrentUser();

// Get watchlist items
$watchlist = [];
$query = "SELECT s.*, 
          f.titre as film_titre, f.poster as film_poster, f.date_sortie as film_date,
          se.titre as serie_titre, se.poster as serie_poster, se.date_premiere as serie_date
          FROM SELECTION s
          LEFT JOIN FILM f ON s.id_film = f.id_film
          LEFT JOIN SERIE se ON s.id_serie = se.id_serie
          WHERE s.id_utilisateur = ?
          ORDER BY s.date_ajout DESC";
          
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$watchlist = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Watchlist - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico">
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
        .stat-badge {
            @apply bg-gray-700/50 hover:bg-gray-600/50 px-4 py-2 rounded-lg transition border border-transparent;
        }
        .stat-badge.active {
            @apply bg-orange-500/20 border-orange-500/50 text-orange-400;
        }
        .watchlist-card {
            @apply relative transition-transform hover:scale-105;
        }
        .status-badge {
            @apply px-2 py-1 rounded-full text-xs font-semibold backdrop-blur-sm;
        }
        .status-badge.watching {
            @apply bg-blue-500/20 text-blue-300 border border-blue-500/30;
        }
        .status-badge.completed {
            @apply bg-green-500/20 text-green-300 border border-green-500/30;
        }
        .status-badge.plan_to_watch {
            @apply bg-yellow-500/20 text-yellow-300 border border-yellow-500/30;
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
        <div class="max-w-7xl mx-auto px-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-black mb-2">Ma Watchlist</h1>
                <p class="text-gray-400 text-lg">Gérez vos films et séries</p>
            </div>
            
            <!-- Stats & Filters -->
            <div class="glass p-6 rounded-2xl mb-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <!-- Stats -->
                    <div class="flex flex-wrap gap-4">
                        <div class="stat-badge active">
                            <span class="flex items-center space-x-2">
                                <span class="font-bold"><?php echo count($watchlist); ?></span>
                                <span>Tous</span>
                            </span>
                        </div>
                        <div class="stat-badge">
                            <span class="flex items-center space-x-2">
                                <i class="fas fa-play text-blue-400"></i>
                                <span class="font-bold"><?php echo count(array_filter($watchlist, fn($item) => $item['type_status'] === 'watching')); ?></span>
                                <span>En cours</span>
                            </span>
                        </div>
                        <div class="stat-badge">
                            <span class="flex items-center space-x-2">
                                <i class="fas fa-check text-green-400"></i>
                                <span class="font-bold"><?php echo count(array_filter($watchlist, fn($item) => $item['type_status'] === 'completed')); ?></span>
                                <span>Terminés</span>
                            </span>
                        </div>
                        <div class="stat-badge">
                            <span class="flex items-center space-x-2">
                                <i class="fas fa-clock text-yellow-400"></i>
                                <span class="font-bold"><?php echo count(array_filter($watchlist, fn($item) => $item['type_status'] === 'plan_to_watch')); ?></span>
                                <span>À voir</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Watchlist Grid -->
            <?php if (empty($watchlist)): ?>
                <div class="text-center py-16 glass rounded-2xl">
                    <i class="fas fa-bookmark text-6xl text-gray-600 mb-4"></i>
                    <h3 class="text-2xl font-bold text-gray-400 mb-2">Watchlist vide</h3>
                    <p class="text-gray-500 mb-6">Commencez à ajouter des films et séries à votre watchlist!</p>
                    <a href="../pages/films.php" class="btn-primary px-6 py-3 rounded-lg font-semibold">
                        <i class="fas fa-search mr-2"></i>Découvrir des contenus
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                    <?php foreach ($watchlist as $item): ?>
                        <?php
                        $is_film = !empty($item['film_titre']);
                        $title = $is_film ? $item['film_titre'] : $item['serie_titre'];
                        $poster = $is_film ? $item['film_poster'] : $item['serie_poster'];
                        $date = $is_film ? $item['film_date'] : $item['serie_date'];
                        $media_type = $is_film ? 'film' : 'serie';
                        ?>
                        
                        <div class="watchlist-card group">
                            <div class="relative overflow-hidden rounded-xl mb-3">
                                <?php if ($poster): ?>
                                    <img src="<?php echo TMDB_IMAGE_BASE_URL . 'w300' . $poster; ?>" 
                                         alt="<?php echo htmlspecialchars($title); ?>"
                                         class="w-full aspect-[2/3] object-cover group-hover:scale-105 transition duration-300">
                                <?php else: ?>
                                    <div class="w-full aspect-[2/3] bg-gray-700/50 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-film text-gray-400 text-4xl"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Status Badge -->
                                <div class="absolute top-2 left-2">
                                    <span class="status-badge <?php echo $item['type_status']; ?>">
                                        <?php 
                                        $status_icons = [
                                            'watching' => 'fa-play',
                                            'completed' => 'fa-check',
                                            'plan_to_watch' => 'fa-clock'
                                        ];
                                        ?>
                                        <i class="fas <?php echo $status_icons[$item['type_status']]; ?> mr-1"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $item['type_status'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Info -->
                            <div class="text-center">
                                <h3 class="font-semibold text-sm mb-1 line-clamp-2"><?php echo htmlspecialchars($title); ?></h3>
                                <p class="text-gray-400 text-xs">
                                    <?php echo $media_type === 'film' ? 'Film' : 'Série'; ?>
                                    <?php if ($date): ?>
                                        • <?php echo date('Y', strtotime($date)); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include '../../includes/footer.php'; ?>
    
    <style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    </style>
</body>
</html>