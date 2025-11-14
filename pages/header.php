<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Déterminer le chemin de base selon l'emplacement de la page
$is_in_pages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$base_path = $is_in_pages ? '../' : '';
?>

<header class="main-header">
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="<?php echo $base_path; ?>index.php" class="logo">
                    <i class="fas fa-film"></i>
                    <span>CineTrack</span>
                </a>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_path; ?>index.php" class="nav-link">Accueil</a>
                </li>
                <li class="nav-item <?php echo $current_page == 'films.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_path; ?>pages/films.php" class="nav-link">Films</a>
                </li>
                <li class="nav-item <?php echo $current_page == 'series.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_path; ?>pages/series.php" class="nav-link">Séries</a>
                </li>
                <li class="nav-item <?php echo $current_page == 'recherche.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_path; ?>pages/recherche.php" class="nav-link">Recherche</a>
                </li>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown <?php echo in_array($current_page, ['profil.php', 'listes.php', 'critiques.php', 'messagerie.php']) ? 'active' : ''; ?>">
                        <a href="#" class="nav-link dropdown-toggle">
                            <i class="fas fa-user"></i>
                            Mon compte
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo $base_path; ?>pages/profil.php"><i class="fas fa-id-card"></i> Mon profil</a></li>
                            <li><a href="<?php echo $base_path; ?>pages/listes.php"><i class="fas fa-list"></i> Mes listes</a></li>
                            <li><a href="<?php echo $base_path; ?>pages/critiques.php"><i class="fas fa-edit"></i> Mes critiques</a></li>
                            <li><a href="<?php echo $base_path; ?>pages/messagerie.php"><i class="fas fa-envelope"></i> Messagerie</a></li>
                            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                <li><a href="<?php echo $base_path; ?>pages/admin.php"><i class="fas fa-cog"></i> Administration</a></li>
                            <?php endif; ?>
                            <li class="dropdown-divider"></li>
                            <li><a href="<?php echo $base_path; ?>includes/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item <?php echo $current_page == 'connexion.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $base_path; ?>pages/connexion.php" class="nav-link">Connexion</a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'inscription.php' ? 'active' : ''; ?>">
                        <a href="<?php echo $base_path; ?>pages/inscription.php" class="btn btn-outline">Inscription</a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>
</header>