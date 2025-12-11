<?php
// config.php - Configuration pour AlwaysData
define('DB_HOST', 'mysql-cinetrack.alwaysdata.net');
define('DB_NAME', 'cinetrack_db');
define('DB_USER', 'cinetrack');
define('DB_PASS', 'webdevwebdev');
define('DB_PORT', '3306');

// TMDb API Configuration
define('TMDB_API_KEY', '80e38ecd2c2b2342551ab68d85bee288');
define('TMDB_BASE_URL', 'https://api.themoviedb.org/3/');
define('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p/');

define('GOOGLE_API_KEY', 'AIzaSyBXRR-kuWzY0KVPdAIXURdFUC_oOvGm2P4');


// reCAPTCHA
define('RECAPTCHA_SITE_KEY', '6LcrXhMsAAAAAKrE4f5EtzyYoqsYbY9yZtTOdlHU');
define('RECAPTCHA_SECRET_KEY', '6LcrXhMsAAAAAJt7LRbhPd1bDcAQc4CTZsK7tBmF');

// Site configuration
define('SITE_URL', 'https://cinetrack.alwaysdata.net');
define('SECRET_KEY', 'cine-track-secret-key-2024');

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