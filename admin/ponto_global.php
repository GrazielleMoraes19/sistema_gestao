<?php
require_once '../config/functions.php';
requireAdmin();
require_once '../includes/header_admin.php';

$db = Database::getInstance()->getConnection();

$mes = $_GET['mes'] ?? date('m');
$ano = $_GET['ano'] ?? date('Y');
$busca = $_GET['busca'] ?? '';

// Atualizar status se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'aprovar_extras') {
        $stmt = $db->prepare("UPDATE ponto_registro SET horas_extras = ?, justificativa_extras = ? WHERE id = ?");
        $stmt->execute([$_POST['horas'], $_POST['justificativa'], $_POST['ponto_id']]);
    }
}

// Listar funcionários
$sql = "SELECT u.*, pr.data_registro, pr.entrada_manha, pr.saida_manha, pr.entrada_tarde, pr.saida_tarde, pr.horas_extras, pr.justificativa_extras, pr.status, pr.id as ponto_id
        FROM users u 
        LEFT JOIN ponto_registro pr ON u.id = pr.user_id 
            AND pr.data_registro BETWEEN ? AND ?
        WHERE u.nivel_acesso = 'funcionario'";
$params = ["$ano-$mes-01", date('Y-m-t', strtotime("$ano-$mes-15"))];

if ($busca) {
    $sql .= " AND u.nome_completo LIKE ?";
    $params[] = "%$busca%";
}

$sql .= " ORDER BY u.nome_completo ASC, pr.data_registro DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Gestão Global de Ponto</h1>
    <p>Visualização e controle de registros de ponto de todos os colaboradores</p>
</div>

<div class="filters-bar">
    <div class="filter-group">
        <label>Mês</label>
        <select class="form-control" id="filterMes" onchange="updateFilters()">
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>" <?= $i == $mes ? 'selected' : '' ?>><?= getMonthName($i) ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="filter-group">
        <label>Ano</label>
        <select class="form-control" id="filterAno" onchange="updateFilters()">
            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $y == $ano ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="filter-group">
        <label>Buscar Funcionário</label>
        <input type="text" class="form-control" placeholder="Nome..." id="filterBusca">
    </div>
    <button class="btn btn-primary btn-sm" onclick="applyFilters()">Filtrar</button>
</div>

<div class="card fade-in">
    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Funcionário</th>
                        <th>Data</th>
                        <th>Entrada</th>
                        <th>Saída Almoço</th>
                        <th>Retorno</th>
                        <th>Saída</th>
                        <th>Horas Extras</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nome_completo']) ?></td>
                        <td><?= formatDate($r['data_registro']) ?></td>
                        <td><?= formatTime($r['entrada_manha']) ?></td>
                        <td><?= formatTime($r['saida_manha']) ?></td>
                        <td><?= formatTime($r['entrada_tarde']) ?></td>
                        <td><?= formatTime($r['saida_tarde']) ?></td>
                        <td><?= $r['horas_extras'] > 0 ? $r['horas_extras'] . 'h' : '-' ?></td>
                        <td><?= getStatusBadge($r['status'] ?: 'sem_registro') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($registros)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">Nenhum registro encontrado para o período.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function applyFilters() {
        const mes = document.getElementById('filterMes').value;
        const ano = document.getElementById('filterAno').value;
        const busca = document.getElementById('filterBusca').value;
        window.location.href = `ponto_global.php?mes=${mes}&ano=${ano}&busca=${encodeURIComponent(busca)}`;
    }
</script>

<?php require_once '../includes/footer.php'; ?>