<?php
include 'api/api-proxmox-disk.php';

error_log("Nagłówki żądania: " . json_encode(getallheaders(), JSON_PRETTY_PRINT));

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    error_log("❌ Nieprawidłowa metoda żądania. Oczekiwano POST, otrzymano: " . $_SERVER["REQUEST_METHOD"]);
    error_log("Dane POST: " . json_encode($_POST, JSON_PRETTY_PRINT));
    error_log("Dane GET: " . json_encode($_GET, JSON_PRETTY_PRINT));
    die("❌ Nieprawidłowa metoda żądania. Oczekiwano POST.");
}

if (empty($_POST["vmid"]) || empty($_POST["ram"]) || empty($_POST["disk"]) || empty($_POST["cpu"]) || empty($_POST["iso"])) {
    error_log("❌ Brakujące dane w formularzu. Przekazano: " . json_encode($_POST));
    die("❌ Brakujące dane w formularzu. Sprawdź, czy wszystkie pola zostały wypełnione.");
}

$vmid = intval($_POST["vmid"]);
$ram = intval($_POST["ram"]) * 1024;
$disk = intval($_POST["disk"]);
$cpu = intval($_POST["cpu"]);
$iso = $_POST["iso"];
$mac = $_POST["mac"];

error_log("Dane wejściowe: VMID=$vmid, RAM=$ram, DISK=$disk, CPU=$cpu, ISO=$iso, MAC=$mac");

$authResponse = file_get_contents("$proxmox_host/api2/json/access/ticket", false, stream_context_create([ 
    'http' => [ 
        'method'  => 'POST', 
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n", 
        'content' => http_build_query([ 
            'username' => $proxmox_user, 
            'password' => $proxmox_pass, 
        ]) 
    ] 
])); 

$authData = json_decode($authResponse, true);

if (!$authData || !isset($authData['data']['ticket'])) {
    error_log("❌ Błąd logowania do Proxmoxa. Odpowiedź: " . json_encode($authData, JSON_PRETTY_PRINT));
    die("❌ Błąd logowania do Proxmoxa.");
}

$ticket = $authData['data']['ticket'];
$csrfToken = $authData['data']['CSRFPreventionToken'];

$net0 = "virtio,bridge=vmbr0";
if (!empty($mac) && preg_match('/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/', $mac)) {
    $net0 = "virtio=" . strtoupper($mac) . ",bridge=vmbr0";
}

$diskName = "vm-{$vmid}-disk-0.qcow2";
$diskCheck = curl_init();
curl_setopt_array($diskCheck, [
    CURLOPT_URL => "$proxmox_host/api2/json/nodes/$node/storage/local/content?filename=$diskName",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Cookie: PVEAuthCookie=$ticket",
        "CSRFPreventionToken: $csrfToken",
    ],
]);

$diskCheckResponse = curl_exec($diskCheck);
curl_close($diskCheck);

$diskCheckData = json_decode($diskCheckResponse, true);

if ($diskCheckData && isset($diskCheckData["data"]) && !empty($diskCheckData["data"])) {
    echo "❌ Dysk już istnieje, przechodzimy do tworzenia maszyny wirtualnej.";
} else {
    $diskCreate = curl_init();
    curl_setopt_array($diskCreate, [
        CURLOPT_URL => "$proxmox_host/api2/json/nodes/$node/storage/local/content",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/x-www-form-urlencoded",
            "Cookie: PVEAuthCookie=$ticket",
            "CSRFPreventionToken: $csrfToken",
        ],
        CURLOPT_POSTFIELDS => http_build_query([
            "filename" => $diskName,
            "vmid" => $vmid,
            "size" => "{$disk}G",
            "format" => "qcow2",
        ]),
    ]);

    $diskResponse = curl_exec($diskCreate);
    curl_close($diskCreate);

    $diskData = json_decode($diskResponse, true);
    if (!$diskData || !isset($diskData["data"])) {
        error_log("❌ Błąd tworzenia dysku: " . json_encode($diskData, JSON_PRETTY_PRINT));
        echo "❌ Błąd tworzenia dysku: " . json_encode($diskData, JSON_PRETTY_PRINT);
    }
}

$data = [
    "vmid" => $vmid,
    "name" => "vps-" . $vmid,
    "memory" => $ram,
    "sockets" => 1,
    "cores" => $cpu,
    "net0" => $net0,
    "ide2" => "local:iso/" . basename($iso) . ",media=cdrom",
    "boot" => "order=scsi0;ide2",
    "scsihw" => "virtio-scsi-pci",
    "scsi0" => "local:" . $diskName,
    "ostype" => "l26",
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "$proxmox_host/api2/json/nodes/$node/qemu",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Cookie: PVEAuthCookie=$ticket",
        "CSRFPreventionToken: $csrfToken",
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
]);

$response = curl_exec($curl);
curl_close($curl);

$responseData = json_decode($response, true);
if ($response === FALSE || !$responseData || !isset($responseData['data'])) {
    error_log("❌ Błąd podczas tworzenia VM. Odpowiedź API: " . json_encode($responseData, JSON_PRETTY_PRINT));
    echo "✅ Dysk został stworzony dla VPS o UUID: " . $vmid . " Pomyślnie!";
    echo '<br><button onclick=window.location.href="../vm-create/vm-form.php">Stworz VPS</button>';
}
?>