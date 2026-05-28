<?php
require_once '../config/functions.php';
requireEmployee();
require_once '../includes/header_employee.php';

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$success = $_GET['success'] ?? '';

// Registrar ponto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['registrar_ponto'])) {
        $data = $_POST['data_registro'];
        $entrada_manha = $_POST['entrada_manha'] ?: null;
        $saida_manha = $_POST['saida_manha'] ?: null;
        $entrada_tarde = $_POST['entrada_tarde'] ?: null;
        $saida_tarde = $_POST['saida_tarde'] ?: null;
        $horas_extras = floatval($_POST['horas_extras'] ?: 0);
        $justificativa_extras = $_POST['justificativa_extras'] ?: null;
        
        $status = 'sem_registro';
        if ($entrada_manha && $saida_tarde) $status = 'trabalhado';
        if ($horas_extras > 0) $status = 'trabalhado';

        $stmt = $db->prepare("
            INSERT INTO ponto_registro (user_id, data_registro, entrada_manha, saida_manha, entrada_tarde, saida_tarde, horas_extras, justificativa_extras, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                entrada_manha = VALUES(entrada_manha),
                saida_manha = VALUES(saida_manha),
                entrada_tarde = VALUES(entrada_tarde),
                saida_tarde = VALUES(saida_tarde),
                horas_extras = VALUES(horas_extras),
                justificativa_extras = VALUES(justificativa_extras),
                status = VALUES(status)
        ");
        $stmt->execute([$userId, $data, $entrada_manha, $saida_manha, $entrada_tarde, $saida_tarde, $horas_extras, $justificativa_extras, $status]);
        $success = 'Ponto registrado com sucesso!';
    }

    if (isset($_POST['registrar_falta'])) {
        $stmt = $db->prepare("
            INSERT INTO atestados (user_id, data_inicio, data_fim, horario_entrada, horario_saida, motivo)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $_POST['data_inicio'], $_POST['data_fim'], $_POST['horario_entrada'], $_POST['horario_saida'], $_POST['motivo']]);
        $success = 'Justificativa de falta registrada!';
    }

    if (isset($_POST['reportar_erro'])) {
        $stmt = $db->prepare("
            INSERT INTO chamados (user_id, tipo, assunto, descricao)
            VALUES (?, 'erro_ponto', ?, ?)
        ");
        $stmt->execute([$userId, $_POST['assunto'], $_POST['descricao']]);
        $success = 'Erro reportado ao RH com sucesso!';
    }
}

$mes = $_GET['mes'] ?? date('m');
$ano = $_GET['ano'] ?? date('Y');

$stmt = $db->prepare("
    SELECT * FROM ponto_registro 
    WHERE user_id = ? AND MONTH(data_registro) = ? AND YEAR(data_registro) = ?
    ORDER BY data_registro ASC
");
$stmt->execute([$userId, $mes, $ano]);
$registros = $stmt->fetchAll();
$registros_map = [];
foreach ($registros as $r) $registros_map[$r['data_registro']] = $r;

$dias_no_mes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
?>

<div class="page-header">
    <h1>Meu Ponto</h1>
    <p>Registro e consulta de ponto individual</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="filters-bar">
    <div class="filter-group">
        <label>Mês</label>
        <select class="form-control" id="filterMes">
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>" <?= $i == $mes ? 'selected' : '' ?>><?= getMonthName($i) ?></option>
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
    <button class="btn btn-primary btn-sm" onclick="window.location='meu_ponto.php?mes='+document.getElementById('filterMes').value+'&ano='+document.getElementById('filterAno').value">Filtrar</button>
    <button class="btn btn-secondary btn-sm" onclick="exportPDF('../api/pdf_generate.php', {tipo: 'ponto_func', mes: '<?= $mes ?>', ano: '<?= $ano ?>'})">Exportar PDF</button>
</div>

<div class="card fade-in" style="margin-bottom: 25px;">
    <div class="card-header">
        <h3>Registrar Ponto - <?= getMonthName($mes) ?>/<?= $ano ?></h3>
    </div>
    <div class="card-body">
        <form method="POST" data-validate>
            <input type="hidden" name="registrar_ponto" value="1">
            <div class="form-group">
                <label>Data</label>
                <input type="date" name="data_registro" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Entrada Manhã</label>
                    <input type="time" name="entrada_manha" class="form-control">
                </div>
                <div class="form-group">
                    <label>Saída Almoço</label>
                    <input type="time" name="saida_manha" class="form-control">
                </div>
                <div class="form-group">
                    <label>Retorno Almoço</label>
                    <input type="time" name="entrada_tarde" class="form-control">
                </div>
                <div class="form-group">
                    <label>Saída Tarde</label>
                    <input type="time" name="saida_tarde" class="form-control">
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Horas Extras</label>
                    <input type="number" name="horas_extras" class="form-control" step="0.25" min="0" placeholder="Ex: 1.5">
                </div>
                <div class="form-group">
                    <label>Justificativa Extras</label>
                    <input type="text" name="justificativa_extras" class="form-control" placeholder="Motivo das horas extras">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Registrar Ponto</button>
        </form>
    </div>
</div>

<div class="card fade-in" style="margin-bottom: 25px;">
    <div class="card-header">
        <h3>Justificativa de Falta (Atestado)</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="registrar_falta" value="1">
            <div class="grid-2">
                <div class="form-group">
                    <label>Data Início</label>
                    <input type="date" name="data_inicio" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Data Fim</label>
                    <input type="date" name="data_fim" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Horário Entrada</label>
                    <input type="time" name="horario_entrada" class="form-control">
                </div>
                <div class="form-group">
                    <label>Horário Saída</label>
                    <input type="time" name="horario_saida" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label>Motivo</label>
                <textarea name="motivo" class="form-control" rows="3" required placeholder="Descreva o motivo do afastamento..."></textarea>
            </div>
            <button type="submit" class="btn btn-secondary">Enviar Justificativa</button>
        </form>
    </div>
</div>

<div class="card fade-in" style="margin-bottom: 25px;">
    <div class="card-header">
        <h3>Reportar Erro no Ponto</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="reportar_erro" value="1">
            <div class="form-group">
                <label>Assunto</label>
                <input type="text" name="assunto" class="form-control" required placeholder="Ex: Registro duplicado, Horário incorreto...">
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" class="form-control" rows="4" required placeholder="Descreva o erro encontrado..."></textarea>
            </div>
            <button type="submit" class="btn btn-danger btn-sm">Reportar Erro</button>
        </form>
    </div>
</div>

<div class="card fade-in">
    <div class="card-header">
        <h3>Resumo do Mês</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Entrada</th>
                    <th>Saída Almoço</th>
                    <th>Retorno</th>
                    <th>Saída</th>
                    <th>H. Extras</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($d = 1; $d <= $dias_no_mes; $d++): 
                    $data = sprintf('%04d-%02d-%02d', $ano, $mes, $d);
                    $registro = $registros_map[$data] ?? null;
                    $status = $registro ? $registro['status'] : 'sem_registro';
                    $dia_semana = date('N', strtotime($data));
                    if ($dia_semana >= 6) continue; // Pula fins de semana
                ?>
                <tr class="<?= $status ?>">
                    <td><?= formatDate($data) ?></td>
                    <td><?= formatTime($registro['entrada_manha'] ?? null) ?></td>
                    <td><?= formatTime($registro['saida_manha'] ?? null) ?></td>
                    <td><?= formatTime($registro['entrada_tarde'] ?? null) ?></td>
                    <td><?= formatTime($registro['saida_tarde'] ?? null) ?></td>
                    <td><?= $registro && $registro['horas_extras'] > 0 ? $registro['horas_extras'] . 'h' : '-' ?></td>
                    <td><?= getStatusBadge($status) ?></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>