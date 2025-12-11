<?php
// pages/user/reservations.php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Récupérer toutes les réservations de l'utilisateur
$query = "SELECT * FROM cinema_reservations 
          WHERE user_id = ? 
          ORDER BY reservation_date DESC, reservation_time DESC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
}
$stmt->close();

// Statistiques
$total_reservations = count($reservations);
$upcoming_count = 0;
$past_count = 0;
$total_spent = 0;

$today = date('Y-m-d');
foreach ($reservations as $reservation) {
    if ($reservation['reservation_date'] >= $today) {
        $upcoming_count++;
    } else {
        $past_count++;
    }
    $total_spent += $reservation['total_price'];
}

// Gestion de l'annulation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'])) {
    $reservation_id = intval($_POST['reservation_id']);
    
    // Vérifier que la réservation appartient bien à l'utilisateur
    $delete_query = "DELETE FROM cinema_reservations 
                     WHERE id = ? AND user_id = ? AND reservation_date >= ?";
    $delete_stmt = $mysqli->prepare($delete_query);
    $delete_stmt->bind_param("iis", $reservation_id, $user['id'], $today);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success_message'] = "Réservation annulée avec succès!";
    } else {
        $_SESSION['error_message'] = "Erreur lors de l'annulation.";
    }
    $delete_stmt->close();
    
    header("Location: reservations.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #0a0e14 0%, #05080d 100%);
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
        
        .reservation-card {
            background: rgba(30, 30, 40, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 140, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .reservation-card:hover {
            border-color: rgba(255, 140, 0, 0.5);
            box-shadow: 0 10px 30px rgba(255, 140, 0, 0.2);
            transform: translateY(-3px);
        }
        
        .upcoming-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .past-badge {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        }
        
        .cancel-btn {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            transition: all 0.3s ease;
        }
        
        .cancel-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }
        
        .film-poster {
            width: 120px;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .empty-state {
            background: rgba(30, 30, 40, 0.3);
            border: 2px dashed rgba(255, 140, 0, 0.3);
        }
    </style>
</head>

<body class="gradient-bg text-white min-h-screen">
    <?php include '../../includes/header.php'; ?>
    
    <main class="pt-32 pb-16">
        <div class="max-w-7xl mx-auto px-6">
            
            <!-- Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="mb-6 bg-green-500/20 border border-green-500/50 rounded-xl p-4 flex items-center">
                    <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                    <span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="mb-6 bg-red-500/20 border border-red-500/50 rounded-xl p-4 flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
                    <span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Header -->
            <div class="mb-12">
                <h1 class="text-5xl font-black mb-4">
                    <i class="fas fa-ticket-alt text-orange-500 mr-3"></i>
                    Mes Réservations
                </h1>
                <p class="text-xl text-gray-400">
                    Gérez toutes vos réservations de cinéma en un seul endroit
                </p>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
                <div class="stat-card p-6 rounded-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-4xl">
                            <i class="fas fa-ticket-alt text-orange-500"></i>
                        </div>
                        <span class="text-3xl font-black text-orange-500"><?php echo $total_reservations; ?></span>
                    </div>
                    <h3 class="text-gray-400 text-sm uppercase tracking-wider">Total</h3>
                </div>
                
                <div class="stat-card p-6 rounded-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-4xl">
                            <i class="fas fa-calendar-check text-green-500"></i>
                        </div>
                        <span class="text-3xl font-black text-green-500"><?php echo $upcoming_count; ?></span>
                    </div>
                    <h3 class="text-gray-400 text-sm uppercase tracking-wider">À venir</h3>
                </div>
                
                <div class="stat-card p-6 rounded-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-4xl">
                            <i class="fas fa-history text-gray-500"></i>
                        </div>
                        <span class="text-3xl font-black text-gray-500"><?php echo $past_count; ?></span>
                    </div>
                    <h3 class="text-gray-400 text-sm uppercase tracking-wider">Passées</h3>
                </div>
                
                <div class="stat-card p-6 rounded-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-4xl">
                            <i class="fas fa-euro-sign text-purple-500"></i>
                        </div>
                        <span class="text-3xl font-black text-purple-500"><?php echo number_format($total_spent, 2); ?>€</span>
                    </div>
                    <h3 class="text-gray-400 text-sm uppercase tracking-wider">Dépensé</h3>
                </div>
            </div>
            
            <!-- Bouton Nouvelle Réservation -->
            <div class="mb-8 text-center">
                <a href="../cinemas.php" style="background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl font-bold text-lg hover:scale-105 transition-transform shadow-lg">
                    <i class="fas fa-plus-circle"></i>
                    Nouvelle Réservation
                </a>
            </div>
            
            <!-- Filtres -->
            <div class="mb-6 flex gap-4 flex-wrap">
                <button onclick="filterReservations('all')" class="filter-btn active px-4 py-2 rounded-lg bg-orange-500/20 border border-orange-500/30 hover:bg-orange-500/30 transition">
                    Toutes
                </button>
                <button onclick="filterReservations('upcoming')" class="filter-btn px-4 py-2 rounded-lg bg-green-500/20 border border-green-500/30 hover:bg-green-500/30 transition">
                    À venir
                </button>
                <button onclick="filterReservations('past')" class="filter-btn px-4 py-2 rounded-lg bg-gray-500/20 border border-gray-500/30 hover:bg-gray-500/30 transition">
                    Passées
                </button>
            </div>
            
            <!-- Liste des Réservations -->
            <?php if (empty($reservations)): ?>
                <div class="empty-state rounded-2xl p-12 text-center">
                    <i class="fas fa-ticket-alt text-6xl text-gray-600 mb-4"></i>
                    <h3 class="text-2xl font-bold mb-2">Aucune réservation</h3>
                    <p class="text-gray-400 mb-6">Vous n'avez pas encore réservé de séances</p>
                    <a href="../cinemas.php" style="background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold hover:scale-105 transition-transform">
                        <i class="fas fa-search"></i>
                        Découvrir les cinémas
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($reservations as $reservation): 
                        $is_upcoming = $reservation['reservation_date'] >= $today;
                        $is_today = $reservation['reservation_date'] === $today;
                    ?>
                        <div class="reservation-card rounded-2xl p-6 <?php echo $is_upcoming ? 'upcoming' : 'past'; ?>" data-type="<?php echo $is_upcoming ? 'upcoming' : 'past'; ?>">
                            <div class="flex flex-col md:flex-row gap-6">
                                
                                <!-- Poster du Film -->
                                <div class="flex-shrink-0">
                                    <?php if (!empty($reservation['film_poster'])): ?>
                                        <img src="<?php echo htmlspecialchars($reservation['film_poster']); ?>" 
                                             alt="<?php echo htmlspecialchars($reservation['film_title']); ?>"
                                             class="film-poster shadow-lg">
                                    <?php else: ?>
                                        <div class="film-poster bg-gray-800 flex items-center justify-center">
                                            <i class="fas fa-film text-4xl text-gray-600"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Informations -->
                                <div class="flex-1">
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="<?php echo $is_upcoming ? 'upcoming-badge' : 'past-badge'; ?> px-3 py-1 rounded-full text-xs font-bold">
                                                    <?php echo $is_today ? '🔥 AUJOURD\'HUI' : ($is_upcoming ? '📅 À venir' : '✓ Passée'); ?>
                                                </span>
                                                <span class="bg-orange-500/20 px-3 py-1 rounded-full text-xs font-bold text-orange-500">
                                                    Code: <?php echo htmlspecialchars($reservation['reservation_code']); ?>
                                                </span>
                                            </div>
                                            
                                            <h3 class="text-2xl font-bold mb-2">
                                                <?php echo htmlspecialchars($reservation['film_title']); ?>
                                            </h3>
                                            
                                            <div class="flex items-center gap-2 text-gray-400 mb-3">
                                                <i class="fas fa-building text-orange-500"></i>
                                                <span class="font-semibold"><?php echo htmlspecialchars($reservation['cinema_name']); ?></span>
                                            </div>
                                            
                                            <?php if (!empty($reservation['cinema_address'])): ?>
                                                <div class="flex items-center gap-2 text-gray-500 text-sm mb-3">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <span><?php echo htmlspecialchars($reservation['cinema_address']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Détails de la Séance -->
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                        <div class="bg-gray-800/30 rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <i class="fas fa-calendar text-orange-500"></i>
                                                <span class="text-xs text-gray-400">Date</span>
                                            </div>
                                            <p class="font-bold"><?php echo date('d/m/Y', strtotime($reservation['reservation_date'])); ?></p>
                                        </div>
                                        
                                        <div class="bg-gray-800/30 rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <i class="fas fa-clock text-orange-500"></i>
                                                <span class="text-xs text-gray-400">Heure</span>
                                            </div>
                                            <p class="font-bold"><?php echo $reservation['reservation_time']; ?></p>
                                        </div>
                                        
                                        <div class="bg-gray-800/30 rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <i class="fas fa-users text-orange-500"></i>
                                                <span class="text-xs text-gray-400">Places</span>
                                            </div>
                                            <p class="font-bold"><?php echo $reservation['number_tickets']; ?> place<?php echo $reservation['number_tickets'] > 1 ? 's' : ''; ?></p>
                                        </div>
                                        
                                        <div class="bg-gray-800/30 rounded-lg p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <i class="fas fa-euro-sign text-orange-500"></i>
                                                <span class="text-xs text-gray-400">Prix</span>
                                            </div>
                                            <p class="font-bold text-green-500"><?php echo number_format($reservation['total_price'], 2); ?>€</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <?php if ($is_upcoming): ?>
                                        <div class="flex gap-3">
                                            <button onclick="showQRCode('<?php echo $reservation['reservation_code']; ?>')" 
                                                    style="background: linear-gradient(135deg, #7800ff 0%, #5500cc 100%);"
                                                    class="flex-1 px-4 py-2 rounded-lg font-semibold hover:scale-105 transition-transform flex items-center justify-center gap-2">
                                                <i class="fas fa-qrcode"></i>
                                                QR Code
                                            </button>
                                            
                                            <button onclick="confirmCancel(<?php echo $reservation['id']; ?>, '<?php echo addslashes($reservation['film_title']); ?>')" 
                                                    class="cancel-btn px-4 py-2 rounded-lg font-semibold flex items-center justify-center gap-2">
                                                <i class="fas fa-times-circle"></i>
                                                Annuler
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="bg-gray-800/30 rounded-lg p-3 text-center text-gray-500">
                                            <i class="fas fa-check-circle mr-2"></i>
                                            Séance terminée
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Modal QR Code -->
    <div id="qrModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm hidden z-50 flex items-center justify-center">
        <div class="bg-gray-900 rounded-2xl p-8 max-w-md w-full mx-4 border border-orange-500/30">
            <div class="text-center">
                <h3 class="text-2xl font-bold mb-4">Code de Réservation</h3>
                <div class="bg-white p-6 rounded-xl mb-4">
                    <div id="qrCodeContainer" class="flex justify-center"></div>
                </div>
                <p class="text-gray-400 mb-2">Présentez ce code au cinéma</p>
                <p id="qrCodeText" class="text-3xl font-black text-orange-500 mb-6"></p>
                <button onclick="closeQRModal()" style="background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);" class="w-full px-6 py-3 rounded-xl font-bold hover:scale-105 transition-transform">
                    Fermer
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal Confirmation Annulation -->
    <div id="cancelModal" class="fixed inset-0 bg-black/90 backdrop-blur-sm hidden z-50 flex items-center justify-center">
        <div class="bg-gray-900 rounded-2xl p-8 max-w-md w-full mx-4 border border-red-500/30">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
                <h3 class="text-2xl font-bold mb-4">Annuler la réservation ?</h3>
                <p class="text-gray-400 mb-6">
                    Êtes-vous sûr de vouloir annuler votre réservation pour<br>
                    <span id="cancelFilmTitle" class="font-bold text-orange-500"></span> ?
                </p>
                <form method="POST" class="flex gap-3">
                    <input type="hidden" name="reservation_id" id="cancelReservationId">
                    <button type="button" onclick="closeCancelModal()" class="flex-1 px-6 py-3 rounded-xl font-bold bg-gray-800 hover:bg-gray-700 transition">
                        Non, garder
                    </button>
                    <button type="submit" name="cancel_reservation" class="cancel-btn flex-1 px-6 py-3 rounded-xl font-bold">
                        Oui, annuler
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        // Filtrage
        function filterReservations(type) {
            const cards = document.querySelectorAll('.reservation-card');
            const buttons = document.querySelectorAll('.filter-btn');
            
            buttons.forEach(btn => btn.classList.remove('active', 'bg-orange-500/40'));
            event.target.classList.add('active', 'bg-orange-500/40');
            
            cards.forEach(card => {
                if (type === 'all') {
                    card.style.display = 'block';
                } else {
                    card.style.display = card.dataset.type === type ? 'block' : 'none';
                }
            });
        }
        
        // QR Code
        function showQRCode(code) {
            document.getElementById('qrModal').classList.remove('hidden');
            document.getElementById('qrCodeText').textContent = code;
            
            const container = document.getElementById('qrCodeContainer');
            container.innerHTML = '';
            
            new QRCode(container, {
                text: code,
                width: 200,
                height: 200,
                colorDark: "#000000",
                colorLight: "#ffffff"
            });
        }
        
        function closeQRModal() {
            document.getElementById('qrModal').classList.add('hidden');
        }
        
        // Annulation
        function confirmCancel(id, title) {
            document.getElementById('cancelReservationId').value = id;
            document.getElementById('cancelFilmTitle').textContent = title;
            document.getElementById('cancelModal').classList.remove('hidden');
        }
        
        function closeCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
        }
        
        // Fermer modals avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeQRModal();
                closeCancelModal();
            }
        });
    </script>
</body>
</html>