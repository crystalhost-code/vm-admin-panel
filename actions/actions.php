<?php
include '../core/functions.php';

if (!isset($_GET['vmid']) || !isset($_GET['action'])) {
    die("Brak wymaganych parametrów!");
}

$vmid = intval($_GET['vmid']);
$action = $_GET['action'];

$auth = getAuthToken($proxmox_host, $proxmox_user, $proxmox_pass);
if (!$auth) {
    die("Błąd logowania do Proxmoxa.");
}

$allowed_actions = ['start', 'stop', 'reset'];

if (!in_array($action, $allowed_actions)) {
    die("Nieprawidłowa akcja!");
}

$url = "$proxmox_host/api2/json/nodes/$node/qemu/$vmid/status/$action";

$opts = ['http' => [
    'method' => 'POST',
    'header' => "Cookie: PVEAuthCookie={$auth['ticket']}\r\nCSRFPreventionToken: {$auth['CSRFPreventionToken']}\r\n"
]];

$context = stream_context_create($opts);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    die("Nie udało się wykonać akcji.");
}

echo "Akcja $action dla VM $vmid została wykonana.";
header("Location: ../dashboard/panel.php");
exit;
?>
