<?php
session_start();
require_once __DIR__ . '/database.php';

// Hashids simplificado para ofuscação de IDs
class ObfuscatedID {
    private static $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private static $salt = 'RH-System-2026-Secure';

    public static function encode($id) {
        $num = intval($id) * 7 + 13;
        $result = '';
        $alphabet = self::$alphabet;
        while ($num > 0) {
            $result = $alphabet[$num % 62] . $result;
            $num = intval($num / 62);
        }
        // Adicionar prefixo e sufixo para ofuscação extra
        $prefix = substr(str_shuffle(self::$salt), 0, 3);
        $suffix = substr(str_shuffle(self::$salt), 0, 2);
        return strtoupper($prefix . '-' . $result . $suffix);
    }

    public static function decode($encoded) {
        $clean = preg_replace('/^[A-Z]{3}-|[A-Z]{2}$/', '', $encoded);
        $num = 0;
        $alphabet = self::$alphabet;
        $len = strlen($clean);
        for ($i = 0; $i < $len; $i++) {
            $pos = stripos($alphabet, $clean[$i]);
            if ($pos !== false) {
                $num = $num * 62 + $pos;
            }
        }
        return intval(($num - 13) / 7);
    }
}

function redirect($path) {
    header("Location: $path");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['nivel_acesso']) && 
           in_array($_SESSION['nivel_acesso'], ['admin', 'rh', 'coordenacao']);
}

function isEmployee() {
    return isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] === 'funcionario';
}

function requireLogin() {
    if (!isLoggedIn()) redirect('../index.php');
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) redirect('../employee/dashboard.php');
}

function requireEmployee() {
    requireLogin();
    if (!isEmployee()) redirect('../admin/dashboard.php');
}

function formatCurrency($value) {
    return 'R$ ' . number_format(floatval($value), 2, ',', '.');
}

function formatDate($date) {
    if (empty($date)) return '';
    return date('d/m/Y', strtotime($date));
}

function formatTime($time) {
    if (empty($time)) return '--:--';
    return date('H:i', strtotime($time));
}

function getMonthName($month) {
    $months = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];
    return $months[intval($month)] ?? '';
}

function getStatusBadge($status) {
    $classes = [
        'trabalhado' => 'badge-success',
        'nao_trabalhado' => 'badge-danger',
        'sem_registro' => 'badge-warning',
        'falta_justificada' => 'badge-info',
        'pendente' => 'badge-warning',
        'aprovado' => 'badge-success',
        'rejeitado' => 'badge-danger',
        'em_andamento' => 'badge-info',
        'concluido' => 'badge-success',
        'cancelado' => 'badge-secondary',
        'aberto' => 'badge-danger',
        'em_analise' => 'badge-warning',
        'resolvido' => 'badge-success',
        'fechado' => 'badge-secondary'
    ];
    $class = $classes[$status] ?? 'badge-secondary';
    return '<span class="badge ' . $class . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}
?>