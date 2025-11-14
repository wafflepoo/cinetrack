import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { movieAPI, userAPI } from '../services/api';
import './Home.css';

const Home = () => {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [popularMovies, setPopularMovies] = useState([]);
  const [popularSeries, setPopularSeries] = useState([]);
  const [loading, setLoading] = useState(true);
  const [favorites, setFavorites] = useState(new Set());
  const [notification, setNotification] = useState(null);

  // Static data (equivalent to your PHP arrays)
  const staticPopularMovies = [
    {
      id: 1,
      titre: 'Dune: Partie Deux',
      poster: 'https://images.unsplash.com/photo-1712687502158-13e25cce1e27?w=300&h=450&fit=crop',
      annee: '2024',
      note: '4.5',
      type: 'film'
    },
    {
      id: 2,
      titre: 'Oppenheimer',
      poster: 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
      annee: '2023',
      note: '4.7',
      type: 'film'
    },
    {
      id: 3,
      titre: 'Barbie',
      poster: 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
      annee: '2023',
      note: '4.2',
      type: 'film'
    },
    {
      id: 4,
      titre: 'Spider-Man: Across the Spider-Verse',
      poster: 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
      annee: '2023',
      note: '4.6',
      type: 'film'
    },
    {
      id: 5,
      titre: 'The Batman',
      poster: 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
      annee: '2022',
      note: '4.3',
      type: 'film'
    },
    {
      id: 6,
      titre: 'Top Gun: Maverick',
      poster: 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
      annee: '2022',
      note: '4.8',
      type: 'film'
    }
  ];

  const staticPopularSeries = [
    {
      id: 1,
      titre: 'Stranger Things',
      poster: 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
      annee: '2016',
      note: '4.6',
      type: 'serie'
    },
    {
      id: 2,
      titre: 'The Last of Us',
      poster: 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
      annee: '2023',
      note: '4.7',
      type: 'serie'
    },
    {
      id: 3,
      titre: 'Wednesday',
      poster: 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
      annee: '2022',
      note: '4.3',
      type: 'serie'
    },
    {
      id: 4,
      titre: 'The Mandalorian',
      poster: 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
      annee: '2019',
      note: '4.5',
      type: 'serie'
    },
    {
      id: 5,
      titre: 'Breaking Bad',
      poster: 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
      annee: '2008',
      note: '4.9',
      type: 'serie'
    },
    {
      id: 6,
      titre: 'Game of Thrones',
      poster: 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=450&fit=crop',
      annee: '2011',
      note: '4.4',
      type: 'serie'
    }
  ];

  // Show notification function
  const showNotification = (message, type = 'info', duration = 5000) => {
    setNotification({ message, type });
    if (duration > 0) {
      setTimeout(() => setNotification(null), duration);
    }
  };

  // Load data on component mount
  useEffect(() => {
    const fetchHomeData = async () => {
      try {
        // For now, using static data
        setPopularMovies(staticPopularMovies);
        setPopularSeries(staticPopularSeries);
        
        // Uncomment when your Flask API is ready:
        // const moviesResponse = await movieAPI.getTrending('movie');
        // const seriesResponse = await movieAPI.getTrending('tv');
        // setPopularMovies(moviesResponse.data.results);
        // setPopularSeries(seriesResponse.data.results);
      } catch (error) {
        console.error('Error fetching home data:', error);
        // Fallback to static data
        setPopularMovies(staticPopularMovies);
        setPopularSeries(staticPopularSeries);
      } finally {
        setLoading(false);
      }
    };

    fetchHomeData();
    initializeScrollAnimations();
  }, []);

  // Scroll animations
  const initializeScrollAnimations = () => {
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
  };

  // View content function
  const viewContent = (type, id) => {
    navigate(`/${type}/${id}`);
  };

  // Toggle favorite function
  const toggleFavorite = async (contentId, contentType, event) => {
    event.stopPropagation();
    
    if (!user) {
      showNotification('Veuillez vous connecter pour ajouter aux favoris', 'warning');
      return;
    }

    try {
      const wasFavorite = favorites.has(`${contentType}-${contentId}`);
      const newFavorites = new Set(favorites);
      
      // Optimistic update
      if (wasFavorite) {
        newFavorites.delete(`${contentType}-${contentId}`);
      } else {
        newFavorites.add(`${contentType}-${contentId}`);
      }
      setFavorites(newFavorites);

      // TODO: Replace with actual API call
      // const response = await userAPI.toggleFavorite(contentId, contentType);
      
      // For now, simulate API call
      showNotification(
        wasFavorite ? '📝 Retiré des favoris' : '✅ Ajouté aux favoris',
        wasFavorite ? 'info' : 'success'
      );

    } catch (error) {
      // Revert on error
      setFavorites(favorites);
      showNotification('❌ Erreur lors de la mise à jour', 'error');
    }
  };

  const isFavorite = (contentId, contentType) => {
    return favorites.has(`${contentType}-${contentId}`);
  };

  // Fallback content function
  const showFallbackContent = () => {
    const fallbackMovies = staticPopularMovies.slice(0, 3);
    const fallbackSeries = staticPopularSeries.slice(0, 3);
    setPopularMovies(fallbackMovies);
    setPopularSeries(fallbackSeries);
  };

  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>Chargement du contenu...</p>
      </div>
    );
  }

  return (
    <div className="home-page">
      {/* Notification */}
      {notification && (
        <div className={`notification notification-${notification.type}`}>
          <div className="notification-content">
            <span className="notification-message">{notification.message}</span>
            <button 
              className="notification-close" 
              onClick={() => setNotification(null)}
              aria-label="Fermer"
            >
              <i className="fas fa-times"></i>
            </button>
          </div>
        </div>
      )}

      {/* Hero Section */}
      <section className="hero">
        <div className="hero-background">
          <div className="hero-content">
            <h1>Bienvenue sur CineTrack</h1>
            <p>Découvrez, partagez et discutez de vos films et séries préférés</p>
            <div className="hero-buttons">
              {!user ? (
                <>
                  <Link to="/inscription" className="btn btn-primary">Commencer</Link>
                  <Link to="/connexion" className="btn btn-secondary">Se connecter</Link>
                </>
              ) : (
                <>
                  <Link to="/recherche" className="btn btn-primary">Découvrir</Link>
                  <Link to="/listes" className="btn btn-secondary">Mes listes</Link>
                </>
              )}
            </div>
          </div>
        </div>
      </section>

      {/* Popular Movies Section */}
      <section className="content-section">
        <div className="container">
          <div className="section-header">
            <h2 className="section-title">Films populaires</h2>
            <Link to="/films" className="section-link">
              Voir tout <i className="fas fa-arrow-right"></i>
            </Link>
          </div>
          <div className="content-grid">
            {popularMovies.length === 0 ? (
              <div className="no-content">
                <i className="fas fa-film"></i>
                <p>Aucun contenu disponible pour le moment</p>
              </div>
            ) : (
              popularMovies.map(movie => (
                <div key={movie.id} className="content-card" onClick={() => viewContent('film', movie.id)}>
                  <div className="content-poster-container">
                    <img 
                      src={movie.poster} 
                      alt={movie.titre} 
                      className="content-poster"
                      loading="lazy"
                      onError={(e) => {
                        e.target.src = 'https://images.unsplash.com/photo-1598899134739-24c46f58b8c0?w=300&h=450&fit=crop';
                      }}
                    />
                    <div className="content-overlay">
                      <button 
                        className={`btn-favorite ${isFavorite(movie.id, 'film') ? 'active' : ''}`}
                        onClick={(e) => toggleFavorite(movie.id, 'film', e)}
                      >
                        <i className={isFavorite(movie.id, 'film') ? 'fas fa-heart' : 'far fa-heart'}></i>
                      </button>
                      <button className="btn-play">
                        <i className="fas fa-play"></i>
                      </button>
                      <div className="content-rating">
                        <i className="fas fa-star"></i>
                        <span>{movie.note}</span>
                      </div>
                    </div>
                  </div>
                  <div className="content-info">
                    <h3 className="content-title" title={movie.titre}>
                      {movie.titre}
                    </h3>
                    <div className="content-meta">
                      <span className="content-year">{movie.annee}</span>
                      <span className="content-type">🎬 Film</span>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      </section>

      {/* Popular Series Section */}
      <section className="content-section">
        <div className="container">
          <div className="section-header">
            <h2 className="section-title">Séries populaires</h2>
            <Link to="/series" className="section-link">
              Voir tout <i className="fas fa-arrow-right"></i>
            </Link>
          </div>
          <div className="content-grid">
            {popularSeries.length === 0 ? (
              <div className="no-content">
                <i className="fas fa-tv"></i>
                <p>Aucun contenu disponible pour le moment</p>
              </div>
            ) : (
              popularSeries.map(serie => (
                <div key={serie.id} className="content-card" onClick={() => viewContent('serie', serie.id)}>
                  <div className="content-poster-container">
                    <img 
                      src={serie.poster} 
                      alt={serie.titre} 
                      className="content-poster"
                      loading="lazy"
                      onError={(e) => {
                        e.target.src = 'https://images.unsplash.com/photo-1598899134739-24c46f58b8c0?w=300&h=450&fit=crop';
                      }}
                    />
                    <div className="content-overlay">
                      <button 
                        className={`btn-favorite ${isFavorite(serie.id, 'serie') ? 'active' : ''}`}
                        onClick={(e) => toggleFavorite(serie.id, 'serie', e)}
                      >
                        <i className={isFavorite(serie.id, 'serie') ? 'fas fa-heart' : 'far fa-heart'}></i>
                      </button>
                      <button className="btn-play">
                        <i className="fas fa-play"></i>
                      </button>
                      <div className="content-rating">
                        <i className="fas fa-star"></i>
                        <span>{serie.note}</span>
                      </div>
                    </div>
                  </div>
                  <div className="content-info">
                    <h3 className="content-title" title={serie.titre}>
                      {serie.titre}
                    </h3>
                    <div className="content-meta">
                      <span className="content-year">{serie.annee}</span>
                      <span className="content-type">📺 Série</span>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="features-section">
        <div className="container">
          <h2 className="section-title">Pourquoi choisir CineTrack ?</h2>
          <div className="features-grid">
            <div className="feature-card">
              <i className="fas fa-film"></i>
              <h3>Catalogue complet</h3>
              <p>Accédez à des milliers de films et séries avec des informations détaillées</p>
            </div>
            <div className="feature-card">
              <i className="fas fa-comments"></i>
              <h3>Communauté active</h3>
              <p>Partagez vos critiques et discutez avec d'autres passionnés</p>
            </div>
            <div className="feature-card">
              <i className="fas fa-list"></i>
              <h3>Listes personnalisées</h3>
              <p>Créez et partagez vos listes de films et séries préférés</p>
            </div>
            <div className="feature-card">
              <i className="fas fa-robot"></i>
              <h3>Recommandations IA</h3>
              <p>Découvrez du contenu adapté à vos goûts</p>
            </div>
          </div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="stats-section">
        <div className="container">
          <div className="stats-grid">
            <div className="stat-item">
              <div className="stat-icon">
                <i className="fas fa-film"></i>
              </div>
              <div className="stat-number">10,000+</div>
              <div className="stat-label">Films</div>
            </div>
            <div className="stat-item">
              <div className="stat-icon">
                <i className="fas fa-tv"></i>
              </div>
              <div className="stat-number">5,000+</div>
              <div className="stat-label">Séries</div>
            </div>
            <div className="stat-item">
              <div className="stat-icon">
                <i className="fas fa-users"></i>
              </div>
              <div className="stat-number">50,000+</div>
              <div className="stat-label">Membres</div>
            </div>
            <div className="stat-item">
              <div className="stat-icon">
                <i className="fas fa-comment"></i>
              </div>
              <div className="stat-number">100,000+</div>
              <div className="stat-label">Critiques</div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default Home;