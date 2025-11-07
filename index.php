<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Données statiques pour la page d'accueil
$popular_movies = [
    [
        'id' => 1,
        'titre' => 'Dune: Partie Deux',
        'poster' => 'https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop',
        'annee' => '2024',
        'note' => '4.5',
        'type' => 'film'
    ],
    [
        'id' => 2,
        'titre' => 'Oppenheimer',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'annee' => '2023',
        'note' => '4.7',
        'type' => 'film'
    ],
    [
        'id' => 3,
        'titre' => 'Barbie',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'annee' => '2023',
        'note' => '4.2',
        'type' => 'film'
    ],
    [
        'id' => 4,
        'titre' => 'Spider-Man: Across the Spider-Verse',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'annee' => '2023',
        'note' => '4.6',
        'type' => 'film'
    ],
    [
        'id' => 5,
        'titre' => 'The Batman',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'annee' => '2022',
        'note' => '4.3',
        'type' => 'film'
    ],
    [
        'id' => 6,
        'titre' => 'Top Gun: Maverick',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'annee' => '2022',
        'note' => '4.8',
        'type' => 'film'
    ]
];

$popular_series = [
    [
        'id' => 1,
        'titre' => 'Stranger Things',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'annee' => '2016',
        'note' => '4.6',
        'type' => 'serie'
    ],
    [
        'id' => 2,
        'titre' => 'The Last of Us',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'annee' => '2023',
        'note' => '4.7',
        'type' => 'serie'
    ],
    [
        'id' => 3,
        'titre' => 'Wednesday',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'annee' => '2022',
        'note' => '4.3',
        'type' => 'serie'
    ],
    [
        'id' => 4,
        'titre' => 'The Mandalorian',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'annee' => '2019',
        'note' => '4.5',
        'type' => 'serie'
    ],
    [
        'id' => 5,
        'titre' => 'Breaking Bad',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'annee' => '2008',
        'note' => '4.9',
        'type' => 'serie'
    ],
    [
        'id' => 6,
        'titre' => 'Game of Thrones',
        'poster' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
        'annee' => '2011',
        'note' => '4.4',
        'type' => 'serie'
    ]
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineTrack - Votre plateforme cinéma et séries</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <!-- Section Hero -->
        <section class="hero">
            <div class="hero-background">
                <div class="hero-content">
                    <h1>Bienvenue sur CineTrack</h1>
                    <p>Découvrez, partagez et discutez de vos films et séries préférés</p>
                    <div class="hero-buttons">
                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <a href="pages/inscription.php" class="btn btn-primary">Commencer</a>
                            <a href="pages/connexion.php" class="btn btn-secondary">Se connecter</a>
                        <?php else: ?>
                            <a href="pages/recherche.php" class="btn btn-primary">Découvrir</a>
                            <a href="pages/listes.php" class="btn btn-secondary">Mes listes</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Films populaires -->
        <section class="content-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Films populaires</h2>
                    <a href="pages/films.php" class="section-link">
                        Voir tout <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="content-grid" id="popular-movies">
                    <?php foreach($popular_movies as $movie): ?>
                        <div class="content-card" onclick="viewContent('film', <?php echo $movie['id']; ?>)">
                            <div class="content-poster-container">
                                <img src="<?php echo $movie['poster']; ?>" 
                                     alt="<?php echo htmlspecialchars($movie['titre']); ?>" 
                                     class="content-poster"
                                     loading="lazy">
                                <div class="content-overlay">
                                    <button class="btn-favorite" 
                                            data-id="<?php echo $movie['id']; ?>" 
                                            data-type="film"
                                            onclick="event.stopPropagation(); toggleFavorite(<?php echo $movie['id']; ?>, 'film', this)">
                                        <i class="far fa-heart"></i>
                                    </button>
                                    <button class="btn-play">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <div class="content-rating">
                                        <i class="fas fa-star"></i>
                                        <span><?php echo $movie['note']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="content-info">
                                <h3 class="content-title" title="<?php echo htmlspecialchars($movie['titre']); ?>">
                                    <?php echo htmlspecialchars($movie['titre']); ?>
                                </h3>
                                <div class="content-meta">
                                    <span class="content-year"><?php echo $movie['annee']; ?></span>
                                    <span class="content-type">🎬 Film</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Section Séries populaires -->
        <section class="content-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Séries populaires</h2>
                    <a href="pages/series.php" class="section-link">
                        Voir tout <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="content-grid" id="popular-series">
                    <?php foreach($popular_series as $serie): ?>
                        <div class="content-card" onclick="viewContent('serie', <?php echo $serie['id']; ?>)">
                            <div class="content-poster-container">
                                <img src="<?php echo $serie['poster']; ?>" 
                                     alt="<?php echo htmlspecialchars($serie['titre']); ?>" 
                                     class="content-poster"
                                     loading="lazy">
                                <div class="content-overlay">
                                    <button class="btn-favorite" 
                                            data-id="<?php echo $serie['id']; ?>" 
                                            data-type="serie"
                                            onclick="event.stopPropagation(); toggleFavorite(<?php echo $serie['id']; ?>, 'serie', this)">
                                        <i class="far fa-heart"></i>
                                    </button>
                                    <button class="btn-play">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <div class="content-rating">
                                        <i class="fas fa-star"></i>
                                        <span><?php echo $serie['note']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="content-info">
                                <h3 class="content-title" title="<?php echo htmlspecialchars($serie['titre']); ?>">
                                    <?php echo htmlspecialchars($serie['titre']); ?>
                                </h3>
                                <div class="content-meta">
                                    <span class="content-year"><?php echo $serie['annee']; ?></span>
                                    <span class="content-type">📺 Série</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Section Fonctionnalités -->
        <section class="features-section">
            <div class="container">
                <h2 class="section-title">Pourquoi choisir CineTrack ?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <i class="fas fa-film"></i>
                        <h3>Catalogue complet</h3>
                        <p>Accédez à des milliers de films et séries avec des informations détaillées</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-comments"></i>
                        <h3>Communauté active</h3>
                        <p>Partagez vos critiques et discutez avec d'autres passionnés</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-list"></i>
                        <h3>Listes personnalisées</h3>
                        <p>Créez et partagez vos listes de films et séries préférés</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-robot"></i>
                        <h3>Recommandations IA</h3>
                        <p>Découvrez du contenu adapté à vos goûts</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Statistiques -->
        <section class="stats-section">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-film"></i>
                        </div>
                        <div class="stat-number">10,000+</div>
                        <div class="stat-label">Films</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-tv"></i>
                        </div>
                        <div class="stat-number">5,000+</div>
                        <div class="stat-label">Séries</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number">50,000+</div>
                        <div class="stat-label">Membres</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div class="stat-number">100,000+</div>
                        <div class="stat-label">Critiques</div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'footer.php'; ?>
    
    <script src="js/script.js"></script>
    <script>
    // Fonctions pour la page d'accueil
    function viewContent(type, id) {
        // Redirection vers la page de détail
        if (type === 'film') {
            window.location.href = `pages/film.php?id=${id}`;
        } else {
            window.location.href = `pages/serie.php?id=${id}`;
        }
    }

    function toggleFavorite(contentId, contentType, button) {
        // Utiliser la fonction de script.js si disponible
        if (typeof window.toggleFavorite === 'function') {
            window.toggleFavorite(contentId, contentType, button);
        } else {
            // Fallback simple
            const icon = button.querySelector('i');
            if (icon.classList.contains('far')) {
                icon.className = 'fas fa-heart';
                button.style.color = 'var(--primary-color)';
                showNotification('Ajouté aux favoris', 'success');
            } else {
                icon.className = 'far fa-heart';
                button.style.color = 'var(--text-color)';
                showNotification('Retiré des favoris', 'info');
            }
        }
    }

    function showNotification(message, type = 'info') {
        // Utiliser la fonction de script.js si disponible
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
        } else {
            // Fallback simple
            console.log(`${type}: ${message}`);
        }
    }

    // Animation au scroll
    document.addEventListener('DOMContentLoaded', function() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);

        const animateElements = document.querySelectorAll('.content-section, .feature-card, .stats-item');
        animateElements.forEach(el => observer.observe(el));
    });
    </script>
</body>
</html>