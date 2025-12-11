<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$list_id = intval($_GET['id'] ?? 0);

// Load list
$stmt = $mysqli->prepare("SELECT * FROM LISTE WHERE id_liste = ? AND id_utilisateur = ?");
$stmt->bind_param("ii", $list_id, $user['id']);
$stmt->execute();
$list = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$list) {
    die("<p class='text-red-400'>Liste introuvable.</p>");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($list["nom_liste"]) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body.modal-open { overflow: hidden; }

        #addItemsModal {
            display: none;
        }
        #addItemsModal.active {
            display: flex;
        }

        /* Center modal */
        #modalBox {
            transform: translate(-50%, -50%);
        }

        /* Scroll in modal */
        #addItemsContent {
            max-height: 70vh;
            overflow-y: auto;
        }
    </style>
</head>

<body class="bg-[#0a0e14] text-white">

<?php include '../../includes/header.php'; ?>

<div class="max-w-6xl mx-auto pt-32 px-6">

    <h1 class="text-4xl font-bold mb-6"><?= htmlspecialchars($list["nom_liste"]) ?></h1>

    <button id="openAddItemsBtn"
            class="px-6 py-3 bg-orange-600 hover:bg-orange-500 rounded-xl font-semibold">
        ➕ Ajouter des éléments
    </button>

    <?php if (!empty($_SESSION["success"])): ?>
        <div class="mt-6 p-4 bg-green-600 text-white rounded-xl">
            <?= $_SESSION["success"]; unset($_SESSION["success"]); ?>
        </div>
    <?php endif; ?>

    <p class="text-gray-400 mt-6">Ici tu affiches les éléments de la liste…</p>

</div>


<!-- POPUP MODAL -->
<div id="addItemsModal"
     class="fixed inset-0 bg-black/80 backdrop-blur-sm items-center justify-center z-50">

    <div id="modalBox"
         class="absolute top-1/2 left-1/2 bg-[#11141a] w-[90%] max-w-5xl rounded-xl p-6 shadow-xl">

        <button onclick="closeModal()" class="absolute top-4 right-4 text-3xl text-gray-300 hover:text-white">&times;</button>

        <h2 class="text-2xl font-bold mb-4">Ajouter à la liste : <?= htmlspecialchars($list["nom_liste"]) ?></h2>

        <div id="addItemsContent" class="pr-2">
            Chargement...
        </div>

        <button id="submitItems"
                class="mt-6 px-6 py-3 bg-orange-600 hover:bg-orange-500 rounded-xl font-semibold">
            Ajouter les éléments sélectionnés
        </button>
    </div>
</div>


<script>
function openModal() {
    document.getElementById("addItemsModal").classList.add("active");
    document.body.classList.add("modal-open");

    fetch("add-items.php?id=<?= $list_id ?>")
        .then(r => r.text())
        .then(html => {
            document.getElementById("addItemsContent").innerHTML = html;
        });
}

function closeModal() {
    document.getElementById("addItemsModal").classList.remove("active");
    document.body.classList.remove("modal-open");
}

document.getElementById("openAddItemsBtn").addEventListener("click", openModal);

// Submit inside modal (AJAX)
document.getElementById("submitItems").addEventListener("click", function () {
    let form = document.querySelector("#addItemsContent form");
    let data = new FormData(form);

    fetch("save-items.php", {
        method: "POST",
        body: data
    }).then(() => {
        closeModal();
        location.reload(); // refresh list and show success message
    });
});
</script>

</body>
</html>
