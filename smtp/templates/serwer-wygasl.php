<?php
function getEmailTemplate($message) {
    return "
    <html>
    <head><title>Serwer Wygasl</title></head>
    <body style='background-color:#f4f4f4; text-align:center;'>
        <h1 style='color:#ff5733;'>Twoj serwer wygasl oplac go w panelu juz teraz</h1>
        <p>$message</p>
    </body>
    </html>
    ";
}
?>
