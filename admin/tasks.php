<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_level'], ['admin', 'rh', 'coordenacao'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';
require_once '../config/functions.php';

// Processar criação de tarefa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_tarefa'])) {
    $usuario_id = intval($_POST['usuario_id']);
    $titulo = sanitize_input($_POST['titulo']);
    $descricao = sanitize_input($_POST['descricao']);
    $prioridade = sanitize_input($_POST['prioridade'] ?? 'media');
    $data_limite = sanitize_input($_POST['data_limite'] ?? null);
    
    if (empty($titulo) || $usuario_id <= 0) {
        $error = 'Preencha os campos obrigatórios';
    } else {
        $sql = "INSERT INTO servicos (usuario_id, titulo, descricao, prioridade, data_limite, criado_por, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pendente')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issssi', $usuario_id, $titulo, $descricao, $prioridade, $data_limite, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = 'Tarefa criada com sucesso';
        } else {
            $error = 'Erro ao criar tarefa';
        }
    }
}

// Buscar tarefas
$sql = "SELECT s.*, u.nome_completo FROM servicos s 
        JOIN usuarios u ON s.usuario_id = u.id 
        ORDER BY s.data_limite ASC, s.prioridade DESC";
$tarefas = $conn->query($sql);

// Buscar usuários
$usuarios = $conn->query("SELECT id, nome_completo FROM usuarios WHERE nivel_acesso = 'funcionario' ORDER BY nome_completo");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarefas - Sistema de RH</title>
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
            <li><a href="tickets.php">📋 Chamados</a></li>
            <li><a href="tasks.php" class="active">✓ Tarefas</a></li>
            <li><a href="../logout.php" onclick="return confirm('Deseja sair?')">🚪 Sair</a></li>
        </nav>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <div class="container">
            <!-- Header -->
            <div class="header">
                <h1>Atribuição de Tarefas</h1>
                <p>Crie e gerencie tarefas para os funcionários</p>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Formulário de Criação -->
            <div class="card" style="margin-bottom: var(--spacing-2xl);">
                <div class="card-header">
                    <h3>Criar Nova Tarefa</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-grid">
                        <input type="hidden" name="criar_tarefa" value="1">

                        <div class="form-group">
                            <label for="usuario_id">Funcionário *</label>
                            <select id="usuario_id" name="usuario_id" class="form-control" required>
                                <option value="">Selecione um funcionário</option>
                                <?php 
                                $usuarios->data_seek(0);
                                while ($user = $usuarios->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['nome_completo']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="titulo">Título *</label>
                            <input type="text" id="titulo" name="titulo" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="prioridade">Prioridade</label>
                            <select id="prioridade" name="prioridade" class="form-control">
                                <option value="baixa">Baixa</option>
                                <option value="media" selected>Média</option>
                                <option value="alta">Alta</option>
                                <option value="urgente">Urgente</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="data_limite">Data Limite</label>
                            <input type="date" id="data_limite" name="data_limite" class="form-control">
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao" class="form-control" rows="4"></textarea>
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-primary">Criar Tarefa</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Tarefas -->
            <div class="card">
                <div class="card-header">
                    <h3>Tarefas Atribuídas</h3>
                </div>
                <div class="card-body">
                    <?php if ($tarefas->num_rows > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Funcionário</th>
                                        <th>Prioridade</th>
                                        <th>Status</th>
                                        <th>Data Limite</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $tarefas->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nome_completo']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $row['prioridade'] === 'urgente' ? 'danger' : 
                                                         ($row['prioridade'] === 'alta' ? 'warning' : 'primary');
                                                ?>">
                                                    <?php echo ucfirst($row['prioridade']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $row['status'] === 'concluido' ? 'success' : 
                                                         ($row['status'] === 'em_andamento' ? 'warning' : 'primary');
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $row['data_limite'] ? formatDate($row['data_limite']) : '-'; ?></td>
                                            <td>
                                                <a href="edit-task.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline">Editar</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">Nenhuma tarefa criada</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-lg);
        }
    </style>

    <script src="../assets/js/main.js"></script>
</body>
</html>
