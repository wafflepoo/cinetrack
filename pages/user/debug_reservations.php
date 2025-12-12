<?php
// debug_reservations.php - À placer à la racine ou dans /pages/user/
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Récupérer UNE réservation pour debug
$query = "SELECT * FROM cinema_reservations WHERE user_id = ? LIMIT 1";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title> Debug Réservations</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #0a0e14;
            color: #fff;
            padding: 2rem;
            line-height: 1.6;
        }
        .debug-box {
            background: rgba(30, 30, 40, 0.8);
            border: 2px solid #ff8c00;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .label {
            color: #ff8c00;
            font-weight: bold;
        }
        .value {
            color: #4ade80;
            word-break: break-all;
        }
        .error {
            color: #ef4444;
            font-weight: bold;
        }
        .success {
            color: #4ade80;
            font-weight: bold;
        }
        pre {
            background: #000;
            padding: 1rem;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic des Réservations</h1>
    
    <?php if ($reservation): ?>
        
        <div class="debug-box">
            <h2>📊 Données de la Réservation</h2>
            <pre><?php print_r($reservation); ?></pre>
        </div>
        
        <div class="debug-box">
            <h2>🖼️ Colonne film_poster</h2>
            <p><span class="label">Valeur brute:</span></p>
            <p class="value"><?php echo htmlspecialchars($reservation['film_poster'] ?? 'NULL'); ?></p>
            
            <p><span class="label">Est vide ?</span> 
                <?php if (empty($reservation['film_poster'])): ?>
                    <span class="error">✗ OUI - C'EST LE PROBLÈME !</span>
                <?php else: ?>
                    <span class="success">✓ NON - La valeur existe</span>
                <?php endif; ?>
            </p>
            
            <p><span class="label">Type de données:</span> 
                <span class="value"><?php echo gettype($reservation['film_poster']); ?></span>
            </p>
            
            <p><span class="label">Longueur:</span> 
                <span class="value"><?php echo strlen($reservation['film_poster'] ?? ''); ?> caractères</span>
            </p>
        </div>
        
        <div class="debug-box">
            <h2>🎬 Informations du Film</h2>
            <p><span class="label">Titre:</span> <span class="value"><?php echo htmlspecialchars($reservation['film_title']); ?></span></p>
            <p><span class="label">ID Film:</span> <span class="value"><?php echo $reservation['film_id']; ?></span></p>
        </div>
        
        <?php if (!empty($reservation['film_poster'])): ?>
            <div class="debug-box">
                <h2>🖼️ Test d'Affichage de l'Image</h2>
                <p><span class="label">URL de l'image:</span></p>
                <p class="value"><?php echo htmlspecialchars($reservation['film_poster']); ?></p>
                
                <p><span class="label">Aperçu:</span></p>
                <img src="<?php echo htmlspecialchars($reservation['film_poster']); ?>" 
                     alt="Test poster" 
                     style="max-width: 300px; border: 2px solid #ff8c00; border-radius: 10px;"
                     onerror="this.parentElement.innerHTML += '<p class=\'error\'>❌ ERREUR : Image non chargée (404 ou URL invalide)</p>'">
            </div>
        <?php else: ?>
            <div class="debug-box">
                <h2 class="error">❌ PROBLÈME IDENTIFIÉ</h2>
                <p>La colonne <code>film_poster</code> est <strong>VIDE</strong> dans la base de données.</p>
                <p>Cela signifie que le poster n'est pas transmis lors de la réservation.</p>
            </div>
        <?php endif; ?>
        
        <div class="debug-box">
            <h2>🔧 Structure de la Table</h2>
            <?php
            $desc = $mysqli->query("DESCRIBE cinema_reservations");
            echo "<pre>";
            while ($col = $desc->fetch_assoc()) {
                echo sprintf(
                    "%-20s %-15s %-10s %-10s\n",
                    $col['Field'],
                    $col['Type'],
                    $col['Null'],
                    $col['Key']
                );
            }
            echo "</pre>";
            ?>
        </div>
        
        <div class="debug-box">
            <h2>📝 Solution</h2>
            <?php if (empty($reservation['film_poster'])): ?>
                <p class="error">Le poster n'est PAS enregistré en base.</p>
                <ol style="margin-left: 2rem;">
                    <li>Vérifiez que le JavaScript dans <code>cinemas.php</code> envoie bien <code>film_poster</code></li>
                    <li>Vérifiez les logs dans <code>reserve_cinema.php</code></li>
                    <li>Testez avec la console réseau du navigateur (F12 → Network)</li>
                </ol>
            <?php else: ?>
                <p class="success">Le poster EST enregistré ! Vérifiez l'affichage dans reservations.php</p>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <div class="debug-box">
            <p class="error">❌ Aucune réservation trouvée pour cet utilisateur.</p>
            <p>Créez d'abord une réservation depuis la page cinémas.</p>
        </div>
    <?php endif; ?>
    
    <div class="debug-box">
        <h2>🧪 Test API avec Console JavaScript</h2>
        <p>Ouvrez la console (F12) et collez ce code pour tester l'API :</p>
        <pre style="background: #1a1a2e; padding: 1rem; border-radius: 5px;">
const testData = new FormData();
testData.append('cinema_id', 'test_123');
testData.append('cinema_name', 'Test Cinema');
testData.append('cinema_address', '123 Rue Test');
testData.append('film_id', '12345');
testData.append('film_title', 'Film Test');
testData.append('film_poster', 'https://image.tmdb.org/t/p/w500/test.jpg');
testData.append('reservation_date', '2024-12-15');
testData.append('reservation_time', '20:30');
testData.append('number_tickets', '2');
testData.append('total_price', '25.00');

fetch('/api/reserve_cinema.php', {
    method: 'POST',
    body: testData
}).then(r => r.json()).then(console.log);
        </pre>
    </div>
    
    <div style="text-align: center; margin-top: 2rem;">
        <a href="reservations.php" style="background: #ff8c00; color: white; padding: 1rem 2rem; border-radius: 10px; text-decoration: none; font-weight: bold;">
            ← Retour aux Réservations
        </a>
    </div>
</body>
</html>