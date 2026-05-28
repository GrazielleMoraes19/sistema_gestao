<?php
require_once '../config/functions.php';
requireAdmin();
require_once '../includes/header_admin.php';

$db = Database::getInstance()->getConnection();

$tipo_relatorio = $_GET['tipo'] ?? 'ponto';

// Histórico de Ponto
if ($tipo_relatorio === 'ponto') {
    $mes = $_GET['mes'] ?? date('m');
    $ano = $_GET['ano'] ?? date('Y');
    $stmt = $db->prepare("
        SELECT pr.*, u.nome_completo, u.obfuscated_id, u.cargo
        FROM ponto_registro pr
        JOIN users u ON pr.user_id = u.id
        WHERE MONTH(pr.data_registro) = ? AND YEAR(pr.data_registro) = ?
        ORDER BY pr.data_registro DESC, u.nome_completo ASC
    ");
    $stmt->execute([$mes, $ano]);
    $dados = $stmt->fetchAll();
}

// Histórico de Folha
if ($tipo_relatorio === 'folha') {
    $mes = $_GET['mes'] ?? date('m');
    $ano = $_GET['ano'] ?? date('Y');
    $stmt = $db->prepare("
        SELECT fp.*, u.nome_completo, u.obfuscated_id, u.cargo,
               (SELECT SUM(valor) FROM descontos WHERE folha_id = fp.id) as total_descontos,
               (SELECT SUM(valor) FROM beneficios WHERE folha_id = fp.id) as total_beneficios
        FROM folha_pagamento fp
        JOIN users u ON fp.user_id = u.id
        WHERE fp.mes_referencia = ? AND fp.ano_referencia = ?
        ORDER BY u.nome_completo ASC
    ");
    $stmt->execute([$mes, $ano]);
    $dados = $stmt->fetchAll();
}
?>

<div class="page-header">
    <h1>Relatórios Gerenciais</h1>
    <p>Extração de históricos completos do sistema</p>
</div>

<div class="tabs">
    <a href="?tipo=ponto&mes=<?= $mes ?? date('m') ?>&ano=<?= $ano ?? date('Y') ?>" class="tab <?= $tipo_relatorio === 'ponto' ? 'active' : '' ?>">Histórico de Ponto</a>
    <a href="?tipo=folha&mes=<?= $mes ?? date('m') ?>&ano=<?= $ano ?? date('Y') ?>" class="tab <?= $tipo_relatorio === 'folha' ? 'active' : '' ?>">Histórico de Folha de Pagamento</a>
</div>

<div class="filters-bar">
    <div class="filter-group">
        <label>Mês</label>
        <select class="form-control" id="filterMes">
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>" <?= $i == ($mes ?? date('m')) ? 'selected' : '' ?>><?= getMonthName($i) ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="filter-group">
        <label>Ano</label>
        <select class="form-control" id="filterAno">
            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $y == ($ano ?? date('Y')) ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="btn-group">
        <button class="btn btn-primary btn-sm" onclick="applyReportFilter()">Filtrar</button>
        <?php if ($tipo_relatorio === 'ponto'): ?>
        <button class="btn btn-secondary btn-sm" onclick="exportPDF('../api/pdf_generate.php', {tipo: 'ponto', mes: document.getElementById('filterMes').value, ano: document.getElementById('filterAno').value})">Exportar PDF</button>
        <?php else: ?>
        <button class="btn btn-secondary btn-sm" onclick="exportPDF('../api/pdf_generate.php', {tipo: 'folha', mes: document.getElementById('filterMes').value, ano: document.getElementById('filterAno').value})">Exportar PDF</button>
        <?php endif; ?>
        <button class="btn btn-secondary btn-sm" onclick="exportToCSV('relatorioTable', 'relatorio_<?= $tipo_relatorio ?>.csv')">Exportar CSV</button>
    </div>
</div>

<div class="card fade-in">
    <div class="card-header">
        <h3><?= $tipo_relatorio === 'ponto' ? 'Histórico de Ponto' : 'Histórico de Folha de Pagamento' ?> - <?= getMonthName($mes ?? date('m')) ?>/<?= $ano ?? date('Y') ?></h3>
        <span style="font-size: 13px; color: var(--text-muted);"><?= count($dados) ?> registros</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table" id="relatorioTable">
                <?php if ($tipo_relatorio === 'ponto'): ?>
                <thead>
                    <tr>
                        <th>Funcionário</th>
                        <th>ID</th>
                        <th>Cargo</th>
                        <th>Data</th>
                        <th>Entrada</th>
                        <th>Saída</th>
                        <th>H. Extras</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dados as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['nome_completo']) ?></td>
                        <td><code><?= $d['obfuscated_id'] ?></code></td>
                        <td><?= htmlspecialchars($d['cargo']) ?></td>
                        <td><?= formatDate($d['data_registro']) ?></td>
                        <td><?= formatTime($d['entrada_manha']) ?></td>
                        <td><?= formatTime($d['saida_tarde']) ?></td>
                        <td><?= $d['horas_extras'] > 0 ? $d['horas_extras'] . 'h' : '-' ?></td>
                        <td><?= getStatusBadge($d['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php else: ?>
                <thead>
                    <tr>
                        <th>Funcionário</th>
                        <th>ID</th>
                        <th>Cargo</th>
                        <th>Período</th>
                        <th>Valor Bruto</th>
                        <th>Descontos</th>
                        <th>Benefícios</th>
                        <th>Valor Líquido</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dados as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['nome_completo']) ?></td>
                        <td><code><?= $d['obfuscated_id'] ?></code></td>
                        <td><?= htmlspecialchars($d['cargo']) ?></td>
                        <td><?= getMonthName($d['mes_referencia']) ?>/<?= $d['ano_referencia'] ?></td>
                        <td><?= formatCurrency($d['valor_bruto']) ?></td>
                        <td style="color: #8a4438;">- <?= formatCurrency($d['total_descontos'] ?: 0) ?></td>
                        <td style="color: #4a6a42;">+ <?= formatCurrency($d['total_beneficios'] ?: 0) ?></td>
                        <td style="font-weight: 700;"><?= formatCurrency($d['valor_liquido']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
    function applyReportFilter() {
        const mes = document.getElementById('filterMes').value;
        const ano = document.getElementById('filterAno').value;
        const tipo = '<?= $tipo_relatorio ?>';
        window.location.href = `relatorios.php?tipo=${tipo}&mes=${mes}&ano=${ano}`;
    }
</script>

<?php require_once '../includes/footer.php'; ?>