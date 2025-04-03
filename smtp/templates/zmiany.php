<?php
function getEmailTemplate($message) {
    return "
    <html>
    <head><title>Zmiany W Hostingu</title></head>
    <body style='font-family:Arial, sans-serif;'>
        <h2>🔔 Zmiany W Hostingu</h2>
        <p>$message</p>
    </body>
    </html>
    ";
}
?>
