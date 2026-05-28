<?php
require_once '../config/functions.php';
requireAdmin();
require_once '../includes/header_admin.php';

$db = Database::getInstance()->getConnection();

// Estatísticas
$stats = [];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE nivel_acesso = 'funcionario'");
$stats['funcionarios'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM ponto_registro WHERE data_registro = CURDATE()");
$stats['ponto_hoje'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM folha_pagamento WHERE mes_referencia = MONTH(CURDATE()) AND ano_referencia = YEAR(CURDATE())");
$stats['folhas_mes'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM chamados WHERE status = 'aberto'");
$stats['chamados_abertos'] = $stmt->fetch()['total'];

// Últimos registros de ponto
$stmt = $db->query("
    SELECT pr.*, u.nome_completo, u.obfuscated_id 
    FROM ponto_registro pr 
    JOIN users u ON pr.user_id = u.id 
    ORDER BY pr.criado_em DESC LIMIT 8
");
$ultimos_pontos = $stmt->fetchAll();

// Chamados recentes
$stmt = $db->query("
    SELECT ch.*, u.nome_completo 
    FROM chamados ch 
    JOIN users u ON ch.user_id = u.id 
    ORDER BY ch.criado_em DESC LIMIT 5
");
$chamados = $stmt->fetchAll();
?>

<div class="page-header fade-in">
    <h1>Dashboard Administrativo</h1>
    <p>Visão geral do sistema de gestão de recursos humanos</p>
    <div style="margin-top: 10px; font-size: 13px; color: var(--text-muted);">
        Hoje: <?= date('d/m/Y') ?> | <span id="realtime-clock"></span>
    </div>
</div>

<div class="stats-grid fade-in">
    <div class="stat-card">
        <div class="stat-icon">&#9813;</div>
        <div class="stat-content">
            <h4><?= $stats['funcionarios'] ?></h4>
            <p>Funcionários Ativos</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">&#9200;</div>
        <div class="stat-content">
            <h4><?= $stats['ponto_hoje'] ?></h4>
            <p>Pontos Registrados Hoje</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">&#128176;</div>
        <div class="stat-content">
            <h4><?= $stats['folhas_mes'] ?></h4>
            <p>Folhas do Mês</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">&#9888;</div>
        <div class="stat-content">
            <h4><?= $stats['chamados_abertos'] ?></h4>
            <p>Chamados Abertos</p>
        </div>
    </div>
</div>

<div class="grid-2 fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Últimos Registros de Ponto</h3>
            <a href="ponto_global.php" class="btn btn-sm btn-secondary">Ver Todos</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Funcionário</th>
                            <th>Data</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_pontos as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nome_completo']) ?></td>
                            <td><?= formatDate($p['data_registro']) ?></td>
                            <td><?= getStatusBadge($p['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Chamados Recentes</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Solicitante</th>
                            <th>Assunto</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chamados as $ch): ?>
                        <tr>
                            <td><?= htmlspecialchars($ch['nome_completo']) ?></td>
                            <td><?= htmlspecialchars(substr($ch['assunto'], 0, 35)) ?>...</td>
                            <td><?= getStatusBadge($ch['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>