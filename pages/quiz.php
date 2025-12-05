<?php
// pages/quiz.php
session_start();
include '../includes/config.conf.php';

// DEUX CATÉGORIES SEULEMENT
$categories = [
    11 => ['name' => 'Films', 'icon' => '🎬', 'color' => '#FFC107'],
    14 => ['name' => 'Séries TV', 'icon' => '📺', 'color' => '#9C27B0']
];

// Catégorie par défaut = Films
$selected_category = isset($_GET['cat']) && isset($categories[$_GET['cat']]) 
    ? (int)$_GET['cat'] 
    : 11; // Films par défaut

$current_category = $categories[$selected_category];
$category_id = $selected_category;
$amount = 10;
$difficulty = 'medium';

// Récupérer les questions depuis l'API Open Trivia
function fetchQuizQuestions($amount = 10, $category = 11, $difficulty = 'medium') {
    $url = "https://opentdb.com/api.php?amount=$amount&category=$category&difficulty=$difficulty&type=multiple&encode=url3986";
    
    error_log("Fetching quiz from: $url");
    
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
    
    error_log("Quiz API Error: HTTP $http_code");
    return getFallbackQuestions($category);
}

// Questions de secours adaptées à la catégorie
function getFallbackQuestions($category = 11) {
    if ($category == 14) { // Séries TV
        return [
            [
                'question' => 'Dans%20quelle%20s%C3%A9rie%20trouve-t-on%20les%20personnages%20de%20Walter%20White%20et%20Jesse%20Pinkman%3F',
                'correct_answer' => 'Breaking%20Bad',
                'incorrect_answers' => ['The%20Wire', 'Narcos', 'Better%20Call%20Saul'],
                'type' => 'multiple'
            ],
            [
                'question' => 'Combien%20y%20a-t-il%20de%20saisons%20de%20Friends%3F',
                'correct_answer' => '10',
                'incorrect_answers' => ['8', '9', '11'],
                'type' => 'multiple'
            ],
            [
                'question' => 'Quel%20est%20le%20nom%20de%20la%20maison%20dans%20Game%20of%20Thrones%20dont%20la%20devise%20est%20%22Winter%20is%20Coming%22%3F',
                'correct_answer' => 'Stark',
                'incorrect_answers' => ['Lannister', 'Targaryen', 'Baratheon'],
                'type' => 'multiple'
            ]
        ];
    } else { // Films
        return [
            [
                'question' => 'Dans%20quel%20film%20entend-on%20la%20citation%20%22I%26%2339%3Bll%20be%20back%22%3F',
                'correct_answer' => 'Terminator',
                'incorrect_answers' => ['Matrix', 'Alien', 'RoboCop'],
                'type' => 'multiple'
            ],
            [
                'question' => 'Qui%20a%20r%C3%A9alis%C3%A9%20le%20film%20%22Inception%22%3F',
                'correct_answer' => 'Christopher%20Nolan',
                'incorrect_answers' => ['Steven%20Spielberg', 'James%20Cameron', 'Quentin%20Tarantino'],
                'type' => 'multiple'
            ],
            [
                'question' => 'Quel%20acteur%20joue%20le%20r%C3%B4le%20principal%20dans%20%22Forrest%20Gump%22%3F',
                'correct_answer' => 'Tom%20Hanks',
                'incorrect_answers' => ['Brad%20Pitt', 'Johnny%20Depp', 'Leonardo%20DiCaprio'],
                'type' => 'multiple'
            ]
        ];
    }
}

// Récupérer les questions
$questions = fetchQuizQuestions($amount, $category_id, $difficulty);

// Traitement des résultats si le formulaire est soumis
$score = 0;
$user_answers = [];
$show_results = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $show_results = true;
    
    foreach ($questions as $index => $question) {
        $question_key = 'q' . $index;
        if (isset($_POST[$question_key])) {
            $user_answer = urldecode($_POST[$question_key]);
            $correct_answer = urldecode($question['correct_answer']);
            
            $user_answers[$index] = [
                'user_answer' => $user_answer,
                'correct_answer' => $correct_answer,
                'is_correct' => ($user_answer === $correct_answer)
            ];
            
            if ($user_answer === $correct_answer) {
                $score++;
            }
        }
    }
    if (isset($_SESSION['user_id']) && !empty($questions)) {
        $user_id = $_SESSION['user_id'];
        $score_data = json_encode($user_answers);
        $total_questions = count($questions);
        $percentage = $total_questions > 0 ? round(($score / $total_questions) * 100, 2) : 0;
        
        try {
            $stmt = $mysqli->prepare("
                INSERT INTO quiz_scores (user_id, score, total_questions, percentage, answers_data, quiz_date, category_id) 
                VALUES (?, ?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->bind_param("iiidsi", $user_id, $score, $total_questions, $percentage, $score_data, $category_id);
            
            if ($stmt->execute()) {
                error_log("Quiz score saved for user $user_id: $score/$total_questions ($percentage%)");
            } else {
                error_log("Failed to save quiz score: " . $stmt->error);
            }
            $stmt->close();
            
        } catch (Exception $e) {
            error_log("Error saving quiz score: " . $e->getMessage());
        }
    }
}

// Calculer le pourcentage
$percentage = count($questions) > 0 ? round(($score / count($questions)) * 100) : 0;

// Messages personnalisés par catégorie
$category_messages = [
    11 => [ // Films
        90 => "🎬 Expert cinéma ! Vous êtes incroyable !",
        70 => "👏 Très bon score ! Vaste culture cinématographique !",
        50 => "👍 Pas mal ! Vous connaissez bien vos classiques.",
        30 => "🤔 Quelques révisions nécessaires, mais bon essai !",
        0 => "😅 Il est temps de regarder plus de films !"
    ],
    14 => [ // Séries TV
        90 => "📺 Expert séries ! Vous êtes un vrai binge-watcher !",
        70 => "👏 Excellent ! Vous connaissez vos séries sur le bout des doigts !",
        50 => "👍 Pas mal ! Vous avez de bonnes connaissances en séries !",
        30 => "🤔 Il est temps de rattraper quelques épisodes !",
        0 => "😅 Peut-être devriez-vous commencer une nouvelle série !"
    ]
];

// Utiliser le message spécifique à la catégorie
$final_message = "😅 Il est temps de regarder plus de {$current_category['name']} !";
$message_list = $category_messages[$category_id];

foreach ($message_list as $threshold => $message) {
    if ($percentage >= $threshold) {
        $final_message = $message;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz <?php echo $current_category['name']; ?> - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .quiz-hero {
            background: linear-gradient(135deg, <?php echo $current_category['color']; ?>10 0%, rgba(233, 30, 99, 0.1) 100%);
            padding: 100px 0 50px;
        }
        
        .question-card {
            background: rgba(30, 30, 40, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        
        .question-card:hover {
            border-color: <?php echo $current_category['color']; ?>30;
            box-shadow: 0 10px 30px <?php echo $current_category['color']; ?>10;
        }
        
        .question-number {
            display: inline-block;
            background: linear-gradient(135deg, <?php echo $current_category['color']; ?> 0%, <?php echo $current_category['color']; ?>CC 100%);
            color: #000;
            font-weight: bold;
            padding: 5px 15px;
            border-radius: 20px;
            margin-bottom: 15px;
        }
        
        .option-label {
            display: block;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .option-label:hover {
            background: <?php echo $current_category['color']; ?>10;
            border-color: <?php echo $current_category['color']; ?>30;
            transform: translateX(5px);
        }
        
        .option-label input[type="radio"]:checked + span {
            color: <?php echo $current_category['color']; ?>;
            font-weight: bold;
        }
        
        .option-label.correct {
            background: rgba(76, 175, 80, 0.2);
            border-color: rgba(76, 175, 80, 0.5);
        }
        
        .option-label.incorrect {
            background: rgba(244, 67, 54, 0.2);
            border-color: rgba(244, 67, 54, 0.5);
        }
        
        .score-circle {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: conic-gradient(#4CAF50 0% <?php echo $percentage; ?>%, #333 <?php echo $percentage; ?>% 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            position: relative;
        }
        
        .score-circle::before {
            content: '';
            position: absolute;
            width: 160px;
            height: 160px;
            background: #1a1a2e;
            border-radius: 50%;
        }
        
        .score-text {
            position: relative;
            z-index: 1;
            font-size: 3rem;
            font-weight: bold;
            color: <?php echo $current_category['color']; ?>;
        }
        
        .share-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .share-btn {
            padding: 12px 25px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .share-btn:hover {
            transform: translateY(-3px);
        }
        
        .share-twitter {
            background: rgba(29, 161, 242, 0.2);
            border-color: rgba(29, 161, 242, 0.3);
        }
        
        .share-facebook {
            background: rgba(66, 103, 178, 0.2);
            border-color: rgba(66, 103, 178, 0.3);
        }
        
        .timer {
            position: fixed;
            top: 100px;
            right: 20px;
            background: <?php echo $current_category['color']; ?>20;
            border: 2px solid <?php echo $current_category['color']; ?>;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            z-index: 100;
        }
        
        .category-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .category-card.active {
            border-color: <?php echo $current_category['color']; ?>;
            box-shadow: 0 0 20px <?php echo $current_category['color']; ?>40;
        }
        
        @media (max-width: 768px) {
            .timer {
                top: 80px;
                right: 10px;
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .score-circle {
                width: 150px;
                height: 150px;
            }
            
            .score-circle::before {
                width: 120px;
                height: 120px;
            }
            
            .score-text {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body class="gradient-bg text-white">
    <?php include '../includes/header.php'; ?>
    
    <main>
        <!-- Hero Section -->
        <section class="quiz-hero">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl md:text-6xl font-black mb-2">
                    <?php echo $current_category['icon']; ?> Quiz <?php echo $current_category['name']; ?>
                </h1>
                <p class="text-xl text-gray-300 mb-8">
                    Testez vos connaissances avec <?php echo count($questions); ?> questions
                </p>
                
                <!-- Sélecteur de catégorie (seulement avant le quiz) -->
                <?php if(!$show_results): ?>
                <div class="mb-10">
                    <h3 class="text-lg font-semibold mb-4 text-gray-300">Choisissez votre quiz :</h3>
                    <div class="flex justify-center gap-6 max-w-xl mx-auto">
                        <?php foreach($categories as $id => $cat): ?>
                        <a href="quiz.php?cat=<?php echo $id; ?>" 
                           class="category-card px-6 py-4 rounded-xl flex flex-col items-center gap-2 <?php echo $id == $category_id ? 'active' : 'bg-gray-800/50'; ?>"
                           style="background: <?php echo $cat['color']; ?>20; border-color: <?php echo $cat['color']; ?>30; min-width: 140px;">
                            <span class="text-3xl"><?php echo $cat['icon']; ?></span>
                            <span class="font-bold text-lg"><?php echo $cat['name']; ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="flex flex-col md:flex-row gap-4 justify-center items-center mb-8">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-clock" style="color: <?php echo $current_category['color']; ?>"></i>
                        <span>10 minutes max</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-question-circle" style="color: <?php echo $current_category['color']; ?>"></i>
                        <span><?php echo count($questions); ?> questions</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-trophy" style="color: <?php echo $current_category['color']; ?>"></i>
                        <span>Niveau <?php echo ucfirst($difficulty); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Timer (seulement pendant le quiz) -->
        <?php if(!$show_results): ?>
        <div class="timer" id="quizTimer">10:00</div>
        <?php endif; ?>
        
        <!-- Quiz Content -->
        <section class="py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <?php if($show_results): ?>
                <!-- Résultats -->
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold mb-6">Vos Résultats</h2>
                    
                    <div class="score-circle">
                        <div class="score-text"><?php echo $score; ?>/<?php echo count($questions); ?></div>
                    </div>
                    
                    <h3 class="text-2xl font-bold mb-4"><?php echo $final_message; ?></h3>
                    <p class="text-gray-300 text-lg mb-8">
                        Score : <?php echo $score; ?> sur <?php echo count($questions); ?> (<?php echo $percentage; ?>%)
                    </p>
                    
                    <div class="share-buttons">
                        <a href="https://twitter.com/intent/tweet?text=J%27ai%20obtenu%20<?php echo $score; ?>%2F<?php echo count($questions); ?>%20au%20Quiz%20<?php echo urlencode($current_category['name']); ?>%20sur%20%40CineTrack&url=<?php echo urlencode(SITE_URL . '/pages/quiz.php?cat=' . $category_id); ?>"
                           target="_blank" class="share-btn share-twitter">
                            <i class="fab fa-twitter"></i> Partager sur Twitter
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . '/pages/quiz.php?cat=' . $category_id); ?>"
                           target="_blank" class="share-btn share-facebook">
                            <i class="fab fa-facebook"></i> Partager sur Facebook
                        </a>
                    </div>
                </div>
                
                <!-- Détails des réponses -->
                <div class="mb-12">
                    <h3 class="text-2xl font-bold mb-6">Correction</h3>
                    
                    <?php foreach($questions as $index => $question): 
                        $user_answer_data = $user_answers[$index] ?? null;
                        $question_text = urldecode($question['question']);
                        $correct_answer = urldecode($question['correct_answer']);
                    ?>
                    <div class="question-card">
                        <div class="question-number">Question <?php echo $index + 1; ?></div>
                        <h4 class="text-xl font-bold mb-4"><?php echo htmlspecialchars($question_text); ?></h4>
                        
                        <div class="space-y-3">
                            <?php 
                            // Toutes les options
                            $all_options = array_merge(
                                [$question['correct_answer']],
                                $question['incorrect_answers']
                            );
                            shuffle($all_options);
                            
                            foreach($all_options as $option):
                                $option_text = urldecode($option);
                                $is_correct = ($option === $question['correct_answer']);
                                $is_user_answer = ($user_answer_data && urldecode($user_answer_data['user_answer']) === $option_text);
                                
                                $class = 'option-label';
                                if ($is_correct) $class .= ' correct';
                                if ($is_user_answer && !$is_correct) $class .= ' incorrect';
                            ?>
                            <div class="<?php echo $class; ?>">
                                <span>
                                    <?php echo htmlspecialchars($option_text); ?>
                                    <?php if($is_correct): ?>
                                        <i class="fas fa-check text-green-500 ml-2"></i>
                                    <?php elseif($is_user_answer && !$is_correct): ?>
                                        <i class="fas fa-times text-red-500 ml-2"></i>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if($user_answer_data): ?>
                        <div class="mt-4 p-4 <?php echo $user_answer_data['is_correct'] ? 'bg-green-500/20' : 'bg-red-500/20'; ?> rounded-lg">
                            <p class="font-semibold">
                                <?php if($user_answer_data['is_correct']): ?>
                                    ✅ Bonne réponse ! Vous avez choisi : <span class="text-green-300"><?php echo htmlspecialchars($user_answer_data['user_answer']); ?></span>
                                <?php else: ?>
                                    ❌ Mauvaise réponse. Vous avez choisi : <span class="text-red-300"><?php echo htmlspecialchars($user_answer_data['user_answer']); ?></span><br>
                                    La bonne réponse était : <span class="text-green-300"><?php echo htmlspecialchars($user_answer_data['correct_answer']); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Actions après le quiz -->
                <div class="text-center space-y-6">
                    <div class="flex flex-wrap justify-center gap-4 mb-6">
                        <a href="quiz.php?cat=<?php echo $category_id; ?>" class="px-8 py-4 rounded-xl text-lg font-bold inline-block" style="background: <?php echo $current_category['color']; ?>;">
                            <i class="fas fa-redo mr-2"></i> Nouveau quiz <?php echo $current_category['name']; ?>
                        </a>
                        <a href="quiz.php" class="bg-gray-700 hover:bg-gray-600 px-8 py-4 rounded-xl text-lg font-bold inline-block">
                            <i class="fas fa-random mr-2"></i> Changer de catégorie
                        </a>
                    </div>
                    
                    <div class="text-gray-400">
                        <p class="mb-2">Vous voulez en savoir plus ?</p>
                        <?php if($category_id == 11): ?>
                            <a href="films.php" class="text-yellow-500 hover:text-yellow-400 font-semibold">
                                <i class="fas fa-film mr-2"></i> Découvrir plus de films
                            </a>
                        <?php elseif($category_id == 14): ?>
                            <a href="series.php" class="text-purple-500 hover:text-purple-400 font-semibold">
                                <i class="fas fa-tv mr-2"></i> Découvrir plus de séries
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- Formulaire du quiz -->
                <form method="POST" action="" id="quizForm">
                    <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                    
                    <?php if(empty($questions)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-exclamation-triangle text-4xl mb-4" style="color: <?php echo $current_category['color']; ?>"></i>
                            <h3 class="text-2xl font-bold mb-2">Problème technique</h3>
                            <p class="text-gray-300 mb-6">Impossible de charger les questions pour le moment.</p>
                            <a href="quiz.php?cat=<?php echo $category_id; ?>" class="px-6 py-3 rounded-xl font-semibold inline-block" style="background: <?php echo $current_category['color']; ?>;">
                                <i class="fas fa-sync mr-2"></i> Réessayer
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach($questions as $index => $question): 
                            $question_text = urldecode($question['question']);
                        ?>
                        <div class="question-card">
                            <div class="question-number">Question <?php echo $index + 1; ?></div>
                            <h4 class="text-xl font-bold mb-6"><?php echo htmlspecialchars($question_text); ?></h4>
                            
                            <div class="space-y-3">
                                <?php 
                                // Mélanger les options
                                $all_options = array_merge(
                                    [$question['correct_answer']],
                                    $question['incorrect_answers']
                                );
                                shuffle($all_options);
                                
                                foreach($all_options as $option_index => $option):
                                    $option_text = urldecode($option);
                                ?>
                                <label class="option-label">
                                    <input type="radio" 
                                           name="q<?php echo $index; ?>" 
                                           value="<?php echo htmlspecialchars($option_text); ?>"
                                           required>
                                    <span><?php echo htmlspecialchars($option_text); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="text-center mt-12">
                            <button type="submit" 
                                    name="submit_quiz" 
                                    class="px-10 py-4 rounded-xl text-lg font-bold hover:scale-105 transition-transform"
                                    style="background: <?php echo $current_category['color']; ?>;">
                                <i class="fas fa-paper-plane mr-2"></i> Voir mon score
                            </button>
                            
                            <p class="text-gray-400 mt-4 text-sm">
                                <i class="fas fa-lightbulb mr-2"></i>
                                Répondez à toutes les questions pour voir votre score
                            </p>
                        </div>
                    <?php endif; ?>
                </form>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
    // Timer pour le quiz
    <?php if(!$show_results): ?>
    let timeLeft = 600; // 10 minutes en secondes
    const timerElement = document.getElementById('quizTimer');
    
    function updateTimer() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        if (timeLeft <= 60) {
            timerElement.style.background = 'rgba(244, 67, 54, 0.3)';
            timerElement.style.borderColor = '#F44336';
        }
        
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            document.getElementById('quizForm').submit();
        }
        
        timeLeft--;
    }
    
    const timerInterval = setInterval(updateTimer, 1000);
    updateTimer();
    <?php endif; ?>
    
    // Animation des options
    document.querySelectorAll('.option-label').forEach(label => {
        label.addEventListener('click', function() {
            // Désélectionner les autres options dans la même question
            const questionContainer = this.closest('.question-card');
            questionContainer.querySelectorAll('.option-label').forEach(otherLabel => {
                otherLabel.style.background = 'rgba(255, 255, 255, 0.05)';
                otherLabel.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            });
            
            // Mettre en surbrillance l'option sélectionnée
            this.style.background = '<?php echo $current_category['color']; ?>15';
            this.style.borderColor = '<?php echo $current_category['color']; ?>40';
        });
    });
    
    // Vérifier si toutes les questions sont répondues avant soumission
    document.getElementById('quizForm')?.addEventListener('submit', function(e) {
        const totalQuestions = <?php echo count($questions); ?>;
        let answered = 0;
        
        for (let i = 0; i < totalQuestions; i++) {
            if (document.querySelector(`input[name="q${i}"]:checked`)) {
                answered++;
            }
        }
        
        if (answered < totalQuestions) {
            e.preventDefault();
            alert(`Veuillez répondre à toutes les questions ! (${answered}/${totalQuestions} répondues)`);
            return false;
        }
    });
    </script>
</body>
</html>