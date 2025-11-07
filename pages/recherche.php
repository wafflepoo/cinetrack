<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$genre = isset($_GET['genre']) ? $_GET['genre'] : '';
$year = isset($_GET['year']) ? $_GET['year'] : '';

// Données statiques pour la recherche
$films = [
    [
        'type' => 'film',
        'id' => 1,
        'titre' => 'Dune: Partie Deux',
        'poster' => 'https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop',
        'date' => '2024-02-28',
        'description' => 'Paul Atreides s\'unit avec Chani et les Fremen pour mener la révolte.'
    ],
    [
        'type' => 'film',
        'id' => 2,
        'titre' => 'Oppenheimer',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date' => '2023-07-19',
        'description' => 'L\'histoire du physicien J. Robert Oppenheimer.'
    ]
];

$series = [
    [
        'type' => 'serie',
        'id' => 1,
        'titre' => 'Stranger Things',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date' => '2016-07-15',
        'description' => 'À Hawkins, en 1983, des événements étranges se produisent.'
    ],
    [
        'type' => 'serie',
        'id' => 2,
        'titre' => 'The Last of Us',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date' => '2023-01-15',
        'description' => 'Joel et Ellie traversent les États-Unis post-apocalyptique.'
    ]
];

// Simulation de recherche
$results = [];
$total_results = 0;

if (!empty($query)) {
    $all_content = array_merge($films, $series);
    
    foreach ($all_content as $item) {
        // Filtre par type
        if ($type !== 'all' && $item['type'] !== $type) {
            continue;
        }
        
        // Filtre par recherche dans le titre
        if (stripos($item['titre'], $query) !== false) {
            $results[] = $item;
        }
    }
    
    $total_results = count($results);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche - CineTrack</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../header.php'; ?>
    
    <main>
        <!-- Hero Section Recherche -->
        <section class="page-hero">
            <div class="page-hero-background">
                <div class="container">
                    <div class="page-hero-content">
                        <h1>🔍 Recherche Avancée</h1>
                        <p>Trouvez les films et séries qui vous correspondent</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Formulaire de Recherche -->
        <section class="search-section">
            <div class="container">
                <div class="search-form-container">
                    <form method="GET" action="recherche.php" class="search-form" id="search-form">
                        <div class="search-main">
                            <div class="search-input-group">
                                <input type="text" 
                                       name="q" 
                                       id="search-query"
                                       value="<?php echo htmlspecialchars($query); ?>" 
                                       placeholder="Rechercher un film, une série, un réalisateur..."
                                       required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                    Rechercher
                                </button>
                            </div>
                        </div>
                        
                        <div class="search-filters">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="search-type">Type :</label>
                                    <select name="type" id="search-type">
                                        <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>Tous</option>
                                        <option value="films" <?php echo $type === 'films' ? 'selected' : ''; ?>>Films</option>
                                        <option value="series" <?php echo $type === 'series' ? 'selected' : ''; ?>>Séries</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Résultats -->
                <div class="search-results-section">
                    <?php if(!empty($query)): ?>
                        <div class="results-header">
                            <h2 class="section-title">
                                <?php if($total_results > 0): ?>
                                    <?php echo number_format($total_results); ?> résultat<?php echo $total_results > 1 ? 's' : ''; ?> pour "<?php echo htmlspecialchars($query); ?>"
                                <?php else: ?>
                                    Aucun résultat pour "<?php echo htmlspecialchars($query); ?>"
                                <?php endif; ?>
                            </h2>
                        </div>

                        <?php if($total_results > 0): ?>
                            <div class="content-grid search-results-grid">
                                <?php foreach($results as $result): ?>
                                    <div class="content-card" data-type="<?php echo $result['type']; ?>">
                                        <div class="content-poster-container">
                                            <img src="<?php echo $result['poster']; ?>" 
                                                 alt="<?php echo htmlspecialchars($result['titre']); ?>" 
                                                 class="content-poster"
                                                 loading="lazy">
                                            <div class="content-overlay">
                                                <button class="btn-favorite" 
                                                        data-id="<?php echo $result['id']; ?>" 
                                                        data-type="<?php echo $result['type']; ?>">
                                                    <i class="far fa-heart"></i>
                                                </button>
                                                <button class="btn-play" onclick="viewContent('<?php echo $result['type']; ?>', <?php echo $result['id']; ?>)">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <div class="content-type-badge">
                                                    <?php echo $result['type'] === 'film' ? '🎬 Film' : '📺 Série'; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="content-info">
                                            <h3 class="content-title" title="<?php echo htmlspecialchars($result['titre']); ?>">
                                                <?php echo htmlspecialchars($result['titre']); ?>
                                            </h3>
                                            <div class="content-meta">
                                                <span class="content-year">
                                                    <?php echo date('Y', strtotime($result['date'])); ?>
                                                </span>
                                                <span class="content-type">
                                                    <?php echo $result['type'] === 'film' ? '🎬' : '📺'; ?>
                                                </span>
                                            </div>
                                            <?php if($result['description']): ?>
                                                <p class="content-description">
                                                    <?php echo truncateText($result['description'], 100); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-results">
                                <i class="fas fa-search"></i>
                                <h3>Aucun résultat trouvé</h3>
                                <p>Essayez de modifier vos critères de recherche :</p>
                                <ul>
                                    <li>Vérifiez l'orthographe des mots</li>
                                    <li>Utilisez des termes plus généraux</li>
                                    <li>Essayez d'autres filtres</li>
                                </ul>
                                <button class="btn btn-primary" onclick="document.getElementById('search-form').reset();">
                                    <i class="fas fa-redo"></i>
                                    Réinitialiser la recherche
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="search-placeholder">
                            <i class="fas fa-search fa-4x"></i>
                            <h3>Que souhaitez-vous regarder aujourd'hui ?</h3>
                            <p>Utilisez la recherche ci-dessus pour trouver des films et séries</p>
                            
                            <div class="search-suggestions">
                                <h4>Suggestions populaires :</h4>
                                <div class="suggestion-tags">
                                    <a href="recherche.php?q=action&type=all" class="suggestion-tag">Action</a>
                                    <a href="recherche.php?q=comédie&type=all" class="suggestion-tag">Comédie</a>
                                    <a href="recherche.php?q=science+fiction&type=all" class="suggestion-tag">Science-Fiction</a>
                                    <a href="recherche.php?q=2023&type=all" class="suggestion-tag">Sorties 2023</a>
                                    <a href="recherche.php?q=marvel&type=all" class="suggestion-tag">Marvel</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    
    <?php include '../footer.php'; ?>
    
    <script src="../js/script.js"></script>
    <script src="../js/recherche.js"></script>
</body>
</html>