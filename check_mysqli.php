<?php
// check_mysqli.php
echo "<h3>Vérification MySQLi</h3>";

// Vérifier si MySQLi existe
if (class_exists('mysqli')) {
    echo "✅ <strong>MySQLi est INSTALLÉ</strong><br>";
    
    // Vérifier la version
    echo "Version MySQLi : " . mysqli_get_client_info() . "<br>";
} else {
    echo "❌ <strong>MySQLi n'est PAS INSTALLÉ</strong><br>";
}

// Vérifier PDO aussi
if (class_exists('PDO')) {
    echo "✅ <strong>PDO est INSTALLÉ</strong><br>";
    echo "Drivers PDO disponibles : " . implode(", ", PDO::getAvailableDrivers()) . "<br>";
} else {
    echo "❌ <strong>PDO n'est PAS INSTALLÉ</strong><br>";
}

// Vérifier les extensions chargées
echo "<h4>Extensions PHP chargées :</h4>";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    if (strpos($ext, 'mysql') !== false) {
        echo "<strong>✅ $ext</strong><br>";
    } else {
        echo "$ext<br>";
    }
}
?>