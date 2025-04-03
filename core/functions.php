<?php
$proxmox_host = "https://proxmox-adress-with-ssl:8006";
$proxmox_user = "user@pam/pve";
$proxmox_pass = "Password";
$node = "Node Name";

function getAuthToken($host, $user, $pass) {
    $url = "$host/api2/json/access/ticket";
    $data = http_build_query(["username" => $user, "password" => $pass]);

    $opts = ['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $data
    ]];

    $context = stream_context_create($opts);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) return false;

    $json = json_decode($result, true);
    return $json['data'] ?? false;
}

function getVMList($host, $ticket, $csrf, $node) {
    $opts = ['http' => [
        'method' => 'GET',
        'header' => "Cookie: PVEAuthCookie=$ticket\r\nCSRFPreventionToken: $csrf\r\n"
    ]];

    $context = stream_context_create($opts);
    $url = "$host/api2/json/nodes/$node/qemu";
    $result = file_get_contents($url, false, $context);

    return $result ? json_decode($result, true)['data'] : [];
}

function getISOList($host, $ticket, $csrf, $node) {
    $opts = ['http' => [
        'method' => 'GET',
        'header' => "Cookie: PVEAuthCookie=$ticket\r\nCSRFPreventionToken: $csrf\r\n"
    ]];

    $context = stream_context_create($opts);
    $url = "$host/api2/json/nodes/$node/storage/local/content";

    $result = file_get_contents($url, false, $context);
    if (!$result) return [];

    $data = json_decode($result, true);
    if (!isset($data['data'])) return [];

    $iso_files = [];
    foreach ($data['data'] as $file) {
        if ($file['content'] === 'iso') {
            $iso_files[] = $file['volid']; 
        }
    }

    return $iso_files;
}

?>
