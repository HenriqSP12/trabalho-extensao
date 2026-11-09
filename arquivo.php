<?php
// ==========================================================================
// Entrega o arquivo do CRLV somente para administradores logados.
// A pasta uploads/ fica bloqueada para acesso direto pelo navegador
// (ver uploads/.htaccess); todo download passa por aqui.
// Uso: arquivo.php?id=ID_DA_MAQUINA
// ==========================================================================
require 'config.php';
exigir_admin();

$stmt = $pdo->prepare('SELECT crlv_arquivo FROM maquinas WHERE id = ?');
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$nome = $stmt->fetchColumn();

// basename() garante que o caminho nunca saia da pasta uploads/
$caminho = $nome ? __DIR__ . '/uploads/' . basename($nome) : '';

if (!$caminho || !is_file($caminho)) {
    http_response_code(404);
    die('<h3 style="font-family:sans-serif">Arquivo não encontrado.</h3>');
}

$tipos = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];
$extensao = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));

header('Content-Type: ' . ($tipos[$extensao] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($caminho));
header('Content-Disposition: inline; filename="' . basename($caminho) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($caminho);
exit;
