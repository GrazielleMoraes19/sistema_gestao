<?php
require_once '../config/functions.php';
requireEmployee();
require_once '../includes/header_employee.php';

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Atualizar status
if (isset($_GET['update_status'])) {
    $stmt = $db->prepare("UPDATE servicos SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['update_status'], $_GET['servico_id'], $userId]);
}

$servicos = $db->prepare("SELECT * FROM servicos WHERE user_id = ? ORDER BY FIELD(prioridade, 'urgente', 'alta', 'media', 'baixa'), data_limite ASC");
$servicos->execute([$userId]);
$servicos = $servicos->fetchAll();
?>

<div class="page-header">
    <h1>Meus Serviços</h1>
    <p>Tarefas e serviços atribuídos a você</p>
</div>

<?php if (empty($servicos)): ?>
<div class="card fade-in">
    <div class="card-body" style="padding: 60px; text-align: center;">
        <div style="font-size: 48px; color: var(--beige-dark); margin-bottom: 15px;">&#9881;</div>
        <h3 style="color: var(--brown);">Nenhum serviço atribuído</h3>
        <p style="color: var(--text-muted); margin-top: 8px;">Aguarde a atribuição de novas tarefas.</p>
    </div>
</div>
<?php else: ?>
<div class="stats-grid fade-in">
    <?php
    $counts = ['pendente' => 0, 'em_andamento' => 0, 'concluido' => 0, 'urgente' => 0];
    foreach ($servicos as $s) {
        $counts[$s['status']] = ($counts[$s['status']] ?? 0) + 1;
        if ($s['prioridade'] === 'urgente' && $s['status'] !== 'concluido') $counts['urgente']++;
    }
    ?>
    <div class="stat-card">
        <div class="stat-icon">&#9888;</div>
        <div class="stat-content">
            <h4><?= $counts['pendente'] ?></h4>
            <p>Pendentes</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">&#9881;</div>
        <div class="stat-content">
            <h4><?= $counts['em_andamento'] ?></h4>
            <p>Em Andamento</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">&#10004;</div>
        <div class="stat-content">
            <h4><?= $counts['concluido'] ?></h4>
            <p>Concluídos</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color: #8a4438;">&#9888;</div>
        <div class="stat-content">
            <h4><?= $counts['urgente'] ?></h4>
            <p>Urgentes</p>
        </div>
    </div>
</div>

<div class="card fade-in">
    <div class="card-body" style="padding: 0;">
        <table class="table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Data Limite</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($servicos as $s): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($s['titulo']) ?></strong>
                        <?php if ($s['descricao']): ?>
                            <br><small style="color: var(--text-muted);"><?= htmlspecialchars($s['descricao']) ?></small>
                        <?php endif; ?>
                    </td>
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
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>