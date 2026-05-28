<?php
require_once __DIR__ . '/../config/functions.php';
requireEmployee();

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão RH - Meu Painel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Sistema RH</h2>
            <p>Área do Colaborador</p>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Meu Espaço</div>
                <a href="dashboard.php" class="nav-item <?=isActive('dashboard')?>">
                    <span class="nav-icon">&#9635;</span> Meu Painel
                </a>
                <a href="meu_ponto.php" class="nav-item <?=isActive('meu_ponto')?>">
                    <span class="nav-icon">&#9200;</span> Meu Ponto
                </a>
                <a href="minha_folha.php" class="nav-item <?=isActive('minha_folha')?>">
                    <span class="nav-icon">&#128176;</span> Minha Folha
                </a>
                <a href="servicos.php" class="nav-item <?=isActive('servicos')?>">
                    <span class="nav-icon">&#9881;</span> Meus Serviços
                </a>
            </div>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar"><?=strtoupper(substr($currentUser['nome_completo'], 0, 2))?></div>
                <div class="user-info">
                    <h4><?=htmlspecialchars($currentUser['nome_completo'])?></h4>
                    <p>ID: <?=$currentUser['obfuscated_id']?></p>
                </div>
            </div>
            <button class="btn-logout" onclick="if(confirm('Deseja sair?')) window.location='../logout.php'">
                Encerrar Sessão
            </button>
        </div>
    </aside>
    <main class="main-content">