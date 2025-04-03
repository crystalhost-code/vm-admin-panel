<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wyślij e-mail</title>
    <script>
        function sendEmail(event) {
            event.preventDefault();

            let formData = new FormData(document.getElementById("emailForm"));

            fetch('../actions/email-sender.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log("Odpowiedź z serwera:", data);
                document.getElementById("status").innerText = data.message;
                if (data.status === "success") {
                    document.getElementById("emailForm").reset();
                }
            })
            .catch(error => {
                console.error("Błąd:", error);
                document.getElementById("status").innerText = "Wystąpił błąd podczas wysyłania.";
            });
        }
    </script>
</head>
<body>
    <h2>Panel wysyłania e-maili</h2>
    <p id="status"></p>
    <form id="emailForm" onsubmit="sendEmail(event)">
        <label>Adres e-mail:</label>
        <input type="email" name="email" required><br>

        <label>Temat:</label>
        <input type="text" name="subject" required><br>

        <label>Wiadomość:</label>
        <textarea name="message" required></textarea><br>

        <label>Wybierz szablon:</label>
        <select name="template">
            <option value="default">Domyślny</option>
            <option value="promo">Promocja</option>
            <option value="notification">Powiadomienie</option>
        </select><br>

        <button type="submit">Wyślij e-mail</button>
    </form>
</body>
</html>
