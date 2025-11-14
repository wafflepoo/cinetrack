import React from 'react';
import { useLocation, Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import './Layout.css';

const Layout = ({ children }) => {
  const location = useLocation();
  const { user, logout } = useAuth();

  return (
    <div className="app">
      {/* Header Component */}
      <header className="main-header">
        <nav className="navbar">
          <div className="nav-container">
            <div className="nav-brand">
              <Link to="/" className="logo">
                <i className="fas fa-film"></i>
                <span>CineTrack</span>
              </Link>
            </div>
            
            <ul className="nav-menu">
              <li className={`nav-item ${location.pathname === '/' ? 'active' : ''}`}>
                <Link to="/" className="nav-link">Accueil</Link>
              </li>
              <li className={`nav-item ${location.pathname === '/films' ? 'active' : ''}`}>
                <Link to="/films" className="nav-link">Films</Link>
              </li>
              <li className={`nav-item ${location.pathname === '/series' ? 'active' : ''}`}>
                <Link to="/series" className="nav-link">Séries</Link>
              </li>
              <li className={`nav-item ${location.pathname === '/recherche' ? 'active' : ''}`}>
                <Link to="/recherche" className="nav-link">Recherche</Link>
              </li>
              
              {user ? (
                <li className={`nav-item dropdown ${
                  ['/profil', '/listes', '/critiques', '/messagerie'].includes(location.pathname) ? 'active' : ''
                }`}>
                  <a href="#" className="nav-link dropdown-toggle">
                    <i className="fas fa-user"></i>
                    Mon compte
                  </a>
                  <ul className="dropdown-menu">
                    <li><Link to="/profil"><i className="fas fa-id-card"></i> Mon profil</Link></li>
                    <li><Link to="/listes"><i className="fas fa-list"></i> Mes listes</Link></li>
                    <li><Link to="/critiques"><i className="fas fa-edit"></i> Mes critiques</Link></li>
                    <li><Link to="/messagerie"><i className="fas fa-envelope"></i> Messagerie</Link></li>
                    {user.role === 'admin' && (
                      <li><Link to="/admin"><i className="fas fa-cog"></i> Administration</Link></li>
                    )}
                    <li className="dropdown-divider"></li>
                    <li><button onClick={logout} className="logout-btn"><i className="fas fa-sign-out-alt"></i> Déconnexion</button></li>
                  </ul>
                </li>
              ) : (
                <>
                  <li className={`nav-item ${location.pathname === '/connexion' ? 'active' : ''}`}>
                    <Link to="/connexion" className="nav-link">Connexion</Link>
                  </li>
                  <li className={`nav-item ${location.pathname === '/inscription' ? 'active' : ''}`}>
                    <Link to="/inscription" className="btn btn-outline">Inscription</Link>
                  </li>
                </>
              )}
            </ul>
            
            <div className="hamburger">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
        </nav>
      </header>

      {/* Main Content */}
      <main className="main-content">
        {children}
      </main>

      {/* Footer Component */}
      <footer className="main-footer">
        <div className="container">
          <div className="footer-content">
            <div className="footer-section">
              <div className="footer-logo">
                <i className="fas fa-film"></i>
                <span>CineTrack</span>
              </div>
              <p className="footer-description">
                La plateforme collaborative pour les passionnés de cinéma et de séries.
              </p>
              <div className="social-links">
                <a href="#" aria-label="Facebook"><i className="fab fa-facebook"></i></a>
                <a href="#" aria-label="Twitter"><i className="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i className="fab fa-instagram"></i></a>
              </div>
            </div>
            
            <div className="footer-section">
              <h3>Navigation</h3>
              <ul className="footer-links">
                <li><Link to="/">Accueil</Link></li>
                <li><Link to="/films">Films</Link></li>
                <li><Link to="/series">Séries</Link></li>
                <li><Link to="/recherche">Recherche</Link></li>
              </ul>
            </div>
            
            <div className="footer-section">
              <h3>Compte</h3>
              <ul className="footer-links">
                {user ? (
                  <>
                    <li><Link to="/profil">Mon profil</Link></li>
                    <li><Link to="/listes">Mes listes</Link></li>
                    <li><Link to="/critiques">Mes critiques</Link></li>
                  </>
                ) : (
                  <>
                    <li><Link to="/connexion">Connexion</Link></li>
                    <li><Link to="/inscription">Inscription</Link></li>
                  </>
                )}
              </ul>
            </div>
            
            <div className="footer-section">
              <h3>Légal</h3>
              <ul className="footer-links">
                <li><a href="#">Mentions légales</a></li>
                <li><a href="#">Politique de confidentialité</a></li>
                <li><a href="#">Conditions d'utilisation</a></li>
                <li><a href="#">Contact</a></li>
              </ul>
            </div>
          </div>
          
          <div className="footer-bottom">
            <p>&copy; {new Date().getFullYear()} CineTrack. Tous droits réservés.</p>
          </div>
        </div>
      </footer>
    </div>
  );
};

export default Layout;