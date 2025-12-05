<?php
// test-vision-debug.php
include 'includes/config.conf.php';

echo "<h1>Test Google Vision API Debug</h1>";

// Vérifier la clé API
echo "<h2>1. Vérification de la clé API</h2>";
echo "Clé API configurée : " . (defined('GOOGLE_VISION_API_KEY') && !empty(GOOGLE_VISION_API_KEY) ? "✅ OUI" : "❌ NON");
echo "<br>Clé : " . substr(GOOGLE_VISION_API_KEY, 0, 20) . "...";

// Test avec une image TMDb connue
$test_image_url = 'https://image.tmdb.org/t/p/w500/qJ2tW6WMUDux911r6m7haRef0WH.jpg'; // The Dark Knight

echo "<h2>2. Test avec une image connue</h2>";
echo "Image test : $test_image_url<br>";

// Fonction de test simplifiée
function testVisionAPI($image_url) {
    $api_key = GOOGLE_VISION_API_KEY;
    $url = 'https://vision.googleapis.com/v1/images:annotate?key=' . $api_key;
    
    // Télécharger l'image
    $image_data = @file_get_contents($image_url);
    if (!$image_data) {
        return "❌ Impossible de télécharger l'image";
    }
    
    $base64_image = base64_encode($image_data);
    
    $request_data = [
        'requests' => [
            [
                'image' => ['content' => $base64_image],
                'features' => [
                    ['type' => 'LABEL_DETECTION', 'maxResults' => 5]
                ]
            ]
        ]
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<h3>Réponse HTTP : $http_code</h3>";
    
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        
        if (isset($data['responses'][0]['labelAnnotations'])) {
            echo "✅ Vision API fonctionne !<br>";
            echo "Labels détectés :<br>";
            foreach ($data['responses'][0]['labelAnnotations'] as $label) {
                echo "- " . $label['description'] . " (" . round($label['score'] * 100) . "%)<br>";
            }
            return $data;
        } else {
            echo "❌ Aucun label détecté<br>";
            echo "<pre>" . print_r($data, true) . "</pre>";
        }
    } else {
        echo "❌ Erreur API<br>";
        echo "Code HTTP : $http_code<br>";
        echo "Erreur : $error<br>";
        
        if ($response) {
            $error_data = json_decode($response, true);
            echo "<pre>" . print_r($error_data, true) . "</pre>";
        }
    }
    
    return null;
}

// Exécuter le test
$result = testVisionAPI($test_image_url);

if ($result) {
    echo "<h2 style='color:green'>✅ Google Vision API fonctionne correctement</h2>";
} else {
    echo "<h2 style='color:red'>❌ Problème avec Google Vision API</h2>";
    echo "<p>Vérifiez :</p>";
    echo "<ul>";
    echo "<li>1. La clé API est-elle correcte ?</li>";
    echo "<li>2. L'API Vision est-elle activée sur Google Cloud ?</li>";
    echo "<li>3. Les quotas sont-ils épuisés ?</li>";
    echo "<li>4. La clé a-t-elle les bonnes restrictions ?</li>";
    echo "</ul>";
}

// Test de la fonction extractKeywordsFromVisionAnalysis
echo "<h2>3. Test de l'extraction de mots-clés</h2>";

if ($result) {
    function testKeywordExtraction($vision_data) {
        $keywords = [];
        
        if (!isset($vision_data['responses'][0])) {
            return $keywords;
        }
        
        $response = $vision_data['responses'][0];
        
        // Extraire les labels
        if (isset($response['labelAnnotations'])) {
            foreach ($response['labelAnnotations'] as $label) {
                if ($label['score'] > 0.7) {
                    $keywords[] = strtolower($label['description']);
                }
            }
        }
        
        return $keywords;
    }
    
    $keywords = testKeywordExtraction($result);
    echo "Mots-clés extraits :<br>";
    echo "<ul>";
    foreach ($keywords as $keyword) {
        echo "<li>$keyword</li>";
    }
    echo "</ul>";
}

echo "<h2>4. Test de recherche TMDb</h2>";

// Test recherche TMDb avec un mot-clé
$test_keyword = 'batman';
$search_url = TMDB_BASE_URL . 'search/multi?api_key=' . TMDB_API_KEY . 
              '&language=fr-FR&query=' . urlencode($test_keyword) . '&page=1';

$response = @file_get_contents($search_url);
if ($response) {
    $data = json_decode($response, true);
    echo "✅ TMDb API fonctionne<br>";
    echo "Résultats pour '$test_keyword' : " . count($data['results'] ?? []) . "<br>";
    
    if (!empty($data['results'])) {
        echo "Premier résultat : " . ($data['results'][0]['title'] ?? $data['results'][0]['name'] ?? 'N/A') . "<br>";
    }
} else {
    echo "❌ Problème avec TMDb API<br>";
}
?>