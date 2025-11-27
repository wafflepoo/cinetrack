<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();
$user = getCurrentUser();

// Get user lists
$user_lists = [];
$query = "SELECT l.*, 
          COUNT(sl.id_selection) as item_count
          FROM LISTE l
          LEFT JOIN SELECTION_LISTE sl ON l.id_liste = sl.id_liste
          WHERE l.id_utilisateur = ?
          GROUP BY l.id_liste
          ORDER BY l.date_creation DESC";
          
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$user_lists = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Listes - CineTrack</title>
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
        .list-card {
            @apply border border-transparent transition-all duration-300;
        }
        .list-card:hover {
            @apply border-orange-500/20 shadow-2xl;
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
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8">
                <div>
                    <h1 class="text-4xl font-black mb-2">Mes Listes</h1>
                    <p class="text-gray-400 text-lg">Organisez vos films et séries en listes personnalisées</p>
                </div>
                
                <!-- Create List Button -->
                <button onclick="openModal()" class="btn-primary px-6 py-3 rounded-lg font-semibold mt-4 md:mt-0">
                    <i class="fas fa-plus mr-2"></i>Créer une liste
                </button>
            </div>
            
            <!-- Lists Grid -->
            <?php if (empty($user_lists)): ?>
                <div class="text-center py-16 glass rounded-2xl">
                    <i class="fas fa-list text-6xl text-gray-600 mb-4"></i>
                    <h3 class="text-2xl font-bold text-gray-400 mb-2">Aucune liste créée</h3>
                    <p class="text-gray-500 mb-6">Créez votre première liste pour organiser vos contenus!</p>
                    <button onclick="openModal()" class="btn-primary px-6 py-3 rounded-lg font-semibold">
                        <i class="fas fa-plus mr-2"></i>Créer ma première liste
                    </button>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($user_lists as $list): ?>
                        <div class="list-card glass p-6 rounded-2xl group hover:scale-105 transition-transform">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($list['nom_liste']); ?></h3>
                                    <p class="text-gray-400 text-sm">
                                        <?php echo $list['item_count']; ?> élément(s)
                                        <?php if ($list['type']): ?>
                                            • <?php echo ucfirst($list['type']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- List Preview -->
                            <div class="mb-4">
                                <div class="grid grid-cols-3 gap-2">
                                    <!-- Placeholder for list items preview -->
                                    <?php for ($i = 0; $i < min(3, $list['item_count']); $i++): ?>
                                        <div class="aspect-[2/3] bg-gray-700/50 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-film text-gray-500"></i>
                                        </div>
                                    <?php endfor; ?>
                                    <?php for ($i = $list['item_count']; $i < 3; $i++): ?>
                                        <div class="aspect-[2/3] bg-gray-800/30 rounded-lg border-2 border-dashed border-gray-600 flex items-center justify-center">
                                            <i class="fas fa-plus text-gray-500"></i>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <!-- Footer -->
                            <div class="flex items-center justify-between text-sm text-gray-400">
                                <span>Créé le <?php echo date('d/m/Y', strtotime($list['date_creation'])); ?></span>
                                <a href="list-detail.php?id=<?php echo $list['id_liste']; ?>" class="text-orange-500 hover:text-orange-400 font-semibold">
                                    Voir la liste <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Create List Modal -->
    <div id="createListModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 opacity-0 invisible transition-all duration-300">
        <div class="glass p-8 rounded-2xl border border-gray-700 shadow-2xl max-w-md w-full mx-4 transform scale-95 transition-transform">
            <h2 class="text-2xl font-bold mb-6">Créer une nouvelle liste</h2>
            
            <form method="POST" action="create-list.php">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nom de la liste</label>
                        <input type="text" name="list_name" required
                               class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition"
                               placeholder="Ma liste de films préférés">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Type de liste</label>
                        <select name="list_type" class="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-orange-500 transition">
                            <option value="films">Films seulement</option>
                            <option value="series">Séries seulement</option>
                            <option value="mixed">Mixte (films et séries)</option>
                            <option value="favorites">Favoris</option>
                            <option value="watchlist">À regarder</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex space-x-4 mt-8">
                    <button type="button" onclick="closeModal()" class="flex-1 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg font-semibold transition">
                        Annuler
                    </button>
                    <button type="submit" class="flex-1 py-3 btn-primary rounded-lg font-semibold">
                        Créer
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-gray-900/50 border-t border-gray-800 py-8">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <p class="text-gray-400">&copy; 2024 CineTrack. Tous droits réservés.</p>
        </div>
    </footer>
    
    <script>
    function openModal() {
        const modal = document.getElementById('createListModal');
        modal.classList.remove('opacity-0', 'invisible', 'scale-95');
        modal.classList.add('opacity-100', 'visible', 'scale-100');
    }
    
    function closeModal() {
        const modal = document.getElementById('createListModal');
        modal.classList.remove('opacity-100', 'visible', 'scale-100');
        modal.classList.add('opacity-0', 'invisible', 'scale-95');
    }
    
    // Close modal on outside click
    document.getElementById('createListModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    </script>
</body>
</html>