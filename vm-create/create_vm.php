<?php
include 'api/api-proxmox-vm.php';

$vmid = intval($_POST["vmid"]);
$ram = intval($_POST["ram"]) * 1024;
$disk = intval($_POST["disk"]);
$cpu = intval($_POST["cpu"]);
$iso = $_POST["iso"];
$mac = $_POST["mac"];

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
    die("❌ Błąd logowania do Proxmoxa.");
}
$ticket = $authData['data']['ticket'];
$csrfToken = $authData['data']['CSRFPreventionToken'];

$net0 = "virtio,bridge=vmbr0";

$diskPath = "local:" . $vmid . "/vm-" . $vmid . "-disk-0.qcow2";

$data = [
    "vmid" => $vmid,
    "name" => "vm-" . $vmid,
    "memory" => $ram,
    "sockets" => 1,
    "cores" => $cpu,
    "net0" => $net0,
    "ide2" => "local:iso/" . basename($iso) . ",media=cdrom",
    "boot" => "order=scsi0;ide2",
    "scsihw" => "virtio-scsi-pci",
    "scsi0" => "$diskPath,format=qcow2,size=" . $disk . "G",
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

if ($response === FALSE) {
    die("❌ Błąd podczas tworzenia VM.");
}

$responseData = json_decode($response, true);
if (!$responseData || !isset($responseData["data"])) {
    echo "❌ Błąd API: " . json_encode($responseData, JSON_PRETTY_PRINT);
    exit;
}

echo "✅ VPS o UUID: " . $vmid . " Został pomyślnie stworzony!!";
echo '<br><button onclick=window.location.href="../dashboard/panel.php">Przejdz do panelu</button>';
?>
