<?php
// search-movies.php - API endpoint pour la recherche AJAX
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Inclure la config (chemin relatif)
$config_path = dirname(__DIR__) . '/includes/config.conf.php';
if (file_exists($config_path)) {
    include $config_path;
} else {
    echo json_encode([]);
    exit;
}

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($query) || strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

function searchMovies($query) {
    if (!defined('TMDB_API_KEY') || !defined('TMDB_BASE_URL')) {
        return [];
    }
    
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'search/movie?api_key=' . $api_key . '&query=' . urlencode($query) . '&language=fr-FR&page=1';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'CineTrack/1.0'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        return $data['results'] ?? [];
    }
    
    return [];
}

$results = searchMovies($query);
echo json_encode($results);
?>