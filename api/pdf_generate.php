<?php
require_once '../config/functions.php';
requireLogin();

$db = Database::getInstance()->getConnection();
$tipo = $_GET['tipo'] ?? '';
$userId = $_SESSION['user_id'];
$userLevel = $_SESSION['nivel_acesso'];

// Buscar dados do usuário logado
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch();

if ($tipo === 'holerite') {
    $folhaId = intval($_GET['folha_id'] ?? 0);
    
    if ($userLevel === 'funcionario') {
        $stmt = $db->prepare("SELECT fp.*, u.nome_completo, u.obfuscated_id, u.cargo, u.cpf FROM folha_pagamento fp JOIN users u ON fp.user_id = u.id WHERE fp.id = ? AND fp.user_id = ?");
        $stmt->execute([$folhaId, $userId]);
    } else {
        $stmt = $db->prepare("SELECT fp.*, u.nome_completo, u.obfuscated_id, u.cargo, u.cpf FROM folha_pagamento fp JOIN users u ON fp.user_id = u.id WHERE fp.id = ?");
        $stmt->execute([$folhaId]);
    }
    $folha = $stmt->fetch();
    
    if (!$folha) die('Folha não encontrada');
    
    $stmt = $db->prepare("SELECT * FROM descontos WHERE folha_id = ?");
    $stmt->execute([$folha['id']]);
    $descontos = $stmt->fetchAll();
    
    $stmt = $db->prepare("SELECT * FROM beneficios WHERE folha_id = ?");
    $stmt->execute([$folha['id']]);
    $beneficios = $stmt->fetchAll();
    
    $mes = $folha['mes_referencia'];
    $ano = $folha['ano_referencia'];
    $mesNome = getMonthName($mes);
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Holerite - <?= htmlspecialchars($folha['nome_completo']) ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Arial, sans-serif; padding: 30px; color: #2C1E10; background: white; }
            .holerite { border: 2px solid #4A3728; padding: 30px; max-width: 700px; margin: 0 auto; }
            .header { text-align: center; border-bottom: 2px solid #6B5340; padding-bottom: 20px; margin-bottom: 20px; }
            .header h1 { font-size: 20px; color: #4A3728; }
            .header p { font-size: 13px; color: #8B7D6B; }
            .info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; font-size: 14px; }
            .info strong { color: #6B5340; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 8px 12px; border: 1px solid #D4C5A9; font-size: 13px; text-align: left; }
            th { background: #F5F0E8; font-weight: 700; }
            .total { display: flex; justify-content: space-between; padding: 15px 20px; background: #4A3728; color: white; font-weight: 700; font-size: 16px; margin-top: 20px; }
            .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #D4C5A9; font-size: 11px; color: #8B7D6B; text-align: center; }
            @media print { body { padding: 0; } }
        </style>
    </head>
    <body>
        <div class="holerite">
            <div class="header">
                <h1>Contracheque - <?= $mesNome ?>/<?= $ano ?></h1>
                <p>Sistema de Gestão RH - Documento para impressão</p>
            </div>
            <div class="info">
                <div><strong>Funcionário:</strong> <?= htmlspecialchars($folha['nome_completo']) ?></div>
                <div><strong>ID:</strong> <?= $folha['obfuscated_id'] ?></div>
                <div><strong>Cargo:</strong> <?= htmlspecialchars($folha['cargo']) ?></div>
                <div><strong>CPF:</strong> <?= $folha['cpf'] ?></div>
                <div><strong>Período:</strong> <?= $mesNome ?>/<?= $ano ?></div>
                <div><strong>Emissão:</strong> <?= date('d/m/Y') ?></div>
            </div>
            <table>
                <thead><tr><th colspan="2">Proventos</th><th colspan="2">Descontos</th></tr></thead>
                <tbody>
                    <tr><td>Salário Base</td><td style="text-align:right;">R$ <?= number_format($folha['valor_bruto'], 2, ',', '.') ?></td><td colspan="2"></td></tr>
                    <?php foreach ($beneficios as $b): ?>
                    <tr><td><?= htmlspecialchars($b['descricao']) ?></td><td style="text-align:right; color: #4a6a42;">+ R$ <?= number_format($b['valor'], 2, ',', '.') ?></td><td colspan="2"></td></tr>
                    <?php endforeach; ?>
                    <?php foreach ($descontos as $d): ?>
                    <tr><td></td><td></td><td><?= htmlspecialchars($d['descricao']) ?></td><td style="text-align:right; color: #8a4438;">- R$ <?= number_format($d['valor'], 2, ',', '.') ?></td></tr>
                    <?php if ($d['justificativa']): ?>
                    <tr><td colspan="2"></td><td colspan="2" style="font-size: 12px; color: #8B7D6B; font-style: italic;"><?= htmlspecialchars($d['justificativa']) ?></td></tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="total">
                <span>Valor Líquido a Receber</span>
                <span>R$ <?= number_format($folha['valor_liquido'], 2, ',', '.') ?></span>
            </div>
            <div class="footer">
                Emitido em <?= date('d/m/Y H:i:s') ?> | Sistema de Gestão RH<br>
                Este documento é um comprovante válido para fins de comprovação de rendimentos.
            </div>
        </div>
        <script>window.onload = function() { window.print(); }</script>
    </body>
    </html>
    <?php
    exit;
}

if ($tipo === 'ponto_func' || $tipo === 'ponto') {
    $mes = intval($_GET['mes'] ?? date('m'));
    $ano = intval($_GET['ano'] ?? date('Y'));
    $mesNome = getMonthName($mes);
    
    if ($tipo === 'ponto_func') {
        $stmt = $db->prepare("SELECT pr.*, u.nome_completo, u.cargo FROM ponto_registro pr JOIN users u ON pr.user_id = u.id WHERE pr.user_id = ? AND MONTH(pr.data_registro) = ? AND YEAR(pr.data_registro) = ? ORDER BY pr.data_registro");
        $stmt->execute([$userId, $mes, $ano]);
        $nomeFunc = $currentUser['nome_completo'];
    } else {
        $stmt = $db->prepare("SELECT pr.*, u.nome_completo, u.cargo FROM ponto_registro pr JOIN users u ON pr.user_id = u.id WHERE MONTH(pr.data_registro) = ? AND YEAR(pr.data_registro) = ? ORDER BY u.nome_completo, pr.data_registro");
        $stmt->execute([$mes, $ano]);
        $nomeFunc = 'Todos os Funcionários';
    }
    $registros = $stmt->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Folha de Ponto - <?= htmlspecialchars($nomeFunc) ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Arial, sans-serif; padding: 30px; color: #2C1E10; background: white; }
            .report { border: 2px solid #4A3728; padding: 30px; }
            .header { text-align: center; border-bottom: 2px solid #6B5340; padding-bottom: 20px; margin-bottom: 20px; }
            .header h1 { font-size: 20px; color: #4A3728; }
            .info { font-size: 14px; margin-bottom: 20px; }
            .info strong { color: #6B5340; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 8px 12px; border: 1px solid #D4C5A9; font-size: 13px; text-align: left; }
            th { background: #F5F0E8; font-weight: 700; }
            .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #D4C5A9; font-size: 11px; color: #8B7D6B; text-align: center; }
            .badge { padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
            .badge-success { background: #e8f0e4; color: #4a6a42; }
            .badge-danger { background: #f0e4e2; color: #8a4438; }
            .badge-warning { background: #f5ede0; color: #8a6a32; }
            .badge-info { background: #e2ecf0; color: #3a5a6a; }
            @media print { body { padding: 0; } }
        </style>
    </head>
    <body>
        <div class="report">
            <div class="header">
                <h1>Folha de Ponto - <?= $mesNome ?>/<?= $ano ?></h1>
                <p>Funcionário: <?= htmlspecialchars($nomeFunc) ?></p>
            </div>
            <table>
                <thead>
                    <tr><th>Data</th><th>Funcionário</th><th>Entrada</th><th>Saída</th><th>H. Extras</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $r): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($r['data_registro'])) ?></td>
                        <td><?= htmlspecialchars($r['nome_completo']) ?></td>
                        <td><?= $r['entrada_manha'] ? date('H:i', strtotime($r['entrada_manha'])) : '-' ?></td>
                        <td><?= $r['saida_tarde'] ? date('H:i', strtotime($r['saida_tarde'])) : '-' ?></td>
                        <td><?= $r['horas_extras'] > 0 ? $r['horas_extras'] . 'h' : '-' ?></td>
                        <td>
                            <?php
                            $badgeClass = match($r['status']) {
                                'trabalhado' => 'badge-success',
                                'nao_trabalhado' => 'badge-danger',
                                'sem_registro' => 'badge-warning',
                                default => 'badge-info'
                            };
                            echo "<span class='badge $badgeClass'>" . ucfirst(str_replace('_', ' ', $r['status'])) . "</span>";
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="footer">
                Emitido em <?= date('d/m/Y H:i:s') ?> | Sistema de Gestão RH
            </div>
        </div>
        <script>window.onload = function() { window.print(); }</script>
    </body>
    </html>
    <?php
    exit;
}

die('Tipo de PDF inválido');
?>