<?php
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

switch ($page) {
    case 'home':
        echo "<h1>Witaj na stronie głównej!</h1>";
        echo "<p>To jest nasza strona główna, która wyświetla się pod /home.</p>";
        break;
    case 'about':
        echo "<h1>O nas</h1>";
        echo "<p>Informacje o naszej firmie.</p>";
        break;
    case 'contact':
        echo "<h1>Kontakt</h1>";
        echo "<p>Skontaktuj się z nami!</p>";
        break;
    default:
        echo "<h1>Strona nie znaleziona</h1>";
        break;
}
?>
