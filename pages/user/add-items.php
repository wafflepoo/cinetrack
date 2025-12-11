<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$list_id = intval($_GET['id']);

// Load list
$stmt = $mysqli->prepare("SELECT * FROM LISTE WHERE id_liste = ? AND id_utilisateur = ?");
$stmt->bind_param("ii", $list_id, $user['id']);
$stmt->execute();
$list = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Already added
$already = ["film"=>[], "serie"=>[]];

$check = $mysqli->prepare("
    SELECT s.id_film, s.id_serie 
    FROM SELECTION_LISTE sl
    JOIN SELECTION s ON sl.id_selection = s.id_selection
    WHERE sl.id_liste = ?
");
$check->bind_param("i", $list_id);
$check->execute();
$res = $check->get_result();

while ($row = $res->fetch_assoc()) {
    if ($row['id_film'])  $already["film"][]  = $row['id_film'];
    if ($row['id_serie']) $already["serie"][] = $row['id_serie'];
}

$media = [];

if ($list['type'] === 'films') {
    $media = $mysqli->query("
        SELECT id_film AS id, titre, poster, 'film' AS type 
        FROM FILM
    ")->fetch_all(MYSQLI_ASSOC);

} elseif ($list['type'] === 'series') {
    $media = $mysqli->query("
        SELECT id_serie AS id, titre, poster, 'serie' AS type 
        FROM SERIE
    ")->fetch_all(MYSQLI_ASSOC);

} else {
    $films = $mysqli->query("
        SELECT id_film AS id, titre, poster, 'film' AS type FROM FILM
    ")->fetch_all(MYSQLI_ASSOC);

    $series = $mysqli->query("
        SELECT id_serie AS id, titre, poster, 'serie' AS type FROM SERIE
    ")->fetch_all(MYSQLI_ASSOC);

    $media = array_merge($films, $series);
}
?>
<form id="addItemsForm">
    <input type="hidden" name="list_id" value="<?= $list_id ?>">

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">

        <?php foreach ($media as $m): ?>
            <?php 
                $disabled = 
                    ($m['type']=="film"  && in_array($m['id'], $already["film"])) ||
                    ($m['type']=="serie" && in_array($m['id'], $already["serie"]));
            ?>

            <label class="block bg-[#1b1f27] p-4 rounded-xl <?= $disabled ? 'opacity-40 pointer-events-none' : '' ?>">
                <img src="https://image.tmdb.org/t/p/w300<?= $m['poster'] ?>" class="rounded-xl mb-3">

                <div class="flex items-center gap-2">
                    <?php if (!$disabled): ?>
                        <input type="checkbox" name="items[]" value="<?= $m['id'].'|'.$m['type'] ?>" class="w-5 h-5">
                    <?php else: ?>
                        <span class="text-orange-500 text-sm">Déjà ajouté</span>
                    <?php endif; ?>

                    <span><?= htmlspecialchars($m['titre']) ?></span>
                </div>

                <p class="text-gray-400 text-sm"><?= strtoupper($m['type']) ?></p>
            </label>
        <?php endforeach; ?>

    </div>
</form>
