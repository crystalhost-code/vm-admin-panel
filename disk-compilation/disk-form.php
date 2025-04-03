<?php
include 'api/api-proxmox-disk.php';
session_start();

$auth = getAuthToken($proxmox_host, $proxmox_user, $proxmox_pass);
if (!$auth) {
    die("Błąd logowania do Proxmoxa.");
}

$iso_list = getISOList($proxmox_host, $auth['ticket'], $auth['CSRFPreventionToken'], $node);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utwórz nową VM</title>
    <style>
        body { font-family: Arial, sans-serif; }
        form { width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        label { display: block; margin-top: 10px; }
        input, select { width: 100%; padding: 8px; margin-top: 5px; }
        button { margin-top: 15px; padding: 10px; background-color: green; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
<h2 style="text-align:center;">Tworzenie Nowej Voluminu i Dysku VM</h2>
<form action="create_disk.php" method="post">
    <label>ID VM:</label>
    <input type="number" name="vmid" required>

    <label>RAM (GB):</label>
    <input type="number" name="ram" required>

    <label>Dysk (GB):</label>
    <input type="number" name="disk" required>

    <label>VCPU:</label>
    <input type="number" name="cpu" required>

    <label>Obraz ISO:</label>
    <select name="iso" required>
        <?php if (empty($iso_list)): ?>
            <option value="">Brak dostępnych ISO</option>
        <?php else: ?>
            <?php foreach ($iso_list as $iso): ?>
                <option value="<?= htmlspecialchars($iso) ?>"><?= htmlspecialchars($iso) ?></option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>

    <label for="mac">MAC Address (opcjonalne):</label>
    <input type="text" id="mac" name="mac" placeholder="Opcjonalne">

    <button type="submit">Utwórz Dysk,VM</button>
</form>
</body>
</html>