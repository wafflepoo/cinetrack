<?php
// User-specific functions
function getUserWatchlist($pdo, $user_id, $status = null) {
    $sql = "
        SELECT s.*, 
               f.titre as film_titre, f.poster as film_poster, f.date_sortie as film_date,
               se.titre as serie_titre, se.poster as serie_poster, se.date_premiere as serie_date
        FROM SELECTION s
        LEFT JOIN FILM f ON s.id_film = f.id_film
        LEFT JOIN SERIE se ON s.id_serie = se.id_serie
        WHERE s.id_utilisateur = :user_id
    ";
    
    if ($status) {
        $sql .= " AND s.type_status = :status";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id, 'status' => $status]);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addToWatchlist($pdo, $user_id, $media_id, $type, $status = 'plan_to_watch') {
    try {
        $table = $type === 'film' ? 'FILM' : 'SERIE';
        $id_field = $type === 'film' ? 'id_film' : 'id_serie';
        
        // Check if already exists
        $check_sql = "
            SELECT id_selection FROM SELECTION 
            WHERE id_utilisateur = :user_id AND $id_field = :media_id
        ";
        $stmt = $pdo->prepare($check_sql);
        $stmt->execute(['user_id' => $user_id, 'media_id' => $media_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing
            $update_sql = "
                UPDATE SELECTION SET type_status = :status 
                WHERE id_selection = :selection_id
            ";
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute(['status' => $status, 'selection_id' => $existing['id_selection']]);
            return $existing['id_selection'];
        } else {
            // Insert new
            $insert_sql = "
                INSERT INTO SELECTION (id_utilisateur, $id_field, type_status, date_ajout)
                VALUES (:user_id, :media_id, :status, NOW())
            ";
            $stmt = $pdo->prepare($insert_sql);
            $stmt->execute([
                'user_id' => $user_id,
                'media_id' => $media_id,
                'status' => $status
            ]);
            return $pdo->lastInsertId();
        }
    } catch (Exception $e) {
        error_log("Watchlist error: " . $e->getMessage());
        return false;
    }
}

function getUserReviews($pdo, $user_id, $type = 'all') {
    $reviews = [];
    
    if ($type === 'all' || $type === 'film') {
        $stmt = $pdo->prepare("
            SELECT cf.*, f.titre, f.poster, f.date_sortie
            FROM CRITIQUE_FILM cf
            JOIN FILM f ON cf.id_film = f.id_film
            WHERE cf.id_utilisateur = :user_id
            ORDER BY cf.date DESC
        ");
        $stmt->execute(['user_id' => $user_id]);
        $reviews['films'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if ($type === 'all' || $type === 'serie') {
        $stmt = $pdo->prepare("
            SELECT cs.*, s.titre, s.poster, s.date_premiere
            FROM CRITIQUE_SERIE cs
            JOIN SERIE s ON cs.id_serie = s.id_serie
            WHERE cs.id_utilisateur = :user_id
            ORDER BY cs.date DESC
        ");
        $stmt->execute(['user_id' => $user_id]);
        $reviews['series'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $reviews;
}

function getUserLists($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT l.*, 
               COUNT(sl.id_selection) as item_count
        FROM LISTE l
        LEFT JOIN SELECTION_LISTE sl ON l.id_liste = sl.id_liste
        WHERE l.id_utilisateur = :user_id
        GROUP BY l.id_liste
        ORDER BY l.date_creation DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>