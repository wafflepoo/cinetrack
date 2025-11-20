<?php
require_once 'config.conf';

// Vérification reCAPTCHA
function verifyRecaptcha($response) {
    if (empty($response)) return false;
    
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $response
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $responseData = json_decode($result);
    return $responseData->success;
}

// Hashage du mot de passe simplifié
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des données
    if (!verifyRecaptcha($_POST['g-recaptcha-response'])) {
        header('Location: ../inscription.php?error=captcha');
        exit;
    }
    
    // Validation des mots de passe
    if ($_POST['password'] !== $_POST['confirm_password']) {
        header('Location: ../inscription.php?error=password');
        exit;
    }
    
    // Connexion à la base de données et insertion
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([
            $_POST['username'],
            $_POST['email'],
            hashPassword($_POST['password'])
        ]);
        
        header('Location: ../connexion.php?success=1');
        exit;
    } catch (PDOException $e) {
        header('Location: ../inscription.php?error=database');
        exit;
    }
}
?>