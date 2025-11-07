<footer class="main-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-logo">
                    <i class="fas fa-film"></i>
                    <span>CineTrack</span>
                </div>
                <p class="footer-description">
                    La plateforme collaborative pour les passionnés de cinéma et de séries.
                </p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Navigation</h3>
                <ul class="footer-links">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="films.php">Films</a></li>
                    <li><a href="series.php">Séries</a></li>
                    <li><a href="recherche.php">Recherche</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Compte</h3>
                <ul class="footer-links">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="profil.php">Mon profil</a></li>
                        <li><a href="listes.php">Mes listes</a></li>
                        <li><a href="critiques.php">Mes critiques</a></li>
                    <?php else: ?>
                        <li><a href="connexion.php">Connexion</a></li>
                        <li><a href="inscription.php">Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Légal</h3>
                <ul class="footer-links">
                    <li><a href="#">Mentions légales</a></li>
                    <li><a href="#">Politique de confidentialité</a></li>
                    <li><a href="#">Conditions d'utilisation</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2025 CineTrack. Tous droits réservés.</p>
        </div>
    </div>
</footer>