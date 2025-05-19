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
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
                .message-box { background-color: white; padding: 15px; border-left: 4px solid #2c3e50; margin: 15px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                .label { font-weight: bold; color: #2c3e50; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Nouveau Message de Contact</h2>
                </div>
                <div class='content'>
                    <p><span class='label'>De :</span> $name</p>
                    <p><span class='label'>Email :</span> $email</p>
                    <p><span class='label'>Sujet :</span> $subject</p>
                    <div class='message-box'>
                        <p><span class='label'>Message :</span></p>
                        <p>" . nl2br(htmlspecialchars($message)) . "</p>
                    </div>
                </div>
                <div class='footer'>
                    <p>Ce message a été envoyé depuis le formulaire de contact de votre portfolio.</p>
                </div>
            </div>
        </body>
        </html>";
        
        // Version texte pour les clients mail qui ne supportent pas HTML
        $mail->AltBody = "Nouveau message de contact\n\n" .
                        "De : $name\n" .
                        "Email : $email\n" .
                        "Sujet : $subject\n\n" .
                        "Message :\n" . $message;
        
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