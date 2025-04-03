<?php
function getEmailTemplate($message) {
    return "
    <html>
    <head>
        <title>Wygasniecie Serwera</title>
    </head>
    <body>
        <h2>Serwer Wygasa w przeciagu 7 dni</h2>
        <p>$message</p>
    </body>
    </html>
    ";
}
?>
