<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_level'], ['admin', 'rh', 'coordenacao'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';
require_once '../config/functions.php';

$mes = intval($_GET['mes'] ?? date('m'));
$ano = intval($_GET['ano'] ?? date('Y'));
$usuario_id = intval($_GET['usuario_id'] ?? 0);

// Construir query
$sql = "SELECT pr.*, u.nome_completo FROM ponto_registro pr 
        JOIN usuarios u ON pr.usuario_id = u.id 
        WHERE MONTH(pr.data_registro) = ? AND YEAR(pr.data_registro) = ?";

$params = [$mes, $ano];
$types = 'ii';

if ($usuario_id > 0) {
    $sql .= " AND pr.usuario_id = ?";
    $params[] = $usuario_id;
    $types .= 'i';
}

$sql .= " ORDER BY pr.data_registro DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$pontos = $stmt->get_result();

// Buscar usuários para filtro
$usuarios = $conn->query("SELECT id, nome_completo FROM usuarios WHERE nivel_acesso = 'funcionario' ORDER BY nome_completo");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Ponto - Sistema de RH</title>
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
            <li><a href="timesheet.php" class="active">⏰ Controle de Ponto</a></li>
            <li><a href="payroll.php">💰 Folha de Pagamento</a></li>
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
                <h1>Controle de Ponto</h1>
                <p>Visualize e gerencie os registros de ponto de todos os funcionários</p>
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

                        <div class="form-group">
                            <label for="usuario_id">Funcionário</label>
                            <select id="usuario_id" name="usuario_id" class="form-control">
                                <option value="0">Todos</option>
                                <?php while ($user = $usuarios->fetch_assoc()): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $usuario_id === $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['nome_completo']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group" style="align-self: flex-end;">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Pontos -->
            <div class="card">
                <div class="card-header">
                    <h3>Registros de Ponto</h3>
                </div>
                <div class="card-body">
                    <?php if ($pontos->num_rows > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Funcionário</th>
                                        <th>Entrada Manhã</th>
                                        <th>Saída Manhã</th>
                                        <th>Entrada Tarde</th>
                                        <th>Saída Tarde</th>
                                        <th>Horas Extras</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $pontos->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo formatDate($row['data_registro']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nome_completo']); ?></td>
                                            <td><?php echo formatTime($row['entrada_manha']); ?></td>
                                            <td><?php echo formatTime($row['saida_manha']); ?></td>
                                            <td><?php echo formatTime($row['entrada_tarde']); ?></td>
                                            <td><?php echo formatTime($row['saida_tarde']); ?></td>
                                            <td><?php echo $row['horas_extras'] . 'h'; ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $row['status'] === 'trabalhado' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">Nenhum registro de ponto encontrado</p>
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
