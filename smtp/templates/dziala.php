<?php
function getEmailTemplate($message) {
    return "
    <html>
    <head><title>Test SMTP</title></head>
    <body>
        <h2>Dziala system SMTP</h2>
        <p>$message</p>
    </body>
    </html>
    ";
}
?>
