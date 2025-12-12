<?php
// pages/cinemas.php - Version améliorée avec AJAX
ob_start();
session_start();
include '../includes/config.conf.php';

function geocodeCity($city) {
    $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($city) . '&limit=1';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'CineTrack/1.0',
        CURLOPT_HTTPHEADER => ['Accept-Language: fr']
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data[0])) {
            return [
                'lat' => $data[0]['lat'],
                'lon' => $data[0]['lon'],
                'display_name' => $data[0]['display_name']
            ];
        }
    }
    
    $defaultCities = [
        'paris' => ['lat' => 48.8566, 'lon' => 2.3522, 'display_name' => 'Paris, France'],
        'lyon' => ['lat' => 45.7640, 'lon' => 4.8357, 'display_name' => 'Lyon, France'],
        'marseille' => ['lat' => 43.2965, 'lon' => 5.3698, 'display_name' => 'Marseille, France'],
    ];
    
    $cityLower = strtolower($city);
    if (isset($defaultCities[$cityLower])) {
        return $defaultCities[$cityLower];
    }
    
    return ['lat' => 48.8566, 'lon' => 2.3522, 'display_name' => 'Paris, France'];
}

$city = isset($_GET['city']) ? trim($_GET['city']) : 'Paris';
$geoData = geocodeCity($city);
$latitude = isset($_GET['lat']) ? floatval($_GET['lat']) : $geoData['lat'];
$longitude = isset($_GET['lon']) ? floatval($_GET['lon']) : $geoData['lon'];
$displayCity = $geoData['display_name'];
$radius = isset($_GET['radius']) ? intval($_GET['radius']) : 5000;

function fetchCinemasFromOverpass($lat, $lon, $radius = 5000) {
    $query = '[out:json][timeout:30];
    (
      node["amenity"="cinema"](around:' . $radius . ',' . $lat . ',' . $lon . ');
      way["amenity"="cinema"](around:' . $radius . ',' . $lat . ',' . $lon . ');
      relation["amenity"="cinema"](around:' . $radius . ',' . $lat . ',' . $lon . ');
    );
    out body;
    >;
    out skel qt;';
    
    $url = 'https://overpass-api.de/api/interpreter?data=' . urlencode($query);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'CineTrack/1.0'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        $cinemas = [];
        
        foreach ($data['elements'] as $element) {
            if (isset($element['tags']['name']) && isset($element['lat']) && isset($element['lon'])) {
                $cinemas[] = [
                    'id' => 'cinema_' . $element['id'],
                    'original_id' => $element['id'],
                    'name' => $element['tags']['name'],
                    'address' => ($element['tags']['addr:street'] ?? '') . ' ' . ($element['tags']['addr:housenumber'] ?? ''),
                    'city' => $element['tags']['addr:city'] ?? '',
                    'postcode' => $element['tags']['addr:postcode'] ?? '',
                    'latitude' => $element['lat'],
                    'longitude' => $element['lon'],
                    'website' => $element['tags']['website'] ?? '',
                    'phone' => $element['tags']['phone'] ?? '',
                    'capacity' => $element['tags']['capacity'] ?? rand(200, 500),
                    'screens' => rand(5, 15)
                ];
            }
        }
        
        return $cinemas;
    }
    
    return getFallbackCinemas($lat, $lon);
}

function getFallbackCinemas($lat, $lon) {
    $cinemas = [];
    $cinemaNames = [
        'CinéMax Premium', 'UGC Ciné Cité', 'MK2 Nation', 'Pathé Gaumont', 
        'Le Grand Rex', 'Cinéma Le Français', 'Cinéma Paramount', 'Le Balzac'
    ];
    
    $streetNames = [
        'Rue de la République', 'Avenue des Champs-Élysées', 'Boulevard Saint-Germain',
        'Place de la Comédie', 'Avenue Jean Médecin', 'Place Bellecour'
    ];
    
    for ($i = 0; $i < 8; $i++) {
        $cinemaLat = $lat + (rand(-200, 200) / 10000);
        $cinemaLon = $lon + (rand(-200, 200) / 10000);
        
        $cinemas[] = [
            'id' => 'cinema_fallback_' . $i,
            'original_id' => 'fallback_' . $i,
            'name' => $cinemaNames[$i],
            'address' => rand(1, 200) . ' ' . $streetNames[array_rand($streetNames)],
            'city' => 'Paris',
            'postcode' => '750' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
            'latitude' => $cinemaLat,
            'longitude' => $cinemaLon,
            'website' => 'https://example.com',
            'phone' => '01 ' . rand(40, 49) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
            'capacity' => rand(300, 600),
            'screens' => rand(8, 16)
        ];
    }
    
    return $cinemas;
}

function fetchRandomMovies($count = 20) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'movie/popular?api_key=' . $api_key . '&language=fr-FR&page=' . rand(1, 5);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'CineTrack/1.0'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        $movies = $data['results'] ?? [];
        
        shuffle($movies);
        $selectedMovies = array_slice($movies, 0, $count);
        
        $formattedMovies = [];
        foreach ($selectedMovies as $movie) {
            $formattedMovies[] = [
                'id' => $movie['id'],
                'title' => $movie['title'],
                'poster' => TMDB_IMAGE_BASE_URL . 'w500' . ($movie['poster_path'] ?? ''),
                'backdrop' => TMDB_IMAGE_BASE_URL . 'original' . ($movie['backdrop_path'] ?? ''),
                'release_date' => $movie['release_date'] ?? '',
                'overview' => $movie['overview'] ?? '',
                'vote_average' => round($movie['vote_average'], 1),
                'duration' => rand(90, 180)
            ];
        }
        
        return $formattedMovies;
    }
    
    return [];
}

$cinemas = fetchCinemasFromOverpass($latitude, $longitude, $radius);
$total_cinemas = count($cinemas);
$randomMovies = fetchRandomMovies(20);

foreach ($cinemas as &$cinema) {
    $numFilms = rand(5, 8);
    shuffle($randomMovies);
    $cinema['films'] = array_slice($randomMovies, 0, $numFilms);
    $cinema['cinema_image'] = !empty($cinema['films'][0]['backdrop']) ? $cinema['films'][0]['backdrop'] : $cinema['films'][0]['poster'];
    
    foreach ($cinema['films'] as &$film) {
        $film['sessions'] = ['10:30', '13:00', '15:30', '18:00', '20:30', '22:45'];
        shuffle($film['sessions']);
        $film['sessions'] = array_slice($film['sessions'], 0, rand(3, 5));
        sort($film['sessions']);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cinémas - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico">
    
    <style>
        /* NOUVEAU THÈME - Épuré, Élégant, Sombre */
        :root {
    --primary-color: #1a1a2e;       /* Fond principal très sombre bleuté */
    --secondary-color: #16213e;     /* Fond secondaire bleu marine */
    --accent-color: #3b82f6;        /* Bleu vif pour les accents */
    --accent-secondary: #8b5cf6;    /* Violet pour secondaires */
    --text-primary: #ffffff;
    --text-secondary: #b0b0c0;
    --border-color: #2d3748;        /* Bordure bleu-gris foncé */
    --gradient-primary: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
}
        body {
            background-color: #0f0f1a;
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        /* Hero Section - Redesign complet */
        .cinemas-hero {
             padding: 100px 20px 60px;
    background: linear-gradient(145deg, 
        rgba(26, 26, 46, 0.95) 0%,     /* #1a1a2e */
        rgba(22, 33, 62, 0.98) 100%);  /* #16213e */
            position: relative;
            overflow: hidden;
            min-height: 70vh;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }
        
        .hero-content-wrapper {
            position: relative;
            z-index: 10;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        .hero-title {
            font-size: clamp(3.5rem, 8vw, 6rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            line-height: 1.1;
        }
        
      .hero-title .highlight {
    color: var(--accent-color);
    background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
        
        .hero-title .highlight::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--accent-color) 0%, transparent 100%);
            opacity: 0.3;
            z-index: -1;
        }
        
        .hero-subtitle {
            font-size: clamp(1.2rem, 2.5vw, 1.8rem);
            color: var(--text-secondary);
            margin-bottom: 3rem;
            max-width: 700px;
            line-height: 1.6;
            font-weight: 300;
        }
        
        /* Stats Grid - Épuré */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 4rem auto;
            max-width: 1200px;
        }
        
        .stat-card {
            background: var(--primary-color);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-color);
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.1);
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--accent-color);
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        
        .stat-description {
            color: #8888a0;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        /* Barre de recherche - Épurée */
        .search-container {
            max-width: 600px;
            margin: 3rem auto;
            position: relative;
        }
        
        .search-wrapper {
            position: relative;
            background: var(--primary-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 4px;
            transition: all 0.3s ease;
        }
        
        .search-wrapper:focus-within {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }
        
        .search-input {
            width: 100%;
            padding: 1.2rem 1.5rem;
            font-size: 1.1rem;
            background: transparent;
            border: none;
            color: var(--text-primary);
            outline: none;
        }
        
        .search-input::placeholder {
            color: var(--text-secondary);
        }
        
        .search-button {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--accent-color);
            border: none;
            border-radius: 8px;
            width: 50px;
            height: 50px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .search-button:hover {
            background: #ff5252;
            transform: translateY(-50%) scale(1.05);
        }
        
        /* Grille de cinémas - Design épuré */
        .cinemas-grid-section {
            padding: 5rem 1rem;
            background: #0a0a14;
        }
        
        .section-title {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 700;
            text-align: center;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .section-subtitle {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 4rem;
            font-size: 1.1rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cinemas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .cinema-card {
            background: var(--primary-color);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .cinema-card:hover {
            transform: translateY(-10px);
            border-color: var(--accent-color);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .cinema-header {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        
        .cinema-header img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        
        .cinema-card:hover .cinema-header img {
            transform: scale(1.05);
        }
        
        .cinema-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
        }
        
        .cinema-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .cinema-badge {
            display: inline-block;
            background: var(--accent-color);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .cinema-body {
            padding: 1.5rem;
        }
        
        .cinema-info {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        
        .cinema-info i {
            margin-right: 0.5rem;
            color: var(--accent-color);
        }
        
        .cinema-details {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-item {
            flex: 1;
            text-align: center;
            padding: 0.8rem;
            background: rgba(255, 107, 107, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(255, 107, 107, 0.2);
        }
        
        .detail-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent-color);
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }
        
        .cinema-actions {
            display: flex;
            gap: 1rem;
        }
        
        .action-btn {
            flex: 1;
            padding: 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .primary-btn {
    background: var(--gradient-primary);
    color: white;
    border: none;
}

.primary-btn:hover {
    background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
}

.secondary-btn:hover {
    border-color: var(--accent-color);
    color: var(--accent-color);
    background: rgba(59, 130, 246, 0.1);
}



.detail-item {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.detail-value {
    color: var(--accent-color);
}

.session-btn {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: var(--accent-color);
}

.session-btn:hover {
    background: var(--accent-color);
    color: white;
}

/* ARRONDIR TOUS LES ÉLÉMENTS */

/* Cartes de cinéma */
.cinema-card {
    border-radius: 20px !important; /* Augmentez ceci */
}

/* Boutons */
.action-btn, .primary-btn, .secondary-btn, .session-btn {
    border-radius: 12px !important; /* Arrondi généreux */
}

/* Cartes statistiques */
.stat-card {
    border-radius: 20px !important;
}

/* Modal et conteneurs */
.modal-content {
    border-radius: 24px !important;
}

/* Champs de recherche */
.search-wrapper {
    border-radius: 16px !important;
}

.search-input {
    border-radius: 12px !important;
}

/* Cartes de film dans le modal */
.film-card {
    border-radius: 16px !important;
}

/* Détails dans les cartes */
.detail-item {
    border-radius: 12px !important;
}

/* Badges */
.cinema-badge {
    border-radius: 12px !important;
}

/* Conteneur map */
.map-container {
    border-radius: 20px !important;
}

/* Contrôles map */
.map-controls {
    border-radius: 16px !important;
}

/* Suggestions de recherche */
.suggestions-container {
    border-radius: 16px !important;
}

.suggestion-item {
    border-radius: 8px !important;
}

/* Bouton de recherche */
.search-button {
    border-radius: 12px !important;
}
        /* Carte Interactive */
        .map-section {
            padding: 5rem 1rem;
            background: var(--primary-color);
        }
        
        .map-container {
            max-width: 1200px;
            margin: 0 auto;
            height: 500px;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .map-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(26, 26, 40, 0.9);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            max-width: 250px;
        }
        
        /* Modals */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 15, 26, 0.95);
            backdrop-filter: blur(10px);
            z-index: 2000;
            overflow-y: auto;
            padding: 2rem;
        }
        
        .modal-content {
            background: var(--primary-color);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            max-width: 1000px;
            margin: 2rem auto;
            position: relative;
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .close-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 2rem;
            cursor: pointer;
            padding: 0.5rem;
            transition: color 0.3s ease;
        }
        
        .close-btn:hover {
            color: var(--accent-color);
        }
        
        .films-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            padding: 2rem;
        }
        
        .film-card {
            background: var(--secondary-color);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .film-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-color);
        }
        
        .film-poster {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        
        .film-info {
            padding: 1.5rem;
        }
        
        .film-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .film-meta {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .sessions-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .session-btn {
            padding: 0.5rem 1rem;
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid rgba(255, 107, 107, 0.3);
            color: var(--accent-color);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .session-btn:hover {
            background: var(--accent-color);
            color: white;
        }
        
        /* Loader */
        .loader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 15, 26, 0.95);
            backdrop-filter: blur(10px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .loader-spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(255, 107, 107, 0.3);
            border-top-color: var(--accent-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Suggestions de recherche */
        .suggestions-container {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--primary-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-top: 0.5rem;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
        }
        
        .suggestion-item {
            padding: 1rem 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .suggestion-item:last-child {
            border-bottom: none;
        }
        
        .suggestion-item:hover {
            background: rgba(255, 107, 107, 0.1);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .cinemas-hero {
                padding: 80px 15px 40px;
                min-height: 60vh;
            }
            
           
.hero-title {
    font-size: clamp(2.5rem, 6vw, 4rem) !important; /* Réduit de 3.5-6rem à 2.5-4rem */
    margin-bottom: 1rem !important;
}

/* Réduire le sous-titre */
.hero-subtitle {
    font-size: clamp(1rem, 2vw, 1.4rem) !important; /* Réduit de 1.2-1.8rem à 1-1.4rem */
    margin-bottom: 2rem !important;
}

/* Réduire les chiffres des statistiques */
.stat-number {
    font-size: 2.5rem !important; /* Réduit de 3.5rem */
    margin-bottom: 0.25rem !important;
}

/* Réduire les cartes de stats si nécessaire */
.stat-card {
    padding: 1.5rem !important; /* Réduit de 2rem */
}

/* Si les stats prennent trop de place verticalement, ajustez le grid */
.stats-grid {
    margin: 2rem auto !important; /* Réduit de 4rem */
    gap: 1rem !important; /* Réduit de 1.5rem */
}
          
            
            .map-container {
                height: 400px;
            }
            
            .map-controls {
                position: relative;
                top: auto;
                right: auto;
                margin: 1rem auto;
                max-width: 100%;
            }
            
            .modal-overlay {
                padding: 1rem;
            }
            
            .films-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
            
            .cinema-actions {
                flex-direction: column;
            }
            
            .search-container {
                margin: 2rem auto;
            }
        }
        
        @media (max-width: 480px) {
            .cinema-details {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .detail-item {
                padding: 0.6rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <!-- Loader -->
    <div id="loader" class="loader">
        <div class="loader-spinner"></div>
    </div>
    
    <!-- Hero Section -->
    <section class="cinemas-hero">
        <div class="hero-content-wrapper">
            <h1 class="hero-title">
                Découvrez les <span class="highlight">cinémas</span> près de chez vous
            </h1>
            
            <p class="hero-subtitle">
                Consultez les séances, et réservez vos places en quelques clics. 
                L'expérience cinéma réinventée.
            </p>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_cinemas; ?></div>
                    <div class="stat-label">Cinémas</div>
                    <div class="stat-description">dans un rayon de <?php echo $radius/1000; ?>km</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($randomMovies); ?></div>
                    <div class="stat-label">Films</div>
                    <div class="stat-description">diffusés actuellement</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($total_cinemas * 15, 0, ',', ' '); ?></div>
                    <div class="stat-label">Salles</div>
                    <div class="stat-description">disponibles au total</div>
                </div>
            </div>
            
            <!-- Barre de recherche -->
            <div class="search-container">
                <div class="search-wrapper">
                    <input type="text" 
                           id="citySearchInput"
                           class="search-input" 
                           placeholder="Entrez votre ville... (ex: Paris, Lyon, Marseille)"
                           value="<?php echo htmlspecialchars($city); ?>"
                           autocomplete="off">
                    <button id="searchButton" class="search-button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div id="searchSuggestions" class="suggestions-container"></div>
                
                <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem; flex-wrap: wrap;">
                    <button onclick="useMyLocation()" class="secondary-btn" style="padding: 0.8rem 1.5rem;">
                        <i class="fas fa-location-crosshairs"></i>
                        Ma localisation
                    </button>
                    
                    <button onclick="showRadiusModal()" class="secondary-btn" style="padding: 0.8rem 1.5rem;">
                        <i class="fas fa-sliders-h"></i>
                        Rayon : <?php echo $radius/1000; ?>km
                    </button>
                </div>
            </div>
            
<div style="text-align: center; margin-top: 2rem;">
    <button onclick="scrollToMap()" class="primary-btn" style="padding: 1rem 2rem; font-size: 1.1rem;">
        <i class="fas fa-map-marked-alt"></i>
        Voir tous les cinémas sur la carte
    </button>
</div>
        </div>
    </section>
    
    <!-- Grille de cinémas -->
    <section class="cinemas-grid-section">
        <div class="container">
            <h2 class="section-title">Nos Cinémas</h2>
            <p class="section-subtitle">
                <?php echo $total_cinemas; ?> cinémas trouvés près de <strong><?php echo htmlspecialchars($city); ?></strong>
            </p>
            
            <?php if($total_cinemas > 0): ?>
                <div class="cinemas-grid">
                    <?php foreach($cinemas as $index => $cinema): ?>
                    <div class="cinema-card">
                        <div class="cinema-header">
                            <img src="<?php echo $cinema['cinema_image']; ?>" alt="<?php echo htmlspecialchars($cinema['name']); ?>">
                            <div class="cinema-overlay">
                                <h3 class="cinema-name"><?php echo htmlspecialchars($cinema['name']); ?></h3>
                                <div class="cinema-badge">
                                    <?php echo count($cinema['films']); ?> films
                                </div>
                            </div>
                        </div>
                        
                        <div class="cinema-body">
                            <div class="cinema-info">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($cinema['address'] . ', ' . $cinema['city']); ?></span>
                            </div>
                            
                            <div class="cinema-details">
                                <div class="detail-item">
                                    <div class="detail-value"><?php echo $cinema['screens']; ?></div>
                                    <div class="detail-label">Salles</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-value"><?php echo $cinema['capacity']; ?></div>
                                    <div class="detail-label">Places</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-value"><?php echo $cinema['phone']; ?></div>
                                    <div class="detail-label">Téléphone</div>
                                </div>
                            </div>
                            
                            <div class="cinema-actions">
                                <button class="action-btn primary-btn" onclick="showFilms(<?php echo $index; ?>)">
                                    <i class="fas fa-ticket-alt"></i>
                                    Voir les séances
                                </button>
                                <button class="action-btn secondary-btn" onclick="zoomToCinema(<?php echo $cinema['latitude']; ?>, <?php echo $cinema['longitude']; ?>, '<?php echo addslashes($cinema['name']); ?>')">
                                    <i class="fas fa-map"></i>
                                    Localiser
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem 2rem; background: var(--primary-color); border-radius: 16px; border: 1px solid var(--border-color); margin-top: 2rem;">
                    <i class="fas fa-film" style="font-size: 3rem; color: var(--accent-color); margin-bottom: 1.5rem; opacity: 0.5;"></i>
                    <h3 style="font-size: 1.8rem; color: var(--text-primary); margin-bottom: 1rem;">Aucun cinéma trouvé</h3>
                    <p style="color: var(--text-secondary); max-width: 500px; margin: 0 auto 2rem;">
                        Aucun cinéma n'a été trouvé dans un rayon de <?php echo $radius/1000; ?>km autour de <?php echo htmlspecialchars($city); ?>.
                    </p>
                    <button onclick="increaseRadius()" class="primary-btn" style="padding: 1rem 2rem; font-size: 1rem;">
                        <i class="fas fa-expand-alt"></i> 
                        Augmenter le rayon de recherche
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Carte Interactive -->
    <section class="map-section">
        <div class="container">
            <h2 class="section-title">Carte Interactive</h2>
            <p class="section-subtitle">
                Localisez les cinémas et visualisez le rayon de recherche
            </p>
            
            <div class="map-container">
                <div id="map" style="width: 100%; height: 100%;"></div>
                <div class="map-controls">
                    <h4 style="color: var(--text-primary); margin-bottom: 1rem; font-size: 1rem;">
                        <i class="fas fa-sliders-h" style="margin-right: 0.5rem;"></i>
                        Contrôles
                    </h4>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; color: var(--text-secondary); margin-bottom: 0.5rem; font-size: 0.9rem;">
                            Rayon : <span id="radiusValue"><?php echo $radius/1000; ?></span> km
                        </label>
                        <input type="range" min="1" max="20" value="<?php echo $radius/1000; ?>" 
                               class="radius-slider" id="radiusSlider" onchange="updateRadius(this.value)"
                               style="width: 100%; height: 6px; background: var(--border-color); border-radius: 3px; outline: none;">
                    </div>
                    <button onclick="recenterMap()" class="secondary-btn" style="width: 100%;">
                        <i class="fas fa-crosshairs"></i>
                        Recentrer la carte
                    </button>
                    <button onclick="showAllCinemasOnMap()" class="primary-btn" style="width: 100%; margin-top: 1rem;">
    <i class="fas fa-expand"></i>
    Voir tous les cinémas
</button>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Modal Films -->
    <div id="filmsModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalCinemaName" class="modal-title"></h2>
                <button onclick="closeFilmsModal()" class="close-btn">×</button>
            </div>
            <div id="filmsGrid" class="films-grid"></div>
        </div>
    </div>
    
    <!-- Modal Réservation -->
    <!-- Modal Réservation -->
<div id="reservationModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2 class="modal-title">Réserver</h2>
            <button onclick="closeReservationModal()" class="close-btn">×</button>
        </div>
        <div style="padding: 2rem;">
            <!-- Nouveau design comme votre image -->
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent-color); margin-bottom: 0.5rem;" id="displayCinemaName"></div>
                <div style="font-size: 0.9rem; color: var(--text-secondary);">Cinéma</div>
            </div>
            
            <div style="background: rgba(59, 130, 246, 0.1); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                    <div style="width: 40px; height: 40px; background: var(--accent-color); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; margin-right: 1rem;">
                        <i class="fas fa-film"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.9rem; color: var(--text-secondary);">Film</div>
                        <div style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary);" id="displayFilmTitle"></div>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center;">
                    <div style="width: 40px; height: 40px; background: var(--accent-secondary); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; margin-right: 1rem;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.9rem; color: var(--text-secondary);">Séance</div>
                        <div style="font-size: 1.1rem; font-weight: 600; color: var(--accent-color);" id="displaySession"></div>
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Date de la séance</div>
                <input type="date" id="resDate" class="search-input" style="width: 100%; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2);">
            </div>
            
            <div style="margin-bottom: 2rem;">
                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Nombre de places</div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <input type="number" id="resTickets" min="1" max="10" value="1" 
                           onchange="updatePrice()" 
                           style="flex: 1; padding: 0.8rem; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px; color: var(--text-primary);">
                    <div style="font-size: 1.1rem; font-weight: 600; color: var(--accent-color);">
                        <span id="ticketPrice">12.50</span>€
                    </div>
                </div>
            </div>
            
            <div style="padding: 1.5rem; background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%); border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.3); margin-bottom: 2rem;">
                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Prix Total</div>
                <div style="font-size: 2rem; font-weight: 700; color: var(--accent-color);">
                    €<span id="totalPrice">12.50</span>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button onclick="closeReservationModal()" class="secondary-btn" style="flex: 1; padding: 1rem;">
                    Annuler
                </button>
                <button onclick="confirmReservation()" class="primary-btn" style="flex: 1; padding: 1rem;">
                    <i class="fas fa-check"></i>
                    Confirmer la réservation
                </button>
            </div>
        </div>
    </div>
</div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const cinemas = <?php echo json_encode($cinemas); ?>;
        let map;
        let currentRadiusCircle;
        let markers = [];
        let selectedReservation = {};
        
        // API de géocodage
        async function searchCities(query) {
            if (query.length < 2) {
                document.getElementById('searchSuggestions').style.display = 'none';
                return;
            }
            
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}+France&countrycodes=fr&limit=8`);
                const data = await response.json();
                
                const suggestionsDiv = document.getElementById('searchSuggestions');
                if (data.length > 0) {
                    suggestionsDiv.innerHTML = data.map(place => `
                        <div class="suggestion-item" onclick="selectSuggestion('${place.display_name}', ${place.lat}, ${place.lon})">
                            <i class="fas fa-map-marker-alt" style="color: var(--accent-color);"></i>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: var(--text-primary);">${place.display_name.split(',')[0]}</div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary);">${place.display_name.split(',').slice(1, 3).join(',')}</div>
                            </div>
                        </div>
                    `).join('');
                    suggestionsDiv.style.display = 'block';
                } else {
                    suggestionsDiv.style.display = 'none';
                }
            } catch (error) {
                console.error('Erreur de recherche:', error);
            }
        }
        
        function selectSuggestion(displayName, lat, lon) {
            const searchInput = document.getElementById('citySearchInput');
            searchInput.value = displayName.split(',')[0];
            document.getElementById('searchSuggestions').style.display = 'none';
            
            if (lat && lon) {
                performSearch(displayName.split(',')[0], lat, lon);
            } else {
                geocodeCity(displayName.split(',')[0]);
            }
        }
        
        async function geocodeCity(city) {
            showLoader();
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(city)}+France&countrycodes=fr&limit=1`);
                const data = await response.json();
                
                if (data.length > 0) {
                    performSearch(city, data[0].lat, data[0].lon);
                } else {
                    hideLoader();
                    alert('Ville non trouvée. Essayez une autre ville.');
                }
            } catch (error) {
                hideLoader();
                alert('Erreur de connexion. Réessayez.');
            }
        }
        
        function performSearch(city, lat, lon) {
            const radius = document.getElementById('radiusSlider').value * 1000;
            window.location.href = `?city=${encodeURIComponent(city)}&lat=${lat}&lon=${lon}&radius=${radius}`;
        }
        // Fonction pour descendre directement à la carte
function scrollToMap() {
    document.querySelector('.map-section').scrollIntoView({
        behavior: 'smooth'
    });
}

// Fonction pour zoomer sur un cinéma spécifique depuis le bouton "Localiser"
function zoomToCinema(lat, lon, name) {
    // D'abord, on descend à la carte
    scrollToMap();
    
    // Ensuite on zoome sur le cinéma
    setTimeout(() => {
        map.setView([lat, lon], 16);
        
        // On ouvre le popup du cinéma
        markers.forEach(marker => {
            if (marker.getLatLng().lat === lat && marker.getLatLng().lng === lon) {
                marker.openPopup();
            }
        });
    }, 500); // Petit délai pour que le scroll se termine
}

// Fonction pour afficher tous les cinémas sur la carte
function showAllCinemasOnMap() {
    // Calculer les bounds pour afficher tous les cinémas
    if (markers.length > 0) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
}
        function useMyLocation() {
            if (navigator.geolocation) {
                showLoader();
                navigator.geolocation.getCurrentPosition(
                    position => {
                        const radius = document.getElementById('radiusSlider').value * 1000;
                        window.location.href = `?city=Ma position&lat=${position.coords.latitude}&lon=${position.coords.longitude}&radius=${radius}`;
                    },
                    error => {
                        hideLoader();
                        alert('Impossible d\'obtenir votre position. Vérifiez les autorisations.');
                    }
                );
            } else {
                alert('La géolocalisation n\'est pas supportée par votre navigateur.');
            }
        }
        
        function updateRadius(km) {
            document.getElementById('radiusValue').textContent = km;
            if (currentRadiusCircle) {
                currentRadiusCircle.setRadius(km * 1000);
            }
        }
        
        function increaseRadius() {
            const currentRadius = parseInt(document.getElementById('radiusSlider').value);
            const newRadius = Math.min(currentRadius + 5, 50);
            document.getElementById('radiusSlider').value = newRadius;
            document.getElementById('radiusValue').textContent = newRadius;
            
            const url = new URL(window.location.href);
            url.searchParams.set('radius', newRadius * 1000);
            window.location.href = url.toString();
        }
        
        function showRadiusModal() {
            const currentRadius = document.getElementById('radiusSlider').value;
            const newRadius = prompt(`Entrez le nouveau rayon de recherche (en km) :`, currentRadius);
            
            if (newRadius !== null && !isNaN(newRadius) && newRadius >= 1 && newRadius <= 50) {
                document.getElementById('radiusSlider').value = newRadius;
                updateRadius(newRadius);
                
                const url = new URL(window.location.href);
                url.searchParams.set('radius', newRadius * 1000);
                window.location.href = url.toString();
            }
        }
        
        function initMap() {
            map = L.map('map').setView([<?php echo $latitude; ?>, <?php echo $longitude; ?>], 13);
            
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);
            
            // Cercle du rayon
            currentRadiusCircle = L.circle([<?php echo $latitude; ?>, <?php echo $longitude; ?>], {
                color: '#ff6b6b',
                fillColor: '#ff6b6b',
                fillOpacity: 0.1,
                radius: <?php echo $radius; ?>
            }).addTo(map);
            
            // Marqueurs des cinémas
            cinemas.forEach(cinema => {
                const marker = L.marker([cinema.latitude, cinema.longitude], {
                    icon: L.divIcon({
                        className: 'cinema-marker',
                        html: `<div style="background: #ff6b6b; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; border: 3px solid white; box-shadow: 0 2px 10px rgba(0,0,0,0.3);"><i class="fas fa-film"></i></div>`,
                        iconSize: [40, 40]
                    })
                }).addTo(map);
                
                marker.bindPopup(`
                    <div style="min-width: 250px;">
                        <h3 style="color: #ff6b6b; font-weight: 700; margin-bottom: 0.5rem;">${cinema.name}</h3>
                        <p style="color: #666; margin-bottom: 0.5rem;"><i class="fas fa-map-marker-alt"></i> ${cinema.address}</p>
                        <p style="color: #666; margin-bottom: 1rem;"><i class="fas fa-film"></i> ${cinema.films.length} films</p>
                        <button onclick="zoomToCinema(${cinema.latitude}, ${cinema.longitude}, '${cinema.name.replace(/'/g, "\\'")}')" 
                                style="background: #ff6b6b; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; width: 100%;">
                            <i class="fas fa-search"></i> Voir les séances
                        </button>
                    </div>
                `);
                
                markers.push(marker);
            });
            
            // Marqueur du centre
            L.marker([<?php echo $latitude; ?>, <?php echo $longitude; ?>], {
                icon: L.divIcon({
                    className: 'center-marker',
                    html: `<div style="background: #2a2a3c; width: 30px; height: 30px; border-radius: 50%; border: 3px solid #ff6b6b; box-shadow: 0 2px 10px rgba(0,0,0,0.3);"></div>`,
                    iconSize: [30, 30]
                })
            }).addTo(map).bindPopup('Centre de recherche');
        }
        
        function zoomToCinema(lat, lon, name) {
            map.setView([lat, lon], 16);
            markers.forEach(marker => {
                if (marker.getLatLng().lat === lat && marker.getLatLng().lng === lon) {
                    marker.openPopup();
                }
            });
        }
        
        function recenterMap() {
            map.setView([<?php echo $latitude; ?>, <?php echo $longitude; ?>], 13);
        }
        
        function showLoader() {
            document.getElementById('loader').style.display = 'flex';
        }
        
        function hideLoader() {
            document.getElementById('loader').style.display = 'none';
        }
        
        function showFilms(cinemaIndex) {
            const cinema = cinemas[cinemaIndex];
            document.getElementById('modalCinemaName').textContent = cinema.name;
            
            const filmsGrid = document.getElementById('filmsGrid');
            filmsGrid.innerHTML = '';
            
            cinema.films.forEach(film => {
                const filmCard = document.createElement('div');
                filmCard.className = 'film-card';
                filmCard.innerHTML = `
                    <img src="${film.poster}" alt="${film.title}" class="film-poster">
                    <div class="film-info">
                        <h4 class="film-title">${film.title}</h4>
                        <div class="film-meta">
                            ${film.duration} min • ${film.release_date} • ★ ${film.vote_average}/10
                        </div>
                        <div class="sessions-list">
                            ${film.sessions.map(session => 
                                `<button class="session-btn" onclick="selectSession('${cinema.original_id}', '${cinema.name}', ${film.id}, '${film.title.replace(/'/g, "\\'")}', '${session}')">${session}</button>`
                            ).join('')}
                        </div>
                        <button class="primary-btn" onclick="selectSession('${cinema.original_id}', '${cinema.name}', ${film.id}, '${film.title.replace(/'/g, "\\'")}', '${film.sessions[0]}')" style="width: 100%; padding: 0.8rem;">
                            <i class="fas fa-ticket-alt"></i> RÉSERVER
                        </button>
                    </div>
                `;
                filmsGrid.appendChild(filmCard);
            });
            
            document.getElementById('filmsModal').style.display = 'block';
        }
        
        function closeFilmsModal() {
            document.getElementById('filmsModal').style.display = 'none';
        }
        
        function selectSession(cinemaId, cinemaName, filmId, filmTitle, session) {
            let filmPoster = '';
            let cinemaAddress = '';
            
            for (let cinema of cinemas) {
                for (let film of cinema.films) {
                    if (film.id == filmId) {
                        filmPoster = film.poster;
                        break;
                    }
                }
                if (cinema.original_id == cinemaId) {
                    cinemaAddress = cinema.address + ', ' + cinema.city + ' ' + cinema.postcode;
                }
                if (filmPoster && cinemaAddress) break;
            }
            
            selectedReservation = {
                cinema_id: cinemaId,
                cinema_name: cinemaName,
                film_id: filmId,
                film_title: filmTitle,
                session: session,
                film_poster: filmPoster,
                cinema_address: cinemaAddress
            };
            
            document.getElementById('displayCinemaName').textContent = cinemaName;
            document.getElementById('displayFilmTitle').textContent = filmTitle;
            document.getElementById('displaySession').textContent = session;
            
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('resDate').value = tomorrow.toISOString().split('T')[0];
            
            updatePrice();
            
            closeFilmsModal();
            document.getElementById('reservationModal').style.display = 'block';
        }
        
        function closeReservationModal() {
            document.getElementById('reservationModal').style.display = 'none';
        }
        
        function updatePrice() {
            const tickets = parseInt(document.getElementById('resTickets').value);
            const price = (tickets * 12.50).toFixed(2);
            document.getElementById('totalPrice').textContent = price;
        }
        
        function confirmReservation() {
            const data = {
                cinema_id: selectedReservation.cinema_id,
                cinema_name: selectedReservation.cinema_name,
                cinema_address: selectedReservation.cinema_address,
                film_id: selectedReservation.film_id,
                film_title: selectedReservation.film_title,
                film_poster: selectedReservation.film_poster,
                reservation_date: document.getElementById('resDate').value,
                reservation_time: selectedReservation.session,
                number_tickets: document.getElementById('resTickets').value,
                total_price: document.getElementById('totalPrice').textContent
            };
            
            const formData = new FormData();
            Object.keys(data).forEach(key => formData.append(key, data[key]));
            
            fetch('../api/reserve_cinema.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    alert('✅ Réservation confirmée!\n\nCode: ' + result.reservation_code + '\n\nRetrouvez-la dans votre espace personnel.');
                    closeReservationModal();
                    setTimeout(() => window.location.href = 'user/reservations.php', 1500);
                } else {
                    alert('❌ Erreur: ' + result.message);
                }
            })
            .catch(err => {
                alert('❌ Erreur de connexion. Réessayez.');
            });
        }
        
        // Événements
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            
            // Recherche en temps réel
            const searchInput = document.getElementById('citySearchInput');
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchCities(this.value);
                }, 300);
            });
            
            // Recherche au clic
            document.getElementById('searchButton').addEventListener('click', function() {
                const city = searchInput.value.trim();
                if (city) {
                    geocodeCity(city);
                }
            });
            
            // Recherche avec Entrée
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const city = this.value.trim();
                    if (city) {
                        geocodeCity(city);
                    }
                }
            });
            
            // Fermer suggestions si clic ailleurs
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !document.getElementById('searchSuggestions').contains(e.target)) {
                    document.getElementById('searchSuggestions').style.display = 'none';
                }
            });
        });
        
        // Navigation clavier
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeFilmsModal();
                closeReservationModal();
            }
        });
    </script>
</body>
</html>