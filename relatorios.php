<?php
require 'config.php';
exigir_admin();

// Filtros (vêm pela URL)
$inicio    = $_GET['inicio'] ?? '';
$fim       = $_GET['fim'] ?? '';
$pesquisar = trim($_GET['pesquisar'] ?? '');

$sql = 'SELECT a.*, u.nome AS operador, m.nome AS maquina
          FROM apontamentos a
          JOIN usuarios u ON u.id = a.usuario_id
          JOIN maquinas m ON m.id = a.maquina_id
         WHERE 1 = 1';
$params = [];

if ($inicio !== '') {
    $sql .= ' AND DATE(a.data_hora) >= ?';
    $params[] = $inicio;
}
if ($fim !== '') {
    $sql .= ' AND DATE(a.data_hora) <= ?';
    $params[] = $fim;
}
if ($pesquisar !== '') {
    $sql .= ' AND (u.nome LIKE ? OR m.nome LIKE ? OR a.servico LIKE ?)';
    array_push($params, "%$pesquisar%", "%$pesquisar%", "%$pesquisar%");
}
$sql .= ' ORDER BY a.data_hora DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll();

// --------------------------------------------------------------------------
// Exportar CSV (relatorios.php?exportar=1&...mesmos filtros)
// --------------------------------------------------------------------------
if (isset($_GET['exportar'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="relatorio_hp_horas.csv"');

    $saida = fopen('php://output', 'w');
    fwrite($saida, "\xEF\xBB\xBF"); // BOM para o Excel reconhecer acentos
    fputcsv($saida, ['Data', 'Operador', 'Máquina', 'Serviço', 'H. Inicial', 'H. Final', 'Total Horas'], ';');
    foreach ($registros as $r) {
        fputcsv($saida, [
            data_br($r['data_hora'], true),
            $r['operador'],
            $r['maquina'],
            $r['servico'],
            numero($r['h_inicial']),
            numero($r['h_final']),
            numero($r['total_horas']),
        ], ';');
    }
    fclose($saida);
    exit;
}

$total_horas = array_sum(array_column($registros, 'total_horas'));
$link_csv = 'relatorios.php?' . http_build_query(['inicio' => $inicio, 'fim' => $fim, 'pesquisar' => $pesquisar, 'exportar' => 1]);

$titulo = 'Relatórios';
require 'cabecalho.php';
?>

<div class="container dashboard-container">
    <div class="header-painel">
        <h2>Relatório de Produção</h2>
        <a href="painel.php" class="btn-sair">Voltar</a>
    </div>

    <form method="get" class="search-form">
        <div class="form-group">
            <label for="inicio">De:</label>
            <input type="date" id="inicio" name="inicio" value="<?= e($inicio) ?>" style="padding: 10px;">
        </div>

        <div class="form-group">
            <label for="fim">Até:</label>
            <input type="date" id="fim" name="fim" value="<?= e($fim) ?>" style="padding: 10px;">
        </div>

        <div class="form-group" style="flex: 2;">
            <label for="pesquisar">Buscar</label>
            <input type="text" id="pesquisar" name="pesquisar" value="<?= e($pesquisar) ?>" placeholder="Operador, máquina ou serviço..." style="padding: 10px;">
        </div>

        <button type="submit" class="btn-login" style="width: auto; padding: 10px 20px; height: 42px;">Buscar</button>
    </form>

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
        <strong><?= count($registros) ?> registro(s) — <?= numero($total_horas) ?>h no total</strong>
        <a href="<?= e($link_csv) ?>" class="btn-excel">📥 Baixar CSV Filtrado</a>
    </div>

    <div style="width: 100%; overflow-x: auto; background: white; border-radius: 8px;">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Operador</th>
                    <th>Máquina</th>
                    <th>Serviço</th>
                    <th>H. Inicial</th>
                    <th>H. Final</th>
                    <th>Total Horas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $r): ?>
                    <tr>
                        <td data-label="Data"><?= data_br($r['data_hora']) ?></td>
                        <td data-label="Operador"><?= e($r['operador']) ?></td>
                        <td data-label="Máquina"><?= e($r['maquina']) ?></td>
                        <td data-label="Serviço"><?= e($r['servico']) ?></td>
                        <td data-label="H. Inicial"><?= numero($r['h_inicial']) ?></td>
                        <td data-label="H. Final"><?= numero($r['h_final']) ?></td>
                        <td data-label="Total"><strong><?= numero($r['total_horas']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$registros): ?>
                    <tr><td colspan="7" style="text-align: center; color: #ef4444; font-weight: bold;">Nenhum registro encontrado neste período.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
