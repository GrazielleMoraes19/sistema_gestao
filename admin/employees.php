<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_level'], ['admin', 'rh', 'coordenacao'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';
require_once '../config/functions.php';

// Processar formulário de adição/edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize_input($_POST['nome'] ?? '');
    $cpf = sanitize_input($_POST['cpf'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $cargo = sanitize_input($_POST['cargo'] ?? '');
    $nivel_acesso = sanitize_input($_POST['nivel_acesso'] ?? 'funcionario');
    $status = sanitize_input($_POST['status'] ?? 'ativo');
    
    if (empty($nome) || empty($cpf) || empty($email)) {
        $error = 'Preencha todos os campos obrigatórios';
    } elseif (!is_valid_cpf($cpf)) {
        $error = 'CPF inválido';
    } elseif (!is_valid_email($email)) {
        $error = 'Email inválido';
    } else {
        // Inserir novo usuário
        $senha_hash = password_hash($cpf, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (nome_completo, cpf, email, cargo, nivel_acesso, status, senha) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssss', $nome, $cpf, $email, $cargo, $nivel_acesso, $status, $senha_hash);
        
        if ($stmt->execute()) {
            $success = 'Funcionário adicionado com sucesso';
        } else {
            $error = 'Erro ao adicionar funcionário: ' . $conn->error;
        }
    }
}

// Buscar funcionários
$search = sanitize_input($_GET['search'] ?? '');
$sql = "SELECT * FROM usuarios WHERE nivel_acesso = 'funcionario'";

if (!empty($search)) {
    $search_term = '%' . $search . '%';
    $sql .= " AND (nome_completo LIKE ? OR cpf LIKE ? OR email LIKE ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $search_term, $search_term, $search_term);
    $stmt->execute();
    $funcionarios = $stmt->get_result();
} else {
    $funcionarios = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funcionários - Sistema de RH</title>
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
            <li><a href="employees.php" class="active">👥 Funcionários</a></li>
            <li><a href="timesheet.php">⏰ Controle de Ponto</a></li>
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
                <h1>Gestão de Funcionários</h1>
                <p>Cadastre, edite e gerencie todos os funcionários</p>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Formulário de Adição -->
            <div class="card" style="margin-bottom: var(--spacing-2xl);">
                <div class="card-header">
                    <h3>Adicionar Novo Funcionário</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-grid">
                        <div class="form-group">
                            <label for="nome">Nome Completo *</label>
                            <input type="text" id="nome" name="nome" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="cpf">CPF *</label>
                            <input type="text" id="cpf" name="cpf" class="form-control" data-mask="cpf" placeholder="000.000.000-00" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="cargo">Cargo</label>
                            <input type="text" id="cargo" name="cargo" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="nivel_acesso">Nível de Acesso</label>
                            <select id="nivel_acesso" name="nivel_acesso" class="form-control">
                                <option value="funcionario">Funcionário</option>
                                <option value="coordenacao">Coordenação</option>
                                <option value="rh">RH</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                                <option value="ferias">Férias</option>
                                <option value="licenca">Licença</option>
                            </select>
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-primary">Adicionar Funcionário</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Busca -->
            <div style="margin-bottom: var(--spacing-lg);">
                <form method="GET" class="form-inline">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Buscar por nome, CPF ou email..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="form-control"
                        style="flex: 1; margin-right: var(--spacing-md);"
                    >
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </form>
            </div>

            <!-- Lista de Funcionários -->
            <div class="card">
                <div class="card-header">
                    <h3>Funcionários Cadastrados</h3>
                </div>
                <div class="card-body">
                    <?php if ($funcionarios->num_rows > 0): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>CPF</th>
                                    <th>Email</th>
                                    <th>Cargo</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $funcionarios->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['nome_completo']); ?></td>
                                        <td><?php echo format_cpf($row['cpf']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['cargo'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $row['status'] === 'ativo' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit-employee.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline">Editar</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center text-muted">Nenhum funcionário cadastrado</p>
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

        .form-inline {
            display: flex;
            gap: var(--spacing-md);
        }
    </style>

    <script src="../assets/js/main.js"></script>
</body>
</html>
