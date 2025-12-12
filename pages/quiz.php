<?php
session_start();
include '../includes/config.conf.php';

// Configuration pour le quiz
define('QUIZ_QUESTIONS_COUNT', 10);

// Fonction pour récupérer des films populaires
function fetchPopularMoviesForQuiz($count = 20) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'movie/popular?api_key=' . $api_key . '&language=fr-FR&page=1';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'CineTrack/1.0'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['results'])) {
            return array_slice($data['results'], 0, $count);
        }
    }
    
    return [];
}

// Fonction pour récupérer des séries populaires
function fetchPopularTVShowsForQuiz($count = 20) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'tv/popular?api_key=' . $api_key . '&language=fr-FR&page=1';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'CineTrack/1.0'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['results'])) {
            return array_slice($data['results'], 0, $count);
        }
    }
    
    return [];
}

// Fonction pour récupérer les détails d'un film
function fetchMovieDetails($movie_id) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'movie/' . $movie_id . '?api_key=' . $api_key . '&language=fr-FR';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'CineTrack/1.0'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        return json_decode($response, true);
    }
    
    return null;
}

// Fonction pour récupérer les crédits d'un film
function fetchMovieCredits($movie_id) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'movie/' . $movie_id . '/credits?api_key=' . $api_key . '&language=fr-FR';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'CineTrack/1.0'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        return json_decode($response, true);
    }
    
    return null;
}

// Fonction pour récupérer les détails d'une série
function fetchTVShowDetails($tv_id) {
    $api_key = TMDB_API_KEY;
    $url = TMDB_BASE_URL . 'tv/' . $tv_id . '?api_key=' . $api_key . '&language=fr-FR';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'CineTrack/1.0'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        return json_decode($response, true);
    }
    
    return null;
}

// Fonction pour générer des questions sur les films
function generateMovieQuestions($movies) {
    $questions = [];
    
    foreach ($movies as $movie) {
        $movie_details = fetchMovieDetails($movie['id']);
        $movie_credits = fetchMovieCredits($movie['id']);
        
        if (!$movie_details || !$movie_credits) {
            continue;
        }
        
        $title = $movie_details['title'];
        $year = substr($movie_details['release_date'], 0, 4);
        
        // Trouver le réalisateur
        $director = '';
        foreach ($movie_credits['crew'] as $person) {
            if ($person['job'] === 'Director') {
                $director = $person['name'];
                break;
            }
        }
        
        // Questions possibles
        $possible_questions = [];
        
        // Question sur le réalisateur
        if (!empty($director)) {
            $possible_questions[] = [
                'question' => "Qui a réalisé le film \"$title\" ?",
                'correct_answer' => $director,
                'type' => 'movie',
                'movie_title' => $title
            ];
        }
        
        // Question sur l'année
        if (!empty($year)) {
            $possible_questions[] = [
                'question' => "En quelle année est sorti le film \"$title\" ?",
                'correct_answer' => $year,
                'type' => 'movie',
                'movie_title' => $title
            ];
        }
        
        // Prendre une question au hasard
        if (!empty($possible_questions)) {
            $selected_question = $possible_questions[array_rand($possible_questions)];
            
            // Générer des réponses incorrectes
            $incorrect_answers = [];
            
            if (strpos($selected_question['question'], 'réalisé') !== false) {
                // Pour les réalisateurs
                $fake_directors = ['Steven Spielberg', 'Christopher Nolan', 'Quentin Tarantino', 'Martin Scorsese', 'James Cameron'];
                shuffle($fake_directors);
                $incorrect_answers = array_slice($fake_directors, 0, 3);
            } else {
                // Pour les années
                $year_int = (int)$year;
                $incorrect_answers = [
                    (string)($year_int - 1),
                    (string)($year_int + 1),
                    (string)($year_int - 2)
                ];
            }
            
            $selected_question['incorrect_answers'] = $incorrect_answers;
            $questions[] = $selected_question;
        }
        
        if (count($questions) >= QUIZ_QUESTIONS_COUNT) {
            break;
        }
    }
    
    return $questions;
}

// Fonction pour générer des questions sur les séries
function generateTVQuestions($tv_shows) {
    $questions = [];
    
    foreach ($tv_shows as $show) {
        $tv_details = fetchTVShowDetails($show['id']);
        
        if (!$tv_details) {
            continue;
        }
        
        $title = $tv_details['name'];
        $year = substr($tv_details['first_air_date'], 0, 4);
        
        // Question sur l'année de création
        if (!empty($year)) {
            $year_int = (int)$year;
            
            $questions[] = [
                'question' => "En quelle année a commencé la série \"$title\" ?",
                'correct_answer' => $year,
                'incorrect_answers' => [
                    (string)($year_int - 1),
                    (string)($year_int + 1),
                    (string)($year_int + 2)
                ],
                'type' => 'tv',
                'show_title' => $title
            ];
        }
        
        if (count($questions) >= QUIZ_QUESTIONS_COUNT) {
            break;
        }
    }
    
    return $questions;
}

// Fonction pour générer des questions de quiz
function generateQuizQuestions() {
    $questions = [];
    
    // Récupérer des films populaires
    $movies = fetchPopularMoviesForQuiz(10);
    $movie_questions = generateMovieQuestions($movies);
    
    // Récupérer des séries populaires
    $tv_shows = fetchPopularTVShowsForQuiz(10);
    $tv_questions = generateTVQuestions($tv_shows);
    
    // Combiner les questions
    $all_questions = array_merge($movie_questions, $tv_questions);
    shuffle($all_questions);
    
    // Prendre le nombre requis de questions
    $questions = array_slice($all_questions, 0, QUIZ_QUESTIONS_COUNT);
    
    // Si pas assez de questions, ajouter des questions de secours
    if (count($questions) < QUIZ_QUESTIONS_COUNT) {
        $questions = array_merge($questions, getFallbackQuestions());
        $questions = array_slice($questions, 0, QUIZ_QUESTIONS_COUNT);
    }
    
    // Mélanger les réponses pour chaque question
    foreach ($questions as &$question) {
        $all_answers = array_merge([$question['correct_answer']], $question['incorrect_answers']);
        shuffle($all_answers);
        $question['all_answers'] = $all_answers;
    }
    
    return $questions;
}

// Questions de secours en français
function getFallbackQuestions() {
    return [
        [
            'question' => 'Quel réalisateur a dirigé la trilogie "Le Seigneur des Anneaux" ?',
            'correct_answer' => 'Peter Jackson',
            'incorrect_answers' => ['Steven Spielberg', 'Christopher Nolan', 'James Cameron'],
            'type' => 'movie',
            'movie_title' => 'Le Seigneur des Anneaux'
        ],
        [
            'question' => 'Quel acteur joue le rôle de Tony Stark/Iron Man dans l\'univers cinématographique Marvel ?',
            'correct_answer' => 'Robert Downey Jr.',
            'incorrect_answers' => ['Chris Evans', 'Chris Hemsworth', 'Mark Ruffalo'],
            'type' => 'movie',
            'movie_title' => 'Iron Man'
        ],
        [
            'question' => 'Quelle série a remporté le plus d\'Emmy Awards en une seule année ?',
            'correct_answer' => 'Game of Thrones',
            'incorrect_answers' => ['Breaking Bad', 'The Crown', 'Stranger Things'],
            'type' => 'tv',
            'show_title' => 'Game of Thrones'
        ],
        [
            'question' => 'Quel film a remporté l\'Oscar du meilleur film en 2020 ?',
            'correct_answer' => 'Parasite',
            'incorrect_answers' => ['1917', 'Joker', 'Once Upon a Time in Hollywood'],
            'type' => 'movie',
            'movie_title' => 'Parasite'
        ],
        [
            'question' => 'Quelle actrice joue Hermione Granger dans la saga "Harry Potter" ?',
            'correct_answer' => 'Emma Watson',
            'incorrect_answers' => ['Emma Stone', 'Keira Knightley', 'Natalie Portman'],
            'type' => 'movie',
            'movie_title' => 'Harry Potter'
        ],
        [
            'question' => 'Quelle est la plus longue série télévisée de l\'histoire en nombre d\'épisodes ?',
            'correct_answer' => 'Les Feux de l\'Amour',
            'incorrect_answers' => ['Grey\'s Anatomy', 'Doctor Who', 'Simpsons'],
            'type' => 'tv',
            'show_title' => 'Les Feux de l\'Amour'
        ],
        [
            'question' => 'Quel réalisateur français a remporté la Palme d\'Or à Cannes pour "Titane" ?',
            'correct_answer' => 'Julia Ducournau',
            'incorrect_answers' => ['Luc Besson', 'Jean-Pierre Jeunet', 'Céline Sciamma'],
            'type' => 'movie',
            'movie_title' => 'Titane'
        ],
        [
            'question' => 'Quel film détient le record du plus grand box-office mondial ?',
            'correct_answer' => 'Avatar',
            'incorrect_answers' => ['Avengers: Endgame', 'Titanic', 'Star Wars: Le Réveil de la Force'],
            'type' => 'movie',
            'movie_title' => 'Avatar'
        ],
        [
            'question' => 'Quelle plateforme de streaming a produit la série "Stranger Things" ?',
            'correct_answer' => 'Netflix',
            'incorrect_answers' => ['Amazon Prime', 'Disney+', 'HBO Max'],
            'type' => 'tv',
            'show_title' => 'Stranger Things'
        ],
        [
            'question' => 'Quel acteur a joué le Joker dans "The Dark Knight" ?',
            'correct_answer' => 'Heath Ledger',
            'incorrect_answers' => ['Joaquin Phoenix', 'Jack Nicholson', 'Jared Leto'],
            'type' => 'movie',
            'movie_title' => 'The Dark Knight'
        ]
    ];
}

// Gestion de la soumission du quiz
$score = 0;
$total_questions = 0;
$user_answers = [];
$show_results = false;
$questions = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les questions de la session
    if (isset($_SESSION['quiz_questions'])) {
        $questions = $_SESSION['quiz_questions'];
        $total_questions = count($questions);
        
        // Calculer le score
        foreach ($questions as $index => $question) {
            $question_key = 'question_' . $index;
            if (isset($_POST[$question_key])) {
                $user_answer = trim($_POST[$question_key]);
                $user_answers[$index] = $user_answer;
                
                if (strcasecmp($user_answer, $question['correct_answer']) == 0) {
                    $score++;
                }
            }
        }
        
        $show_results = true;
        
        // Sauvegarder le score dans la session
        $_SESSION['last_quiz_score'] = $score;
        $_SESSION['last_quiz_total'] = $total_questions;
        $_SESSION['last_quiz_date'] = date('Y-m-d');
    }
} else {
    // Nouveau quiz - générer les questions
    $questions = generateQuizQuestions();
    
    // Mélanger les réponses pour chaque question
    foreach ($questions as &$question) {
        $all_answers = array_merge([$question['correct_answer']], $question['incorrect_answers']);
        shuffle($all_answers);
        $question['all_answers'] = $all_answers;
    }
    
    // Sauvegarder les questions dans la session
    $_SESSION['quiz_questions'] = $questions;
    $_SESSION['quiz_generated_at'] = time();
    $total_questions = count($questions);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Cinéma - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
            scroll-behavior: smooth;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #0a0e14 0%, #05080d 100%);
            min-height: 100vh;
        }
        
        .quiz-header-card {
            background: linear-gradient(135deg, rgba(25, 25, 40, 0.9) 0%, rgba(15, 15, 30, 0.95) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 140, 0, 0.15);
            border-radius: 16px;
        }
        
        .question-counter {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.25rem;
            box-shadow: 0 4px 15px rgba(255, 140, 0, 0.3);
        }
        
        .type-badge {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .movie-badge {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #93c5fd;
        }
        
        .tv-badge {
            background: rgba(168, 85, 247, 0.15);
            border: 1px solid rgba(168, 85, 247, 0.3);
            color: #d8b4fe;
        }
        
        .quiz-progress-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 3px;
            overflow: hidden;
            flex-grow: 1;
        }
        
        .quiz-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff8c00 0%, #ffa500 100%);
            transition: width 0.5s ease;
        }
        
        .option-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .option-card:hover {
            border-color: rgba(255, 140, 0, 0.3);
            background: rgba(255, 140, 0, 0.05);
            transform: translateY(-2px);
        }
        
        .option-card.selected {
            border-color: rgba(255, 140, 0, 0.5);
            background: rgba(255, 140, 0, 0.1);
        }
        
        .answer-option {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }
        
        .answer-letter {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: rgba(255, 255, 255, 0.7);
            flex-shrink: 0;
        }
        
        input[type="radio"]:checked + label .answer-letter {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            color: white;
            border-color: rgba(255, 140, 0, 0.5);
        }
        
        .question-progress-bar {
            height: 4px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .question-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff8c00 0%, #ffa500 100%);
        }
        
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #ff9d1a 0%, #ff7c1a 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 140, 0, 0.3);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        /* Styles pour les résultats */
        .score-circle {
            width: 180px;
            height: 180px;
            border: 4px solid;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            font-weight: bold;
            margin: 0 auto;
            position: relative;
        }
        
        .score-circle::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, #ff8c00, #ff6b00, #ff8c00);
            animation: rotate 3s linear infinite;
            z-index: -1;
        }
        
        .score-circle::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            right: 2px;
            bottom: 2px;
            background: linear-gradient(135deg, #0a0e14 0%, #05080d 100%);
            border-radius: 50%;
            z-index: -1;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="gradient-bg text-white">
    <!-- Header -->
    <?php include '../includes/header.php'; ?>
    
    <main class="min-h-screen py-8 px-4 md:px-6 pt-24">
        <div class="max-w-4xl mx-auto">
            
            <?php if (!$show_results): ?>
                <!-- En-tête du quiz - Style de l'image -->
                <div class="quiz-header-card p-6 mb-8 fade-in">
                    <!-- Défi quotidien -->
                    <div class="mb-6">
                        <div class="inline-flex items-center px-4 py-2 rounded-lg bg-gradient-to-r from-orange-500/10 to-red-500/10 border border-orange-500/20 mb-4">
                            <i class="fas fa-bolt text-orange-400 mr-2"></i>
                            <span class="text-orange-400 font-semibold">Défi quotidien - 10 questions - 100% français</span>
                        </div>
                        
                        <!-- Titre et date -->
                        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
                            <div>
                                <h2 class="text-2xl font-bold text-white mb-1">Quiz du jour</h2>
                                <p class="text-gray-400"><?php echo date('d/m/Y'); ?></p>
                            </div>
                            
                            <!-- Stats -->
                            <div class="flex items-center gap-6">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-white"><?php echo $total_questions; ?>/10</div>
                                    <div class="text-gray-400 text-sm">Questions</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-white">~10</div>
                                    <div class="text-gray-400 text-sm">minutes</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Barre de progression globale -->
                        <div>
                            <div class="flex items-center justify-between text-sm text-gray-400 mb-2">
                                <span>Votre progression</span>
                                <span><?php echo $total_questions; ?>/10 questions</span>
                            </div>
                            <div class="quiz-progress-bar">
                                <div class="quiz-progress-fill" style="width: <?php echo ($total_questions / 10 * 100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire du quiz -->
                <form method="POST" id="quizForm" class="space-y-6">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="glass-card p-6 rounded-xl fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
                            <!-- En-tête de la question -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-4">
                                    <div class="question-counter">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-gray-300 font-medium">Question <?php echo $index + 1; ?>/10</span>
                                        <span class="type-badge <?php echo $question['type'] === 'movie' ? 'movie-badge' : 'tv-badge'; ?>">
                                            <i class="fas <?php echo $question['type'] === 'movie' ? 'fa-film' : 'fa-tv'; ?> mr-1"></i>
                                            <?php echo $question['type'] === 'movie' ? 'Film' : 'Série'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Barre de progression de la question -->
                                <div class="hidden md:block w-32">
                                    <div class="question-progress-bar">
                                        <div class="question-progress-fill" style="width: <?php echo (($index + 1) / 10 * 100); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Question -->
                            <h3 class="text-xl font-bold mb-6 text-white leading-relaxed">
                                <?php echo htmlspecialchars($question['question']); ?>
                            </h3>
                            
                            <?php if (isset($question['movie_title']) || isset($question['show_title'])): ?>
                                <div class="mb-6 p-4 rounded-lg bg-gradient-to-r from-orange-500/5 to-transparent border-l-4 border-orange-500">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-info-circle text-orange-400"></i>
                                        <span class="text-gray-300">
                                            <span class="text-gray-400">À propos :</span>
                                            <strong class="ml-2 text-orange-300"><?php echo htmlspecialchars($question['movie_title'] ?? $question['show_title']); ?></strong>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Options de réponse -->
                            <div class="grid gap-3">
                                <?php foreach ($question['all_answers'] as $answer_index => $answer): ?>
                                    <div class="option-card p-4 rounded-lg">
                                        <input type="radio" 
                                               name="question_<?php echo $index; ?>" 
                                               value="<?php echo htmlspecialchars($answer); ?>" 
                                               id="q<?php echo $index; ?>_a<?php echo $answer_index; ?>"
                                               class="hidden peer">
                                        <label for="q<?php echo $index; ?>_a<?php echo $answer_index; ?>" 
                                               class="answer-option cursor-pointer">
                                            <div class="answer-letter">
                                                <?php echo chr(65 + $answer_index); ?>
                                            </div>
                                            <span class="text-gray-200"><?php echo htmlspecialchars($answer); ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Bouton de soumission -->
                    <div class="text-center mt-10">
                        <button type="submit" 
                                class="btn-primary px-12 py-4 rounded-xl text-lg font-bold inline-flex items-center gap-3">
                            <i class="fas fa-paper-plane"></i>
                            Valider mes réponses
                        </button>
                        <p class="text-gray-400 mt-4 text-sm">
                            <i class="fas fa-info-circle mr-2"></i>
                            Une fois validées, vos réponses ne pourront plus être modifiées
                        </p>
                    </div>
                </form>
                
            <?php else: ?>
                <!-- Résultats du quiz -->
                <div class="fade-in">
                    <!-- En-tête des résultats -->
                    <div class="quiz-header-card p-8 text-center mb-8">
                        <div class="inline-flex items-center gap-3 mb-6">
                            <div class="p-4 rounded-xl bg-gradient-to-br from-yellow-500/20 to-orange-600/20">
                                <i class="fas fa-trophy text-3xl text-yellow-400"></i>
                            </div>
                            <h2 class="text-3xl font-bold text-white">
                                Votre Score
                            </h2>
                        </div>
                        
                        <div class="mb-8">
                            <?php
                            $percentage = ($score / $total_questions) * 100;
                            $color_class = '';
                            $message = '';
                            $icon = '';
                            
                            if ($percentage >= 80) {
                                $color_class = 'border-green-500 text-green-400';
                                $message = 'Excellent ! Vous êtes un vrai cinéphile ! 🎬';
                                $icon = 'fas fa-crown';
                            } elseif ($percentage >= 60) {
                                $color_class = 'border-blue-500 text-blue-400';
                                $message = 'Très bon score ! Continue comme ça ! 👍';
                                $icon = 'fas fa-star';
                            } elseif ($percentage >= 40) {
                                $color_class = 'border-yellow-500 text-yellow-400';
                                $message = 'Pas mal ! Il y a encore des choses à découvrir ! 💡';
                                $icon = 'fas fa-lightbulb';
                            } else {
                                $color_class = 'border-orange-500 text-orange-400';
                                $message = 'Il est temps de regarder plus de films ! 😊';
                                $icon = 'fas fa-film';
                            }
                            ?>
                            
                            <div class="score-circle <?php echo $color_class; ?> mb-6 mx-auto">
                                <div class="relative z-10">
                                    <div class="text-4xl font-bold"><?php echo $score; ?>/<?php echo $total_questions; ?></div>
                                    <div class="text-sm text-gray-400 mt-1"><?php echo round($percentage); ?>%</div>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <div class="flex items-center justify-center gap-2 text-xl font-semibold mb-2">
                                    <i class="<?php echo $icon; ?> text-orange-400"></i>
                                    <span class="text-gray-300"><?php echo $message; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Barre de progression -->
                        <div class="max-w-md mx-auto mb-6">
                            <div class="flex justify-between mb-3 text-sm">
                                <span class="text-gray-400">Votre progression</span>
                                <span class="font-bold"><?php echo $score; ?> sur <?php echo $total_questions; ?> questions</span>
                            </div>
                            <div class="quiz-progress-bar w-full">
                                <div class="quiz-progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Détail des réponses -->
                    <div class="glass-card p-6 rounded-xl mb-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 rounded-lg bg-gradient-to-br from-green-500/20 to-green-600/20">
                                <i class="fas fa-clipboard-check text-green-400"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white">
                                Détail des réponses
                            </h3>
                        </div>
                        
                        <div class="space-y-6">
                            <?php foreach ($_SESSION['quiz_questions'] as $index => $question): ?>
                                <?php
                                $user_answer = $user_answers[$index] ?? '';
                                $is_correct = strcasecmp($user_answer, $question['correct_answer']) == 0;
                                ?>
                                <div class="p-5 rounded-lg border <?php echo $is_correct ? 'border-green-500/30 bg-green-500/5' : 'border-red-500/30 bg-red-500/5'; ?>">
                                    <div class="flex flex-wrap items-center justify-between mb-4 gap-3">
                                        <div class="flex items-center gap-4">
                                            <div class="question-counter" style="background: <?php echo $is_correct ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'; ?>">
                                                <?php echo $index + 1; ?>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold">Question <?php echo $index + 1; ?></span>
                                                    <span class="type-badge <?php echo $question['type'] === 'movie' ? 'movie-badge' : 'tv-badge'; ?>">
                                                        <?php echo $question['type'] === 'movie' ? 'Film' : 'Série'; ?>
                                                    </span>
                                                </div>
                                                <span class="text-sm <?php echo $is_correct ? 'text-green-400' : 'text-red-400'; ?>">
                                                    <?php echo $is_correct ? '+1 point' : '0 point'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h4 class="text-lg font-semibold mb-4 text-gray-100"><?php echo htmlspecialchars($question['question']); ?></h4>
                                    
                                    <div class="grid md:grid-cols-2 gap-4">
                                        <div>
                                            <div class="text-gray-400 text-sm mb-2">Votre réponse</div>
                                            <div class="p-3 rounded-lg bg-gray-800/50">
                                                <?php echo htmlspecialchars($user_answer ?: 'Non répondue'); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-gray-400 text-sm mb-2">Bonne réponse</div>
                                            <div class="p-3 rounded-lg bg-gray-800/50 border border-green-500/30">
                                                <?php echo htmlspecialchars($question['correct_answer']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="quiz.php" 
                           class="btn-primary px-8 py-4 rounded-xl font-bold inline-flex items-center gap-3">
                            <i class="fas fa-redo"></i>
                            Nouveau quiz
                        </a>
                        <a href="../index.php" 
                           class="glass-card px-8 py-4 rounded-xl font-bold inline-flex items-center gap-3">
                            <i class="fas fa-home"></i>
                            Retour à l'accueil
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Sélection automatique des options au clic
        document.addEventListener('DOMContentLoaded', function() {
            const optionCards = document.querySelectorAll('.option-card');
            
            optionCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Désélectionner toutes les autres options de la même question
                    const input = card.querySelector('input[type="radio"]');
                    const questionName = input.name;
                    
                    document.querySelectorAll(`input[name="${questionName}"]`).forEach(otherInput => {
                        otherInput.closest('.option-card').classList.remove('selected');
                    });
                    
                    // Sélectionner cette option
                    input.checked = true;
                    card.classList.add('selected');
                });
            });
            
            // Validation du formulaire
            const quizForm = document.getElementById('quizForm');
            if (quizForm) {
                quizForm.addEventListener('submit', function(e) {
                    const answeredQuestions = new Set();
                    document.querySelectorAll('input[type="radio"]:checked').forEach(input => {
                        answeredQuestions.add(input.name);
                    });
                    
                    if (answeredQuestions.size < <?php echo $total_questions; ?>) {
                        e.preventDefault();
                        const unanswered = <?php echo $total_questions; ?> - answeredQuestions.size;
                        alert(`Il vous reste ${unanswered} question(s) sans réponse. Veuillez répondre à toutes les questions avant de soumettre.`);
                    } else {
                        // Afficher un loader
                        const submitBtn = quizForm.querySelector('button[type="submit"]');
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Calcul du score...';
                        submitBtn.disabled = true;
                    }
                });
            }
        });
    </script>
</body>
</html>