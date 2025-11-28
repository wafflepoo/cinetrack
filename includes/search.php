<?php
// includes/search.php - API de recherche pour la barre de recherche hero
session_start();
include 'config.conf.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'multi';

if (empty($query)) {
    echo json_encode(['results' => []]);
    exit;
}

// Fonction pour rechercher dans l'API TMDb
function searchMultiFromAPI($query, $page = 1) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'search/multi?api_key=' . $api_key . '&query=' . urlencode($query) . '&language=fr-FR&page=' . $page;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
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

// Fonction pour rechercher uniquement les films
function searchMoviesFromAPI($query, $page = 1) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'search/movie?api_key=' . $api_key . '&query=' . urlencode($query) . '&language=fr-FR&page=' . $page;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
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

// Effectuer la recherche
if ($type === 'multi') {
    $results = searchMultiFromAPI($query);
} else {
    $results = searchMoviesFromAPI($query);
}

// Filtrer les résultats sans poster et exclure les personnes
$filteredResults = array_filter($results, function($item) {
    // Exclure les personnes (acteurs/réalisateurs)
    if ($item['media_type'] === 'person') {
        return false;
    }
    // Inclure seulement les films/séries avec poster
    return !empty($item['poster_path']);
});

// Limiter à 8 résultats
$limitedResults = array_slice($filteredResults, 0, 8);

// Formater les résultats
$formattedResults = array_map(function($item) {
    return [
        'id' => $item['id'],
        'title' => $item['title'] ?? $item['name'] ?? 'Titre inconnu',
        'media_type' => $item['media_type'] ?? 'movie',
        'poster_path' => $item['poster_path'] ? 'https://image.tmdb.org/t/p/w92' . $item['poster_path'] : null,
        'release_date' => $item['release_date'] ?? $item['first_air_date'] ?? null,
        'vote_average' => $item['vote_average'] ?? 0,
        'overview' => $item['overview'] ?? ''
    ];
}, $limitedResults);

echo json_encode([
    'success' => true,
    'results' => $formattedResults,
    'total_results' => count($formattedResults),
    'query' => $query
]);
?>