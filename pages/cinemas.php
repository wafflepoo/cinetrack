<?php
// pages/cinemas.php - Version respectant le thème existant
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    
    // Image du cinéma = backdrop du premier film
    $cinema['cinema_image'] = !empty($cinema['films'][0]['backdrop']) ? $cinema['films'][0]['backdrop'] : $cinema['films'][0]['poster'];
    
    foreach ($cinema['films'] as &$film) {
        $film['sessions'] = ['10:30', '13:00', '15:30', '18:00', '20:30', '22:45'];
        shuffle($film['sessions']);
        $film['sessions'] = array_slice($film['sessions'], 0, rand(3, 5));
        sort($film['sessions']);
    }
}

if ($mysqli->connect_error) {
    error_log("Database error: " . $mysqli->connect_error);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎬 Cinémas - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/animations.css">
    
    <style>
        /* Hero avec votre thème orange/violet */
        .cinemas-hero {
            background: linear-gradient(135deg, rgba(255, 140, 0, 0.1) 0%, rgba(120, 0, 255, 0.1) 100%);
            padding: 120px 0 60px;
            position: relative;
            overflow: hidden;
        }
        
        .cinemas-hero::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 140, 0, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(120, 0, 255, 0.2) 0%, transparent 50%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .mega-title {
            font-size: clamp(3rem, 10vw, 8rem);
            font-weight: 900;
            letter-spacing: -0.05em;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #ff8c00 0%, #7800ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: float 6s ease-in-out infinite;
            position: relative;
            z-index: 10;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .mega-subtitle {
            font-size: clamp(1.2rem, 3vw, 2rem);
            color: #9ca3af;
            margin-bottom: 3rem;
            animation: fadeInUp 1s ease forwards 0.5s;
            opacity: 0;
            position: relative;
            z-index: 10;
        }
        
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
            from {
                opacity: 0;
                transform: translateY(30px);
            }
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            background: linear-gradient(135deg, #ff8c00 0%, #ffa500 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .search-mega {
            max-width: 600px;
            margin: 2rem auto;
            animation: fadeInUp 1s ease forwards 1s;
            opacity: 0;
            position: relative;
            z-index: 10;
        }
        
        .search-input-mega {
            width: 100%;
            padding: 1.5rem 2rem;
            font-size: 1.2rem;
            border-radius: 50px;
            border: 2px solid rgba(255, 140, 0, 0.3);
            background: rgba(30, 30, 40, 0.5);
            backdrop-filter: blur(10px);
            color: white;
            transition: all 0.3s;
        }
        
        .search-input-mega:focus {
            outline: none;
            border-color: #ff8c00;
            box-shadow: 0 0 30px rgba(255, 140, 0, 0.4);
        }
        
        /* Slider de cinémas */
        .slider-container {
            position: relative;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 4rem;
        }
        
        .cinema-slide {
            min-width: 100%;
            background: rgba(30, 30, 40, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 140, 0, 0.2);
            border-radius: 30px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .cinema-slide:hover {
            border-color: rgba(255, 140, 0, 0.5);
            box-shadow: 0 20px 60px rgba(255, 140, 0, 0.2);
        }
        
        .cinema-slides {
            display: flex;
            transition: transform 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .cinema-banner {
            position: relative;
            height: 400px;
            overflow: hidden;
        }
        
        .cinema-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .cinema-banner-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 3rem;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.95) 100%);
        }
        
        .cinema-name-mega {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 900;
            color: #fff;
            margin-bottom: 0.5rem;
            text-shadow: 0 0 20px rgba(255, 140, 0, 0.5);
        }
        
        .cinema-meta-mega {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .meta-item-mega {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #9ca3af;
        }
        
        .view-films-btn {
            margin-top: 2rem;
            padding: 1.2rem 3rem;
            font-size: 1.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            border: none;
            border-radius: 50px;
            color: white;
            cursor: pointer;
            transition: all 0.4s;
            box-shadow: 0 10px 30px rgba(255, 140, 0, 0.4);
        }
        
        .view-films-btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 50px rgba(255, 140, 0, 0.6);
        }
        
        /* Navigation */
        .slider-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 140, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 140, 0, 0.3);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 100;
        }
        
        .slider-nav:hover {
            background: rgba(255, 140, 0, 0.8);
            transform: translateY(-50%) scale(1.1);
        }
        
        .slider-nav.prev { left: 0; }
        .slider-nav.next { right: 0; }
        
        .slider-dots {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 3rem;
        }
        
        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .dot.active {
            background: #ff8c00;
            width: 35px;
            border-radius: 10px;
        }
        
        /* Modal Films */
        .films-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .films-modal-content {
            max-width: 1400px;
            margin: 4rem auto;
            padding: 2rem;
        }
        
        .close-films-btn {
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid rgba(239, 68, 68, 0.3);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .close-films-btn:hover {
            background: rgba(239, 68, 68, 0.8);
            transform: rotate(90deg);
        }
        
        .films-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .film-card-mega {
            background: rgba(30, 30, 40, 0.5);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s;
            border: 1px solid rgba(255, 140, 0, 0.2);
        }
        
        .film-card-mega:hover {
            transform: translateY(-10px);
            border-color: rgba(255, 140, 0, 0.5);
            box-shadow: 0 20px 50px rgba(255, 140, 0, 0.3);
        }
        
        .film-poster-mega {
            width: 100%;
            height: 420px;
            object-fit: cover;
        }
        
        .film-info-mega {
            padding: 1.5rem;
        }
        
        .film-title-mega {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
            color: white;
        }
        
        .film-rating {
            display: inline-block;
            background: linear-gradient(135deg, #ff8c00 0%, #ffa500 100%);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .sessions-mega {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }
        
        .session-btn-mega {
            padding: 0.6rem 1.2rem;
            background: rgba(255, 140, 0, 0.1);
            border: 2px solid rgba(255, 140, 0, 0.3);
            border-radius: 10px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .session-btn-mega:hover,
        .session-btn-mega.selected {
            background: rgba(255, 140, 0, 0.3);
            border-color: #ff8c00;
            transform: scale(1.05);
        }
        
        .reserve-btn-film {
            width: 100%;
            padding: 1rem;
            margin-top: 1rem;
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            border: none;
            border-radius: 15px;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .reserve-btn-film:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(255, 140, 0, 0.5);
        }
        
        .reserve-btn-film:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Modal Réservation */
        .reservation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
        }
        
        .reservation-content {
            background: rgba(30, 30, 40, 0.95);
            border: 3px solid rgba(255, 140, 0, 0.3);
            border-radius: 30px;
            padding: 3rem;
            max-width: 600px;
            width: 90%;
            margin: 2rem auto;
        }
        
        .form-group-mega {
            margin-bottom: 1.5rem;
        }
        
        .form-label-mega {
            display: block;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #ff8c00;
        }
        
        .form-input-mega {
            width: 100%;
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 140, 0, 0.2);
            border-radius: 15px;
            color: white;
            font-size: 1rem;
        }
        
        .form-input-mega:focus {
            outline: none;
            border-color: #ff8c00;
            box-shadow: 0 0 20px rgba(255, 140, 0, 0.3);
        }
        
        .price-display {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            padding: 1.5rem;
            border-radius: 20px;
            text-align: center;
            font-size: 2rem;
            font-weight: 900;
            margin: 2rem 0;
        }
        
        .section-title-mega {
            font-size: clamp(2rem, 5vw, 4rem);
            font-weight: 900;
            text-align: center;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ff8c00 0%, #7800ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .map-container-mega {
            max-width: 1400px;
            margin: 0 auto;
            height: 600px;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5);
            border: 3px solid rgba(255, 140, 0, 0.3);
        }
        
        @media (max-width: 768px) {
            .slider-container {
                padding: 0 1rem;
            }
            
            .cinema-banner {
                height: 250px;
            }
            
            .films-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="gradient-bg text-white">
    <?php include '../includes/header.php'; ?>
    
    <!-- Hero -->
    <section class="cinemas-hero">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="mega-title">🎬 CINÉMAS</h1>
            <p class="mega-subtitle">Découvrez les meilleurs cinémas près de chez vous</p>
            
            <div class="flex justify-center gap-8 mb-8">
                <div>
                    <div class="stat-number"><?php echo $total_cinemas; ?></div>
                    <div style="color: #9ca3af;">Cinémas</div>
                </div>
                <div>
                    <div class="stat-number"><?php echo count($randomMovies); ?></div>
                    <div style="color: #9ca3af;">Films</div>
                </div>
                <div>
                    <div class="stat-number"><?php echo $radius/1000; ?>km</div>
                    <div style="color: #9ca3af;">Rayon</div>
                </div>
            </div>
            
            <div class="search-mega">
                <div style="position: relative;">
                    <input type="text" 
                           id="citySearchInput"
                           class="search-input-mega" 
                           placeholder="Entrez votre ville... (ex: Paris, Lyon, Marseille)"
                           value="<?php echo htmlspecialchars($city); ?>"
                           autocomplete="off">
                    <div id="searchSuggestions" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: rgba(30, 30, 40, 0.95); backdrop-filter: blur(10px); border-radius: 20px; margin-top: 0.5rem; border: 2px solid rgba(255, 140, 0, 0.3); max-height: 300px; overflow-y: auto; z-index: 1000;"></div>
                </div>
                <button onclick="useMyLocation()" style="margin-top: 1rem; padding: 1rem 2rem; background: linear-gradient(135deg, #7800ff 0%, #5500cc 100%); border: none; border-radius: 50px; color: white; font-weight: 700; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-location-crosshairs"></i>
                    Utiliser ma localisation
                </button>
            </div>
        </div>
    </section>
    
    <!-- Slider Cinémas -->
    <section style="padding: 6rem 2rem;">
        <h2 class="section-title-mega">Nos Cinémas</h2>
        <p style="text-align: center; color: #9ca3af; margin-bottom: 4rem; font-size: 1.2rem;">
            <?php echo $total_cinemas; ?> cinémas trouvés • Explorez les films
        </p>
        
        <div class="slider-container">
            <div class="slider-nav prev" onclick="changeSlide(-1)">
                <i class="fas fa-chevron-left fa-2x"></i>
            </div>
            
            <div style="overflow: hidden; border-radius: 30px;">
                <div class="cinema-slides" id="cinemaSlides">
                    <?php foreach($cinemas as $index => $cinema): ?>
                    <div class="cinema-slide">
                        <div class="cinema-banner">
                            <img src="<?php echo $cinema['cinema_image']; ?>" alt="<?php echo htmlspecialchars($cinema['name']); ?>">
                            <div class="cinema-banner-overlay">
                                <h3 class="cinema-name-mega"><?php echo htmlspecialchars($cinema['name']); ?></h3>
                                <p style="color: #9ca3af; font-size: 1.1rem;">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($cinema['address'] . ', ' . $cinema['city'] . ' ' . $cinema['postcode']); ?>
                                </p>
                                <div class="cinema-meta-mega">
                                    <div class="meta-item-mega">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo $cinema['phone']; ?></span>
                                    </div>
                                    <div class="meta-item-mega">
                                        <i class="fas fa-chair"></i>
                                        <span><?php echo $cinema['capacity']; ?> places</span>
                                    </div>
                                    <div class="meta-item-mega">
                                        <i class="fas fa-film"></i>
                                        <span><?php echo $cinema['screens']; ?> salles</span>
                                    </div>
                                </div>
                                <button class="view-films-btn" onclick="showFilms(<?php echo $index; ?>)">
                                    <i class="fas fa-play-circle"></i> Voir les films (<?php echo count($cinema['films']); ?>)
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="slider-nav next" onclick="changeSlide(1)">
                <i class="fas fa-chevron-right fa-2x"></i>
            </div>
        </div>
        
        <div class="slider-dots" id="sliderDots"></div>
    </section>
    
    <!-- Carte -->
    <section style="padding: 4rem 2rem; background: rgba(255, 140, 0, 0.02);">
        <h2 class="section-title-mega">Carte Interactive</h2>
        <p style="text-align: center; color: #9ca3af; margin-bottom: 3rem; font-size: 1.1rem;">
            Localisez vos cinémas préférés
        </p>
        <div class="map-container-mega">
            <div id="map" style="width: 100%; height: 100%;"></div>
        </div>
    </section>
    
    <!-- Modal Films -->
    <div id="filmsModal" class="films-modal">
        <div class="films-modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem;">
                <h2 class="section-title-mega" id="modalCinemaName" style="margin: 0;"></h2>
                <button class="close-films-btn" onclick="closeFilmsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="films-grid" id="filmsGrid"></div>
        </div>
    </div>
    
    <!-- Modal Réservation (Design moderne inline) -->
   <!-- Modal Réservation (Design amélioré) -->
    <div id="reservationModal" class="reservation-modal">
        <div class="reservation-content">
            <button class="close-films-btn" onclick="closeReservationModal()" style="position: absolute; top: 1.5rem; right: 1.5rem;">
                <i class="fas fa-times"></i>
            </button>
            
            <div style="text-align: center; margin-bottom: 3rem;">
                <div style="display: inline-block; width: 80px; height: 80px; background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; box-shadow: 0 10px 30px rgba(255, 140, 0, 0.4);">
                    <i class="fas fa-ticket-alt fa-2x" style="color: white;"></i>
                </div>
                <h3 style="font-size: 2.5rem; font-weight: 900; color: white; margin: 0;">
                    Réserver votre séance
                </h3>
                <p style="color: #9ca3af; font-size: 1.1rem; margin-top: 0.5rem;">Complétez les informations ci-dessous</p>
            </div>
            
            <div id="reservationFormContent">
                <div class="form-group-mega">
                    <label class="form-label-mega"><i class="fas fa-building"></i> Cinéma</label>
                    <input type="text" class="form-input-mega" id="displayCinemaName" readonly>
                </div>
                
                <div class="form-group-mega">
                    <label class="form-label-mega"><i class="fas fa-film"></i> Film</label>
                    <input type="text" class="form-input-mega" id="displayFilmTitle" readonly>
                </div>
                
                <div class="form-group-mega">
                    <label class="form-label-mega"><i class="fas fa-clock"></i> Séance</label>
                    <input type="text" class="form-input-mega" id="displaySession" readonly>
                </div>
                
                <div class="form-group-mega">
                    <label class="form-label-mega"><i class="fas fa-calendar"></i> Date de la séance</label>
                    <input type="date" class="form-input-mega" id="resDate" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group-mega">
                    <label class="form-label-mega"><i class="fas fa-users"></i> Nombre de places</label>
                    <select class="form-input-mega" id="resTickets" onchange="updatePrice()">
                        <option value="1">1 place - 12.50€</option>
                        <option value="2">2 places - 25.00€</option>
                        <option value="3">3 places - 37.50€</option>
                        <option value="4">4 places - 50.00€</option>
                        <option value="5">5 places - 62.50€</option>
                        <option value="6">6 places - 75.00€</option>
                    </select>
                </div>
                
                <div class="price-display">
                    <div style="font-size: 1rem; opacity: 0.8; margin-bottom: 0.5rem;">Prix Total</div>
                    <div><span id="totalPrice">12.50</span>€</div>
                </div>
                
                <button onclick="confirmReservation()" class="reserve-btn-film" style="width: 100%; padding: 1.5rem; font-size: 1.3rem;">
                    <i class="fas fa-check-circle"></i> CONFIRMER LA RÉSERVATION
                </button>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const cinemas = <?php echo json_encode($cinemas); ?>;
        let currentSlide = 0;
        let map;
        let selectedReservation = {};
        
        // Villes françaises pour l'autocomplétion
        const frenchCities = [
            'Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 
            'Montpellier', 'Bordeaux', 'Lille', 'Rennes', 'Reims', 'Le Havre', 
            'Saint-Étienne', 'Toulon', 'Grenoble', 'Dijon', 'Angers', 'Nîmes', 
            'Villeurbanne', 'Le Mans', 'Aix-en-Provence', 'Clermont-Ferrand', 'Brest',
            'Tours', 'Amiens', 'Limoges', 'Annecy', 'Perpignan', 'Boulogne-Billancourt'
        ];
        
        // Recherche dynamique avec suggestions
        const searchInput = document.getElementById('citySearchInput');
        const suggestionsDiv = document.getElementById('searchSuggestions');
        
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            
            if (query.length < 2) {
                suggestionsDiv.style.display = 'none';
                return;
            }
            
            const matches = frenchCities.filter(city => 
                city.toLowerCase().startsWith(query) || city.toLowerCase().includes(query)
            ).slice(0, 8);
            
            if (matches.length > 0) {
                suggestionsDiv.innerHTML = matches.map(city => `
                    <div onclick="selectCity('${city}')" style="padding: 1rem 1.5rem; cursor: pointer; transition: all 0.3s; border-bottom: 1px solid rgba(255, 140, 0, 0.1);" 
                         onmouseover="this.style.background='rgba(255, 140, 0, 0.1)'" 
                         onmouseout="this.style.background='transparent'">
                        <i class="fas fa-map-marker-alt" style="color: #ff8c00; margin-right: 0.5rem;"></i>
                        <span style="font-weight: 600;">${city}</span>
                    </div>
                `).join('');
                suggestionsDiv.style.display = 'block';
            } else {
                suggestionsDiv.style.display = 'none';
            }
        });
        
        function selectCity(city) {
            searchInput.value = city;
            suggestionsDiv.style.display = 'none';
            searchCinemas(city);
        }
        
        function searchCinemas(city) {
            // Afficher un loader
            showLoader();
            
            // Géocoder puis recharger
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(city)}&limit=1`)
                .then(r => r.json())
                .then(data => {
                    if (data && data.length > 0) {
                        window.location.href = `?city=${encodeURIComponent(city)}&lat=${data[0].lat}&lon=${data[0].lon}&radius=<?php echo $radius; ?>`;
                    } else {
                        alert('Ville non trouvée. Essayez une autre recherche.');
                        hideLoader();
                    }
                })
                .catch(err => {
                    alert('Erreur de connexion. Réessayez.');
                    hideLoader();
                });
        }
        
        function useMyLocation() {
            if (navigator.geolocation) {
                showLoader();
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        window.location.href = `?city=Ma position&lat=${position.coords.latitude}&lon=${position.coords.longitude}&radius=<?php echo $radius; ?>`;
                    },
                    function(error) {
                        hideLoader();
                        alert('Impossible d\'obtenir votre position. Vérifiez les autorisations.');
                    }
                );
            } else {
                alert('La géolocalisation n\'est pas supportée par votre navigateur.');
            }
        }
        
        function showLoader() {
            const loader = document.createElement('div');
            loader.id = 'pageLoader';
            loader.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 9999; display: flex; align-items: center; justify-content: center;';
            loader.innerHTML = '<div style="text-align: center;"><i class="fas fa-spinner fa-spin fa-3x" style="color: #ff8c00;"></i><p style="margin-top: 1rem; font-size: 1.2rem;">Recherche en cours...</p></div>';
            document.body.appendChild(loader);
        }
        
        function hideLoader() {
            const loader = document.getElementById('pageLoader');
            if (loader) loader.remove();
        }
        
        // Fermer suggestions si clic ailleurs
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.style.display = 'none';
            }
        });
        
        // Slider
        function initDots() {
            const dotsContainer = document.getElementById('sliderDots');
            cinemas.forEach((_, index) => {
                const dot = document.createElement('div');
                dot.className = 'dot' + (index === 0 ? ' active' : '');
                dot.onclick = () => goToSlide(index);
                dotsContainer.appendChild(dot);
            });
        }
        
        function changeSlide(direction) {
            currentSlide += direction;
            if (currentSlide < 0) currentSlide = cinemas.length - 1;
            if (currentSlide >= cinemas.length) currentSlide = 0;
            updateSlider();
        }
        
        function goToSlide(index) {
            currentSlide = index;
            updateSlider();
        }
        
        function updateSlider() {
            document.getElementById('cinemaSlides').style.transform = `translateX(-${currentSlide * 100}%)`;
            document.querySelectorAll('.dot').forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }
        
        // Films
        function showFilms(cinemaIndex) {
            const cinema = cinemas[cinemaIndex];
            document.getElementById('modalCinemaName').textContent = cinema.name;
            
            const filmsGrid = document.getElementById('filmsGrid');
            filmsGrid.innerHTML = '';
            
            cinema.films.forEach(film => {
                const filmCard = document.createElement('div');
                filmCard.className = 'film-card-mega';
                filmCard.innerHTML = `
                    <img src="${film.poster}" alt="${film.title}" class="film-poster-mega">
                    <div class="film-info-mega">
                        <h4 class="film-title-mega">${film.title}</h4>
                        <div class="film-rating">★ ${film.vote_average}/10</div>
                        <p style="color: #9ca3af; font-size: 0.9rem; margin-bottom: 1rem;">
                            ${film.duration} min • ${film.release_date}
                        </p>
                        <div style="margin-bottom: 1rem;">
                            <strong style="color: #ff8c00; font-size: 0.9rem;">Séances disponibles:</strong>
                            <div class="sessions-mega" style="margin-top: 0.5rem;">
                                ${film.sessions.map(session => 
                                    `<button class="session-btn-mega" onclick="selectSession('${cinema.original_id}', '${cinema.name}', ${film.id}, '${film.title.replace(/'/g, "\\'")}', '${session}')">${session}</button>`
                                ).join('')}
                            </div>
                        </div>
                        <button class="reserve-btn-film" onclick="openQuickReserve('${cinema.original_id}', '${cinema.name.replace(/'/g, "\\'")}', ${film.id}, '${film.title.replace(/'/g, "\\'")}', '${film.sessions[0]}')">
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
        
        function openQuickReserve(cinemaId, cinemaName, filmId, filmTitle, session) {
            selectSession(cinemaId, cinemaName, filmId, filmTitle, session);
        }
        
        function selectSession(cinemaId, cinemaName, filmId, filmTitle, session) {
            selectedReservation = {
                cinema_id: cinemaId,
                cinema_name: cinemaName,
                film_id: filmId,
                film_title: filmTitle,
                session: session
            };
            
            document.getElementById('displayCinemaName').value = cinemaName;
            document.getElementById('displayFilmTitle').value = filmTitle;
            document.getElementById('displaySession').value = session;
            
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('resDate').value = tomorrow.toISOString().split('T')[0];
            
            updatePrice();
            
            closeFilmsModal();
            document.getElementById('reservationModal').style.display = 'flex';
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
                cinema_address: '',
                film_id: selectedReservation.film_id,
                film_title: selectedReservation.film_title,
                film_poster: '',
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
                    alert('✅ Réservation confirmée!\n\nCode de réservation: ' + result.reservation_code + '\n\nVous pouvez retrouver votre réservation dans votre espace personnel.');
                    closeReservationModal();
                    setTimeout(() => window.location.href = '../user/reservations.php', 1500);
                } else {
                    alert('❌ Erreur: ' + result.message);
                }
            })
            .catch(err => {
                alert('❌ Erreur de connexion. Réessayez.');
            });
        }
        
        // Carte
        function initMap() {
            map = L.map('map').setView([<?php echo $latitude; ?>, <?php echo $longitude; ?>], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);
            
            L.circle([<?php echo $latitude; ?>, <?php echo $longitude; ?>], {
                color: '#ff8c00',
                fillColor: '#ff8c00',
                fillOpacity: 0.1,
                radius: <?php echo $radius; ?>
            }).addTo(map);
            
            cinemas.forEach(cinema => {
                L.marker([cinema.latitude, cinema.longitude])
                    .addTo(map)
                    .bindPopup(`<strong>${cinema.name}</strong><br>${cinema.address}`);
            });
        }
        
        // Init
        document.addEventListener('DOMContentLoaded', function() {
            initDots();
            initMap();
        });
        
        // Navigation clavier
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') changeSlide(-1);
            if (e.key === 'ArrowRight') changeSlide(1);
            if (e.key === 'Escape') {
                closeFilmsModal();
                closeReservationModal();
            }
        });
    </script>
</body>
</html>