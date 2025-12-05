<?php
// includes/quiz_widget.php
session_start();
if (!isset($_SESSION['user_id'])) {
    return; // Ne pas afficher si non connecté
}

// Vérifier si l'utilisateur a déjà fait le quiz aujourd'hui
$today = date('Y-m-d');
$has_done_quiz_today = false; // À implémenter avec votre DB si besoin

if (!$has_done_quiz_today):
?>
<div class="quiz-widget glass p-6 rounded-2xl mb-8">
    <h3 class="text-xl font-bold mb-4 flex items-center gap-3">
        <i class="fas fa-bolt text-yellow-500"></i>
        Défi du jour
    </h3>
    <p class="text-gray-300 mb-4">Testez vos connaissances avec notre quiz cinéma quotidien !</p>
    
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="text-3xl font-bold">10</div>
            <div class="text-sm text-gray-400">questions</div>
        </div>
        <div>
            <div class="text-3xl font-bold">10<span class="text-lg">min</span></div>
            <div class="text-sm text-gray-400">temps</div>
        </div>
        <div>
            <div class="text-3xl font-bold"><?php echo rand(50, 85); ?>%</div>
            <div class="text-sm text-gray-400">réussite</div>
        </div>
    </div>
    
    <a href="/pages/quiz.php" class="w-full btn-primary py-3 rounded-xl font-semibold text-center block">
        <i class="fas fa-play mr-2"></i> Commencer le quiz
    </a>
    
    <p class="text-xs text-gray-500 mt-3 text-center">
        Nouveau quiz chaque jour à minuit
    </p>
</div>
<?php else: ?>
<div class="quiz-widget glass p-6 rounded-2xl mb-8">
    <h3 class="text-xl font-bold mb-4 flex items-center gap-3">
        <i class="fas fa-check-circle text-green-500"></i>
        Quiz terminé !
    </h3>
    <p class="text-gray-300 mb-4">Vous avez déjà fait le quiz d'aujourd'hui. Revenez demain !</p>
    
    <div class="text-center">
        <div class="text-4xl font-bold text-green-400 mb-2">8/10</div>
        <div class="text-sm text-gray-400 mb-4">Votre score d'aujourd'hui</div>
        
        <a href="/pages/quiz.php" class="text-orange-400 hover:text-orange-300 font-semibold">
            <i class="fas fa-chart-line mr-2"></i> Voir mes statistiques
        </a>
    </div>
</div>
<?php endif; ?>