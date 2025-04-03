<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require '../core/smtp.php';

error_log("Debug: Skrypt email-sender.php startuje...");

$template = $_POST['template'] ?? 'default';
$templatePath = realpath("../smtp/templates/$template.php");

if (!$templatePath || !file_exists($templatePath)) {
    error_log("Błąd: Szablon $templatePath nie istnieje");
    echo json_encode(["status" => "error", "message" => "Brak szablonu e-maila!"]);
    exit;
}

require $templatePath;

$response = ['status' => 'error', 'message' => 'Nieznany błąd - sprawdź logi'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';

    error_log("Debug: Otrzymano email=$email, temat=$subject, wiadomość=$message");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Niepoprawny adres e-mail.";
    } elseif (empty($subject) || empty($message)) {
        $response['message'] = "Wszystkie pola są wymagane.";
    } else {
        $body = getEmailTemplate($message);
        if (sendEmail($email, $subject, $body)) {
            $response = ['status' => 'success', 'message' => 'E-mail wysłany pomyślnie!'];
        } else {
            $response['message'] = "Błąd wysyłania e-maila.";
        }
    }
}

error_log("Debug: Odpowiedź JSON: " . json_encode($response));
echo json_encode($response);
?>
