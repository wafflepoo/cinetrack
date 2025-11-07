<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineTrack - Films et Séries</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #e50914;
            --secondary-color: #141414;
            --tertiary-color: #2d2d2d;
            --accent-color: #1a1a1a;
            --text-color: #ffffff;
            --text-secondary: #b3b3b3;
            --text-muted: #808080;
            --border-color: #404040;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --error-color: #e74c3c;
            --transition: all 0.3s ease;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
            --gradient: linear-gradient(135deg, var(--primary-color) 0%, #b81d24 100%);
        }

        /* Reset et styles de base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-color);
            line-height: 1.6;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
            transition: var(--transition);
        }

        ul {
            list-style: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .main-header {
            position: fixed;
            top: 0;
            width: 100%;
            background-color: var(--secondary-color);
            z-index: 1000;
            transition: var(--transition);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .nav-brand .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-link {
            color: var(--text-color);
            font-weight: 500;
            padding: 8px 12px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .nav-link:hover,
        .nav-item.active .nav-link {
            color: var(--primary-color);
            background-color: rgba(229, 9, 20, 0.1);
        }

        .nav-auth {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Boutons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            justify-content: center;
        }

        .btn-primary {
            background: var(--gradient);
            color: var(--text-color);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(229, 9, 20, 0.4);
        }

        .btn-secondary {
            background-color: var(--tertiary-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: var(--border-color);
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            height: 85vh;
            min-height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--text-color);
            margin-top: 70px;
            padding: 40px 20px;
        }

        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                linear-gradient(rgba(20,20,20,0.85), rgba(20,20,20,0.92)),
                url('https://images.unsplash.com/photo-1535016120720-40c646be5580?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80') center/cover;
            z-index: -1;
        }

        .hero-content {
            max-width: 800px;
            padding: 0 20px;
        }

        .hero h1 {
            font-size: 3.2rem;
            font-weight: 800;
            margin-bottom: 15px;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.8);
            line-height: 1.1;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 25px;
            color: var(--text-secondary);
            line-height: 1.4;
            font-weight: 300;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .hero-buttons .btn {
            padding: 14px 32px;
            font-size: 1.1rem;
            border-radius: 8px;
            font-weight: 600;
        }

        /* Sections */
        .content-section {
            padding: 80px 0;
        }

        .section-title {
            font-size: 2rem;
            margin-bottom: 40px;
            color: var(--text-color);
            text-align: center;
        }

        /* Grid de contenu */
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .content-card {
            background-color: var(--tertiary-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
        }

        .content-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.7);
        }

        .content-poster-container {
            position: relative;
            width: 100%;
            height: 300px;
            overflow: hidden;
        }

        .content-poster {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .content-card:hover .content-poster {
            transform: scale(1.05);
        }

        .content-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                to bottom,
                rgba(0, 0, 0, 0.7) 0%,
                transparent 30%,
                transparent 70%,
                rgba(0, 0, 0, 0.9) 100%
            );
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 15px;
        }

        .content-card:hover .content-overlay {
            opacity: 1;
        }

        .content-info {
            padding: 15px;
        }

        .content-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-color);
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.6em;
        }

        .content-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        /* Footer */
        .main-footer {
            background-color: var(--accent-color);
            padding: 60px 0 20px;
            border-top: 1px solid var(--border-color);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .footer-description {
            color: var(--text-secondary);
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: var(--tertiary-color);
            border-radius: 50%;
            color: var(--text-color);
            transition: var(--transition);
        }

        .social-links a:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .footer-section h3 {
            color: var(--text-color);
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: var(--text-secondary);
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--primary-color);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.2rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .content-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .nav-menu {
                display: none;
            }
            
            .hamburger {
                display: flex;
                flex-direction: column;
                cursor: pointer;
                gap: 4px;
            }
            
            .hamburger span {
                width: 25px;
                height: 3px;
                background-color: var(--text-color);
                transition: var(--transition);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="#" class="logo">
                    <i class="fas fa-film"></i>
                    <span>CineTrack</span>
                </a>
            </div>
            
            <nav class="nav-menu">
                <ul class="nav-list">
                    <li class="nav-item active"><a href="#" class="nav-link">Accueil</a></li>
                    <li class="nav-item"><a href="#" class="nav-link">Films</a></li>
                    <li class="nav-item"><a href="#" class="nav-link">Séries</a></li>
                    <li class="nav-item"><a href="#" class="nav-link">Recherche</a></li>
                </ul>
            </nav>
            
            <div class="nav-auth">
                <a href="#" class="btn btn-secondary">Connexion</a>
                <a href="#" class="btn btn-primary">Inscription</a>
            </div>
            
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-background"></div>
        <div class="hero-content">
            <h1>Bienvenue sur CineTrack</h1>
            <p>Découvrez, partagez et discutez de vos films et séries préférés</p>
            <div class="hero-buttons">
                <a href="#" class="btn btn-primary">Commencer</a>
                <a href="#" class="btn btn-secondary">Se connecter</a>
            </div>
        </div>
    </section>

    <!-- Films Section -->
    <section class="content-section">
        <div class="container">
            <h2 class="section-title">Films Populaires</h2>
            <div class="content-grid">
                <!-- Les cartes de films seront ajoutées ici dynamiquement -->
                <div class="content-card">
                    <div class="content-poster-container">
                        <img src="https://via.placeholder.com/300x450" alt="Film" class="content-poster">
                        <div class="content-overlay">
                            <button class="btn-favorite"><i class="far fa-heart"></i></button>
                            <button class="btn-play"><i class="fas fa-play"></i></button>
                            <div class="content-rating"><i class="fas fa-star"></i> 8.5</div>
                        </div>
                    </div>
                    <div class="content-info">
                        <h3 class="content-title">Titre du Film</h3>
                        <div class="content-meta">
                            <span class="content-year">2023</span>
                            <span class="content-type">Film</span>
                        </div>
                    </div>
                </div>
                <!-- Plus de cartes... -->
            </div>
        </div>
    </section>

    <!-- Séries Section -->
    <section class="content-section">
        <div class="container">
            <h2 class="section-title">Séries Populaires</h2>
            <div class="content-grid">
                <!-- Les cartes de séries seront ajoutées ici dynamiquement -->
                <div class="content-card">
                    <div class="content-poster-container">
                        <img src="https://via.placeholder.com/300x450" alt="Série" class="content-poster">
                        <div class="content-overlay">
                            <button class="btn-favorite"><i class="far fa-heart"></i></button>
                            <button class="btn-play"><i class="fas fa-play"></i></button>
                            <div class="content-rating"><i class="fas fa-star"></i> 9.0</div>
                        </div>
                    </div>
                    <div class="content-info">
                        <h3 class="content-title">Titre de la Série</h3>
                        <div class="content-meta">
                            <span class="content-year">2023</span>
                            <span class="content-type">Série</span>
                        </div>
                    </div>
                </div>
                <!-- Plus de cartes... -->
            </div>
        </div>
    </section>

 <?php include '../footer.php'; ?>

    <script>
        // Script pour gérer le scroll du header
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.main-header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Script pour le menu hamburger (mobile)
        document.querySelector('.hamburger').addEventListener('click', function() {
            this.classList.toggle('active');
            document.querySelector('.nav-menu').classList.toggle('active');
        });
    </script>
</body>
</html>