<?php
// Test simple d'upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
    $upload_dir = 'uploads/test/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0775, true);
    
    $filename = 'test_' . uniqid() . '.jpg';
    $target = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['test_image']['tmp_name'], $target)) {
        echo "✅ Image uploadée: $target<br>";
        
        // Test GD
        if (function_exists('imagecreatefromstring')) {
            $data = file_get_contents($target);
            $img = imagecreatefromstring($data);
            if ($img) {
                echo "✅ GD fonctionne!<br>";
                echo "Taille: " . imagesx($img) . "x" . imagesy($img) . "<br>";
                imagedestroy($img);
            } else {
                echo "❌ GD échec<br>";
            }
        }
    }
}
?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_image">
    <button type="submit">Test Upload</button>
</form>