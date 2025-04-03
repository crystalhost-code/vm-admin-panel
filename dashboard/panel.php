<?php
include '../core/functions.php';

$auth = getAuthToken($proxmox_host, $proxmox_user, $proxmox_pass);
if (!$auth) {
    die("Błąd logowania do Proxmoxa.");
}

$vm_list = getVMList($proxmox_host, $auth['ticket'], $auth['CSRFPreventionToken'], $node);

usort($vm_list, function($a, $b) {
    return $a['vmid'] <=> $b['vmid'];
});

// Generowanie losowych danych do wykresów (demo)
$vm_stats = [];
foreach ($vm_list as $vm) {
    $vm_stats[$vm['vmid']] = [
        'cpu' => array_map(function() { return rand(5, 95); }, range(1, 24)),
        'ram' => array_map(function() { return rand(10, 90); }, range(1, 24)),
        'disk' => array_map(function() { return rand(20, 80); }, range(1, 24))
    ];
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quantum VM - Panel zarządzania</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Orbitron:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
:root {
    --quantum-primary: #6e0fff;
    --quantum-primary-dark: #4a00d4;
    --quantum-primary-light: #9d5cff;
    --quantum-secondary: #00f7ff;
    --quantum-accent: #ff2d7a;
    --quantum-dark: #0b071a;
    --quantum-darker: #070313;
    --quantum-light: #f0f0ff;
    --quantum-gray: #2a2a3c;
    --quantum-success: #00ff88;
    --quantum-warning: #ffcc00;
    --quantum-danger: #ff3860;
    --quantum-glass: rgba(15, 10, 35, 0.65);
    --quantum-header-height: 90px;
    --quantum-sidebar-width: 300px;
    --quantum-transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    --quantum-glow: 0 0 15px currentColor;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Space Grotesk', sans-serif;
    color: var(--quantum-light);
    background: var(--quantum-darker);
    overflow-x: hidden;
    min-height: 100vh;
    display: flex;
    line-height: 1.6;
}

/* Efekty tła */
.quantum-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -3;
    background: radial-gradient(ellipse at 75% 30%, var(--quantum-primary-dark) 0%, var(--quantum-darker) 70%);
    overflow: hidden;
}

.quantum-particle {
    position: absolute;
    border-radius: 50%;
    filter: blur(40px);
    opacity: 0.15;
    animation: quantum-float 15s infinite ease-in-out;
}

.particle-1 {
    width: 400px;
    height: 400px;
    background: var(--quantum-primary);
    top: 20%;
    left: 10%;
    animation-delay: 0s;
}

.particle-2 {
    width: 600px;
    height: 600px;
    background: var(--quantum-secondary);
    bottom: 15%;
    right: 10%;
    animation-delay: 2s;
    animation-duration: 20s;
}

.particle-3 {
    width: 300px;
    height: 300px;
    background: var(--quantum-accent);
    top: 50%;
    left: 50%;
    animation-delay: 4s;
    animation-duration: 12s;
}

@keyframes quantum-float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(10%, 15%) scale(1.1); }
    50% { transform: translate(5%, -10%) scale(0.9); }
    75% { transform: translate(-10%, 5%) scale(1.05); }
}

/* Panel boczny */
.quantum-sidebar {
    width: var(--quantum-sidebar-width);
    height: 100vh;
    background: var(--quantum-glass);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    padding: 30px 20px;
    position: fixed;
    z-index: 100;
    display: flex;
    flex-direction: column;
    transition: var(--quantum-transition);
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
}

.sidebar-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 40px;
    padding-left: 10px;
}

.sidebar-logo {
    font-family: 'Orbitron', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(90deg, var(--quantum-primary), var(--quantum-secondary));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    letter-spacing: 2px;
    text-shadow: var(--quantum-glow);
    position: relative;
}

.sidebar-logo::after {
    content: 'PRO';
    position: absolute;
    top: -10px;
    right: -25px;
    font-size: 0.7rem;
    background: var(--quantum-accent);
    color: var(--quantum-darker);
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 700;
}

.sidebar-menu {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    border-radius: 12px;
    color: var(--quantum-light);
    text-decoration: none;
    font-weight: 500;
    transition: var(--quantum-transition);
    position: relative;
    overflow: hidden;
}

.menu-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(110, 15, 255, 0.2), transparent);
    transition: var(--quantum-transition);
}

.menu-item:hover::before {
    left: 100%;
}

.menu-item i {
    font-size: 1.2rem;
    width: 24px;
    text-align: center;
    color: var(--quantum-primary-light);
    transition: var(--quantum-transition);
}

.menu-item span {
    position: relative;
    z-index: 1;
}

.menu-item:hover {
    background: rgba(110, 15, 255, 0.15);
    transform: translateX(5px);
}

.menu-item:hover i {
    color: var(--quantum-secondary);
    text-shadow: var(--quantum-glow);
}

.menu-item.active {
    background: linear-gradient(90deg, rgba(110, 15, 255, 0.3), transparent);
    box-shadow: inset 5px 0 0 var(--quantum-primary);
}

.menu-item.active i {
    color: var(--quantum-secondary);
}

.sidebar-footer {
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-radius: 12px;
    transition: var(--quantum-transition);
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.user-profile::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(110, 15, 255, 0.2), transparent);
    opacity: 0;
    transition: var(--quantum-transition);
}

.user-profile:hover::before {
    opacity: 1;
}

.user-profile:hover {
    background: rgba(255, 255, 255, 0.05);
}

.user-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--quantum-primary), var(--quantum-accent));
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: var(--quantum-dark);
    position: relative;
    z-index: 1;
    transition: var(--quantum-transition);
}

.user-profile:hover .user-avatar {
    transform: rotate(15deg);
    box-shadow: 0 0 20px var(--quantum-primary);
}

.user-info {
    flex-grow: 1;
    position: relative;
    z-index: 1;
}

.user-name {
    font-weight: 600;
    margin-bottom: 3px;
}

.user-role {
    font-size: 0.8rem;
    color: var(--quantum-primary-light);
    opacity: 0.8;
    display: flex;
    align-items: center;
    gap: 5px;
}

.user-role::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--quantum-success);
    display: inline-block;
}

.user-settings {
    color: var(--quantum-light);
    opacity: 0.6;
    transition: var(--quantum-transition);
    position: relative;
    z-index: 1;
}

.user-profile:hover .user-settings {
    opacity: 1;
    transform: rotate(90deg);
    color: var(--quantum-secondary);
}

/* Główna zawartość */
.quantum-content {
    flex-grow: 1;
    margin-left: var(--quantum-sidebar-width);
    padding: 40px;
    transition: var(--quantum-transition);
    position: relative;
}

/* Nagłówek */
.quantum-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    position: relative;
}

.page-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--quantum-light);
    letter-spacing: 1px;
    position: relative;
    display: inline-block;
}

.page-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 0;
    width: 60%;
    height: 3px;
    background: linear-gradient(90deg, var(--quantum-primary), transparent);
    border-radius: 3px;
}

.page-title span {
    background: linear-gradient(90deg, var(--quantum-primary), var(--quantum-secondary));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.header-actions {
    display: flex;
    gap: 20px;
    align-items: center;
}

.search-bar {
    position: relative;
    width: 250px;
}

.search-bar input {
    width: 100%;
    padding: 12px 20px 12px 45px;
    border-radius: 30px;
    border: none;
    background: rgba(255, 255, 255, 0.08);
    color: var(--quantum-light);
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.9rem;
    transition: var(--quantum-transition);
    border: 1px solid transparent;
}

.search-bar input:focus {
    outline: none;
    border-color: var(--quantum-primary);
    background: rgba(15, 10, 35, 0.7);
    box-shadow: 0 0 0 3px rgba(110, 15, 255, 0.2);
}

.search-bar i {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--quantum-primary-light);
}

.action-btn {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--quantum-light);
    cursor: pointer;
    transition: var(--quantum-transition);
    position: relative;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.action-btn:hover {
    background: rgba(110, 15, 255, 0.3);
    transform: translateY(-3px);
    color: var(--quantum-secondary);
    border-color: var(--quantum-primary);
}

.notification-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--quantum-accent);
    border: 2px solid var(--quantum-dark);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.2); opacity: 0.8; }
    100% { transform: scale(1); opacity: 1; }
}

/* Karty statystyk */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
    margin-bottom: 30px;
}

.stats-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 25px;
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.03);
    transition: var(--quantum-transition);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.03);
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(110, 15, 255, 0.05), transparent);
    z-index: -1;
}

.stats-card:hover {
    background: rgba(110, 15, 255, 0.1);
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(110, 15, 255, 0.2);
    border-color: rgba(110, 15, 255, 0.2);
}

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
    transition: var(--quantum-transition);
}

.stats-card:hover .stats-icon {
    transform: scale(1.1) rotate(10deg);
    box-shadow: 0 0 20px currentColor;
}

.stats-icon.cpu {
    background: linear-gradient(135deg, var(--quantum-primary), #8a2be2);
}

.stats-icon.ram {
    background: linear-gradient(135deg, var(--quantum-secondary), #00b4d8);
}

.stats-icon.disk {
    background: linear-gradient(135deg, var(--quantum-accent), #ff758c);
}

.stats-info {
    flex-grow: 1;
}

.stats-value {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 5px;
    font-family: 'Orbitron', sans-serif;
    background: linear-gradient(90deg, var(--quantum-light), var(--quantum-primary-light));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    letter-spacing: 1px;
}

.stats-label {
    font-size: 0.9rem;
    color: var(--quantum-primary-light);
    opacity: 0.8;
}

/* Ulepszone kafelki VM */
.vm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
}

.vm-card {
    position: relative;
    overflow: hidden;
    z-index: 1;
    border-radius: 16px;
    background: rgba(20, 15, 45, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(110, 15, 255, 0.2);
    transition: var(--quantum-transition);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.vm-card:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 12px 40px rgba(110, 15, 255, 0.3);
    border-color: var(--quantum-primary);
}

.vm-card-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
}

.vm-status {
    position: absolute;
    top: 20px;
    right: 20px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.status-running {
    background: rgba(0, 255, 136, 0.15);
    color: var(--quantum-success);
}

.status-stopped {
    background: rgba(255, 56, 96, 0.15);
    color: var(--quantum-danger);
}

.vm-id {
    font-family: 'Orbitron', sans-serif;
    font-size: 0.9rem;
    color: var(--quantum-primary-light);
    margin-bottom: 5px;
}

.vm-name {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--quantum-light);
    margin-bottom: 15px;
}

.vm-specs {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    padding: 0 20px 20px;
}

.spec-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.spec-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(110, 15, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--quantum-primary-light);
    font-size: 1.1rem;
}

.spec-info {
    flex: 1;
}

.spec-label {
    font-size: 0.8rem;
    color: var(--quantum-primary-light);
    opacity: 0.8;
    margin-bottom: 3px;
}

.spec-value {
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--quantum-light);
}

/* Wskaźnik zużycia CPU */
.cpu-usage {
    margin: 15px 20px 0;
    padding: 15px;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
}

.usage-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 0.9rem;
    color: var(--quantum-primary-light);
}

.usage-bar {
    height: 8px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    overflow: hidden;
    position: relative;
}

.usage-progress {
    height: 100%;
    border-radius: 4px;
    background: linear-gradient(90deg, var(--quantum-primary), var(--quantum-accent));
    position: relative;
    transition: width 1s ease;
}

.usage-progress::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, 
        transparent, 
        rgba(255, 255, 255, 0.3), 
        transparent);
    animation: shine 2s infinite;
}

@keyframes shine {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Przyciski akcji */
.vm-actions {
    display: flex;
    gap: 10px;
    padding: 0 20px 20px;
}

.vm-btn {
    flex: 1;
    padding: 10px;
    border-radius: 8px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: var(--quantum-transition);
    border: none;
    color: white;
    font-size: 0.85rem;
}

.vm-btn i {
    font-size: 0.9rem;
}

.vm-btn-start {
    background: linear-gradient(135deg, var(--quantum-success), #00cc7a);
}

.vm-btn-stop {
    background: linear-gradient(135deg, var(--quantum-danger), #ff1a4a);
}

.vm-btn-restart {
    background: linear-gradient(135deg, var(--quantum-warning), #ffb400);
}

.vm-btn-console {
    background: linear-gradient(135deg, var(--quantum-primary), var(--quantum-primary-dark));
}

.vm-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

/* Konsola VM */
.console-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    z-index: 1000;
    display: flex;
    justify-content: center;
    align-items: center;
    opacity: 0;
    visibility: hidden;
    transition: var(--quantum-transition);
}

.console-overlay.active {
    opacity: 1;
    visibility: visible;
}

.console-container {
    width: 90%;
    max-width: 1200px;
    height: 80vh;
    background: var(--quantum-dark);
    border-radius: 16px;
    border: 1px solid var(--quantum-primary);
    box-shadow: 0 0 40px rgba(110, 15, 255, 0.3);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform: scale(0.9);
    transition: var(--quantum-transition);
}

.console-overlay.active .console-container {
    transform: scale(1);
}

.console-header {
    padding: 15px 20px;
    background: linear-gradient(90deg, var(--quantum-primary-dark), var(--quantum-dark));
    border-bottom: 1px solid rgba(110, 15, 255, 0.3);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.console-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 1.2rem;
    color: var(--quantum-secondary);
    display: flex;
    align-items: center;
    gap: 10px;
}

.console-title i {
    color: var(--quantum-primary-light);
}

.console-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--quantum-transition);
    color: var(--quantum-light);
}

.console-close:hover {
    background: var(--quantum-danger);
    color: white;
    transform: rotate(90deg);
}

.console-body {
    flex-grow: 1;
    padding: 20px;
    background: #0a0815;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    color: #e0e0e0;
    position: relative;
}

.console-output {
    white-space: pre-wrap;
    word-break: break-all;
    line-height: 1.5;
    margin-bottom: 20px;
    min-height: calc(100% - 60px);
}

.console-input-container {
    position: sticky;
    bottom: 0;
    background: #0a0815;
    padding: 15px 0;
    border-top: 1px solid rgba(110, 15, 255, 0.2);
    display: flex;
    gap: 10px;
}

.console-prompt {
    color: var(--quantum-secondary);
    font-weight: bold;
    margin-right: 10px;
    white-space: nowrap;
}

.console-input {
    flex-grow: 1;
    background: transparent;
    border: none;
    color: white;
    font-family: 'Courier New', monospace;
    font-size: 1rem;
    outline: none;
    caret-color: var(--quantum-secondary);
}

.console-input:focus {
    outline: none;
}

.console-actions {
    display: flex;
    gap: 10px;
    padding: 0 20px 20px;
    background: rgba(20, 15, 45, 0.7);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.console-btn {
    padding: 10px 15px;
    border-radius: 8px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: var(--quantum-transition);
    border: none;
    color: white;
    font-size: 0.85rem;
    flex: 1;
}

.console-btn i {
    font-size: 0.9rem;
}

.console-btn-start {
    background: linear-gradient(135deg, var(--quantum-success), #00cc7a);
}

.console-btn-stop {
    background: linear-gradient(135deg, var(--quantum-danger), #ff1a4a);
}

.console-btn-restart {
    background: linear-gradient(135deg, var(--quantum-warning), #ffb400);
}

.console-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

/* Responsywność */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    :root {
        --quantum-sidebar-width: 90px;
    }
    
    .sidebar-logo span, .menu-item span, .user-info, .user-settings {
        display: none;
    }
    
    .sidebar-header {
        justify-content: center;
        padding-left: 0;
    }
    
    .menu-item {
        justify-content: center;
        padding: 15px;
    }
    
    .menu-item i {
        font-size: 1.4rem;
    }
    
    .user-avatar {
        width: 50px;
        height: 50px;
    }
    
    .search-bar {
        width: 200px;
    }
    
    .console-container {
        width: 95%;
        height: 85vh;
    }
}

@media (max-width: 992px) {
    .quantum-content {
        padding: 30px;
    }
    
    .vm-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .quantum-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .header-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .search-bar {
        width: 100%;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .console-actions {
        flex-wrap: wrap;
    }
    
    .console-btn {
        min-width: calc(50% - 5px);
    }
}

@media (max-width: 576px) {
    .quantum-content {
        padding: 20px 15px;
    }
    
    .vm-actions {
        flex-wrap: wrap;
    }
    
    .vm-btn {
        min-width: calc(50% - 6px);
    }
    
    .stats-card {
        flex-direction: column;
        text-align: center;
    }
    
    .spec-item {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }
    
    .console-header {
        padding: 10px 15px;
    }
    
    .console-title {
        font-size: 1rem;
    }
    
    .console-close {
        width: 30px;
        height: 30px;
    }
}
    </style>
</head>
<body>
    <!-- Efekty tła -->
    <div class="quantum-bg">
        <div class="quantum-particle particle-1"></div>
        <div class="quantum-particle particle-2"></div>
        <div class="quantum-particle particle-3"></div>
    </div>

    <!-- Panel boczny -->
    <aside class="quantum-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">Crystal<span>Host</span></div>
        </div>
        
        <nav class="sidebar-menu">
            <a href="#" class="menu-item active">
                <i class="fas fa-server"></i>
                <span>Maszyny Wirtualne</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-network-wired"></i>
                <span>Sieć</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-hdd"></i>
                <span>Dyski</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-shield-alt"></i>
                <span>Bezpieczeństwo</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-chart-pie"></i>
                <span>Statystyki</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-cogs"></i>
                <span>Ustawienia</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">AD</div>
                <div class="user-info">
                    <div class="user-name">Administrator</div>
                    <div class="user-role">Super Admin</div>
                </div>
                <i class="fas fa-cog user-settings"></i>
            </div>
        </div>
    </aside>

    <!-- Główna zawartość -->
    <main class="quantum-content">
        <div class="quantum-header">
            <h1 class="page-title">Panel <span>Zarządzania</span></h1>
            
            <div class="header-actions">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Szukaj maszyn...">
                </div>
                <div class="action-btn">
                    <i class="fas fa-bell"></i>
                    <div class="notification-badge"></div>
                </div>
                <div class="action-btn">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
        </div>
        
        <!-- Statystyki systemowe -->
        <div class="stats-grid">
            <div class="stats-card">
                <div class="stats-icon cpu">
                    <i class="fas fa-microchip"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-value">24.7%</div>
                    <div class="stats-label">Użycie CPU</div>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon ram">
                    <i class="fas fa-memory"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-value">8.4/16GB</div>
                    <div class="stats-label">Użycie RAM</div>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon disk">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-value">1.8TB</div>
                    <div class="stats-label">Wolne miejsce</div>
                </div>
            </div>
        </div>
        
        <!-- Lista maszyn wirtualnych -->
        <div class="vm-grid">
            <?php foreach ($vm_list as $vm): ?>
            <div class="quantum-card vm-card">
                <div class="vm-card-header">
                    <div class="vm-id">VM#<?= htmlspecialchars($vm['vmid']) ?></div>
                    <h3 class="vm-name"><?= htmlspecialchars($vm['name'] ?? 'Serwer #'.$vm['vmid']) ?></h3>
                    <div class="vm-status <?= $vm['status'] === 'running' ? 'status-running' : 'status-stopped' ?>">
                        <i class="fas fa-circle"></i>
                        <?= $vm['status'] === 'running' ? 'Działa' : 'Zatrzymana' ?>
                    </div>
                </div>
                
                <div class="vm-specs">
                    <div class="spec-item">
                        <div class="spec-icon">
                            <i class="fas fa-microchip"></i>
                        </div>
                        <div class="spec-info">
                            <span class="spec-label">Procesor</span>
                            <span class="spec-value"><?= htmlspecialchars($vm['maxcpu']) ?> rdzeni</span>
                        </div>
                    </div>
                    
                    <div class="spec-item">
                        <div class="spec-icon">
                            <i class="fas fa-memory"></i>
                        </div>
                        <div class="spec-info">
                            <span class="spec-label">RAM</span>
                            <span class="spec-value"><?= round($vm['maxmem'] / 1024 / 1024 / 1024, 1) ?> GB</span>
                        </div>
                    </div>
                    
                    <div class="spec-item">
                        <div class="spec-icon">
                            <i class="fas fa-hdd"></i>
                        </div>
                        <div class="spec-info">
                            <span class="spec-label">Dysk</span>
                            <span class="spec-value"><?= round($vm['maxdisk'] / 1024 / 1024 / 1024, 1) ?> GB</span>
                        </div>
                    </div>
                    
                    <div class="spec-item">
                        <div class="spec-icon">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <div class="spec-info">
                            <span class="spec-label">Sieć</span>
                            <span class="spec-value">10 Gbps</span>
                        </div>
                    </div>
                </div>
                
                <!-- Wskaźnik zużycia CPU -->
                <div class="cpu-usage">
                    <div class="usage-header">
                        <span>Zużycie CPU</span>
                        <span><?= $vm['cpu_usage'] ?>%</span>
                    </div>
                    <div class="usage-bar">
                        <div class="usage-progress" style="width: <?= $vm['cpu_usage'] ?>%"></div>
                    </div>
                </div>
                
                <div class="vm-actions">
                    <a href="../actions/actions.php?vmid=<?= $vm['vmid'] ?>&action=start" class="vm-btn vm-btn-start">
                        <i class="fas fa-play"></i> Start
                    </a>
                    <a href="../actions/actions.php?vmid=<?= $vm['vmid'] ?>&action=stop" class="vm-btn vm-btn-stop">
                        <i class="fas fa-stop"></i> Stop
                    </a>
                    <a href="../actions/actions.php?vmid=<?= $vm['vmid'] ?>&action=reset" class="vm-btn vm-btn-restart">
                        <i class="fas fa-sync-alt"></i> Restart
                    </a>
                    <a href="#" class="vm-btn vm-btn-console" data-vmid="<?= $vm['vmid'] ?>" data-vmname="<?= htmlspecialchars($vm['name'] ?? 'Serwer #'.$vm['vmid']) ?>">
                        <i class="fas fa-terminal"></i> Konsola
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Konsola VM -->
    <div class="console-overlay" id="consoleOverlay">
        <div class="console-container">
            <div class="console-header">
                <div class="console-title">
                    <i class="fas fa-terminal"></i>
                    <span id="consoleTitle">Konsola VM</span>
                </div>
                <div class="console-close" id="consoleClose">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="console-body">
                <div class="console-output" id="consoleOutput">
                    Witaj w konsoli maszyny wirtualnej.<br>
                    Połączono z serwerem SSH.<br>
                    Oczekiwanie na polecenia...<br><br>
                    root@vm:~# 
                </div>
                <div class="console-input-container">
                    <span class="console-prompt">root@vm:~#</span>
                    <input type="text" class="console-input" id="consoleInput" autocomplete="off" spellcheck="false">
                </div>
            </div>
            <div class="console-actions">
                <button class="console-btn console-btn-start">
                    <i class="fas fa-play"></i> Start
                </button>
                <button class="console-btn console-btn-stop">
                    <i class="fas fa-stop"></i> Stop
                </button>
                <button class="console-btn console-btn-restart">
                    <i class="fas fa-sync-alt"></i> Restart
                </button>
            </div>
        </div>
    </div>

    <script>
        // Animacja kart przy ładowaniu
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.vm-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
            
            // Inicjalna pozycja kart
            document.querySelectorAll('.vm-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
            });

            // Obsługa konsoli
            const consoleBtns = document.querySelectorAll('.vm-btn-console');
            const consoleOverlay = document.getElementById('consoleOverlay');
            const consoleClose = document.getElementById('consoleClose');
            const consoleTitle = document.getElementById('consoleTitle');
            const consoleOutput = document.getElementById('consoleOutput');
            const consoleInput = document.getElementById('consoleInput');

            // Otwieranie konsoli
            consoleBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const vmid = this.getAttribute('data-vmid');
                    const vmname = this.getAttribute('data-vmname');
                    
                    consoleTitle.textContent = `Konsola VM#${vmid} - ${vmname}`;
                    consoleOverlay.classList.add('active');
                    consoleInput.focus();
                });
            });

            // Zamykanie konsoli
            consoleClose.addEventListener('click', function() {
                consoleOverlay.classList.remove('active');
            });

            // Obsługa wprowadzania komend
            consoleInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    const command = this.value.trim();
                    if (command) {
                        // Symulacja odpowiedzi serwera
                        const response = `root@vm:~# ${command}\n` + 
                                        `Odpowiedź na polecenie "${command}"\n\n` +
                                        `root@vm:~# `;
                        
                        consoleOutput.innerHTML += response;
                        this.value = '';
                        
                        // Przewiń do dołu
                        consoleOutput.scrollTop = consoleOutput.scrollHeight;
                    }
                }
            });

            // Obsługa przycisków akcji w konsoli
            const actionBtns = document.querySelectorAll('.console-btn');
            actionBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.classList.contains('console-btn-start') ? 'start' : 
                                  this.classList.contains('console-btn-stop') ? 'stop' : 'restart';
                    
                    const actionText = action === 'start' ? 'uruchamianie' : 
                                      action === 'stop' ? 'zatrzymywanie' : 'restartowanie';
                    
                    const response = `root@vm:~# ${action}\n` + 
                                    `Trwa ${actionText} maszyny wirtualnej...\n` +
                                    `Operacja zakończona pomyślnie.\n\n` +
                                    `root@vm:~# `;
                    
                    consoleOutput.innerHTML += response;
                    consoleOutput.scrollTop = consoleOutput.scrollHeight;
                });
            });
        });
    </script>
</body>
</html>