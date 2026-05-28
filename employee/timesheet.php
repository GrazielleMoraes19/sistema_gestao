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

// Buscar pontos do mês
$sql = "SELECT * FROM ponto_registro WHERE usuario_id = ? AND MONTH(data_registro) = ? AND YEAR(data_registro) = ? ORDER BY data_registro DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iii', $usuario_id, $mes, $ano);
$stmt->execute();
$pontos = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Ponto - Sistema de RH</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Barra de Navegação -->
    <header style="background-color: var(--color-white); box-shadow: var(--shadow-md); padding: var(--spacing-lg);">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0;">Meu Ponto</h2>
                <p style="margin: var(--spacing-sm) 0 0; color: var(--color-gray);">Consulte seus registros de ponto</p>
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
                    <p class="text-center text-muted">Nenhum registro de ponto para este período</p>
                <?php endif; ?>
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
