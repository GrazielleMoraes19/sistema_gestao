<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_level'], ['admin', 'rh', 'coordenacao'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';
require_once '../config/functions.php';

// Processar resposta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responder'])) {
    $chamado_id = intval($_POST['chamado_id']);
    $resposta = sanitize_input($_POST['resposta']);
    $status = sanitize_input($_POST['status'] ?? 'em_analise');
    
    $sql = "UPDATE chamados SET resposta = ?, status = ?, respondido_por = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssii', $resposta, $status, $_SESSION['user_id'], $chamado_id);
    
    if ($stmt->execute()) {
        $success = 'Chamado atualizado com sucesso';
    } else {
        $error = 'Erro ao atualizar chamado';
    }
}

// Buscar chamados
$status_filter = sanitize_input($_GET['status'] ?? '');
$sql = "SELECT c.*, u.nome_completo FROM chamados c 
        JOIN usuarios u ON c.usuario_id = u.id";

if (!empty($status_filter)) {
    $sql .= " WHERE c.status = '$status_filter'";
}

$sql .= " ORDER BY c.data_criacao DESC";
$chamados = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamados - Sistema de RH</title>
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
            <li><a href="payroll.php">💰 Folha de Pagamento</a></li>
            <li><a href="tickets.php" class="active">📋 Chamados</a></li>
            <li><a href="tasks.php">✓ Tarefas</a></li>
            <li><a href="../logout.php" onclick="return confirm('Deseja sair?')">🚪 Sair</a></li>
        </nav>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <div class="container">
            <!-- Header -->
            <div class="header">
                <h1>Gestão de Chamados</h1>
                <p>Visualize e responda aos chamados dos funcionários</p>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Filtros -->
            <div style="margin-bottom: var(--spacing-lg);">
                <a href="tickets.php" class="btn btn-outline <?php echo empty($status_filter) ? 'btn-primary' : ''; ?>">Todos</a>
                <a href="tickets.php?status=aberto" class="btn btn-outline <?php echo $status_filter === 'aberto' ? 'btn-primary' : ''; ?>">Abertos</a>
                <a href="tickets.php?status=em_analise" class="btn btn-outline <?php echo $status_filter === 'em_analise' ? 'btn-primary' : ''; ?>">Em Análise</a>
                <a href="tickets.php?status=resolvido" class="btn btn-outline <?php echo $status_filter === 'resolvido' ? 'btn-primary' : ''; ?>">Resolvidos</a>
            </div>

            <!-- Lista de Chamados -->
            <div class="card">
                <div class="card-body">
                    <?php if ($chamados->num_rows > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Assunto</th>
                                        <th>Funcionário</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Data</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $chamados->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['assunto']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nome_completo']); ?></td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    <?php echo ucfirst(str_replace('_', ' ', $row['tipo'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $row['status'] === 'aberto' ? 'danger' : ($row['status'] === 'resolvido' ? 'success' : 'warning'); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($row['data_criacao']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline" onclick="toggleDetails(<?php echo $row['id']; ?>)">Ver</button>
                                            </td>
                                        </tr>
                                        <tr id="details-<?php echo $row['id']; ?>" style="display: none;">
                                            <td colspan="7">
                                                <div style="padding: var(--spacing-lg); background-color: var(--color-light); border-radius: var(--border-radius);">
                                                    <h4>Descrição</h4>
                                                    <p><?php echo nl2br(htmlspecialchars($row['descricao'])); ?></p>

                                                    <?php if ($row['resposta']): ?>
                                                        <h4 style="margin-top: var(--spacing-lg);">Resposta</h4>
                                                        <p><?php echo nl2br(htmlspecialchars($row['resposta'])); ?></p>
                                                    <?php endif; ?>

                                                    <?php if ($row['status'] !== 'fechado'): ?>
                                                        <form method="POST" style="margin-top: var(--spacing-lg);">
                                                            <input type="hidden" name="chamado_id" value="<?php echo $row['id']; ?>">
                                                            <input type="hidden" name="responder" value="1">

                                                            <div class="form-group">
                                                                <label for="resposta-<?php echo $row['id']; ?>">Sua Resposta</label>
                                                                <textarea 
                                                                    id="resposta-<?php echo $row['id']; ?>" 
                                                                    name="resposta" 
                                                                    class="form-control"
                                                                    rows="4"
                                                                    required
                                                                ></textarea>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="status-<?php echo $row['id']; ?>">Status</label>
                                                                <select id="status-<?php echo $row['id']; ?>" name="status" class="form-control">
                                                                    <option value="em_analise" <?php echo $row['status'] === 'em_analise' ? 'selected' : ''; ?>>Em Análise</option>
                                                                    <option value="resolvido">Resolvido</option>
                                                                    <option value="fechado">Fechado</option>
                                                                </select>
                                                            </div>

                                                            <button type="submit" class="btn btn-primary">Responder</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">Nenhum chamado encontrado</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleDetails(id) {
            const details = document.getElementById('details-' + id);
            if (details.style.display === 'none') {
                details.style.display = 'table-row';
            } else {
                details.style.display = 'none';
            }
        }
    </script>

    <script src="../assets/js/main.js"></script>
</body>
</html>
