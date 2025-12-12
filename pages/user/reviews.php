<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();
$user = getCurrentUser();

// ------------------- LOAD USER REVIEWS -------------------

// Film reviews
$query = "SELECT cf.*, f.titre, f.poster, f.date_sortie, cf.id_film
          FROM CRITIQUE_FILM cf
          JOIN FILM f ON cf.id_film = f.id_film
          WHERE cf.id_utilisateur = ?
          ORDER BY cf.date DESC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$film_reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Series reviews
$query = "SELECT cs.*, s.titre, s.poster, s.date_premiere, cs.id_serie
          FROM CRITIQUE_SERIE cs
          JOIN SERIE s ON cs.id_serie = s.id_serie
          WHERE cs.id_utilisateur = ?
          ORDER BY cs.date DESC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$serie_reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Critiques - CineTrack</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico">

    <style>
        .gradient-bg { background: linear-gradient(135deg, #0a0e14, #05080d); }
        .glass { background: rgba(30,30,40,0.3); backdrop-filter: blur(10px); }
        .review-card:hover { transform: scale(1.02); border-color: rgba(255,130,0,0.2); }
        .rating-circle { width:48px;height:48px;border-radius:9999px;display:flex;align-items:center;justify-content:center;font-weight:bold; }

        .star { color:#444; font-size:1.2rem; }
        .star.filled { color:#FFD700; }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen">

<?php include '../../includes/header.php'; ?>

<main class="pt-32 pb-16">
    <div class="max-w-6xl mx-auto px-6">

        <h1 class="text-4xl font-black mb-2">Mes Critiques</h1>
        <p class="text-gray-400 text-lg mb-8">Vos avis sur les films et séries</p>

        <!-- FILTER TABS -->
        <div class="glass p-6 rounded-xl mb-10">
            <div class="flex bg-gray-800/50 p-1 rounded-lg gap-1 w-fit">
                <button class="tab-btn active bg-orange-500 text-white px-6 py-2 rounded-lg" data-filter="all">
                    Tous (<?= count($film_reviews) + count($serie_reviews) ?>)
                </button>
                <button class="tab-btn text-gray-300 px-6 py-2 rounded-lg" data-filter="film">
                    Films (<?= count($film_reviews) ?>)
                </button>
                <button class="tab-btn text-gray-300 px-6 py-2 rounded-lg" data-filter="serie">
                    Séries (<?= count($serie_reviews) ?>)
                </button>
            </div>
        </div>

        <div id="reviews-container" class="space-y-6">

        <?php if (empty($film_reviews) && empty($serie_reviews)): ?>
            <div class="text-center py-16 glass rounded-xl">
                <i class="fas fa-star text-6xl text-gray-500 mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-300 mb-2">Aucune critique</h3>
                <p class="text-gray-500 mb-6">Commencez à critiquer vos films et séries !</p>
            </div>

        <?php else: ?>

            <!-- ---------------- FILM REVIEWS ---------------- -->
            <?php foreach ($film_reviews as $review): ?>
            <div class="review-item film-review review-card glass p-6 rounded-xl relative group">

                <!-- DELETE BUTTON -->
                <button onclick="deleteReview(<?= $review['id_film'] ?>, 'film')"
                        class="absolute top-4 right-4 bg-red-600 text-white w-8 h-8 rounded-full hidden group-hover:flex items-center justify-center">
                    ✕
                </button>

                <div class="flex gap-6">

                    <!-- Poster -->
                    <div class="flex-shrink-0">
                        <?php if ($review['poster']): ?>
                            <img src="<?= TMDB_IMAGE_BASE_URL . 'w300' . $review['poster'] ?>" class="w-24 rounded-lg shadow-lg">
                        <?php else: ?>
                            <div class="w-24 h-36 bg-gray-700 rounded-lg flex items-center justify-center">
                                <i class="fas fa-film text-gray-400 text-2xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Content -->
                    <div class="flex-grow">
                        <div class="flex justify-between mb-2">
                            <div>
                                <h3 class="text-xl font-bold"><?= htmlspecialchars($review['titre']) ?></h3>
                                <p class="text-gray-400 text-sm">Film • <?= date('Y', strtotime($review['date_sortie'])) ?></p>
                            </div>

                            <!-- STAR RATING -->
                            <div class="text-right">
                                <?php
                                    $stars = round($review['note'] / 2); // convert 0–10 to 0–5
                                    for ($i=1; $i<=5; $i++):
                                ?>
                                    <i class="fa-solid fa-star star <?= $i <= $stars ? 'filled':'' ?>"></i>
                                <?php endfor; ?>
                                <div class="text-sm mt-1">(<?= number_format($review['note']/2, 1) ?>)</div>
                            </div>
                        </div>

                        <?php if (!empty($review['texte'])): ?>
                            <div class="bg-gray-800/30 p-4 rounded-lg mb-2">
                                <p class="text-gray-300"><?= nl2br(htmlspecialchars($review['texte'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <p class="text-gray-500 text-sm">Posté le <?= date('d/m/Y à H:i', strtotime($review['date'])) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- ---------------- SERIES REVIEWS ---------------- -->
            <?php foreach ($serie_reviews as $review): ?>
            <div class="review-item serie-review review-card glass p-6 rounded-xl relative group">

                <button onclick="deleteReview(<?= $review['id_serie'] ?>, 'serie')"
                        class="absolute top-4 right-4 bg-red-600 text-white w-8 h-8 rounded-full hidden group-hover:flex items-center justify-center">
                    ✕
                </button>

                <div class="flex gap-6">

                    <div class="flex-shrink-0">
                        <?php if ($review['poster']): ?>
                            <img src="<?= TMDB_IMAGE_BASE_URL . 'w300' . $review['poster'] ?>" class="w-24 rounded-lg shadow-lg">
                        <?php else: ?>
                            <div class="w-24 h-36 bg-gray-700 rounded-lg flex items-center justify-center">
                                <i class="fas fa-tv text-gray-400 text-2xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex-grow">
                        <div class="flex justify-between mb-2">
                            <div>
                                <h3 class="text-xl font-bold"><?= htmlspecialchars($review['titre']) ?></h3>
                                <p class="text-gray-400 text-sm">Série • <?= date('Y', strtotime($review['date_premiere'])) ?></p>
                            </div>

                            <!-- STAR RATING -->
                            <div class="text-right">
                                <?php
                                    $stars = round($review['note'] / 2);
                                    for ($i=1; $i<=5; $i++):
                                ?>
                                    <i class="fa-solid fa-star star <?= $i <= $stars ? 'filled':'' ?>"></i>
                                <?php endfor; ?>
                                <div class="text-sm mt-1">(<?= number_format($review['note']/2, 1) ?>)</div>
                            </div>
                        </div>

                        <?php if (!empty($review['texte'])): ?>
                        <div class="bg-gray-800/30 p-4 rounded-lg mb-2">
                            <p class="text-gray-300"><?= nl2br(htmlspecialchars($review['texte'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <p class="text-gray-500 text-sm">Posté le <?= date('d/m/Y à H:i', strtotime($review['date'])) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>

<script>
// DELETE REVIEW
function deleteReview(id, type) {
    let data = new FormData();
    data.append("id", id);
    data.append("type", type);

    fetch("delete-review.php", {
        method: "POST",
        body: data
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === "success") location.reload();
    });
}

// FILTERS
document.querySelectorAll(".tab-btn").forEach(btn => {
    btn.addEventListener("click", () => {

        document.querySelectorAll(".tab-btn").forEach(b =>
            b.classList.remove("bg-orange-500","text-white")
        );
        btn.classList.add("bg-orange-500","text-white");

        const filter = btn.dataset.filter;
        const items = document.querySelectorAll(".review-item");

        items.forEach(card => {
            if (filter === "all") card.style.display = "block";
            else if (filter === "film") card.classList.contains("film-review") ? card.style.display="block" : card.style.display="none";
            else if (filter === "serie") card.classList.contains("serie-review") ? card.style.display="block" : card.style.display="none";
        });
    });
});
</script>

</body>
</html>

<?php
function getRatingColor($rating) {
    if ($rating >= 8) return 'border-green-500 text-green-400 bg-green-500/10';
    if ($rating >= 6) return 'border-yellow-500 text-yellow-400 bg-yellow-500/10';
    if ($rating >= 4) return 'border-orange-500 text-orange-400 bg-orange-500/10';
    return 'border-red-500 text-red-400 bg-red-500/10';
}
?>
