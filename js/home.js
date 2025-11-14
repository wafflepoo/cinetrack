// Script spécifique à la page d'accueil
document.addEventListener('DOMContentLoaded', function() {
    loadPopularContent();
    initializeScrollAnimations();
});

// Chargement des films et séries populaires
async function loadPopularContent() {
    try {
        // Films populaires - Données simulées améliorées
        const movies = [
            {
                id: 1,
                titre: "Dune: Partie Deux",
                poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop",
                annee: "2024",
                note: "4.5",
                type: "film"
            },
            {
                id: 2,
                titre: "Oppenheimer",
                poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop",
                annee: "2023",
                note: "4.7",
                type: "film"
            },
            {
                id: 3,
                titre: "Barbie",
                poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop",
                annee: "2023",
                note: "4.2",
                type: "film"
            },
            {
                id: 4,
                titre: "Spider-Man: Across the Spider-Verse",
                poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop",
                annee: "2023",
                note: "4.6",
                type: "film"
            },
            {
                id: 5,
                titre: "The Batman",
                poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop",
                annee: "2022",
                note: "4.3",
                type: "film"
            },
            {
                id: 6,
                titre: "Top Gun: Maverick",
                poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop",
                annee: "2022",
                note: "4.8",
                type: "film"
            }
        ];

        // Séries populaires
        const series = [
            {
                id: 1,
                titre: "Stranger Things",
                poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop",
                annee: "2016",
                note: "4.6",
                type: "serie"
            },
            {
                id: 2,
                titre: "The Last of Us",
                poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop",
                annee: "2023",
                note: "4.7",
                type: "serie"
            },
            {
                id: 3,
                titre: "Wednesday",
                poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop",
                annee: "2022",
                note: "4.3",
                type: "serie"
            },
            {
                id: 4,
                titre: "The Mandalorian",
                poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop",
                annee: "2019",
                note: "4.5",
                type: "serie"
            },
            {
                id: 5,
                titre: "Breaking Bad",
                poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop",
                annee: "2008",
                note: "4.9",
                type: "serie"
            },
            {
                id: 6,
                titre: "Game of Thrones",
                poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop",
                annee: "2011",
                note: "4.4",
                type: "serie"
            }
        ];

        displayContent('popular-movies', movies, 'film');
        displayContent('popular-series', series, 'serie');

    } catch (error) {
        console.error('Erreur chargement contenu:', error);
        showFallbackContent();
    }
}

// Affichage du contenu dans les grids
function displayContent(containerId, content, type) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error('Container non trouvé:', containerId);
        return;
    }

    if (!content || content.length === 0) {
        container.innerHTML = `
            <div class="no-content">
                <i class="fas fa-film"></i>
                <p>Aucun contenu disponible pour le moment</p>
            </div>
        `;
        return;
    }

    container.innerHTML = content.map(item => `
        <div class="content-card" onclick="viewContent('${type}', ${item.id})">
            <div class="content-poster-container">
                <img src="${item.poster}" 
                     alt="${item.titre}" 
                     class="content-poster"
                     loading="lazy"
                     onerror="this.src='https://images.unsplash.com/photo-1598899134739-24c46f58b8c0?w=300&h=450&fit=crop'">
                <div class="content-overlay">
                    <button class="btn-favorite" onclick="event.stopPropagation(); toggleFavorite(${item.id}, '${type}', this)">
                        <i class="far fa-heart"></i>
                    </button>
                    <button class="btn-play">
                        <i class="fas fa-play"></i>
                    </button>
                    <div class="content-rating">
                        <i class="fas fa-star"></i>
                        <span>${item.note}</span>
                    </div>
                </div>
            </div>
            <div class="content-info">
                <h3 class="content-title" title="${item.titre}">${item.titre}</h3>
                <div class="content-meta">
                    <span class="content-year">${item.annee}</span>
                    <span class="content-type">${type === 'film' ? '🎬 Film' : '📺 Série'}</span>
                </div>
            </div>
        </div>
    `).join('');
}

// Redirection vers la page de détail
function viewContent(type, id) {
    // Pour l'instant, redirige vers une page de détail simulée
    console.log(`Voir ${type} avec ID: ${id}`);
    // window.location.href = `${type === 'film' ? 'film' : 'serie'}.php?id=${id}`;
    
    // Simulation - Afficher une notification
    showNotification(`Ouverture des détails pour ${type} #${id}`, 'info');
}

// Contenu de secours
function showFallbackContent() {
    const fallbackMovies = [
        { 
            id: 1, 
            titre: "Dune: Partie Deux", 
            poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop", 
            annee: "2024", 
            note: "4.5",
            type: "film"
        },
        { 
            id: 2, 
            titre: "Oppenheimer", 
            poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop", 
            annee: "2023", 
            note: "4.7",
            type: "film"
        },
        { 
            id: 3, 
            titre: "Barbie", 
            poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop", 
            annee: "2023", 
            note: "4.2",
            type: "film"
        }
    ];

    const fallbackSeries = [
        { 
            id: 1, 
            titre: "Stranger Things", 
            poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop", 
            annee: "2016", 
            note: "4.6",
            type: "serie"
        },
        { 
            id: 2, 
            titre: "The Last of Us", 
            poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop", 
            annee: "2023", 
            note: "4.7",
            type: "serie"
        },
        { 
            id: 3, 
            titre: "Wednesday", 
            poster: "https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop", 
            annee: "2022", 
            note: "4.3",
            type: "serie"
        }
    ];

    displayContent('popular-movies', fallbackMovies, 'film');
    displayContent('popular-series', fallbackSeries, 'serie');
}

// Animations au scroll
function initializeScrollAnimations() {
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

    const animateElements = document.querySelectorAll('.content-section, .feature-card');
    animateElements.forEach(el => observer.observe(el));
}

// Fonction de notification (utilise celle de script.js)
function showNotification(message, type = 'info') {
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
    } else {
        // Fallback simple
        console.log(`${type}: ${message}`);
    }
}

// Toggle favorite (utilise celle de script.js)
function toggleFavorite(contentId, contentType, button) {
    if (typeof window.toggleFavorite === 'function') {
        window.toggleFavorite(contentId, contentType, button);
    } else {
        // Fallback simple
        const icon = button.querySelector('i');
        if (icon.classList.contains('far')) {
            icon.className = 'fas fa-heart';
            button.style.color = 'var(--primary-color)';
        } else {
            icon.className = 'far fa-heart';
            button.style.color = 'var(--text-color)';
        }
    }
}