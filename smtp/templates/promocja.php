<?php
function getEmailTemplate($message) {
    return "
    <html>
    <head><title>Promocja</title></head>
    <body style='background-color:#f4f4f4; text-align:center;'>
        <h1 style='color:#ff5733;'>🔥 Super Oferta! 🔥</h1>
        <p>$message</p>
    </body>
    </html>
    ";
}
?>
