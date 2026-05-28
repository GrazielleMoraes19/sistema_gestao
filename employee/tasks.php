<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'funcionario') {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';
require_once '../config/functions.php';

$usuario_id = $_SESSION['user_id'];

// Atualizar status de tarefa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_status'])) {
    $tarefa_id = intval($_POST['tarefa_id']);
    $novo_status = sanitize_input($_POST['status']);
    
    $sql = "UPDATE servicos SET status = ? WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $novo_status, $tarefa_id, $usuario_id);
    $stmt->execute();
}

// Buscar tarefas
$sql = "SELECT * FROM servicos WHERE usuario_id = ? ORDER BY data_limite ASC, prioridade DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$tarefas = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Tarefas - Sistema de RH</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Barra de Navegação -->
    <header style="background-color: var(--color-white); box-shadow: var(--shadow-md); padding: var(--spacing-lg);">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0;">Minhas Tarefas</h2>
                <p style="margin: var(--spacing-sm) 0 0; color: var(--color-gray);">Acompanhe suas tarefas e responsabilidades</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-outline">← Voltar</a>
                <a href="../logout.php" class="btn btn-outline" onclick="return confirm('Deseja sair?')">🚪 Sair</a>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="container" style="padding: var(--spacing-lg); margin-top: var(--spacing-lg);">
        <!-- Resumo -->
        <div class="dashboard-grid">
            <?php 
            $tarefas->data_seek(0);
            $pendentes = 0;
            $em_andamento = 0;
            $concluidas = 0;
            
            while ($row = $tarefas->fetch_assoc()) {
                if ($row['status'] === 'pendente') $pendentes++;
                elseif ($row['status'] === 'em_andamento') $em_andamento++;
                elseif ($row['status'] === 'concluido') $concluidas++;
            }
            ?>
            <div class="stat-card">
                <h4>Pendentes</h4>
                <div class="value"><?php echo $pendentes; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #f39c12;">
                <h4>Em Andamento</h4>
                <div class="value"><?php echo $em_andamento; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #27ae60;">
                <h4>Concluídas</h4>
                <div class="value"><?php echo $concluidas; ?></div>
            </div>
        </div>

        <!-- Lista de Tarefas -->
        <div class="card" style="margin-top: var(--spacing-2xl);">
            <div class="card-header">
                <h3>Tarefas Atribuídas</h3>
            </div>
            <div class="card-body">
                <?php if ($tarefas->num_rows > 0): ?>
                    <div style="display: grid; gap: var(--spacing-lg);">
                        <?php 
                        $tarefas->data_seek(0);
                        while ($row = $tarefas->fetch_assoc()): 
                        ?>
                            <div style="border: 1px solid var(--color-border); border-radius: var(--border-radius); padding: var(--spacing-lg);">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--spacing-md);">
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 var(--spacing-sm);"><?php echo htmlspecialchars($row['titulo']); ?></h4>
                                        <div style="display: flex; gap: var(--spacing-md); flex-wrap: wrap;">
                                            <span class="badge badge-<?php 
                                                echo $row['prioridade'] === 'urgente' ? 'danger' : 
                                                     ($row['prioridade'] === 'alta' ? 'warning' : 'primary');
                                            ?>">
                                                <?php echo ucfirst($row['prioridade']); ?>
                                            </span>
                                            <span class="badge badge-<?php 
                                                echo $row['status'] === 'concluido' ? 'success' : 
                                                     ($row['status'] === 'em_andamento' ? 'warning' : 'primary');
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($row['descricao']): ?>
                                    <p style="margin: 0 0 var(--spacing-md); color: var(--color-gray);">
                                        <?php echo nl2br(htmlspecialchars($row['descricao'])); ?>
                                    </p>
                                <?php endif; ?>

                                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: var(--spacing-md); border-top: 1px solid var(--color-border);">
                                    <p style="margin: 0; font-size: var(--font-size-sm); color: var(--color-gray);">
                                        <?php echo $row['data_limite'] ? 'Prazo: ' . formatDate($row['data_limite']) : 'Sem prazo definido'; ?>
                                    </p>
                                    <form method="POST" style="display: flex; gap: var(--spacing-md);">
                                        <input type="hidden" name="tarefa_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="atualizar_status" value="1">
                                        <select name="status" class="form-control" style="width: auto;" onchange="this.form.submit()">
                                            <option value="pendente" <?php echo $row['status'] === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                            <option value="em_andamento" <?php echo $row['status'] === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                                            <option value="concluido" <?php echo $row['status'] === 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">Nenhuma tarefa atribuída</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
