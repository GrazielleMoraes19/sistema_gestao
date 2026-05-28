<?php
require_once '../config/functions.php';
requireEmployee();
require_once '../includes/header_employee.php';

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$success = $_GET['success'] ?? '';

// Reportar erro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reportar_erro'])) {
    $stmt = $db->prepare("
        INSERT INTO chamados (user_id, tipo, assunto, descricao)
        VALUES (?, 'erro_folha', ?, ?)
    ");
    $stmt->execute([$userId, $_POST['assunto'], $_POST['descricao']]);
    $success = 'Erro reportado ao RH com sucesso!';
}

$mes = $_GET['mes'] ?? date('m');
$ano = $_GET['ano'] ?? date('Y');

// Folha atual
$stmt = $db->prepare("
    SELECT * FROM folha_pagamento WHERE user_id = ? AND mes_referencia = ? AND ano_referencia = ?
");
$stmt->execute([$userId, $mes, $ano]);
$folha = $stmt->fetch();

$descontos = [];
$beneficios = [];
if ($folha) {
    $stmt = $db->prepare("SELECT * FROM descontos WHERE folha_id = ?");
    $stmt->execute([$folha['id']]);
    $descontos = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT * FROM beneficios WHERE folha_id = ?");
    $stmt->execute([$folha['id']]);
    $beneficios = $stmt->fetchAll();
}

// Histórico
$stmt = $db->prepare("
    SELECT * FROM folha_pagamento WHERE user_id = ? ORDER BY ano_referencia DESC, mes_referencia DESC LIMIT 12
");
$stmt->execute([$userId]);
$historico = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Minha Folha de Pagamento</h1>
    <p>Consulta de holerite e histórico de pagamentos</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="filters-bar">
    <div class="filter-group">
        <label>Mês Referência</label>
        <select class="form-control" id="filterMes">
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?= $i ?>" <?= $i == $mes ? 'selected' : '' ?>><?= getMonthName($i) ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="filter-group">
        <label>Ano</label>
        <select class="form-control" id="filterAno">
            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $y == $ano ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <button class="btn btn-primary btn-sm" onclick="window.location='minha_folha.php?mes='+document.getElementById('filterMes').value+'&ano='+document.getElementById('filterAno').value">Consultar</button>
    <?php if ($folha): ?>
    <button class="btn btn-secondary btn-sm" onclick="exportPDF('../api/pdf_generate.php', {tipo: 'holerite', folha_id: <?= $folha['id'] ?>})">Exportar PDF</button>
    <?php endif; ?>
</div>

<?php if ($folha): ?>
<div class="holerite fade-in" id="holerite-print">
    <div class="holerite-header">
        <h2>Contracheque - <?= getMonthName($mes) ?>/<?= $ano ?></h2>
        <p>Sistema de Gestão RH</p>
    </div>
    
    <div class="holerite-info">
        <div><strong>Funcionário:</strong> <?= htmlspecialchars($currentUser['nome_completo']) ?></div>
        <div><strong>ID:</strong> <?= $currentUser['obfuscated_id'] ?></div>
        <div><strong>Cargo:</strong> <?= htmlspecialchars($currentUser['cargo']) ?></div>
        <div><strong>CPF:</strong> <?= $currentUser['cpf'] ?></div>
        <div><strong>Período:</strong> <?= getMonthName($mes) ?>/<?= $ano ?></div>
        <div><strong>Emissão:</strong> <?= date('d/m/Y') ?></div>
    </div>

    <table class="holerite-table">
        <thead>
            <tr>
                <th colspan="2">Proventos</th>
                <th colspan="2">Descontos</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Salário Base</td>
                <td style="text-align: right;"><?= formatCurrency($folha['valor_bruto']) ?></td>
                <td colspan="2"></td>
            </tr>
            <?php foreach ($beneficios as $b): ?>
            <tr>
                <td><?= htmlspecialchars($b['descricao']) ?></td>
                <td style="text-align: right; color: #4a6a42;">+ <?= formatCurrency($b['valor']) ?></td>
                <td colspan="2"></td>
            </tr>
            <?php endforeach; ?>
            <?php foreach ($descontos as $d): ?>
            <tr>
                <td></td>
                <td></td>
                <td><?= htmlspecialchars($d['descricao']) ?></td>
                <td style="text-align: right; color: #8a4438;">- <?= formatCurrency($d['valor']) ?></td>
            </tr>
            <?php if ($d['justificativa']): ?>
            <tr>
                <td colspan="2"></td>
                <td colspan="2" style="font-size: 12px; color: var(--text-muted); font-style: italic;"><?= htmlspecialchars($d['justificativa']) ?></td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="holerite-total">
        <span>Valor Líquido a Receber</span>
        <span><?= formatCurrency($folha['valor_liquido']) ?></span>
    </div>

    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color); font-size: 12px; color: var(--text-muted); text-align: center;">
        Este documento é um comprovante de pagamento gerado pelo Sistema de Gestão RH.<br>
        Emitido em <?= date('d/m/Y H:i:s') ?>
    </div>
</div>

<div class="card fade-in" style="margin-top: 25px;">
    <div class="card-header">
        <h3>Reportar Erro no Pagamento</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group">
                <label>Assunto</label>
                <input type="text" name="assunto" class="form-control" required placeholder="Ex: Valor incorreto, Desconto indevido...">
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" class="form-control" rows="4" required placeholder="Descreva o problema encontrado..."></textarea>
            </div>
            <button type="submit" class="btn btn-danger btn-sm">Reportar Erro</button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="card fade-in">
    <div class="card-body" style="padding: 60px; text-align: center;">
        <div style="font-size: 48px; color: var(--beige-dark); margin-bottom: 15px;">&#128176;</div>
        <h3 style="color: var(--brown);">Nenhuma folha encontrada</h3>
        <p style="color: var(--text-muted); margin-top: 8px;">Não há folha de pagamento registrada para <?= getMonthName($mes) ?>/<?= $ano ?>.</p>
    </div>
</div>
<?php endif; ?>

<div class="card fade-in" style="margin-top: 25px;">
    <div class="card-header">
        <h3>Histórico de Pagamentos</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="table">
            <thead>
                <tr>
                    <th>Período</th>
                    <th>Valor Bruto</th>
                    <th>Valor Líquido</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historico as $h): ?>
                <tr <?= ($h['mes_referencia'] == $mes && $h['ano_referencia'] == $ano) ? 'style="background: var(--cream);"' : '' ?>>
                    <td><?= getMonthName($h['mes_referencia']) ?>/<?= $h['ano_referencia'] ?></td>
                    <td><?= formatCurrency($h['valor_bruto']) ?></td>
                    <td style="font-weight: 700;"><?= formatCurrency($h['valor_liquido']) ?></td>
                    <td><?= getStatusBadge('concluido') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>