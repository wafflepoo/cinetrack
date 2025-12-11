<?php
// search-series.php - API pour la recherche AJAX des séries
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include '../includes/config.conf.php';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($query) || strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

function searchSeries($query) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'search/tv?api_key=' . $api_key . '&query=' . urlencode($query) . '&language=fr-FR&page=1';
    
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

$results = searchSeries($query);
echo json_encode($results);
?>