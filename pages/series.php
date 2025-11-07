<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Données statiques des séries
$series = [
    [
        'id_serie' => 1,
        'titre' => 'Stranger Things',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_premiere' => '2016-07-15',
        'description' => 'À Hawkins, en 1983, des événements étranges se produisent après la disparition d\'un jeune garçon.',
        'nb_saisons' => 4,
        'note_moyenne' => 4.6,
        'nb_critiques' => 342
    ],
    [
        'id_serie' => 2,
        'titre' => 'The Last of Us',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_premiere' => '2023-01-15',
        'description' => 'Joel et Ellie traversent les États-Unis post-apocalyptique pour trouver un remède.',
        'nb_saisons' => 1,
        'note_moyenne' => 4.7,
        'nb_critiques' => 289
    ],
    [
        'id_serie' => 3,
        'titre' => 'Wednesday',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_premiere' => '2022-11-23',
        'description' => 'Wednesday Addams enquête sur une série de meurtres à l\'académie Nevermore.',
        'nb_saisons' => 1,
        'note_moyenne' => 4.3,
        'nb_critiques' => 198
    ],
    [
        'id_serie' => 4,
        'titre' => 'The Mandalorian',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_premiere' => '2019-11-12',
        'description' => 'Les aventures de Mando, un chasseur de primes solitaire dans la galaxie Star Wars.',
        'nb_saisons' => 3,
        'note_moyenne' => 4.5,
        'nb_critiques' => 267
    ],
    [
        'id_serie' => 5,
        'titre' => 'Breaking Bad',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_premiere' => '2008-01-20',
        'description' => 'Un professeur de chimie atteint d\'un cancer se lance dans la fabrication de méthamphétamine.',
        'nb_saisons' => 5,
        'note_moyenne' => 4.9,
        'nb_critiques' => 412
    ],
    [
        'id_serie' => 6,
        'titre' => 'Game of Thrones',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_premiere' => '2011-04-17',
        'description' => 'Neuf familles nobles se battent pour le contrôle des terres de Westeros.',
        'nb_saisons' => 8,
        'note_moyenne' => 4.4,
        'nb_critiques' => 523
    ],
    [
        'id_serie' => 7,
        'titre' => 'The Witcher',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_premiere' => '2019-12-20',
        'description' => 'Geralt de Riv, un chasseur de monstres mutant, lutte pour trouver sa place dans le monde.',
        'nb_saisons' => 3,
        'note_moyenne' => 4.2,
        'nb_critiques' => 187
    ],
    [
        'id_serie' => 8,
        'titre' => 'The Crown',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_premiere' => '2016-11-04',
        'description' => 'La vie du règne de la reine Elizabeth II et les événements qui ont façonné le XXe siècle.',
        'nb_saisons' => 6,
        'note_moyenne' => 4.1,
        'nb_critiques' => 156
    ]
];

$total_series = count($series);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8;
$total_pages = ceil($total_series / $limit);
$series_to_display = $series;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toutes les Séries - CineTrack</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../header.php'; ?>
    
    <main>
        <!-- Hero Section Séries -->
        <section class="page-hero">
            <div class="page-hero-background">
                <div class="container">
                    <div class="page-hero-content">
                        <h1>📺 Toutes les Séries</h1>
                        <p>Plongez dans des univers captivants</p>
                        <div class="page-hero-stats">
                            <div class="stat">
                                <span class="stat-number"><?php echo number_format($total_series); ?></span>
                                <span class="stat-label">Séries</span>
                            </div>
                            <div class="stat">
                                <span class="stat-number"><?php echo date('Y'); ?></span>
                                <span class="stat-label">Nouvelles séries</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Filtres et Recherche -->
        <section class="filters-section">
            <div class="container">
                <div class="filters-container">
                    <div class="search-box">
                        <input type="text" id="series-search" placeholder="Rechercher une série...">
                        <i class="fas fa-search"></i>
                    </div>
                    
                    <div class="filter-group">
                        <select id="genre-filter">
                            <option value="">Tous les genres</option>
                            <option value="drame">Drame</option>
                            <option value="comedie">Comédie</option>
                            <option value="science-fiction">Science-Fiction</option>
                            <option value="fantastique">Fantastique</option>
                            <option value="policier">Policier</option>
                        </select>
                        
                        <select id="status-filter">
                            <option value="">Tous les statuts</option>
                            <option value="en_cours">En cours</option>
                            <option value="termine">Terminé</option>
                        </select>
                        
                        <select id="sort-filter">
                            <option value="date_desc">Plus récentes</option>
                            <option value="date_asc">Plus anciennes</option>
                            <option value="title_asc">A-Z</option>
                            <option value="title_desc">Z-A</option>
                            <option value="rating_desc">Mieux notées</option>
                        </select>
                    </div>
                </div>
            </div>
        </section>

        <!-- Grille de Séries -->
        <section class="content-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Notre Collection</h2>
                    <div class="results-count">
                        <?php echo number_format($total_series); ?> séries disponibles
                    </div>
                </div>

                <div class="content-grid" id="series-grid">
                    <?php if(empty($series_to_display)): ?>
                        <div class="no-content">
                            <i class="fas fa-tv"></i>
                            <h3>Aucune série trouvée</h3>
                            <p>Essayez de modifier vos filtres de recherche</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($series_to_display as $serie): ?>
                            <div class="content-card" data-serie-id="<?php echo $serie['id_serie']; ?>">
                                <div class="content-poster-container">
                                    <img src="<?php echo $serie['poster']; ?>" 
                                         alt="<?php echo htmlspecialchars($serie['titre']); ?>" 
                                         class="content-poster"
                                         loading="lazy">
                                    <div class="content-overlay">
                                        <button class="btn-favorite" 
                                                data-id="<?php echo $serie['id_serie']; ?>" 
                                                data-type="serie">
                                            <i class="far fa-heart"></i>
                                        </button>
                                        <button class="btn-play" onclick="viewSerie(<?php echo $serie['id_serie']; ?>)">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <?php if($serie['note_moyenne']): ?>
                                            <div class="content-rating">
                                                <i class="fas fa-star"></i>
                                                <span><?php echo number_format($serie['note_moyenne'], 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="content-info">
                                    <h3 class="content-title" title="<?php echo htmlspecialchars($serie['titre']); ?>">
                                        <?php echo htmlspecialchars($serie['titre']); ?>
                                    </h3>
                                    <div class="content-meta">
                                        <span class="content-year">
                                            <?php echo date('Y', strtotime($serie['date_premiere'])); ?>
                                        </span>
                                        <span class="content-seasons">
                                            <i class="fas fa-layer-group"></i>
                                            <?php echo $serie['nb_saisons'] . ' saison' . ($serie['nb_saisons'] > 1 ? 's' : ''); ?>
                                        </span>
                                    </div>
                                    <?php if($serie['nb_critiques']): ?>
                                        <div class="content-reviews">
                                            <i class="fas fa-comment"></i>
                                            <?php echo $serie['nb_critiques']; ?> critique<?php echo $serie['nb_critiques'] > 1 ? 's' : ''; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination" id="series-pagination">
                            <?php 
                            $current_page = $page;
                            $max_visible = 5;
                            $start_page = max(1, $current_page - floor($max_visible / 2));
                            $end_page = min($total_pages, $start_page + $max_visible - 1);
                            
                            if($start_page > 1) {
                                echo '<a href="series.php?page=1" class="pagination-btn">1</a>';
                                if($start_page > 2) echo '<span class="pagination-ellipsis">...</span>';
                            }
                            
                            for($i = $start_page; $i <= $end_page; $i++) {
                                $active = $i == $current_page ? 'pagination-active' : '';
                                echo "<a href=\"series.php?page=$i\" class=\"pagination-btn $active\">$i</a>";
                            }
                            
                            if($end_page < $total_pages) {
                                if($end_page < $total_pages - 1) echo '<span class="pagination-ellipsis">...</span>';
                                echo "<a href=\"series.php?page=$total_pages\" class=\"pagination-btn\">$total_pages</a>";
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <?php include '../footer.php'; ?>
    
    <script src="../js/script.js"></script>
    <script src="../js/series.js"></script>
</body>
</html>