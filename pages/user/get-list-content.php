<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();

$list_id = intval($_GET['id']);
$user = getCurrentUser();

// Load items
$stmt = $mysqli->prepare("
    SELECT s.*, f.titre AS film_titre, f.poster AS film_poster,
           sr.titre AS serie_titre, sr.poster AS serie_poster
    FROM SELECTION_LISTE sl
    JOIN SELECTION s ON sl.id_selection = s.id_selection
    LEFT JOIN FILM f ON s.id_film = f.id_film
    LEFT JOIN SERIE sr ON s.id_serie = sr.id_serie
    WHERE sl.id_liste = ?
");
$stmt->bind_param("i", $list_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($data as $item):

    if ($item['id_film']) {
        $title = $item['film_titre'];
        $poster = $item['film_poster'];
        $type = "Film";
    } else {
        $title = $item['serie_titre'];
        $poster = $item['serie_poster'];
        $type = "Série";
    }
?>
<div class="bg-[#1b1f27] p-3 rounded-xl">
    <img src="https://image.tmdb.org/t/p/w300<?= $poster ?>" class="rounded-xl mb-2">
    <h3 class="font-semibold text-white text-sm"><?= htmlspecialchars($title) ?></h3>
    <p class="text-gray-400 text-xs"><?= $type ?></p>
</div>

<?php endforeach; ?>

<?php if (empty($data)): ?>
<p class="text-gray-400">La liste est vide.</p>
<?php endif; ?>
