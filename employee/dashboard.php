<?php
require_once '../config/functions.php';
requireEmployee();
require_once '../includes/header_employee.php';

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Estatísticas do funcionário
$stmt = $db->prepare("SELECT COUNT(*) FROM ponto_registro WHERE user_id = ? AND data_registro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$stmt->execute([$userId]);
$pontos_mes = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM ponto_registro WHERE user_id = ? AND status = 'trabalhado' AND data_registro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$stmt->execute([$userId]);
$dias_trabalhados = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT valor_liquido FROM folha_pagamento WHERE user_id = ? ORDER BY ano_referencia DESC, mes_referencia DESC LIMIT 1");
$stmt->execute([$userId]);
$ultimo_salario = $stmt->fetchColumn() ?: 0;

$stmt = $db->prepare("SELECT COUNT(*) FROM servicos WHERE user_id = ? AND status = 'pendente'");
$stmt->execute([$userId]);
$tarefas_pendentes = $stmt->fetchColumn();

// Últimos serviços
$stmt = $db->prepare("SELECT * FROM servicos WHERE user_id = ? ORDER BY data_criacao DESC LIMIT 5");
$stmt->execute([$userId]);
$servicos = $stmt->fetchAll();

// Últimos chamados
$stmt = $db->prepare("SELECT * FROM chamados WHERE user_id = ? ORDER BY criado_em DESC LIMIT 3");
$stmt->execute([$userId]);
$chamados = $stmt->fetchAll();
?>

<div class="page-header fade-in">
    <h1>Bem-vindo, <?= htmlspecialchars(explode(' ', $currentUser['nome_completo'])[0]) ?></h1>
    <p>ID: <?= $currentUser['obfuscated_id'] ?> | Cargo: <?= htmlspecialchars($currentUser['cargo']) ?></p>
    <div style="margin-top: 10px; font-size: 13px; color: var(--text-muted);">
        Hoje: <?= date('d/m/Y') ?> | <span id="realtime-clock"></span>
    </div>
</div>

<div class="stats-grid fade-in">
    <div class="stat-card">
        <div class="stat-icon">&#9200;</div>
        <div class="stat-content">
            <h4><?= $pontos_mes ?></h4>
            <p>Registros (30 dias)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">&#128197;</div>
        <div class="stat-content">
            <h4><?= $dias_trabalhados ?></h4>
            <p>Dias Trabalhados</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">&#128176;</div>
        <div class="stat-content">
            <h4><?= formatCurrency($ultimo_salario) ?></h4>
            <p>Último Salário Líquido</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">&#9881;</div>
        <div class="stat-content">
            <h4><?= $tarefas_pendentes ?></h4>
            <p>Tarefas Pendentes</p>
        </div>
    </div>
</div>

<div class="grid-2 fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Meus Serviços Recentes</h3>
            <a href="servicos.php" class="btn btn-sm btn-secondary">Ver Todos</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($servicos)): ?>
                <div style="padding: 30px; text-align: center; color: var(--text-muted);">Nenhum serviço atribuído.</div>
            <?php else: ?>
                <?php foreach ($servicos as $s): ?>
                <div style="padding: 16px 20px; border-bottom: 1px solid var(--beige-light);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong><?= htmlspecialchars($s['titulo']) ?></strong>
                        <?= getStatusBadge($s['status']) ?>
                    </div>
                    <?php if ($s['data_limite']): ?>
                        <small style="color: var(--text-muted);">Limite: <?= formatDate($s['data_limite']) ?></small>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Meus Chamados</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($chamados)): ?>
                <div style="padding: 30px; text-align: center; color: var(--text-muted);">Nenhum chamado aberto.</div>
            <?php else: ?>
                <?php foreach ($chamados as $ch): ?>
                <div style="padding: 16px 20px; border-bottom: 1px solid var(--beige-light);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span><?= htmlspecialchars($ch['assunto']) ?></span>
                        <?= getStatusBadge($ch['status']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>