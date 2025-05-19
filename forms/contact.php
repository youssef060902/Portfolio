<?php
header('Content-Type: application/json');

// Inclure l'autoloader de Composer
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Connexion à la base
$host = '127.0.0.1';
$dbname = 'cv';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO contact (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':subject' => $subject,
            ':message' => $message
        ]);
        
        // Configuration de PHPMailer
        $mail = new PHPMailer(true);
        
        // Configuration du serveur SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'youssefcarma@gmail.com'; // Votre adresse Gmail
        $mail->Password = 'oupl cahg lkac cxun'; // Votre mot de passe d'application Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Destinataires
        $mail->setFrom('youssefcarma@gmail.com', 'Portfolio Contact Form');
        $mail->addAddress('youssefcarma@gmail.com');
        $mail->addReplyTo($email, $name);
        
        // Contenu
        $mail->isHTML(true);
        $mail->Subject = "Nouveau message de contact de $name";
        $mail->Body = "
            <h2>Nouveau message de contact</h2>
            <p><strong>Nom :</strong> $name</p>
            <p><strong>Email :</strong> $email</p>
            <p><strong>Sujet :</strong> $subject</p>
            <p><strong>Message :</strong></p>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
        ";
        
        $mail->send();
        
        echo json_encode(['status' => 'success', 'message' => 'Your message has been sent. Thank you!']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to send message: ' . $e->getMessage()]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to send email: ' . $mail->ErrorInfo]);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}
?>