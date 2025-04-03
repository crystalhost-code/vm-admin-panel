<?php
function getEmailTemplate($message) {
    return "
    <html>
    <head><title>Wiadomość</title></head>
    <body>
        <h2>Wiadomość od Administratora</h2>
        <p>$message</p>
    </body>
    </html>
    ";
}
?>
