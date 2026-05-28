<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_level'], ['admin', 'rh'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';
require_once '../config/functions.php';

$mes = intval($_GET['mes'] ?? date('m'));
$ano = intval($_GET['ano'] ?? date('Y'));

// Buscar folhas de pagamento
$sql = "SELECT fp.*, u.nome_completo, u.cpf FROM folha_pagamento fp 
        JOIN usuarios u ON fp.usuario_id = u.id 
        WHERE fp.mes_referencia = ? AND fp.ano_referencia = ?
        ORDER BY u.nome_completo";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $mes, $ano);
$stmt->execute();
$folhas = $stmt->get_result();

// Calcular totais
$total_bruto = 0;
$total_liquido = 0;
$total_descontos = 0;

$temp_result = $conn->query("SELECT SUM(valor_bruto) as bruto, SUM(valor_liquido) as liquido FROM folha_pagamento WHERE mes_referencia = $mes AND ano_referencia = $ano");
$totais = $temp_result->fetch_assoc();
$total_bruto = $totais['bruto'] ?? 0;
$total_liquido = $totais['liquido'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Folha de Pagamento - Sistema de RH</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">RH</div>
            <h3><?php echo $_SESSION['user_name']; ?></h3>
        </div>
        <nav class="sidebar-nav">
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="employees.php">👥 Funcionários</a></li>
            <li><a href="timesheet.php">⏰ Controle de Ponto</a></li>
            <li><a href="payroll.php" class="active">💰 Folha de Pagamento</a></li>
            <li><a href="tickets.php">📋 Chamados</a></li>
            <li><a href="tasks.php">✓ Tarefas</a></li>
            <li><a href="../logout.php" onclick="return confirm('Deseja sair?')">🚪 Sair</a></li>
        </nav>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <div class="container">
            <!-- Header -->
            <div class="header">
                <h1>Folha de Pagamento</h1>
                <p>Gerencie e visualize as folhas de pagamento dos funcionários</p>
            </div>

            <!-- Filtros -->
            <div class="card" style="margin-bottom: var(--spacing-lg);">
                <div class="card-body">
                    <form method="GET" class="form-filters">
                        <div class="form-group">
                            <label for="mes">Mês</label>
                            <select id="mes" name="mes" class="form-control">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $mes === $i ? 'selected' : ''; ?>>
                                        <?php echo strftime('%B', mktime(0, 0, 0, $i, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="ano">Ano</label>
                            <select id="ano" name="ano" class="form-control">
                                <?php for ($i = date('Y'); $i >= date('Y') - 2; $i--): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $ano === $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group" style="align-self: flex-end;">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resumo -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <h4>Total Bruto</h4>
                    <div class="value"><?php echo formatCurrency($total_bruto); ?></div>
                </div>
                <div class="stat-card" style="border-left-color: #e74c3c;">
                    <h4>Total Descontos</h4>
                    <div class="value"><?php echo formatCurrency($total_bruto - $total_liquido); ?></div>
                </div>
                <div class="stat-card" style="border-left-color: #27ae60;">
                    <h4>Total Líquido</h4>
                    <div class="value"><?php echo formatCurrency($total_liquido); ?></div>
                </div>
                <div class="stat-card" style="border-left-color: #f39c12;">
                    <h4>Funcionários</h4>
                    <div class="value"><?php echo $folhas->num_rows; ?></div>
                </div>
            </div>

            <!-- Tabela de Folhas -->
            <div class="card" style="margin-top: var(--spacing-2xl);">
                <div class="card-header">
                    <h3>Folhas de Pagamento</h3>
                </div>
                <div class="card-body">
                    <?php if ($folhas->num_rows > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Funcionário</th>
                                        <th>CPF</th>
                                        <th>Valor Bruto</th>
                                        <th>Descontos</th>
                                        <th>Valor Líquido</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $folhas->data_seek(0);
                                    while ($row = $folhas->fetch_assoc()): 
                                        $descontos = $row['valor_bruto'] - $row['valor_liquido'];
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['nome_completo']); ?></td>
                                            <td><?php echo formatCPF($row['cpf']); ?></td>
                                            <td><?php echo formatCurrency($row['valor_bruto']); ?></td>
                                            <td><?php echo formatCurrency($descontos); ?></td>
                                            <td><?php echo formatCurrency($row['valor_liquido']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $row['status'] === 'paga' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view-payslip.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline">Ver</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">Nenhuma folha de pagamento cadastrada para este período</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <style>
        .form-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            align-items: flex-end;
        }
    </style>

    <script src="../assets/js/main.js"></script>
</body>
</html>
