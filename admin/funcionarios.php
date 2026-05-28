<?php
require_once '../config/functions.php';
requireAdmin();
require_once '../includes/header_admin.php';

$db = Database::getInstance()->getConnection();
$success = $_GET['success'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome_completo']);
    $cpf = preg_replace('/\D/', '', $_POST['cpf']);
    $nascimento = $_POST['data_nascimento'];
    $cargo = trim($_POST['cargo']);
    $email = trim($_POST['email']);
    $nivel = $_POST['nivel_acesso'];
    $senha = $_POST['senha'] ?: 'password';

    if ($nome && $cpf && $nascimento && $cargo && $email) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $next_id = 0;
        $stmt = $db->query("SELECT MAX(id) as max_id FROM users");
        $row = $stmt->fetch();
        $next_id = $row['max_id'] + 1;
        $obfuscated = ObfuscatedID::encode($next_id);

        $stmt = $db->prepare("INSERT INTO users (obfuscated_id, nome_completo, cpf, data_nascimento, cargo, email, senha_hash, nivel_acesso) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$obfuscated, $nome, $cpf, $nascimento, $cargo, $email, $senha_hash, $nivel]);
        $success = 'Funcionário cadastrado com sucesso!';
    }
}

// Listar funcionários
$filtro = $_GET['filtro'] ?? '';
$sql = "SELECT * FROM users WHERE nivel_acesso = 'funcionario'";
$params = [];
if ($filtro) {
    $sql .= " AND nome_completo LIKE ?";
    $params[] = "%$filtro%";
}
$sql .= " ORDER BY nome_completo ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$funcionarios = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Gestão de Funcionários</h1>
    <p>Cadastro e gerenciamento de colaboradores</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card fade-in" style="margin-bottom: 25px;">
    <div class="card-header">
        <h3>Novo Funcionário</h3>
    </div>
    <div class="card-body">
        <form method="POST" data-validate>
            <div class="grid-2">
                <div class="form-group">
                    <label>Nome Completo</label>
                    <input type="text" name="nome_completo" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>CPF</label>
                    <input type="text" name="cpf" class="form-control cpf-input" required maxlength="14">
                </div>
                <div class="form-group">
                    <label>Data de Nascimento</label>
                    <input type="date" name="data_nascimento" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Cargo</label>
                    <input type="text" name="cargo" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nível de Acesso</label>
                    <select name="nivel_acesso" class="form-control">
                        <option value="funcionario">Funcionário</option>
                        <option value="rh">RH</option>
                        <option value="coordenacao">Coordenação</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Senha Inicial</label>
                    <input type="text" name="senha" class="form-control" placeholder="Padrão: password">
                </div>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Cadastrar Funcionário</button>
                <button type="reset" class="btn btn-secondary">Limpar</button>
            </div>
        </form>
    </div>
</div>

<div class="card fade-in">
    <div class="card-header">
        <h3>Funcionários Cadastrados</h3>
        <span style="font-size: 13px; color: var(--text-muted);"><?= count($funcionarios) ?> registros</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <div style="padding: 20px;">
            <input type="text" id="searchFunc" class="form-control" placeholder="Buscar por nome..." style="max-width: 350px;">
        </div>
        <div class="table-container">
            <table class="table" id="funcTable">
                <thead>
                    <tr>
                        <th>ID Ofuscado</th>
                        <th>Nome Completo</th>
                        <th>CPF</th>
                        <th>Cargo</th>
                        <th>E-mail</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($funcionarios as $f): ?>
                    <tr>
                        <td><code style="background: var(--cream); padding: 3px 8px; border-radius: 4px; font-size: 13px;"><?= $f['obfuscated_id'] ?></code></td>
                        <td><?= htmlspecialchars($f['nome_completo']) ?></td>
                        <td><?= $f['cpf'] ?></td>
                        <td><?= htmlspecialchars($f['cargo']) ?></td>
                        <td><?= htmlspecialchars($f['email']) ?></td>
                        <td><?= getStatusBadge($f['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        searchTable('searchFunc', 'funcTable');
    });
</script>

<?php require_once '../includes/footer.php'; ?>