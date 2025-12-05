<?php
// test_api.php - À mettre dans ton site pour tester
echo "<h2>🔍 Test API QuizzAPI sur AlwaysData</h2>";

// Test 1: QuizzAPI
echo "<h3>1. Test QuizzAPI (française)</h3>";
$url = "https://quizzapi.jomoreschi.fr/api/v1/quiz?limit=3&category=cinema&language=fr";

echo "URL: " . $url . "<br>";

// Essayer cURL d'abord
if (function_exists('curl_version')) {
    echo "cURL disponible ✅<br>";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'CineTrack-Test'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: " . $http_code . "<br>";
    echo "Error: " . ($error ?: 'Aucune') . "<br>";
    
    if ($http_code === 200) {
        echo "<span style='color:green;'>✅ Succès!</span><br>";
        $data = json_decode($response, true);
        echo "Questions reçues: " . count($data['quizzes'] ?? []) . "<br>";
        
        // Afficher une question exemple
        if (!empty($data['quizzes'][0])) {
            echo "<pre>";
            print_r($data['quizzes'][0]);
            echo "</pre>";
        }
    } else {
        echo "<span style='color:red;'>❌ Échec</span><br>";
    }
} else {
    echo "cURL non disponible ❌<br>";
}

// Test 2: The Trivia API
echo "<h3>2. Test The Trivia API (anglaise)</h3>";
$url2 = "https://the-trivia-api.com/v2/questions?limit=3&categories=film_and_tv";

if (function_exists('curl_version')) {
    $ch2 = curl_init();
    curl_setopt_array($ch2, [
        CURLOPT_URL => $url2,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response2 = curl_exec($ch2);
    $http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    
    echo "HTTP Code: " . $http_code2 . "<br>";
    echo ($http_code2 === 200) ? "✅ Succès" : "❌ Échec";
}

// Test 3: file_get_contents
echo "<h3>3. Test file_get_contents</h3>";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'ON ✅' : 'OFF ❌') . "<br>";

if (ini_get('allow_url_fopen')) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'header' => "User-Agent: CineTrack-Test\r\n"
        ]
    ]);
    
    $test = @file_get_contents("https://quizzapi.jomoreschi.fr/api/v1/quiz?limit=1", false, $context);
    echo ($test !== false) ? "✅ file_get_contents fonctionne" : "❌ file_get_contents échoue";
}
?>