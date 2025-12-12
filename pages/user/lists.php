<?php
session_start();
require_once '../../includes/config.conf.php';
require_once '../../includes/auth.php';
requireLogin();

$user = getCurrentUser();

// Load user lists
$stmt = $mysqli->prepare("
    SELECT l.*, COUNT(sl.id_selection) AS item_count
    FROM LISTE l
    LEFT JOIN SELECTION_LISTE sl ON l.id_liste = sl.id_liste
    WHERE l.id_utilisateur = ?
    GROUP BY l.id_liste
    ORDER BY l.date_creation DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$user_lists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Listes - CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico">
    <style>
        body.modal-open { overflow: hidden; }

        .gradient-bg {
            background: linear-gradient(135deg, #0a0e14 0%, #05080d 100%);
        }
        .glass {
            background: rgba(30, 30, 40, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.05);
        }

        /* Modal base */
        .modal {
            opacity: 0;
            visibility: hidden;
            transition: 0.25s ease;
        }
        .modal.active {
            opacity: 1;
            visibility: visible;
        }

        /* Add-items modal */
        #addItemsModal {
            display: none;
        }
        #addItemsModal.active {
            display: flex;
        }

        #addItemsContent {
            max-height: 70vh;
            overflow-y: auto;
        }

        #viewListContent {
            max-height: 70vh;
            overflow-y: auto;
        }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen">

<?php include '../../includes/header.php'; ?>

<main class="pt-32 pb-16">
    <div class="max-w-7xl mx-auto px-6">

        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-black mb-2">Mes Listes</h1>
                <p class="text-gray-400 text-lg">Organisez vos films et séries en listes personnalisées</p>
            </div>

            <button onclick="openCreateListModal()" class="btn-primary px-6 py-3 rounded-lg font-semibold mt-4 md:mt-0">
                <i class="fas fa-plus mr-2"></i>Créer une liste
            </button>
        </div>

        <!-- Lists Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($user_lists as $list): ?>
                <div class="glass p-6 rounded-2xl group hover:scale-105 transition-transform">
                    <h3 class="text-xl font-bold">
                        <?= htmlspecialchars($list['nom_liste']) ?>
                    </h3>
                    <p class="text-gray-400 text-sm mb-3">
                        <?= $list['item_count'] ?> élément(s)
                    </p>

                    <button onclick="openViewListModal(<?= $list['id_liste'] ?>)"
                            class="text-orange-500 hover:text-orange-400 font-semibold">
                        Voir la liste →
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- CREATE LIST MODAL -->
<div id="createListModal" class="modal fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50">
    <div class="glass p-8 rounded-2xl max-w-md w-full mx-4">
        <h2 class="text-2xl font-bold mb-6">Créer une nouvelle liste</h2>

        <form id="createListForm">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium">Nom de la liste</label>
                    <input name="list_name" required
                        class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium">Type</label>
                    <select name="list_type"
                        class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg">
                        <option value="films">Films</option>
                        <option value="series">Séries</option>
                        <option value="mixed">Mixte</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-4 mt-8">
                <button type="button" onclick="closeCreateListModal()" class="flex-1 py-3 bg-gray-700 rounded-lg">
                    Annuler
                </button>
                <button type="submit" class="flex-1 py-3 bg-orange-600 rounded-lg">
                    Créer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- VIEW LIST MODAL -->
<div id="viewListModal" class="modal fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
    <div class="glass p-6 rounded-xl max-w-5xl w-[90%] relative">
        <button onclick="closeViewListModal()" class="absolute top-4 right-4 text-3xl text-gray-300">&times;</button>

        <h2 class="text-2xl font-bold mb-4">Contenu de la liste</h2>
<div class="flex gap-4 mb-4">
   
 
</div>

        <div id="viewListContent" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            Chargement…
        </div>

       <div class="flex gap-4 mt-6">
    
    <!-- Ajouter -->
    <button id="openAddInsideView"
        class="px-6 py-3 bg-orange-600 hover:bg-orange-500 rounded-lg">
        Ajouter des éléments
    </button>

    <!-- Supprimer la liste -->
    <button onclick="deleteListDirect(currentListId)"
        class="px-6 py-3 bg-red-600 hover:bg-red-500 rounded-lg">
        Supprimer la liste
    </button>

</div>

    </div>
</div>

<!-- ADD ITEMS MODAL -->
<div id="addItemsModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm items-center justify-center z-50">
    <div class="absolute top-1/2 left-1/2 bg-[#11141a] w-[90%] max-w-5xl p-6 rounded-xl"
         style="transform: translate(-50%, -50%);">

        <button onclick="closeAddItemsModal()" class="absolute top-4 right-4 text-3xl text-gray-300">&times;</button>

        <h2 class="text-2xl font-bold mb-4">Ajouter des éléments</h2>

        <div id="addItemsContent" class="pr-2">Chargement…</div>

        <button id="submitItems" class="mt-6 px-6 py-3 bg-orange-600 rounded-xl">
            Ajouter
        </button>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
/* CREATE LIST */
function openCreateListModal() {
    document.getElementById('createListModal').classList.add("active");
}
function closeCreateListModal() {
    document.getElementById('createListModal').classList.remove("active");
}

document.getElementById("createListForm").addEventListener("submit", e => {
    e.preventDefault();

    let form = new FormData(e.target);

    fetch("create-list.php", { method:"POST", body:form })
        .then(r => r.json())
        .then(res => {
            closeCreateListModal();
            openAddItemsModal(res.list_id);
        });
});

/* VIEW LIST */
let currentListId = null;

function openViewListModal(list_id) {
    currentListId = list_id;
    document.getElementById("viewListModal").classList.add("active");

    fetch("get-list-content.php?id=" + list_id)
        .then(r => r.text())
        .then(html => document.getElementById("viewListContent").innerHTML = html);

    document.getElementById("openAddInsideView").onclick = () => openAddItemsModal(list_id);
}

function closeViewListModal() {
    document.getElementById("viewListModal").classList.remove("active");
}

/* ADD ITEMS */
function openAddItemsModal(list_id) {
    document.getElementById("addItemsModal").classList.add("active");

    fetch("add-items.php?id=" + list_id)
        .then(r => r.text())
        .then(html => document.getElementById("addItemsContent").innerHTML = html);
}

function closeAddItemsModal() {
    document.getElementById("addItemsModal").classList.remove("active");
}

document.getElementById("submitItems").addEventListener("click", () => {
    let form = document.querySelector("#addItemsContent form");
    let data = new FormData(form);

    fetch("save-items.php", { method:"POST", body:data })
        .then(() => {
            closeAddItemsModal();
            location.reload();
        });
});

/* REMOVE ITEM */
function removeItem(selection_id, list_id) {
    let data = new FormData();
    data.append("selection_id", selection_id);
    data.append("list_id", list_id);

    fetch("remove-item.php", { method:"POST", body:data })
        .then(r => r.json())
        .then(res => {
            if (res.status === "success") {
                openViewListModal(list_id); // reload content
            }
        });
}

/* DELETE LIST */
function deleteList(list_id) {
    if (!confirm("Supprimer définitivement cette liste ?")) return;

    let data = new FormData();
    data.append("list_id", list_id);

    fetch("delete-list.php", { method:"POST", body:data })
        .then(r => r.json())
        .then(res => {
            if (res.status === "success") {
                location.reload();
            }
        });
}

/* EDIT LIST */
function editList(list_id) {
    const newName = prompt("Nouveau nom de la liste :");
    if (!newName) return;

    let data = new FormData();
    data.append("list_id", list_id);
    data.append("list_name", newName);
    data.append("list_type", "mixed"); // or keep existing

    fetch("edit-list.php", { method:"POST", body:data })
        .then(r => r.json())
        .then(res => {
            if (res.status === "success") {
                location.reload();
            }
        });
}
function deleteListDirect(list_id) {
    let data = new FormData();
    data.append("list_id", list_id);

    fetch("delete-list.php", {
        method: "POST",
        body: data
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === "success") {
            closeViewListModal();
            location.reload(); // refresh lists page
        }
    });
}

</script>

</body>
</html>
