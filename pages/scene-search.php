<?php
session_start();
include '../includes/config.conf.php';

// Définir les constantes si non définies
if (!defined('MAX_UPLOAD_SIZE')) {
    define('MAX_UPLOAD_SIZE', 5242880);
}
if (!defined('ALLOWED_IMAGE_TYPES')) {
    define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
}

// ============================
// FONCTIONS D'UPLOAD ET UTILITAIRES
// ============================

// Fonction pour uploader et rendre l'image accessible publiquement
function uploadAndGetPublicUrl($file) {
    $upload_dir = '../uploads/scene_search/';
    
    // Créer le dossier s'il n'existe pas
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0775, true);
    }
    
    $filename = uniqid('scene_', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
    $target_file = $upload_dir . $filename;
    
    // Vérifications
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['error' => 'Le fichier n\'est pas une image valide.'];
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['error' => 'L\'image est trop volumineuse (max ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB).'];
    }
    
    $allowed_mime = ALLOWED_IMAGE_TYPES;
    if (!in_array($check['mime'], $allowed_mime)) {
        return ['error' => 'Format non supporté. Utilisez JPG, PNG, GIF ou WebP.'];
    }
    
    // Uploader
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // URL publique pour l'API
        $public_url = SITE_URL . '/uploads/scene_search/' . $filename;
        
        return [
            'success' => true, 
            'path' => $target_file, 
            'filename' => $filename,
            'public_url' => $public_url,
            'mime_type' => $check['mime']
        ];
    } else {
        return ['error' => 'Erreur lors de l\'upload.'];
    }
}

// Télécharger une image depuis une URL
function downloadImage($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'CineTrack/1.0 (+https://cinetrack.alwaysdata.net)'
    ]);
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code === 200 ? $data : false;
}

// ============================
// ANALYSE D'IMAGE GRATUITE
// ============================

function analyzeImageFree($image_path) {
    $keywords = [];
    
    // Journaliser pour débogage
    error_log("=== ANALYSE IMAGE: " . basename($image_path) . " ===");
    
    // 1. ANALYSE DU NOM DE FICHIER (le plus important)
    $filename_keywords = extractKeywordsFromFilename($image_path);
    if (!empty($filename_keywords)) {
        $keywords = array_merge($keywords, $filename_keywords);
        error_log("Mots-clés fichier: " . implode(', ', $filename_keywords));
    }
    
    // 2. ANALYSE AVEC GD (si disponible)
    if (function_exists('imagecreatefromstring') && file_exists($image_path)) {
        $image_data = @file_get_contents($image_path);
        if ($image_data) {
            $image = @imagecreatefromstring($image_data);
            if ($image) {
                $gd_keywords = analyzeWithGD($image);
                $keywords = array_merge($keywords, $gd_keywords);
                imagedestroy($image);
                error_log("Mots-clés GD: " . implode(', ', $gd_keywords));
            }
        }
    }
    
    // 3. DÉTECTION DU TYPE D'IMAGE par ratio
    $size_info = @getimagesize($image_path);
    if ($size_info) {
        $width = $size_info[0];
        $height = $size_info[1];
        $ratio = $width / $height;
        
        if ($ratio >= 0.6 && $ratio <= 0.8) {
            $keywords[] = 'poster';
            $keywords[] = 'movie poster';
            $keywords[] = 'cover art';
            error_log("Ratio {$ratio}: Détecté comme affiche");
        } elseif ($ratio >= 1.7 && $ratio <= 1.9) {
            $keywords[] = 'screenshot';
            $keywords[] = 'movie scene';
            $keywords[] = 'film scene';
            error_log("Ratio {$ratio}: Détecté comme capture");
        }
    }
    
    // 4. MOTS-CLÉS INTELLIGENTS basés sur ce qu'on a trouvé
    $smart_keywords = generateSmartKeywords($keywords);
    $keywords = array_merge($keywords, $smart_keywords);
    
    // 5. FILTRER les mots-clés pour films uniquement
    $filtered = filterMovieKeywords($keywords);
    
    // 6. ÉVITER LES RECHERCHES GÉNÉRIQUES
    // Si on n'a trouvé que des mots génériques, essayer une approche différente
    $generic_words = ['movie', 'film', 'cinema', 'hollywood'];
    $has_specific_keywords = false;
    
    foreach ($filtered as $keyword) {
        if (!in_array($keyword, $generic_words)) {
            $has_specific_keywords = true;
            break;
        }
    }
    
    if (!$has_specific_keywords && !empty($filename_keywords)) {
        // Utiliser plus agressivement les mots du nom de fichier
        foreach ($filename_keywords as $word) {
            if (strlen($word) > 3) {
                $filtered[] = $word;
                // Chercher aussi avec "movie" devant
                $filtered[] = 'movie ' . $word;
                $filtered[] = 'film ' . $word;
            }
        }
    }
    
    // Limiter et retourner
    $result = array_slice(array_unique($filtered), 0, 8);
    error_log("Mots-clés finaux: " . implode(', ', $result));
    error_log("=== FIN ANALYSE ===");
    
    return $result;
}

// Fonction d'analyse GD améliorée
function analyzeWithGD($image) {
    $keywords = [];
    $width = imagesx($image);
    $height = imagesy($image);
    
    if ($width <= 0 || $height <= 0) return $keywords;
    
    // Analyser plusieurs points
    $points = [
        [intval($width * 0.1), intval($height * 0.1)],   // Coin supérieur gauche
        [intval($width * 0.9), intval($height * 0.1)],   // Coin supérieur droit
        [intval($width * 0.5), intval($height * 0.5)],   // Centre
        [intval($width * 0.1), intval($height * 0.9)],   // Coin inférieur gauche
        [intval($width * 0.9), intval($height * 0.9)],   // Coin inférieur droit
    ];
    
    $total_r = $total_g = $total_b = 0;
    $count = 0;
    
    foreach ($points as $point) {
        list($x, $y) = $point;
        if ($x < $width && $y < $height) {
            $rgb = imagecolorat($image, $x, $y);
            $total_r += ($rgb >> 16) & 0xFF;
            $total_g += ($rgb >> 8) & 0xFF;
            $total_b += $rgb & 0xFF;
            $count++;
        }
    }
    
    if ($count > 0) {
        $avg_r = $total_r / $count;
        $avg_g = $total_g / $count;
        $avg_b = $total_b / $count;
        $brightness = ($avg_r + $avg_g + $avg_b) / 3;
        
        // Détection d'ambiance
        if ($brightness < 80) {
            $keywords[] = 'dark';
            $keywords[] = 'noir';
            $keywords[] = 'thriller';
            $keywords[] = 'horror';
            $keywords[] = 'mystery';
        } elseif ($brightness > 180) {
            $keywords[] = 'bright';
            $keywords[] = 'light';
            $keywords[] = 'comedy';
            $keywords[] = 'romance';
            $keywords[] = 'family';
        }
        
        // Détection de teinte
        if ($avg_r > $avg_g + 30 && $avg_r > $avg_b + 30) {
            $keywords[] = 'warm';
            $keywords[] = 'action';
            $keywords[] = 'drama';
        } elseif ($avg_b > $avg_r + 30 && $avg_b > $avg_g + 30) {
            $keywords[] = 'cold';
            $keywords[] = 'sci-fi';
            $keywords[] = 'fantasy';
        }
    }
    
    return array_unique($keywords);
}

// Fonction d'extraction du nom de fichier AMÉLIORÉE
function extractKeywordsFromFilename($file_path) {
    $keywords = [];
    $filename = basename($file_path);
    
    // Nettoyer et séparer
    $clean = preg_replace('/[^a-zA-Z0-9]/', ' ', $filename);
    $clean = preg_replace('/([a-z])([A-Z])/', '$1 $2', $clean); // Séparer camelCase
    $clean = strtolower($clean);
    
    // Mots à IGNORER
    $stop_words = [
        'img', 'image', 'photo', 'pic', 'picture', 'screenshot', 
        'scene', 'movie', 'film', 'cinema', 'upload', 'file',
        'copy', 'download', 'saved', 'wp', 'dcim', 'snap',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp',
        'whatsapp', 'instagram', 'facebook', 'twitter', 'tiktok',
        'sans', 'titre', 'untitled', 'new', 'latest', 'version',
        'edit', 'edited', 'final', 'hd', 'high', 'quality', 'qr'
    ];
    
    $words = explode(' ', $clean);
    foreach ($words as $word) {
        $word = trim($word);
        
        // Filtrer
        if (strlen($word) < 3) continue;
        if (in_array($word, $stop_words)) continue;
        if (is_numeric($word) && strlen($word) != 4) continue; // Garder seulement les années
        
        // Ajouter le mot
        $keywords[] = $word;
        
        // Traitements spéciaux
        if (preg_match('/^(19|20)\d{2}$/', $word)) {
            // C'est une année
            $keywords[] = $word . ' film';
            $keywords[] = $word . ' movie';
            $keywords[] = 'year ' . $word;
        }
        
        // Si le mot ressemble à un titre (contient des chiffres ou majuscules dans l'original)
        if (preg_match('/[A-Z]/', $word) || preg_match('/\d/', $word)) {
            $keywords[] = 'title ' . $word;
        }
    }
    
    return array_unique($keywords);
}

// Générer des mots-clés intelligents basés sur les mots trouvés
function generateSmartKeywords($existing_keywords) {
    $smart = [];
    $genres = [
        'action', 'adventure', 'animation', 'comedy', 'crime', 
        'drama', 'fantasy', 'horror', 'mystery', 'romance', 
        'sci-fi', 'thriller', 'war', 'western', 'musical'
    ];
    
    // Mots qui suggèrent des genres
    $genre_triggers = [
        'dark' => ['thriller', 'horror', 'noir', 'mystery'],
        'bright' => ['comedy', 'romance', 'family', 'musical'],
        'colorful' => ['animation', 'fantasy', 'family'],
        'warm' => ['action', 'drama', 'romance'],
        'cold' => ['sci-fi', 'thriller', 'mystery'],
        'poster' => ['graphic', 'art', 'design', 'cover'],
        'screenshot' => ['scene', 'shot', 'frame', 'still'],
    ];
    
    // Ajouter des genres basés sur les mots existants
    foreach ($existing_keywords as $word) {
        // Si c'est déjà un genre, le renforcer
        if (in_array($word, $genres)) {
            $smart[] = $word;
        }
        
        // Chercher des déclencheurs
        foreach ($genre_triggers as $trigger => $suggestions) {
            if (strpos($word, $trigger) !== false) {
                $smart = array_merge($smart, $suggestions);
            }
        }
    }
    
    return array_unique($smart);
}

// Fonction de recherche AVEC GARDE-FOU
function searchMoviesByKeywords($keywords) {
    if (!defined('TMDB_API_KEY') || empty(TMDB_API_KEY)) {
        return [];
    }
    
    $api_key = TMDB_API_KEY;
    $all_results = [];
    $seen_ids = [];
    
    // Nettoyer les mots-clés
    $clean_keywords = [];
    foreach ($keywords as $keyword) {
        $keyword = trim($keyword);
        if (strlen($keyword) > 2 && !is_numeric($keyword)) {
            $clean_keywords[] = $keyword;
        }
    }
    
    // Prendre les mots-clés les plus prometteurs
    $search_keywords = array_slice($clean_keywords, 0, 6);
    
    error_log("Recherche avec mots-clés: " . implode(', ', $search_keywords));
    
    foreach ($search_keywords as $keyword) {
        // Éviter les recherches trop génériques
        $generic = ['movie', 'film', 'cinema', 'hollywood'];
        if (in_array($keyword, $generic)) {
            continue; // Sauter ces recherches
        }
        
        // 1. Recherche MULTI (films + séries)
        $multi_url = TMDB_BASE_URL . 'search/multi?api_key=' . $api_key . 
                    '&language=fr-FR&query=' . urlencode($keyword) . 
                    '&page=1&include_adult=false';
        
        $response = @file_get_contents($multi_url);
        if ($response) {
            $data = json_decode($response, true);
            if (!empty($data['results'])) {
                foreach ($data['results'] as $item) {
                    // Filtrer seulement films et séries avec poster
                    if (($item['media_type'] === 'movie' || $item['media_type'] === 'tv') && 
                        !empty($item['poster_path']) && 
                        !in_array($item['id'], $seen_ids)) {
                        
                        $item['matched_keyword'] = $keyword;
                        $item['search_score'] = calculateSearchScore($item, $keyword);
                        $all_results[] = $item;
                        $seen_ids[] = $item['id'];
                    }
                }
                error_log("✓ '$keyword': " . count($data['results']) . " résultats");
            }
        }
        
        usleep(300000); // Pause pour éviter rate limiting
        
        // Limiter les résultats
        if (count($all_results) >= 15) {
            break;
        }
    }
    
    // Si pas assez de résultats, chercher des films populaires
    if (count($all_results) < 5) {
        error_log("Pas assez de résultats, recherche films populaires");
        $popular_url = TMDB_BASE_URL . 'movie/popular?api_key=' . $api_key . '&language=fr-FR&page=1';
        $response = @file_get_contents($popular_url);
        if ($response) {
            $data = json_decode($response, true);
            if (!empty($data['results'])) {
                foreach ($data['results'] as $movie) {
                    if (!in_array($movie['id'], $seen_ids)) {
                        $movie['media_type'] = 'movie';
                        $movie['matched_keyword'] = 'popular';
                        $movie['search_score'] = 0.5;
                        $all_results[] = $movie;
                    }
                }
            }
        }
    }
    
    return $all_results;
}


// Calculer un score de pertinence
function calculateSearchScore($item, $keyword) {
    $score = 0.3;
    
    // Bonus pour popularité
    if (isset($item['popularity'])) {
        $score += min($item['popularity'] / 150, 0.4);
    }
    
    // Bonus pour les votes
    if (isset($item['vote_count']) && $item['vote_count'] > 100) {
        $score += 0.1;
    }
    
    // Bonus pour la note
    if (isset($item['vote_average']) && $item['vote_average'] > 7) {
        $score += 0.1;
    }
    
    // Vérifier si le mot-clé est dans le titre
    $title = strtolower($item['title'] ?? $item['name'] ?? '');
    $keyword_lower = strtolower($keyword);
    
    if (strpos($title, $keyword_lower) !== false) {
        $score += 0.2;
    }
    
    // Vérifier si le mot-clé est dans la description
    $overview = strtolower($item['overview'] ?? '');
    if (strpos($overview, $keyword_lower) !== false) {
        $score += 0.1;
    }
    
    // Bonus pour le poster
    if (!empty($item['poster_path'])) {
        $score += 0.15;
    }
    
    return min($score, 0.95);
}

// ============================
// FONCTION PRINCIPALE DE RECHERCHE
// ============================

// Fonction principale pour rechercher un film par image
function searchMovieByImageFree($image_path) {
    // Analyser l'image pour obtenir des mots-clés
    $keywords = analyzeImageFree($image_path);
    
    // Journaliser pour débogage
    error_log("Free Analysis Keywords: " . implode(', ', $keywords));
    
    // Rechercher avec ces mots-clés
    $tmdb_results = searchMoviesByKeywords($keywords);
    
    // Si pas assez de résultats, ajouter une recherche générique
    if (count($tmdb_results) < 5) {
        $fallback_results = fallbackGenericSearch();
        $tmdb_results = array_merge($tmdb_results, $fallback_results);
    }
    
    // Calculer les scores finaux
    $scored_results = calculateFreeScores($tmdb_results, $keywords);
    
    // Trier et filtrer
    return sortAndFilterResults($scored_results);
}

// Recherche générique de fallback
function fallbackGenericSearch() {
    $results = [];
    $api_key = TMDB_API_KEY;
    
    // Différentes catégories populaires
    $categories = [
        'popular' => 'movie/popular',
        'top_rated' => 'movie/top_rated',
        'now_playing' => 'movie/now_playing',
        'upcoming' => 'movie/upcoming'
    ];
    
    foreach ($categories as $cat_name => $endpoint) {
        $url = TMDB_BASE_URL . $endpoint . '?api_key=' . $api_key . '&language=fr-FR&page=1';
        
        $response = @file_get_contents($url);
        if ($response) {
            $data = json_decode($response, true);
            if (!empty($data['results'])) {
                foreach ($data['results'] as $movie) {
                    $movie['media_type'] = 'movie';
                    $movie['matched_keyword'] = $cat_name;
                    $movie['search_score'] = 0.4;
                    $results[] = $movie;
                }
            }
        }
        
        usleep(100000);
        
        // Limiter
        if (count($results) >= defined('MAX_FALLBACK_RESULTS') ? MAX_FALLBACK_RESULTS : 8) {
            break;
        }
    }
    
    return $results;
}

// Calculer les scores pour l'analyse gratuite
function calculateFreeScores($movies, $keywords) {
    $scored_movies = [];
    
    foreach ($movies as $movie) {
        $final_score = $movie['search_score'] ?? 0.3;
        
        // Bonus pour les films récents (après 2010)
        $release_date = $movie['release_date'] ?? $movie['first_air_date'] ?? '';
        if ($release_date && strtotime($release_date) > strtotime('2010-01-01')) {
            $final_score += 0.1;
        }
        
        // Bonus si le genre correspond aux mots-clés
        if (isset($movie['genre_ids']) && !empty($keywords)) {
            $genre_keywords = ['action', 'comedy', 'drama', 'horror', 'thriller', 
                              'romance', 'fantasy', 'adventure', 'sci-fi'];
            
            foreach ($keywords as $keyword) {
                if (in_array($keyword, $genre_keywords)) {
                    $final_score += 0.05;
                }
            }
        }
        
        // Ajouter les infos au résultat
        $movie['similarity_score'] = min($final_score, 0.98);
        $movie['search_method'] = 'free_analysis';
        $movie['detected_keywords'] = array_slice($keywords, 0, 3);
        
        $scored_movies[] = $movie;
    }
    
    return $scored_movies;
}

// Filtrer les mots-clés pour garder ceux pertinents aux films
function filterMovieKeywords($keywords) {
    $movie_keywords = [];
    $common_words = ['the', 'and', 'for', 'with', 'this', 'that', 'from', 'have', 'were', 'what'];
    
    // Genres de films
    $genres = unserialize(MOVIE_GENRES);
    
    // Termes liés aux films
    $film_terms = [
        'movie' => 2, 'film' => 2, 'cinema' => 2, 'hollywood' => 1.5,
        'actor' => 1.5, 'actress' => 1.5, 'director' => 1.5, 'scene' => 1.5,
        'poster' => 1.5, 'trailer' => 1.5, 'star' => 1.5, 'character' => 1,
        'blockbuster' => 1.5, 'award' => 1, 'oscar' => 1.2
    ];
    
    foreach ($keywords as $keyword) {
        $keyword_lower = strtolower($keyword);
        
        // Ignorer les mots trop communs ou courts
        if (in_array($keyword_lower, $common_words) || strlen($keyword_lower) < 3) {
            continue;
        }
        
        // Vérifier les termes spécifiques aux films
        if (isset($film_terms[$keyword_lower])) {
            $movie_keywords[$keyword_lower] = $film_terms[$keyword_lower];
        }
        
        // Vérifier les genres
        if (in_array($keyword_lower, $genres)) {
            $movie_keywords[$keyword_lower] = 1.5;
        }
        
        // Mots liés à l'époque ou au style
        $era_terms = ['modern', 'classic', 'vintage', 'retro', 'contemporary',
                     'historical', 'futuristic', 'noir', 'neo-noir', 'old'];
        if (in_array($keyword_lower, $era_terms)) {
            $movie_keywords[$keyword_lower] = 0.8;
        }
        
        // Ambiance
        $mood_terms = ['dark', 'light', 'warm', 'cold', 'somber', 'bright', 'colorful'];
        if (in_array($keyword_lower, $mood_terms)) {
            $movie_keywords[$keyword_lower] = 0.5;
        }
    }
    
    // Trier par poids décroissant
    arsort($movie_keywords);
    
    // Retourner seulement les clés
    $result = array_keys($movie_keywords);
    
    // Ajouter des termes génériques si pas assez de résultats
    if (count($result) < 3) {
        $result = array_merge($result, ['movie', 'film', 'cinema']);
    }
    
    return array_unique($result);
}

// Trier et filtrer les résultats
function sortAndFilterResults($results) {
    if (empty($results)) {
        return [];
    }
    
    // Éliminer les doublons par ID
    $unique_results = [];
    $seen_ids = [];
    
    foreach ($results as $result) {
        if (!isset($result['id'])) continue;
        
        if (!in_array($result['id'], $seen_ids)) {
            $seen_ids[] = $result['id'];
            $unique_results[] = $result;
        }
    }
    
    // Trier par score de similarité décroissant
    usort($unique_results, function($a, $b) {
        $score_a = $a['similarity_score'] ?? 0;
        $score_b = $b['similarity_score'] ?? 0;
        return $score_b <=> $score_a;
    });
    
    // Limiter au nombre maximum défini
    $max_results = defined('MAX_SEARCH_RESULTS') ? MAX_SEARCH_RESULTS : 12;
    return array_slice($unique_results, 0, $max_results);
}

// Fonction pour obtenir le nom du genre
function getGenreName($genre_id) {
    $genres = [
        28 => 'Action',
        12 => 'Aventure',
        16 => 'Animation',
        35 => 'Comédie',
        80 => 'Crime',
        99 => 'Documentaire',
        18 => 'Drame',
        10751 => 'Familial',
        14 => 'Fantastique',
        36 => 'Histoire',
        27 => 'Horreur',
        10402 => 'Musique',
        9648 => 'Mystère',
        10749 => 'Romance',
        878 => 'Science-Fiction',
        10770 => 'Téléfilm',
        53 => 'Thriller',
        10752 => 'Guerre',
        37 => 'Western'
    ];
    
    return $genres[$genre_id] ?? 'Film';
}

// ============================
// TRAITEMENT DU FORMULAIRE
// ============================

$movie_results = [];
$uploaded_image = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['scene_image'])) {
    // Upload de l'image
    $upload_result = uploadAndGetPublicUrl($_FILES['scene_image']);
    
    if (isset($upload_result['error'])) {
        $error = $upload_result['error'];
    } else {
        $uploaded_image = $upload_result['path'];
        $_SESSION['last_uploaded_image'] = $upload_result['filename'];
        
        // Journaliser l'upload
        error_log("Image uploaded: " . $upload_result['filename']);
        
        // Rechercher le film par image (version gratuite)
        $movie_results = searchMovieByImageFree($upload_result['path']);
        
        if (empty($movie_results)) {
            $error = "Aucun film trouvé pour cette image. Essayez avec :<br>
                     • Une affiche de film claire<br>
                     • Une capture d'écran avec des acteurs visibles<br>
                     • Une image haute qualité<br>
                     • Évitez les images trop petites ou floues";
        } else {
            // Sauvegarder les résultats en session
            $_SESSION['last_search_results'] = $movie_results;
            $_SESSION['last_search_image'] = $upload_result['filename'];
            $_SESSION['search_method'] = 'free_analysis';
        }
    }
}

// Récupérer les résultats de la session si disponible
if (empty($movie_results) && isset($_SESSION['last_search_results'])) {
    $movie_results = $_SESSION['last_search_results'];
    $uploaded_image = '../uploads/scene_search/' . ($_SESSION['last_search_image'] ?? '');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche de Films par Image - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg { 
            background: linear-gradient(135deg, #0a0e14 0%, #1a1f2e 100%);
            min-height: 100vh;
        }
        
        .glass {
            background: rgba(25, 30, 45, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ff6b00 0%, #ff8c00 100%);
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(255, 107, 0, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #ff7c1a 0%, #ff9d1a 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 0, 0.4);
        }
        
        .upload-area {
            border: 3px dashed rgba(255, 140, 0, 0.3);
            transition: all 0.3s ease;
            background: rgba(255, 140, 0, 0.02);
        }
        
        .upload-area:hover {
            border-color: rgba(255, 140, 0, 0.6);
            background: rgba(255, 140, 0, 0.05);
        }
        
        .result-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 140, 0, 0.15);
        }
        
        .similarity-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.9) 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .confidence-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .confidence-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff6b00, #ff8c00);
            border-radius: 3px;
        }
    </style>
</head>
<body class="gradient-bg text-white">
    <?php include '../includes/header.php'; ?>
    
    <main class="py-8 md:py-12 px-4 md:px-6">
        <div class="max-w-6xl mx-auto">
            <!-- En-tête -->
            <div class="text-center mb-8 md:mb-12">
                <h1 class="text-3xl md:text-5xl font-bold mb-2 md:mb-4 bg-gradient-to-r from-orange-400 to-orange-600 bg-clip-text text-transparent">
                    Shazam Cinéma
                </h1>
                <p class="text-gray-300 text-base md:text-lg">
                    Identifiez un film à partir d'une image
                </p>
            </div>
            
            <!-- Zone d'upload -->
            <div class="glass rounded-xl md:rounded-2xl p-6 md:p-8 mb-8 md:mb-12">
                <form method="POST" enctype="multipart/form-data" id="uploadForm" class="space-y-6">
                    <div class="text-center mb-4 md:mb-6">
                        <h2 class="text-xl md:text-2xl font-bold mb-2">Uploader une image</h2>
                        <p class="text-gray-400 text-sm md:text-base">Capture, affiche, scène de film...</p>
                    </div>
                    
                    <!-- Zone de drag & drop -->
                    <div class="upload-area glass rounded-lg md:rounded-xl p-6 md:p-8 text-center cursor-pointer" 
                         onclick="document.getElementById('scene_image').click()">
                        <input type="file" 
                               name="scene_image" 
                               id="scene_image" 
                               accept="image/*" 
                               class="hidden"
                               onchange="previewImage(event)">
                        
                        <div class="mb-4">
                            <i class="fas fa-cloud-upload-alt text-4xl md:text-5xl text-gray-400 mb-3 md:mb-4"></i>
                            <p class="text-lg md:text-xl font-medium mb-2">Cliquez pour sélectionner une image</p>
                            <p class="text-gray-400 mb-3 md:mb-4">Glissez-déposez ou cliquez pour parcourir</p>
                            <div class="text-xs md:text-sm text-gray-500 space-y-1">
                                <p>Formats supportés : JPG, PNG, GIF, WebP</p>
                                <p>Taille maximale : 5MB</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Aperçu de l'image -->
                    <div id="imagePreview" class="hidden text-center">
                        <div class="inline-block relative">
                            <img id="preview" src="" alt="Aperçu" class="max-w-full max-h-64 rounded-lg mx-auto shadow-lg">
                            <button type="button" 
                                    onclick="clearPreview()"
                                    class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="text-sm text-gray-400 mt-2">Image sélectionnée</p>
                    </div>
                    
                    <!-- Conseils -->
                    <div class="glass p-4 rounded-lg md:rounded-xl">
                        <h3 class="font-bold mb-3 flex items-center gap-2 text-orange-400">
                            <i class="fas fa-lightbulb"></i>
                            Conseils pour de meilleurs résultats
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check text-green-400 mt-1"></i>
                                <span class="text-sm text-gray-300">Utilisez des images nettes et bien éclairées</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check text-green-400 mt-1"></i>
                                <span class="text-sm text-gray-300">Les affiches de films donnent les meilleurs résultats</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check text-green-400 mt-1"></i>
                                <span class="text-sm text-gray-300">Nommez votre image avec des mots descriptifs</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="fas fa-check text-green-400 mt-1"></i>
                                <span class="text-sm text-gray-300">Évitez les images trop sombres ou floues</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bouton de soumission -->
                    <div class="text-center pt-2">
                        <button type="submit" 
                                id="submitBtn"
                                class="btn-primary px-8 md:px-10 py-3 md:py-4 rounded-lg md:rounded-xl text-base md:text-lg font-bold inline-flex items-center justify-center gap-2 md:gap-3 w-full md:w-auto">
                            <i class="fas fa-search"></i>
                            <span>Analyser l'image et identifier</span>
                        </button>
                        <p class="text-gray-400 text-xs md:text-sm mt-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Analyse intelligente gratuite (sans Google Vision)
                        </p>
                    </div>
                </form>
            </div>
            
            <!-- Messages d'erreur -->
            <?php if (!empty($error)): ?>
                <div class="glass rounded-xl md:rounded-2xl p-6 md:p-8 mb-6 md:mb-8 border border-red-500/30 bg-red-500/5">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-red-500/20 flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-lg md:text-xl text-red-400"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg md:text-xl font-bold text-red-400 mb-2">Aucun résultat trouvé</h3>
                            <div class="text-gray-300 text-sm md:text-base">
                                <?php echo nl2br(htmlspecialchars($error)); ?>
                            </div>
                            <div class="mt-4">
                                <button onclick="window.location.reload()" 
                                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-medium inline-flex items-center gap-2">
                                    <i class="fas fa-redo"></i>
                                    Essayer une autre image
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Résultats -->
            <?php if (!empty($movie_results)): ?>
                <div class="glass rounded-xl md:rounded-2xl p-6 md:p-8 mb-8">
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 md:mb-8 gap-4">
                        <div>
                            <h2 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">Résultats de la recherche</h2>
                            <p class="text-gray-400">
                                <span class="text-orange-400 font-bold"><?php echo count($movie_results); ?></span> films/séries trouvés pour votre image
                            </p>
                        </div>
                        
                        <?php if (isset($_SESSION['last_uploaded_image'])): ?>
                            <div class="text-center">
                                <p class="text-sm text-gray-400 mb-2">Image analysée :</p>
                                <div class="relative inline-block">
                                    <img src="../uploads/scene_search/<?php echo htmlspecialchars($_SESSION['last_uploaded_image']); ?>" 
                                         alt="Image recherchée" 
                                         class="w-20 h-20 md:w-24 md:h-24 object-cover rounded-lg shadow-lg border-2 border-orange-500/30">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Statistiques -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-4 mb-6 md:mb-8">
                        <div class="glass p-3 md:p-4 rounded-lg text-center">
                            <p class="text-2xl md:text-3xl font-bold text-orange-400"><?php echo count($movie_results); ?></p>
                            <p class="text-xs md:text-sm text-gray-400">Résultats</p>
                        </div>
                        <div class="glass p-3 md:p-4 rounded-lg text-center">
                            <p class="text-2xl md:text-3xl font-bold text-blue-400">
                                <?php 
                                $avg_score = array_reduce($movie_results, function($carry, $movie) {
                                    return $carry + ($movie['similarity_score'] ?? 0);
                                }, 0) / count($movie_results);
                                echo round($avg_score * 100, 0);
                                ?>%
                            </p>
                            <p class="text-xs md:text-sm text-gray-400">Pertinence moyenne</p>
                        </div>
                        <div class="glass p-3 md:p-4 rounded-lg text-center">
                            <p class="text-2xl md:text-3xl font-bold text-green-400">
                                Analyse Gratuite
                            </p>
                            <p class="text-xs md:text-sm text-gray-400">Méthode utilisée</p>
                        </div>
                    </div>
                    
                    <!-- Grille des résultats -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
                        <?php foreach ($movie_results as $movie): 
                            $is_movie = ($movie['media_type'] ?? 'movie') === 'movie';
                            $title = htmlspecialchars($movie['title'] ?? $movie['name'] ?? 'Titre inconnu');
                            $year = isset($movie['release_date']) 
                                  ? date('Y', strtotime($movie['release_date']))
                                  : (isset($movie['first_air_date']) 
                                     ? date('Y', strtotime($movie['first_air_date']))
                                     : '');
                            $score = $movie['similarity_score'] ?? 0;
                            $score_percent = round($score * 100);
                            $vote_average = $movie['vote_average'] ?? 0;
                            $overview = $movie['overview'] ?? '';
                            $poster_path = $movie['poster_path'] ?? '';
                        ?>
                            <div class="result-card glass rounded-lg overflow-hidden relative h-full">
                                <!-- Badge de score -->
                                <div class="similarity-badge">
                                    <?php echo $score_percent; ?>%
                                </div>
                                
                                <!-- Poster/Image -->
                                <div class="relative h-48 md:h-56 overflow-hidden bg-gradient-to-br from-gray-800 to-gray-900">
                                    <?php if (!empty($poster_path)): ?>
                                        <img src="<?php echo TMDB_IMAGE_BASE_URL . 'w500' . $poster_path; ?>" 
                                             alt="<?php echo $title; ?>"
                                             class="w-full h-full object-cover"
                                             onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAwIiBoZWlnaHQ9IjUwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNTAwIiBoZWlnaHQ9IjUwMCIgZmlsbD0iIzFlMWUyZSIvPjx0ZXh0IHg9IjI1MCIgeT0iMjUwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMjQiIGZpbGw9IiM1MTUxNjUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5ObyBJbWFnZTwvdGV4dD48L3N2Zz4='">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="fas fa-film text-4xl text-gray-600"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Type badge -->
                                    <div class="absolute bottom-3 left-3">
                                        <span class="px-2 py-1 rounded text-xs font-bold <?php echo $is_movie ? 'bg-orange-500' : 'bg-purple-500'; ?>">
                                            <?php echo $is_movie ? 'FILM' : 'SÉRIE'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Contenu -->
                                <div class="p-4">
                                    <h3 class="text-lg font-bold mb-1 truncate" title="<?php echo $title; ?>">
                                        <?php echo $title; ?>
                                    </h3>
                                    
                                    <div class="flex items-center justify-between mb-3">
                                        <?php if ($year): ?>
                                            <span class="text-gray-400 text-sm">
                                                <i class="far fa-calendar-alt mr-1"></i><?php echo $year; ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($vote_average > 0): ?>
                                            <div class="flex items-center gap-1">
                                                <i class="fas fa-star text-yellow-400 text-sm"></i>
                                                <span class="font-bold text-sm"><?php echo number_format($vote_average, 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Barre de confiance -->
                                    <div class="mb-3">
                                        <div class="flex justify-between text-xs text-gray-400 mb-1">
                                            <span>Correspondance :</span>
                                            <span><?php echo $score_percent; ?>%</span>
                                        </div>
                                        <div class="confidence-bar">
                                            <div class="confidence-fill" style="width: <?php echo $score_percent; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($overview)): ?>
                                        <p class="text-gray-300 text-sm mb-4">
                                            <?php echo htmlspecialchars(substr($overview, 0, 80)); ?>...
                                        </p>
                                    <?php endif; ?>
                                    
                                    <!-- Bouton détails -->
                                    <a href="../movie.php?id=<?php echo $movie['id']; ?>&type=<?php echo $is_movie ? 'movie' : 'tv'; ?>" 
                                       class="btn-primary px-3 py-2 rounded-lg text-sm font-bold inline-flex items-center gap-2 w-full justify-center">
                                        <i class="fas fa-info-circle"></i>
                                        Voir détails
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Actions -->
                    <div class="mt-8 pt-6 border-t border-gray-700">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div>
                                <p class="text-gray-400 text-sm">
                                    <i class="fas fa-bolt text-orange-400 mr-2"></i>
                                    Analyse gratuite sans API payante
                                </p>
                            </div>
                            <div class="flex gap-3">
                                <button onclick="window.location.reload()" 
                                        class="glass px-4 py-2 rounded-lg font-medium inline-flex items-center gap-2 hover:bg-gray-700/50">
                                    <i class="fas fa-redo"></i>
                                    Nouvelle recherche
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)): ?>
                <!-- État de chargement -->
                <div class="glass rounded-xl md:rounded-2xl p-8 md:p-12 text-center">
                    <div class="mb-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 md:w-20 md:h-20 rounded-full bg-gradient-to-br from-orange-500/20 to-orange-600/20 mb-4">
                            <i class="fas fa-spinner fa-spin text-3xl md:text-4xl text-orange-400"></i>
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold mb-2">Analyse en cours...</h3>
                        <p class="text-gray-400 max-w-md mx-auto">
                            Notre système analyse votre image et recherche les films correspondants.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Section d'information -->
            <div class="glass rounded-xl md:rounded-2xl p-6 md:p-8 mt-8">
                <h3 class="text-xl md:text-2xl font-bold mb-6 text-center">
                    <i class="fas fa-question-circle text-orange-400 mr-2"></i>
                    Comment fonctionne notre recherche par image ?
                </h3>
                <div class="grid md:grid-cols-2 gap-6 md:gap-8">
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center">
                                <i class="fas fa-palette text-blue-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold mb-1">Analyse de couleurs</h4>
                                <p class="text-gray-300 text-sm">
                                    Notre système analyse les couleurs dominantes pour déterminer l'ambiance du film.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center">
                                <i class="fas fa-file-alt text-green-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold mb-1">Analyse du nom de fichier</h4>
                                <p class="text-gray-300 text-sm">
                                    Les mots dans le nom de votre image sont utilisés pour améliorer la recherche.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-purple-500/20 flex items-center justify-center">
                                <i class="fas fa-search text-purple-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold mb-1">Recherche TMDb</h4>
                                <p class="text-gray-300 text-sm">
                                    Nous interrogeons la base de données TMDb avec les mots-clés extraits.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-orange-500/20 flex items-center justify-center">
                                <i class="fas fa-sort-amount-down text-orange-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold mb-1">Classement intelligent</h4>
                                <p class="text-gray-300 text-sm">
                                    Les résultats sont triés par pertinence basée sur plusieurs critères.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 text-center">
                    <p class="text-gray-400 text-sm">
                        <i class="fas fa-lock mr-2"></i>
                        Système 100% gratuit - Aucune API payante utilisée
                    </p>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Gestion de l'aperçu d'image
        function previewImage(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('preview');
            const previewDiv = document.getElementById('imagePreview');
            const dropArea = document.getElementById('dropArea');
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewDiv.classList.remove('hidden');
                    if (dropArea) {
                        dropArea.style.display = 'none';
                    }
                }
                
                reader.readAsDataURL(file);
            }
        }
        
        function clearPreview() {
            const previewDiv = document.getElementById('imagePreview');
            const dropArea = document.getElementById('dropArea');
            const fileInput = document.getElementById('scene_image');
            
            if (previewDiv) previewDiv.classList.add('hidden');
            if (dropArea) dropArea.style.display = 'block';
            if (fileInput) fileInput.value = '';
        }
        
        // Gestion du formulaire
        const uploadForm = document.getElementById('uploadForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', function() {
                const submitBtn = document.getElementById('submitBtn');
                const fileInput = document.getElementById('scene_image');
                
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Analyse en cours...';
                    submitBtn.disabled = true;
                }
                
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    alert('Veuillez sélectionner une image à analyser.');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-search mr-2"></i>Analyser l\'image et identifier';
                        submitBtn.disabled = false;
                    }
                    return false;
                }
                return true;
            });
        }
        
        // Drag & drop
        const dropArea = document.getElementById('dropArea');
        if (dropArea) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, function() {
                    dropArea.style.borderColor = 'rgba(255, 140, 0, 0.8)';
                    dropArea.style.background = 'rgba(255, 140, 0, 0.1)';
                }, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, function() {
                    dropArea.style.borderColor = 'rgba(255, 140, 0, 0.3)';
                    dropArea.style.background = 'rgba(255, 140, 0, 0.02)';
                }, false);
            });
            
            dropArea.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files.length > 0) {
                    document.getElementById('scene_image').files = files;
                    previewImage({ target: { files: files } });
                }
            }, false);
        }
    </script>
</body>
</html>