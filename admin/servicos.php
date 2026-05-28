<?php
require_once '../config/functions.php';
requireAdmin();
require_once '../includes/header_admin.php';

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_servico'])) {
    $stmt = $db->prepare("INSERT INTO servicos (user_id, titulo, descricao, prioridade, data_limite, criado_por) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['user_id'], $_POST['titulo'], $_POST['descricao'], $_POST['prioridade'], $_POST['data_limite'] ?: null, $_SESSION['user_id']]);
    $success = 'Serviço atribuído com sucesso!';
}

if (isset($_GET['update_status'])) {
    $stmt = $db->prepare("UPDATE servicos SET status = ? WHERE id = ?");
    $stmt->execute([$_GET['update_status'], $_GET['servico_id']]);
}

$stmt = $db->query("SELECT id, nome_completo FROM users WHERE nivel_acesso = 'funcionario' ORDER BY nome_completo");
$funcionarios = $stmt->fetchAll();

$busca = $_GET['busca'] ?? '';
$sql = "SELECT s.*, u.nome_completo as func_nome, u2.nome_completo as criador_nome 
        FROM servicos s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN users u2 ON s.criado_por = u2.id";
$params = [];
if ($busca) {
    $sql .= " WHERE s.titulo LIKE ? OR u.nome_completo LIKE ?";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}
$sql .= " ORDER BY s.data_criacao DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$servicos = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Gestão de Serviços</h1>
    <p>Atribuição e acompanhamento de tarefas dos colaboradores</p>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card fade-in" style="margin-bottom: 25px;">
    <div class="card-header">
        <h3>Atribuir Novo Serviço</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="criar_servico" value="1">
            <div class="grid-2">
                <div class="form-group">
                    <label>Colaborador</label>
                    <select name="user_id" class="form-control" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($funcionarios as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Prioridade</label>
                    <select name="prioridade" class="form-control">
                        <option value="baixa">Baixa</option>
                        <option value="media" selected>Média</option>
                        <option value="alta">Alta</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Título</label>
                <input type="text" name="titulo" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Data Limite</label>
                <input type="date" name="data_limite" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Atribuir Serviço</button>
        </form>
    </div>
</div>

<div class="card fade-in">
    <div class="card-header">
        <h3>Serviços Atribuídos</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Colaborador</th>
                        <th>Prioridade</th>
                        <th>Status</th>
                        <th>Limite</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicos as $s): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($s['titulo']) ?></strong>
                            <?php if ($s['descricao']): ?><br><small style="color: var(--text-muted);"><?= htmlspecialchars(substr($s['descricao'], 0, 60)) ?>...</small><?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($s['func_nome']) ?></td>
                        <td><?= getStatusBadge($s['prioridade']) ?></td>
                        <td><?= getStatusBadge($s['status']) ?></td>
                        <td><?= $s['data_limite'] ? formatDate($s['data_limite']) : '-' ?></td>
                        <td>
                            <?php if ($s['status'] === 'pendente'): ?>
                                <a href="?update_status=em_andamento&servico_id=<?= $s['id'] ?>" class="btn btn-sm btn-secondary">Iniciar</a>
                            <?php elseif ($s['status'] === 'em_andamento'): ?>
                                <a href="?update_status=concluido&servico_id=<?= $s['id'] ?>" class="btn btn-sm btn-success">Concluir</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>