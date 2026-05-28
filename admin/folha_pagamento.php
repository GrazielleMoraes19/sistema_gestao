<?php
require_once '../config/functions.php';
requireAdmin();
require_once '../includes/header_admin.php';

$db = Database::getInstance()->getConnection();
$success = $_GET['success'] ?? '';

// Gerar folha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_folha'])) {
    $user_id = $_POST['user_id'];
    $mes = $_POST['mes_referencia'];
    $ano = $_POST['ano_referencia'];
    $valor_bruto = floatval($_POST['valor_bruto']);
    $descontos = $_POST['descontos'];
    $desconto_valores = $_POST['desconto_valores'];
    $desconto_justificativas = $_POST['desconto_justificativas'];
    $beneficios = $_POST['beneficios'];
    $beneficio_valores = $_POST['beneficio_valores'];

    $total_descontos = 0;
    foreach ($desconto_valores as $dv) $total_descontos += floatval($dv);
    $total_beneficios = 0;
    foreach ($beneficio_valores as $bv) $total_beneficios += floatval($bv);
    
    $valor_liquido = $valor_bruto - $total_descontos + $total_beneficios;

    $stmt = $db->prepare("INSERT INTO folha_pagamento (user_id, mes_referencia, ano_referencia, valor_bruto, valor_liquido) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $mes, $ano, $valor_bruto, $valor_liquido]);
    $folha_id = $db->lastInsertId();

    for ($i = 0; $i < count($descontos); $i++) {
        if ($descontos[$i] && $desconto_valores[$i]) {
            $stmt = $db->prepare("INSERT INTO descontos (folha_id, descricao, valor, justificativa) VALUES (?, ?, ?, ?)");
            $stmt->execute([$folha_id, $descontos[$i], $desconto_valores[$i], $desconto_justificativas[$i] ?? '']);
        }
    }

    for ($i = 0; $i < count($beneficios); $i++) {
        if ($beneficios[$i] && $beneficio_valores[$i]) {
            $stmt = $db->prepare("INSERT INTO beneficios (folha_id, descricao, valor) VALUES (?, ?, ?)");
            $stmt->execute([$folha_id, $beneficios[$i], $beneficio_valores[$i]]);
        }
    }

    $success = 'Folha de pagamento gerada com sucesso!';
}

// Buscar funcionários
$stmt = $db->query("SELECT id, nome_completo FROM users WHERE nivel_acesso = 'funcionario' ORDER BY nome_completo");
$funcionarios = $stmt->fetchAll();

// Buscar folhas
$busca_nome = $_GET['busca'] ?? '';
$sql = "SELECT fp.*, u.nome_completo, u.obfuscated_id FROM folha_pagamento fp JOIN users u ON fp.user_id = u.id";
$params = [];
if ($busca_nome) {
    $sql .= " WHERE u.nome_completo LIKE ?";
    $params[] = "%$busca_nome%";
}
$sql .= " ORDER BY fp.ano_referencia DESC, fp.mes_referencia DESC LIMIT 50";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$folhas = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Gestão de Folha de Pagamento</h1>
    <p>Geração e gerenciamento de holerites dos colaboradores</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="grid-2 fade-in">
    <div class="card">
        <div class="card-header">
            <h3>Gerar Nova Folha</h3>
        </div>
        <div class="card-body">
            <form method="POST" data-validate>
                <input type="hidden" name="gerar_folha" value="1">
                <div class="form-group">
                    <label>Funcionário</label>
                    <select name="user_id" class="form-control" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($funcionarios as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid-2" style="margin-bottom: 15px;">
                    <div class="form-group">
                        <label>Mês Referência</label>
                        <select name="mes_referencia" class="form-control">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $i == date('n') ? 'selected' : '' ?>><?= getMonthName($i) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ano</label>
                        <input type="number" name="ano_referencia" class="form-control" value="<?= date('Y') ?>" min="2020" max="2030">
                    </div>
                </div>
                <div class="form-group">
                    <label>Valor Bruto (R$)</label>
                    <input type="text" name="valor_bruto" class="form-control currency-input" required>
                </div>

                <h4 style="margin: 20px 0 10px; font-size: 14px; color: var(--brown);">Descontos</h4>
                <div id="descontos-container">
                    <div class="desconto-row" style="margin-bottom: 10px;">
                        <div class="grid-3">
                            <input type="text" name="descontos[]" class="form-control" placeholder="Descrição">
                            <input type="text" name="desconto_valores[]" class="form-control currency-input" placeholder="Valor">
                            <input type="text" name="desconto_justificativas[]" class="form-control" placeholder="Justificativa">
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addDesconto()" style="margin-bottom: 20px;">+ Adicionar Desconto</button>

                <h4 style="margin: 20px 0 10px; font-size: 14px; color: var(--brown);">Benefícios</h4>
                <div id="beneficios-container">
                    <div class="beneficio-row" style="margin-bottom: 10px;">
                        <div class="grid-2">
                            <input type="text" name="beneficios[]" class="form-control" placeholder="Descrição">
                            <input type="text" name="beneficio_valores[]" class="form-control currency-input" placeholder="Valor">
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addBeneficio()" style="margin-bottom: 20px;">+ Adicionar Benefício</button>

                <button type="submit" class="btn btn-primary btn-full">Gerar Folha de Pagamento</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Folhas Geradas</h3>
        </div>
        <div style="padding: 15px;">
            <input type="text" id="searchFolha" class="form-control" placeholder="Buscar por nome..." style="max-width: 100%;">
        </div>
        <div class="table-container" style="border-radius: 0; border: none;">
            <table class="table" id="folhaTable">
                <thead>
                    <tr>
                        <th>Funcionário</th>
                        <th>Período</th>
                        <th>Bruto</th>
                        <th>Líquido</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($folhas as $f): ?>
                    <tr>
                        <td><?= htmlspecialchars($f['nome_completo']) ?></td>
                        <td><?= getMonthName($f['mes_referencia']) ?>/<?= $f['ano_referencia'] ?></td>
                        <td><?= formatCurrency($f['valor_bruto']) ?></td>
                        <td style="font-weight: 700; color: var(--brown-dark);"><?= formatCurrency($f['valor_liquido']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function addDesconto() {
        const container = document.getElementById('descontos-container');
        const row = document.createElement('div');
        row.className = 'desconto-row';
        row.style.marginBottom = '10px';
        row.innerHTML = `
            <div class="grid-3">
                <input type="text" name="descontos[]" class="form-control" placeholder="Descrição">
                <input type="text" name="desconto_valores[]" class="form-control currency-input" placeholder="Valor">
                <input type="text" name="desconto_justificativas[]" class="form-control" placeholder="Justificativa">
            </div>
        `;
        container.appendChild(row);
    }

    function addBeneficio() {
        const container = document.getElementById('beneficios-container');
        const row = document.createElement('div');
        row.className = 'beneficio-row';
        row.style.marginBottom = '10px';
        row.innerHTML = `
            <div class="grid-2">
                <input type="text" name="beneficios[]" class="form-control" placeholder="Descrição">
                <input type="text" name="beneficio_valores[]" class="form-control currency-input" placeholder="Valor">
            </div>
        `;
        container.appendChild(row);
    }

    document.addEventListener('DOMContentLoaded', function() {
        searchTable('searchFolha', 'folhaTable');
    });
</script>

<?php require_once '../includes/footer.php'; ?>