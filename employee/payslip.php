<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'funcionario') {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';
require_once '../config/functions.php';

$usuario_id = $_SESSION['user_id'];
$mes = intval($_GET['mes'] ?? date('m'));
$ano = intval($_GET['ano'] ?? date('Y'));

// Buscar holerite
$sql = "SELECT * FROM folha_pagamento WHERE usuario_id = ? AND mes_referencia = ? AND ano_referencia = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iii', $usuario_id, $mes, $ano);
$stmt->execute();
$holerite = $stmt->get_result()->fetch_assoc();

// Buscar descontos e benefícios
$descontos = [];
$beneficios = [];

if ($holerite) {
    $descontos_result = $conn->query("SELECT * FROM descontos WHERE folha_id = {$holerite['id']}");
    while ($row = $descontos_result->fetch_assoc()) {
        $descontos[] = $row;
    }

    $beneficios_result = $conn->query("SELECT * FROM beneficios WHERE folha_id = {$holerite['id']}");
    while ($row = $beneficios_result->fetch_assoc()) {
        $beneficios[] = $row;
    }
}

// Buscar histórico de holerites
$historico = $conn->query("SELECT mes_referencia, ano_referencia FROM folha_pagamento WHERE usuario_id = $usuario_id ORDER BY ano_referencia DESC, mes_referencia DESC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Holerite - Sistema de RH</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Barra de Navegação -->
    <header style="background-color: var(--color-white); box-shadow: var(--shadow-md); padding: var(--spacing-lg);">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0;">Meu Holerite</h2>
                <p style="margin: var(--spacing-sm) 0 0; color: var(--color-gray);">Consulte seus contracheques</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-outline">← Voltar</a>
                <a href="../logout.php" class="btn btn-outline" onclick="return confirm('Deseja sair?')">🚪 Sair</a>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="container" style="padding: var(--spacing-lg); margin-top: var(--spacing-lg);">
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

        <!-- Holerite -->
        <?php if ($holerite): ?>
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, var(--color-accent) 0%, var(--color-info) 100%);">
                    <h3 style="color: white; margin: 0;">Contracheque</h3>
                </div>
                <div class="card-body">
                    <!-- Informações Pessoais -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-lg); margin-bottom: var(--spacing-2xl); padding-bottom: var(--spacing-lg); border-bottom: 1px solid var(--color-border);">
                        <div>
                            <p style="margin: 0; color: var(--color-gray); font-size: var(--font-size-sm);">Período</p>
                            <p style="margin: var(--spacing-sm) 0 0; font-weight: 500;">
                                <?php echo strftime('%B/%Y', mktime(0, 0, 0, $mes, 1, $ano)); ?>
                            </p>
                        </div>
                        <div>
                            <p style="margin: 0; color: var(--color-gray); font-size: var(--font-size-sm);">Status</p>
                            <p style="margin: var(--spacing-sm) 0 0; font-weight: 500;">
                                <span class="badge badge-<?php echo $holerite['status'] === 'paga' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($holerite['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Proventos -->
                    <div style="margin-bottom: var(--spacing-2xl);">
                        <h4>Proventos</h4>
                        <div style="display: flex; justify-content: space-between; padding: var(--spacing-md) 0; border-bottom: 1px solid var(--color-border);">
                            <span>Salário Base</span>
                            <span><?php echo formatCurrency($holerite['valor_bruto']); ?></span>
                        </div>
                        <?php foreach ($beneficios as $beneficio): ?>
                            <div style="display: flex; justify-content: space-between; padding: var(--spacing-md) 0; border-bottom: 1px solid var(--color-border);">
                                <span><?php echo htmlspecialchars($beneficio['descricao']); ?></span>
                                <span><?php echo formatCurrency($beneficio['valor']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Descontos -->
                    <div style="margin-bottom: var(--spacing-2xl);">
                        <h4>Descontos</h4>
                        <?php if (count($descontos) > 0): ?>
                            <?php foreach ($descontos as $desconto): ?>
                                <div style="display: flex; justify-content: space-between; padding: var(--spacing-md) 0; border-bottom: 1px solid var(--color-border);">
                                    <span><?php echo htmlspecialchars($desconto['descricao']); ?></span>
                                    <span style="color: var(--color-danger);">-<?php echo formatCurrency($desconto['valor']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--color-gray);">Nenhum desconto</p>
                        <?php endif; ?>
                    </div>

                    <!-- Resumo -->
                    <div style="background-color: var(--color-light); padding: var(--spacing-lg); border-radius: var(--border-radius); margin-bottom: var(--spacing-lg);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-md);">
                            <span>Salário Bruto</span>
                            <span style="font-weight: bold;"><?php echo formatCurrency($holerite['valor_bruto']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-md);">
                            <span>Total de Descontos</span>
                            <span style="font-weight: bold; color: var(--color-danger);">-<?php echo formatCurrency($holerite['valor_bruto'] - $holerite['valor_liquido']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-top: var(--spacing-md); border-top: 2px solid var(--color-border);">
                            <span style="font-size: var(--font-size-lg); font-weight: bold;">Salário Líquido</span>
                            <span style="font-size: var(--font-size-lg); font-weight: bold; color: var(--color-success);"><?php echo formatCurrency($holerite['valor_liquido']); ?></span>
                        </div>
                    </div>

                    <div style="text-align: center; color: var(--color-gray); font-size: var(--font-size-sm); padding-top: var(--spacing-lg); border-top: 1px solid var(--color-border);">
                        <p>Este é um documento oficial do sistema de gestão de RH</p>
                        <p>Gerado em <?php echo date('d/m/Y'); ?></p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body" style="text-align: center; padding: var(--spacing-2xl);">
                    <p class="text-muted">Nenhum holerite disponível para este período</p>
                </div>
            </div>
        <?php endif; ?>
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
