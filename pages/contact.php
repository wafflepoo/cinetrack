<?php
session_start();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message_content = trim($_POST['message'] ?? '');

    // Validation des champs
    if (empty($name) || strlen($name) < 2) {
        $errors['name'] = 'Veuillez entrer votre nom';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Veuillez entrer une adresse email valide';
    }

    if (empty($subject)) {
        $errors['subject'] = 'Veuillez entrer un sujet';
    }

    if (empty($message_content)) {
        $errors['message'] = 'Veuillez écrire un message';
    }

    // Si pas d'erreurs, envoi des emails
    if (empty($errors)) {
        // Email vers l'admin
        $to_admin = "cinetracksite@gmail.com";
        $admin_subject = "Nouveau message depuis le formulaire de contact: $subject";
        $admin_message = "Nom: $name\nEmail: $email\nSujet: $subject\nMessage: $message_content";
        $headers_admin = "From: $email\r\nReply-To: $email\r\nX-Mailer: PHP/" . phpversion();

        $sent_admin = mail($to_admin, $admin_subject, $admin_message, $headers_admin);

        // Email de confirmation à l'utilisateur
        $to_user = $email;
        $user_subject = "CineTrack - Nous avons bien reçu votre message";
        $user_message = "Bonjour $name,\n\nMerci de nous avoir contactés ! Nous avons bien reçu votre message et vous répondrons dans les plus brefs délais.\n\nVotre message :\nSujet : $subject\nMessage : $message_content\n\nCordialement,\nL'équipe CineTrack";
        $headers_user = "From: cinetracksite@gmail.com\r\nReply-To: cinetracksite@gmail.com\r\nX-Mailer: PHP/" . phpversion();

        $sent_user = mail($to_user, $user_subject, $user_message, $headers_user);

        $success = $sent_admin && $sent_user;
        if (!$success) {
            $errors['general'] = 'Une erreur est survenue lors de l\'envoi des emails. Veuillez réessayer.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact CineTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col">
    <?php include '../includes/header.php'; ?>

    <main class="flex-grow flex items-center justify-center py-16 px-4">
        <div class="container max-w-2xl w-full">
            <div class="glass rounded-2xl p-8 shadow-2xl">
                <h1 class="text-3xl font-bold mb-6 text-orange-500">Contactez-nous</h1>
                <?php if ($success): ?>
                    <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-6">
                        <strong>Merci !</strong> Votre message a été envoyé et nous avons confirmé la réception à votre email.
                    </div>
                <?php endif; ?>
                <?php if (isset($errors['general'])): ?>
                    <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-6">
                        <?php echo htmlspecialchars($errors['general']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Nom</label>
                        <input type="text" id="name" name="name" class="w-full px-4 py-3 form-input rounded-lg bg-gray-800 border border-gray-600" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        <?php if (isset($errors['name'])): ?><div class="text-red-500 text-sm mt-1"><?php echo $errors['name']; ?></div><?php endif; ?>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                        <input type="email" id="email" name="email" class="w-full px-4 py-3 form-input rounded-lg bg-gray-800 border border-gray-600" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        <?php if (isset($errors['email'])): ?><div class="text-red-500 text-sm mt-1"><?php echo $errors['email']; ?></div><?php endif; ?>
                    </div>
                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-300 mb-2">Sujet</label>
                        <input type="text" id="subject" name="subject" class="w-full px-4 py-3 form-input rounded-lg bg-gray-800 border border-gray-600" 
                               value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
                        <?php if (isset($errors['subject'])): ?><div class="text-red-500 text-sm mt-1"><?php echo $errors['subject']; ?></div><?php endif; ?>
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-300 mb-2">Message</label>
                        <textarea id="message" name="message" rows="5" class="w-full px-4 py-3 form-input rounded-lg bg-gray-800 border border-gray-600" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        <?php if (isset($errors['message'])): ?><div class="text-red-500 text-sm mt-1"><?php echo $errors['message']; ?></div><?php endif; ?>
                    </div>

                    <button type="submit" class="w-full py-3 bg-orange-500 hover:bg-orange-600 rounded-lg font-semibold text-white transition-all duration-300">
                        Envoyer
                    </button>
                </form>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
