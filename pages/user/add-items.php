<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$list_id = intval($_GET['id'] ?? 0);

// Récupérer les infos de la liste
$stmt = $mysqli->prepare("SELECT * FROM LISTE WHERE id_liste = ? AND id_utilisateur = ?");
$stmt->bind_param("ii", $list_id, $user['id']);
$stmt->execute();
$list = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$list) {
    die("Liste introuvable");
}

// Récupérer les médias disponibles selon le type de liste
$media = [];
if ($list['type'] === 'films') {
    $result = $mysqli->query("SELECT id_film AS id, titre FROM FILM");
    $media = $result->fetch_all(MYSQLI_ASSOC);
} elseif ($list['type'] === 'series') {
    $result = $mysqli->query("SELECT id_serie AS id, titre FROM SERIE");
    $media = $result->fetch_all(MYSQLI_ASSOC);
} else { // mixed
    $films = $mysqli->query("SELECT id_film AS id, titre, 'film' AS type FROM FILMS")->fetch_all(MYSQLI_ASSOC);
    $series = $mysqli->query("SELECT id_serie AS id, titre, 'series' AS type FROM SERIES")->fetch_all(MYSQLI_ASSOC);
    $media = array_merge($films, $series);
}
?>

<h1>Ajouter à la liste: <?php echo htmlspecialchars($list['nom_liste']); ?></h1>
<form method="POST" action="save-items.php">
    <input type="hidden" name="id_liste" value="<?php echo $list_id; ?>">
    <?php foreach ($media as $m): ?>
        <label>
            <input type="checkbox" name="media[]" value="<?php echo $m['id']; ?>">
            <?php echo htmlspecialchars($m['titre']); ?>
        </label><br>
    <?php endforeach; ?>
    <button type="submit">Ajouter à la liste</button>
</form>
