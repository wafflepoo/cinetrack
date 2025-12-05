<?php
// config.conf.php - Configuration pour AlwaysData
define('DB_HOST', 'mysql-cinetrack.alwaysdata.net');
define('DB_NAME', 'cinetrack_db');
define('DB_USER', 'cinetrack');
define('DB_PASS', 'webdevwebdev');
define('DB_PORT', '3306');

// TMDb API Configuration
define('TMDB_API_KEY', '80e38ecd2c2b2342551ab68d85bee288');
define('TMDB_BASE_URL', 'https://api.themoviedb.org/3/');
define('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p/');

// reCAPTCHA
define('RECAPTCHA_SITE_KEY', '6LcrXhMsAAAAAKrE4f5EtzyYoqsYbY9yZtTOdlHU');
define('RECAPTCHA_SECRET_KEY', '6LcrXhMsAAAAAJt7LRbhPd1bDcAQc4CTZsK7tBmF');

// Site configuration
define('SITE_URL', 'https://cinetrack.alwaysdata.net');
define('SECRET_KEY', 'cine-track-secret-key-2024');

// Configuration pour la recherche par image
define('USE_IMAGE_SEARCH', true);
define('IMAGE_SEARCH_METHOD', 'free_analysis'); // Version gratuite avec GD

// Google Vision API DÉSACTIVÉE (pour éviter l'erreur 403)
// define('GOOGLE_VISION_API_KEY', '');
// define('GOOGLE_VISION_URL', '');

// Paramètres pour l'analyse gratuite (GD est activé !)
define('ENABLE_COLOR_ANALYSIS', true); // ✅ GD activé sur AlwaysData
define('ENABLE_FILENAME_ANALYSIS', true);
define('MAX_KEYWORDS', 10);
define('MAX_FALLBACK_RESULTS', 8);

// === AJOUTS NÉCESSAIRES POUR scene-search.php === //
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Configuration des limites
define('MAX_SEARCH_RESULTS', 12);

// Genres de films pour améliorer la recherche
define('MOVIE_GENRES', serialize([
    'action', 'adventure', 'animation', 'comedy', 'crime', 'documentary',
    'drama', 'family', 'fantasy', 'history', 'horror', 'music',
    'mystery', 'romance', 'science fiction', 'thriller', 'war', 'western'
]));

// Mots-clés intelligents par défaut
define('DEFAULT_KEYWORDS', serialize([
    'movie', 'film', 'cinema', 'hollywood', 'blockbuster',
    'action', 'drama', 'comedy', 'thriller', 'romance',
    'adventure', 'fantasy', 'horror', 'mystery', 'sci-fi'
]));
// === FIN DES AJOUTS === //

// MySQLi connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Check connection
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    die("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
}

$mysqli->set_charset("utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>