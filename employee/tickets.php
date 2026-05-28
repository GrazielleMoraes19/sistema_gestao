<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'funcionario') {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';
require_once '../config/functions.php';

$usuario_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Processar novo chamado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_chamado'])) {
    $tipo = sanitize_input($_POST['tipo']);
    $assunto = sanitize_input($_POST['assunto']);
    $descricao = sanitize_input($_POST['descricao']);
    
    if (empty($assunto) || empty($descricao)) {
        $error = 'Preencha todos os campos obrigatórios';
    } else {
        $sql = "INSERT INTO chamados (usuario_id, tipo, assunto, descricao, status) VALUES (?, ?, ?, ?, 'aberto')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isss', $usuario_id, $tipo, $assunto, $descricao);
        
        if ($stmt->execute()) {
            $success = 'Chamado criado com sucesso';
        } else {
            $error = 'Erro ao criar chamado';
        }
    }
}

// Buscar chamados
$chamados = $conn->query("SELECT * FROM chamados WHERE usuario_id = $usuario_id ORDER BY data_criacao DESC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Chamados - Sistema de RH</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Barra de Navegação -->
    <header style="background-color: var(--color-white); box-shadow: var(--shadow-md); padding: var(--spacing-lg);">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0;">Meus Chamados</h2>
                <p style="margin: var(--spacing-sm) 0 0; color: var(--color-gray);">Reporte erros e acompanhe suas solicitações</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-outline">← Voltar</a>
                <a href="../logout.php" class="btn btn-outline" onclick="return confirm('Deseja sair?')">🚪 Sair</a>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="container" style="padding: var(--spacing-lg); margin-top: var(--spacing-lg);">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Formulário de Novo Chamado -->
        <div class="card" style="margin-bottom: var(--spacing-2xl);">
            <div class="card-header">
                <h3>Criar Novo Chamado</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="criar_chamado" value="1">

                    <div class="form-group">
                        <label for="tipo">Tipo de Chamado *</label>
                        <select id="tipo" name="tipo" class="form-control" required>
                            <option value="erro_ponto">Erro no Ponto</option>
                            <option value="erro_folha">Erro na Folha</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="assunto">Assunto *</label>
                        <input type="text" id="assunto" name="assunto" class="form-control" placeholder="Resumo do problema" required>
                    </div>

                    <div class="form-group">
                        <label for="descricao">Descrição *</label>
                        <textarea id="descricao" name="descricao" class="form-control" rows="5" placeholder="Descreva detalhadamente o problema..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Criar Chamado</button>
                </form>
            </div>
        </div>

        <!-- Lista de Chamados -->
        <div class="card">
            <div class="card-header">
                <h3>Meus Chamados</h3>
            </div>
            <div class="card-body">
                <?php if ($chamados->num_rows > 0): ?>
                    <div style="display: grid; gap: var(--spacing-lg);">
                        <?php while ($row = $chamados->fetch_assoc()): ?>
                            <div style="border: 1px solid var(--color-border); border-radius: var(--border-radius); padding: var(--spacing-lg);">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--spacing-md);">
                                    <div>
                                        <h4 style="margin: 0 0 var(--spacing-sm);"><?php echo htmlspecialchars($row['assunto']); ?></h4>
                                        <p style="margin: 0; color: var(--color-gray); font-size: var(--font-size-sm);">
                                            #<?php echo $row['id']; ?> • <?php echo formatDate($row['data_criacao']); ?>
                                        </p>
                                    </div>
                                    <span class="badge badge-<?php echo $row['status'] === 'aberto' ? 'danger' : ($row['status'] === 'resolvido' ? 'success' : 'warning'); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                    </span>
                                </div>

                                <p style="margin: 0 0 var(--spacing-md); color: var(--color-gray);">
                                    <?php echo nl2br(htmlspecialchars($row['descricao'])); ?>
                                </p>

                                <?php if ($row['resposta']): ?>
                                    <div style="background-color: var(--color-light); padding: var(--spacing-md); border-radius: var(--border-radius); margin-top: var(--spacing-md);">
                                        <p style="margin: 0 0 var(--spacing-sm); font-weight: 500; color: var(--color-primary);">Resposta do Administrador:</p>
                                        <p style="margin: 0; color: var(--color-dark);">
                                            <?php echo nl2br(htmlspecialchars($row['resposta'])); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">Você não possui chamados abertos</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
