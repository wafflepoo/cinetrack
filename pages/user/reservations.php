<?php
// user/reservations.php - Gestion des réservations utilisateur
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../../includes/config.conf.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les réservations de l'utilisateur
$reservations = [];
$query = "SELECT * FROM cinema_reservations WHERE user_id = ? ORDER BY reservation_date DESC, reservation_time DESC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
}
$stmt->close();

// Annuler une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'])) {
    $reservation_id = intval($_POST['reservation_id']);
    
    $update_query = "UPDATE cinema_reservations SET reservation_status = 'cancelled' WHERE reservation_id = ? AND user_id = ?";
    $update_stmt = $mysqli->prepare($update_query);
    $update_stmt->bind_param('ii', $reservation_id, $user_id);
    
    if ($update_stmt->execute()) {
        $success_message = "✅ Réservation annulée avec succès !";
        // Rafraîchir les réservations
        header("Location: reservations.php?success=1");
        exit();
    } else {
        $error_message = "❌ Erreur lors de l'annulation.";
    }
    $update_stmt->close();
}

$total_reservations = count($reservations);
$active_reservations = count(array_filter($reservations, function($r) {
    return $r['reservation_status'] === 'confirmed' && 
           strtotime($r['reservation_date'] . ' ' . $r['reservation_time']) >= time();
}));
$total_spent = array_sum(array_column($reservations, 'total_price'));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎟️ Mes Réservations - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/animations.css">
    
    <style>
        /* Hero avec le thème orange/violet */
        .reservations-hero {
            background: linear-gradient(135deg, rgba(255, 140, 0, 0.1) 0%, rgba(120, 0, 255, 0.1) 100%);
            padding: 120px 0 60px;
            position: relative;
            overflow: hidden;
        }
        
        .reservations-hero::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 140, 0, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(120, 0, 255, 0.2) 0%, transparent 50%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .mega-title {
            font-size: clamp(3rem, 10vw, 8rem);
            font-weight: 900;
            letter-spacing: -0.05em;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #ff8c00 0%, #7800ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: float 6s ease-in-out infinite;
            position: relative;
            z-index: 10;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .mega-subtitle {
            font-size: clamp(1.2rem, 3vw, 2rem);
            color: #9ca3af;
            margin-bottom: 3rem;
            animation: fadeInUp 1s ease forwards 0.5s;
            opacity: 0;
            position: relative;
            z-index: 10;
        }
        
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
            from {
                opacity: 0;
                transform: translateY(30px);
            }
        }
        
        /* Statistiques */
        .stat-card {
            background: rgba(30, 30, 40, 0.4);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 140, 0, 0.2);
            border-radius: 25px;
            padding: 2rem;
            transition: all 0.4s;
        }
        
        .stat-card:hover {
            border-color: rgba(255, 140, 0, 0.5);
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(255, 140, 0, 0.2);
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #ff8c00 0%, #ffa500 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, rgba(255, 140, 0, 0.2) 0%, rgba(120, 0, 255, 0.2) 100%);
        }
        
        /* Carte de réservation */
        .reservation-card {
            background: rgba(30, 30, 40, 0.5);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 140, 0, 0.2);
            border-radius: 25px;
            overflow: hidden;
            transition: all 0.4s;
            height: 100%;
        }
        
        .reservation-card:hover {
            border-color: rgba(255, 140, 0, 0.5);
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(255, 140, 0, 0.3);
        }
        
        .reservation-header {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .reservation-code {
            font-family: 'Courier New', monospace;
            font-size: 1.3rem;
            font-weight: 900;
            letter-spacing: 2px;
            background: rgba(0, 0, 0, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 12px;
        }
        
        .status-badge {
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        .status-confirmed {
            background: rgba(34, 197, 94, 0.2);
            color: #10b981;
            border: 2px solid rgba(34, 197, 94, 0.3);
        }
        
        .status-cancelled {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 2px solid rgba(239, 68, 68, 0.3);
        }
        
        .status-completed {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 2px solid rgba(59, 130, 246, 0.3);
        }
        
        .reservation-content {
            padding: 2rem;
        }
        
        .film-poster-container {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            height: 300px;
            margin-bottom: 1.5rem;
        }
        
        .film-poster {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .film-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.95) 100%);
        }
        
        .film-title {
            font-size: 1.8rem;
            font-weight: 900;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .reservation-details {
            margin: 1.5rem 0;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 0.8rem;
            background: rgba(255, 140, 0, 0.05);
            border-radius: 12px;
        }
        
        .detail-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(255, 140, 0, 0.2) 0%, rgba(120, 0, 255, 0.2) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ff8c00;
        }
        
        .price-tag {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            padding: 1rem 1.5rem;
            border-radius: 15px;
            font-size: 1.8rem;
            font-weight: 900;
            text-align: center;
            margin-top: 1rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn-ticket {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-print {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 2px solid rgba(59, 130, 246, 0.3);
        }
        
        .btn-print:hover {
            background: rgba(59, 130, 246, 0.4);
            transform: scale(1.05);
        }
        
        .btn-cancel {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 2px solid rgba(239, 68, 68, 0.3);
        }
        
        .btn-cancel:hover {
            background: rgba(239, 68, 68, 0.4);
            transform: scale(1.05);
        }
        
        .btn-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-disabled:hover {
            transform: none !important;
        }
        
        /* Modal d'annulation */
        .cancel-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .cancel-modal-content {
            background: rgba(30, 30, 40, 0.95);
            border: 3px solid rgba(239, 68, 68, 0.3);
            border-radius: 30px;
            padding: 3rem;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        
        .warning-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(239, 68, 68, 0.4) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: #ef4444;
            font-size: 3rem;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 6rem 2rem;
        }
        
        .empty-icon {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, rgba(255, 140, 0, 0.1) 0%, rgba(120, 0, 255, 0.1) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: #ff8c00;
            font-size: 4rem;
        }
        
        .section-title {
            font-size: clamp(2rem, 5vw, 4rem);
            font-weight: 900;
            text-align: center;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ff8c00 0%, #7800ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Filtres */
        .filter-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }
        
        .filter-btn {
            padding: 1rem 2rem;
            border: 2px solid rgba(255, 140, 0, 0.3);
            background: rgba(30, 30, 40, 0.5);
            border-radius: 50px;
            color: #9ca3af;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            color: white;
            border-color: transparent;
        }
        
        @media (max-width: 768px) {
            .stat-card {
                padding: 1.5rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .reservation-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="gradient-bg text-white">
    <?php include '../includes/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="reservations-hero">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="mega-title">🎟️ MES RÉSERVATIONS</h1>
            <p class="mega-subtitle">Gérez toutes vos séances de cinéma au même endroit</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <!-- Carte Statistique 1 -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-ticket-alt fa-2x" style="color: #ff8c00;"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_reservations; ?></div>
                    <div style="color: #9ca3af; font-size: 1.1rem;">Réservations totales</div>
                </div>
                
                <!-- Carte Statistique 2 -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-play-circle fa-2x" style="color: #7800ff;"></i>
                    </div>
                    <div class="stat-number"><?php echo $active_reservations; ?></div>
                    <div style="color: #9ca3af; font-size: 1.1rem;">Séances à venir</div>
                </div>
                
                <!-- Carte Statistique 3 -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-euro-sign fa-2x" style="color: #10b981;"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($total_spent, 2, ',', ' '); ?>€</div>
                    <div style="color: #9ca3af; font-size: 1.1rem;">Total dépensé</div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Filtres -->
    <section style="padding: 4rem 2rem;">
        <div class="max-w-7xl mx-auto">
            <div class="filter-buttons">
                <button class="filter-btn active" onclick="filterReservations('all')">
                    <i class="fas fa-list"></i> Toutes
                </button>
                <button class="filter-btn" onclick="filterReservations('confirmed')">
                    <i class="fas fa-check-circle"></i> Confirmées
                </button>
                <button class="filter-btn" onclick="filterReservations('upcoming')">
                    <i class="fas fa-clock"></i> À venir
                </button>
                <button class="filter-btn" onclick="filterReservations('cancelled')">
                    <i class="fas fa-times-circle"></i> Annulées
                </button>
                <button class="filter-btn" onclick="filterReservations('completed')">
                    <i class="fas fa-check-double"></i> Terminées
                </button>
            </div>
            
            <?php if (isset($success_message)): ?>
            <div style="background: rgba(34, 197, 94, 0.2); border: 2px solid rgba(34, 197, 94, 0.3); border-radius: 15px; padding: 1.5rem; margin-bottom: 2rem; text-align: center;">
                <i class="fas fa-check-circle" style="color: #10b981; font-size: 1.5rem; margin-right: 0.5rem;"></i>
                <span style="font-size: 1.1rem; font-weight: 600;"><?php echo $success_message; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div style="background: rgba(239, 68, 68, 0.2); border: 2px solid rgba(239, 68, 68, 0.3); border-radius: 15px; padding: 1.5rem; margin-bottom: 2rem; text-align: center;">
                <i class="fas fa-exclamation-circle" style="color: #ef4444; font-size: 1.5rem; margin-right: 0.5rem;"></i>
                <span style="font-size: 1.1rem; font-weight: 600;"><?php echo $error_message; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (empty($reservations)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-film"></i>
                </div>
                <h3 class="section-title" style="margin-bottom: 1rem;">Aucune réservation</h3>
                <p style="color: #9ca3af; font-size: 1.2rem; max-width: 500px; margin: 0 auto 3rem;">
                    Vous n'avez pas encore réservé de séance de cinéma. Explorez nos cinémas et réservez votre première séance !
                </p>
                <a href="../pages/cinemas.php" style="display: inline-block; padding: 1.2rem 3rem; background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%); border-radius: 50px; color: white; font-weight: 700; font-size: 1.1rem; text-decoration: none; transition: all 0.3s; box-shadow: 0 10px 30px rgba(255, 140, 0, 0.4);">
                    <i class="fas fa-search"></i> Découvrir les cinémas
                </a>
            </div>
            <?php else: ?>
            <!-- Grille de réservations -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <?php foreach ($reservations as $reservation): 
                    $is_upcoming = strtotime($reservation['reservation_date'] . ' ' . $reservation['reservation_time']) >= time();
                    $can_cancel = $reservation['reservation_status'] === 'confirmed' && $is_upcoming;
                ?>
                <div class="reservation-card" data-status="<?php echo $reservation['reservation_status']; ?>" 
                     data-date="<?php echo $reservation['reservation_date']; ?> <?php echo $reservation['reservation_time']; ?>">
                    <!-- En-tête -->
                    <div class="reservation-header">
                        <div>
                            <div class="reservation-code"><?php echo htmlspecialchars($reservation['reservation_code']); ?></div>
                            <div style="font-size: 0.9rem; margin-top: 0.5rem;">
                                Réservé le <?php echo date('d/m/Y H:i', strtotime($reservation['created_at'])); ?>
                            </div>
                        </div>
                        <div class="status-badge status-<?php echo $reservation['reservation_status']; ?>">
                            <?php 
                            $status_text = [
                                'confirmed' => 'Confirmée',
                                'cancelled' => 'Annulée',
                                'completed' => 'Terminée'
                            ];
                            echo $status_text[$reservation['reservation_status']];
                            ?>
                        </div>
                    </div>
                    
                    <!-- Contenu -->
                    <div class="reservation-content">
                        <!-- Film -->
                        <div class="film-poster-container">
                            <img src="<?php echo htmlspecialchars($reservation['film_poster'] ?: 'https://via.placeholder.com/400x600'); ?>" 
                                 alt="<?php echo htmlspecialchars($reservation['film_title']); ?>" 
                                 class="film-poster">
                            <div class="film-overlay">
                                <h3 class="film-title"><?php echo htmlspecialchars($reservation['film_title']); ?></h3>
                                <div style="display: flex; gap: 1rem; color: #9ca3af; font-size: 0.9rem;">
                                    <span><i class="fas fa-film"></i> Cinéma</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Détails -->
                        <div class="reservation-details">
                            <!-- Cinéma -->
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 700; font-size: 1.1rem;">
                                        <?php echo htmlspecialchars($reservation['cinema_name']); ?>
                                    </div>
                                    <div style="color: #9ca3af; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($reservation['cinema_address']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Date & Heure -->
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 700; font-size: 1.1rem;">
                                        <?php echo date('d/m/Y', strtotime($reservation['reservation_date'])); ?>
                                        à <?php echo $reservation['reservation_time']; ?>
                                    </div>
                                    <div style="color: #9ca3af; font-size: 0.9rem;">
                                        <?php 
                                        $session_datetime = strtotime($reservation['reservation_date'] . ' ' . $reservation['reservation_time']);
                                        if ($session_datetime > time()) {
                                            $diff = $session_datetime - time();
                                            $days = floor($diff / (60*60*24));
                                            $hours = floor(($diff % (60*60*24)) / (60*60));
                                            echo "Dans $days jours et $hours heures";
                                        } else {
                                            echo "Séance passée";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Places -->
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 700; font-size: 1.1rem;">
                                        <?php echo $reservation['number_tickets']; ?> place<?php echo $reservation['number_tickets'] > 1 ? 's' : ''; ?>
                                    </div>
                                    <div style="color: #9ca3af; font-size: 0.9rem;">
                                        Ticket<?php echo $reservation['number_tickets'] > 1 ? 's' : ''; ?> n°<?php echo substr($reservation['reservation_code'], -6); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Prix -->
                        <div class="price-tag">
                            <?php echo number_format($reservation['total_price'], 2, ',', ' '); ?>€
                        </div>
                        
                        <!-- Actions -->
                        <div class="action-buttons">
                            <button class="btn-ticket btn-print" onclick="printTicket('<?php echo $reservation['reservation_code']; ?>')">
                                <i class="fas fa-print"></i> Imprimer
                            </button>
                            
                            <?php if ($can_cancel): ?>
                            <button class="btn-ticket btn-cancel" onclick="openCancelModal(<?php echo $reservation['reservation_id']; ?>, '<?php echo htmlspecialchars($reservation['film_title']); ?>', '<?php echo $reservation['reservation_date']; ?>', '<?php echo $reservation['reservation_time']; ?>')">
                                <i class="fas fa-times"></i> Annuler
                            </button>
                            <?php else: ?>
                            <button class="btn-ticket btn-cancel btn-disabled">
                                <i class="fas fa-times"></i> Annulation impossible
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Modal d'annulation -->
    <div id="cancelModal" class="cancel-modal">
        <div class="cancel-modal-content">
            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="font-size: 2rem; font-weight: 900; margin-bottom: 1rem; color: white;">Annuler la réservation</h3>
            <p style="color: #9ca3af; margin-bottom: 2rem; font-size: 1.1rem;" id="cancelModalText">
                Êtes-vous sûr de vouloir annuler cette réservation ?
            </p>
            <form method="POST" id="cancelForm">
                <input type="hidden" name="reservation_id" id="cancelReservationId">
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <button type="button" onclick="closeCancelModal()" style="padding: 1rem 2rem; background: rgba(255, 140, 0, 0.2); border: 2px solid rgba(255, 140, 0, 0.3); border-radius: 15px; color: white; font-weight: 700; cursor: pointer; transition: all 0.3s;">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="submit" name="cancel_reservation" style="padding: 1rem 2rem; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; border-radius: 15px; color: white; font-weight: 700; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-check"></i> Oui, annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Filtrage des réservations
        function filterReservations(filter) {
            const cards = document.querySelectorAll('.reservation-card');
            const buttons = document.querySelectorAll('.filter-btn');
            
            // Mettre à jour les boutons actifs
            buttons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.toLowerCase().includes(filter)) {
                    btn.classList.add('active');
                }
            });
            
            // Filtrer les cartes
            cards.forEach(card => {
                const status = card.dataset.status;
                const dateTime = new Date(card.dataset.date);
                const now = new Date();
                const isUpcoming = dateTime >= now;
                
                let show = false;
                
                switch(filter) {
                    case 'all':
                        show = true;
                        break;
                    case 'confirmed':
                        show = status === 'confirmed';
                        break;
                    case 'upcoming':
                        show = status === 'confirmed' && isUpcoming;
                        break;
                    case 'cancelled':
                        show = status === 'cancelled';
                        break;
                    case 'completed':
                        show = (status === 'completed' || (status === 'confirmed' && !isUpcoming));
                        break;
                }
                
                card.style.display = show ? 'block' : 'none';
                card.style.animation = show ? 'fadeInUp 0.5s ease forwards' : 'none';
            });
        }
        
        // Modal d'annulation
        let cancelModal = document.getElementById('cancelModal');
        
        function openCancelModal(reservationId, filmTitle, date, time) {
            const modalText = document.getElementById('cancelModalText');
            const formattedDate = new Date(date + ' ' + time).toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            modalText.innerHTML = `
                Vous êtes sur le point d'annuler votre réservation pour :<br><br>
                <strong style="color: #ff8c00; font-size: 1.2rem;">${filmTitle}</strong><br>
                <span style="color: #9ca3af;">${formattedDate}</span><br><br>
                Cette action est irréversible. Souhaitez-vous continuer ?
            `;
            
            document.getElementById('cancelReservationId').value = reservationId;
            cancelModal.style.display = 'flex';
        }
        
        function closeCancelModal() {
            cancelModal.style.display = 'none';
        }
        
        // Impression du ticket
        function printTicket(reservationCode) {
            const reservationCard = document.querySelector(`[data-reservation="${reservationCode}"]`);
            if (!reservationCard) return;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Ticket - ${reservationCode}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .ticket { border: 3px dashed #ff8c00; padding: 20px; max-width: 400px; margin: 0 auto; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .cinema-name { font-size: 24px; font-weight: bold; color: #ff8c00; }
                        .film-title { font-size: 20px; font-weight: bold; margin: 10px 0; }
                        .qr-code { text-align: center; margin: 20px 0; font-family: monospace; }
                        .code { font-size: 18px; letter-spacing: 3px; font-weight: bold; }
                        .info { margin: 10px 0; }
                        .label { color: #666; }
                        @media print {
                            body { -webkit-print-color-adjust: exact; }
                        }
                    </style>
                </head>
                <body>
                    <div class="ticket">
                        <div class="header">
                            <div class="cinema-name">CINETRACK</div>
                            <div style="color: #666;">Votre billet électronique</div>
                        </div>
                        <div class="film-title">${reservationCard.querySelector('.film-title').textContent}</div>
                        <div class="qr-code">
                            <div style="border: 1px solid #000; padding: 10px; display: inline-block; margin: 10px;">
                                <div style="font-size: 12px;">SCAN ME</div>
                                <div class="code">${reservationCode}</div>
                            </div>
                        </div>
                        <div class="info">
                            <div class="label">Cinéma:</div>
                            <div>${reservationCard.querySelector('[data-cinema]').textContent}</div>
                        </div>
                        <div class="info">
                            <div class="label">Date & Heure:</div>
                            <div>${reservationCard.querySelector('[data-datetime]').textContent}</div>
                        </div>
                        <div class="info">
                            <div class="label">Places:</div>
                            <div>${reservationCard.querySelector('[data-tickets]').textContent}</div>
                        </div>
                        <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
                            Présentez ce billet à l'entrée du cinéma
                        </div>
                    </div>
                    <script>
                        window.onload = function() { window.print(); };
                    </script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
        
        // Animation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            // Animer les cartes une par une
            const cards = document.querySelectorAll('.reservation-card');
            cards.forEach((card, index) => {
                card.style.animation = `fadeInUp 0.5s ease forwards ${index * 0.1}s`;
                card.style.opacity = '0';
            });
            
            // Fermer le modal avec Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeCancelModal();
            });
            
            // Fermer le modal en cliquant à l'extérieur
            cancelModal.addEventListener('click', function(e) {
                if (e.target === cancelModal) closeCancelModal();
            });
        });
    </script>
</body>
</html>