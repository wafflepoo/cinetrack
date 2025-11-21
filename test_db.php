<?php
// test_db.php
echo "<h2>Test de connexion à la base de données</h2>";

// Chemin vers votre fichier config
require_once 'includes/config.conf';

echo "<h3>Paramètres de configuration :</h3>";
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_USER: " . DB_USER . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_PASS: " . (defined('DB_PASS') && DB_PASS ? '*** (défini)' : 'non défini') . "<br>";

echo "<h3>Test MySQLi :</h3>";
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        echo "<span style='color: red;'>❌ Échec MySQLi: " . $conn->connect_error . "</span>";
    } else {
        echo "<span style='color: green;'>✅ MySQLi connecté avec succès!</span><br>";
        
        // Test table access
        $result = $conn->query("SELECT COUNT(*) as count FROM UTILISATEUR");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✅ Table UTILISATEUR accessible. Enregistrements: " . $row['count'] . "<br>";
        } else {
            echo "<span style='color: red;'>❌ Erreur accès table: " . $conn->error . "</span><br>";
        }
        
        // Test structure table
        echo "<h4>Structure de la table UTILISATEUR :</h4>";
        $result = $conn->query("DESCRIBE UTILISATEUR");
        if ($result) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td>";
                echo "<td>" . $row['Extra'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Exception MySQLi: " . $e->getMessage() . "</span>";
}

echo "<h3>Test PDO :</h3>";
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<span style='color: green;'>✅ PDO connecté avec succès!</span><br>";
    
    // Test table access
    $stmt = $conn->query("SELECT COUNT(*) as count FROM UTILISATEUR");
    if ($stmt) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Table UTILISATEUR accessible. Enregistrements: " . $row['count'] . "<br>";
    }
    
} catch (PDOException $e) {
    echo "<span style='color: red;'>❌ Échec PDO: " . $e->getMessage() . "</span>";
}

echo "<h3>Informations PHP :</h3>";
echo "Version PHP: " . phpversion() . "<br>";
echo "Extensions chargées: " . implode(", ", get_loaded_extensions()) . "<br>";

// Vérifier si MySQLi est disponible
if (class_exists('mysqli')) {
    echo "<span style='color: green;'>✅ Extension MySQLi disponible</span><br>";
} else {
    echo "<span style='color: red;'>❌ Extension MySQLi non disponible</span><br>";
}

// Vérifier si PDO est disponible
if (class_exists('PDO')) {
    echo "<span style='color: green;'>✅ Extension PDO disponible</span><br>";
    $pdo_drivers = PDO::getAvailableDrivers();
    echo "Drivers PDO disponibles: " . implode(", ", $pdo_drivers) . "<br>";
} else {
    echo "<span style='color: red;'>❌ Extension PDO non disponible</span><br>";
}
?>