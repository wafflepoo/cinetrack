<?php
// api-test.php - Teste tes APIs
include __DIR__ . '/../includes/config.conf.php';

echo "<h1>Test des APIs Cinémas</h1>";

// Test Google Places
echo "<h2>Test Google Places API</h2>";
$api_key = GOOGLE_API_KEY;
$ville = "Paris";
$url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=cinéma " . urlencode($ville) . "&key=" . $api_key . "&language=fr";

$response = file_get_contents($url);
if ($response) {
    $data = json_decode($response, true);
    echo "<pre>" . print_r($data['results'][0] ?? 'Aucun résultat', true) . "</pre>";
} else {
    echo "Erreur Google Places API";
}

// Test TMDB Now Playing
echo "<h2>Test TMDB Now Playing</h2>";
$url = TMDB_BASE_URL . 'movie/now_playing?api_key=' . TMDB_API_KEY . '&language=fr-FR';
$response = file_get_contents($url);
if ($response) {
    $data = json_decode($response, true);
    echo "Films à l'affiche : " . count($data['results'] ?? []);
}
?>