<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Données statiques des films
$films = [
    [
        'id_film' => 1,
        'titre' => 'Dune: Partie Deux',
        'poster' => 'https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop',
        'date_sortie' => '2024-02-28',
        'description' => 'Paul Atreides s\'unit avec Chani et les Fremen pour mener la révolte contre ceux qui ont détruit sa famille.',
        'note_moyenne' => 4.5,
        'nb_critiques' => 128,
        'duree' => 166,
        'realisateur' => 'Denis Villeneuve'
    ],
    [
        'id_film' => 2,
        'titre' => 'Oppenheimer',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_sortie' => '2023-07-19',
        'description' => 'L\'histoire du physicien J. Robert Oppenheimer et son rôle dans le développement de la bombe atomique.',
        'note_moyenne' => 4.7,
        'nb_critiques' => 215,
        'duree' => 180,
        'realisateur' => 'Christopher Nolan'
    ],
    [
        'id_film' => 3,
        'titre' => 'Barbie',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_sortie' => '2023-07-19',
        'description' => 'Barbie quitte le monde parfait de Barbie Land pour découvrir le monde réel.',
        'note_moyenne' => 4.2,
        'nb_critiques' => 189,
        'duree' => 114,
        'realisateur' => 'Greta Gerwig'
    ],
    [
        'id_film' => 4,
        'titre' => 'Spider-Man: Across the Spider-Verse',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_sortie' => '2023-05-31',
        'description' => 'Miles Morales replonge dans le Multivers pour retrouver Gwen Stacy.',
        'note_moyenne' => 4.6,
        'nb_critiques' => 167,
        'duree' => 140,
        'realisateur' => 'Joaquim Dos Santos'
    ],
    [
        'id_film' => 5,
        'titre' => 'The Batman',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_sortie' => '2022-03-02',
        'description' => 'Batman enquête sur la corruption à Gotham City et affaire le Tueur de l\'Énigme.',
        'note_moyenne' => 4.3,
        'nb_critiques' => 203,
        'duree' => 176,
        'realisateur' => 'Matt Reeves'
    ],
    [
        'id_film' => 6,
        'titre' => 'Top Gun: Maverick',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_sortie' => '2022-05-18',
        'description' => 'Après plus de 30 ans de service, Pete "Maverick" Mitchell forme de jeunes pilotes.',
        'note_moyenne' => 4.8,
        'nb_critiques' => 178,
        'duree' => 131,
        'realisateur' => 'Joseph Kosinski'
    ],
    [
        'id_film' => 7,
        'titre' => 'Avatar: The Way of Water',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_sortie' => '2022-12-14',
        'description' => 'Jake Sully et Ney\'tiri forment une famille sur Pandora.',
        'note_moyenne' => 4.1,
        'nb_critiques' => 145,
        'duree' => 192,
        'realisateur' => 'James Cameron'
    ],
    [
        'id_film' => 8,
        'titre' => 'Black Panther: Wakanda Forever',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'date_sortie' => '2022-11-09',
        'description' => 'Le peuple du Wakanda se bat pour protéger son nation.',
        'note_moyenne' => 4.0,
        'nb_critiques' => 132,
        'duree' => 161,
        'realisateur' => 'Ryan Coogler'
    ]
];

$total_films = count($films);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8;
$total_pages = ceil($total_films / $limit);
$films_to_display = $films;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tous les Films - CineTrack</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../header.php'; ?>
    
    <main>
        <!-- Hero Section Films -->
        <section class="page-hero">
            <div class="page-hero-background">
                <div class="container">
                    <div class="page-hero-content">
                        <h1>🎬 Tous les Films</h1>
                        <p>Découvrez notre vaste collection de films</p>
                        <div class="page-hero-stats">
                            <div class="stat">
                                <span class="stat-number"><?php echo number_format($total_films); ?></span>
                                <span class="stat-label">Films</span>
                            </div>
                            <div class="stat">
                                <span class="stat-number"><?php echo date('Y'); ?></span>
                                <span class="stat-label">Année en cours</span>
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
                        <input type="text" id="film-search" placeholder="Rechercher un film...">
                        <i class="fas fa-search"></i>
                    </div>
                    
                    <div class="filter-group">
                        <select id="genre-filter">
                            <option value="">Tous les genres</option>
                            <option value="action">Action</option>
                            <option value="drame">Drame</option>
                            <option value="comedie">Comédie</option>
                            <option value="science-fiction">Science-Fiction</option>
                            <option value="thriller">Thriller</option>
                        </select>
                        
                        <select id="year-filter">
                            <option value="">Toutes les années</option>
                            <?php for($year = date('Y'); $year >= 1950; $year--): ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endfor; ?>
                        </select>
                        
                        <select id="sort-filter">
                            <option value="date_desc">Plus récents</option>
                            <option value="date_asc">Plus anciens</option>
                            <option value="title_asc">A-Z</option>
                            <option value="title_desc">Z-A</option>
                            <option value="rating_desc">Mieux notés</option>
                        </select>
                    </div>
                </div>
            </div>
        </section>

        <!-- Grille de Films -->
        <section class="content-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Notre Collection</h2>
                    <div class="results-count">
                        <?php echo number_format($total_films); ?> films disponibles
                    </div>
                </div>

                <div class="content-grid" id="films-grid">
                    <?php if(empty($films_to_display)): ?>
                        <div class="no-content">
                            <i class="fas fa-film"></i>
                            <h3>Aucun film trouvé</h3>
                            <p>Essayez de modifier vos filtres de recherche</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($films_to_display as $film): ?>
                            <div class="content-card" data-film-id="<?php echo $film['id_film']; ?>">
                                <div class="content-poster-container">
                                    <img src="<?php echo $film['poster']; ?>" 
                                         alt="<?php echo htmlspecialchars($film['titre']); ?>" 
                                         class="content-poster"
                                         loading="lazy">
                                    <div class="content-overlay">
                                        <button class="btn-favorite" 
                                                data-id="<?php echo $film['id_film']; ?>" 
                                                data-type="film">
                                            <i class="far fa-heart"></i>
                                        </button>
                                        <button class="btn-play" onclick="viewFilm(<?php echo $film['id_film']; ?>)">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <?php if($film['note_moyenne']): ?>
                                            <div class="content-rating">
                                                <i class="fas fa-star"></i>
                                                <span><?php echo number_format($film['note_moyenne'], 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="content-info">
                                    <h3 class="content-title" title="<?php echo htmlspecialchars($film['titre']); ?>">
                                        <?php echo htmlspecialchars($film['titre']); ?>
                                    </h3>
                                    <div class="content-meta">
                                        <span class="content-year">
                                            <?php echo date('Y', strtotime($film['date_sortie'])); ?>
                                        </span>
                                        <span class="content-runtime">
                                            <i class="fas fa-clock"></i> <?php echo $film['duree']; ?>min
                                        </span>
                                    </div>
                                    <div class="content-director">
                                        <i class="fas fa-user"></i> <?php echo $film['realisateur']; ?>
                                    </div>
                                    <?php if($film['nb_critiques']): ?>
                                        <div class="content-reviews">
                                            <i class="fas fa-comment"></i>
                                            <?php echo $film['nb_critiques']; ?> critique<?php echo $film['nb_critiques'] > 1 ? 's' : ''; ?>
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
                        <div class="pagination" id="films-pagination">
                            <?php 
                            $current_page = $page;
                            $max_visible = 5;
                            $start_page = max(1, $current_page - floor($max_visible / 2));
                            $end_page = min($total_pages, $start_page + $max_visible - 1);
                            
                            if($current_page > 1): ?>
                                <a href="films.php?page=<?php echo $current_page - 1; ?>" class="pagination-btn pagination-prev">
                                    <i class="fas fa-chevron-left"></i> Précédent
                                </a>
                            <?php endif;
                            
                            if($start_page > 1): ?>
                                <a href="films.php?page=1" class="pagination-btn">1</a>
                                <?php if($start_page > 2): ?>
                                    <span class="pagination-ellipsis">...</span>
                                <?php endif;
                            endif;
                            
                            for($i = $start_page; $i <= $end_page; $i++):
                                $active = $i == $current_page ? 'pagination-active' : ''; ?>
                                <a href="films.php?page=<?php echo $i; ?>" class="pagination-btn <?php echo $active; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor;
                            
                            if($end_page < $total_pages):
                                if($end_page < $total_pages - 1): ?>
                                    <span class="pagination-ellipsis">...</span>
                                <?php endif; ?>
                                <a href="films.php?page=<?php echo $total_pages; ?>" class="pagination-btn">
                                    <?php echo $total_pages; ?>
                                </a>
                            <?php endif;
                            
                            if($current_page < $total_pages): ?>
                                <a href="films.php?page=<?php echo $current_page + 1; ?>" class="pagination-btn pagination-next">
                                    Suivant <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <?php include '../footer.php'; ?>
    
    <script src="../js/script.js"></script>
    <script src="../js/films.js"></script>
</body>
</html>