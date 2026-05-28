<?php
require_once __DIR__ . '/../config/functions.php';
requireAdmin();

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
    <title>Gestão RH - Painel Administrativo</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Sistema RH</h2>
            <p>Painel Administrativo</p>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Principal</div>
                <a href="dashboard.php" class="nav-item <?=isActive('dashboard')?>">
                    <span class="nav-icon">&#9635;</span> Dashboard
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">Gestão de Pessoal</div>
                <a href="funcionarios.php" class="nav-item <?=isActive('funcionarios')?>">
                    <span class="nav-icon">&#9813;</span> Funcionários
                </a>
                <a href="ponto_global.php" class="nav-item <?=isActive('ponto_global')?>">
                    <span class="nav-icon">&#9200;</span> Gestão de Ponto
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">Financeiro</div>
                <a href="folha_pagamento.php" class="nav-item <?=isActive('folha_pagamento')?>">
                    <span class="nav-icon">&#128176;</span> Folha de Pagamento
                </a>
                <a href="relatorios.php" class="nav-item <?=isActive('relatorios')?>">
                    <span class="nav-icon">&#128202;</span> Relatórios
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">Operacional</div>
                <a href="servicos.php" class="nav-item <?=isActive('servicos')?>">
                    <span class="nav-icon">&#9881;</span> Serviços
                </a>
            </div>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar"><?=strtoupper(substr($currentUser['nome_completo'], 0, 2))?></div>
                <div class="user-info">
                    <h4><?=htmlspecialchars($currentUser['nome_completo'])?></h4>
                    <p><?=ucfirst($currentUser['nivel_acesso'])?></p>
                </div>
            </div>
            <button class="btn-logout" onclick="if(confirm('Deseja sair?')) window.location='../logout.php'">
                Encerrar Sessão
            </button>
        </div>
    </aside>
    <main class="main-content">