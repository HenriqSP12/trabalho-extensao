<?php
require 'config.php';
exigir_admin();

// Filtros (vêm pela URL: ?mes=11&ano=2026&maquina_id=&busca=)
$mes        = (int) ($_GET['mes'] ?? date('n'));
$ano        = (int) ($_GET['ano'] ?? date('Y'));
$maquina_id = (int) ($_GET['maquina_id'] ?? 0);
$busca      = trim($_GET['busca'] ?? '');

$sql = 'SELECT a.*, u.nome AS operador, m.nome AS maquina
          FROM apontamentos a
          JOIN usuarios u ON u.id = a.usuario_id
          JOIN maquinas m ON m.id = a.maquina_id
         WHERE MONTH(a.data_hora) = ? AND YEAR(a.data_hora) = ?';
$params = [$mes, $ano];

if ($maquina_id) {
    $sql .= ' AND a.maquina_id = ?';
    $params[] = $maquina_id;
}
if ($busca !== '') {
    $sql .= ' AND (a.servico LIKE ? OR u.nome LIKE ? OR m.nome LIKE ?)';
    array_push($params, "%$busca%", "%$busca%", "%$busca%");
}
$sql .= ' ORDER BY a.data_hora DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll();

$total_horas = array_sum(array_column($registros, 'total_horas'));
$maquinas = $pdo->query('SELECT id, nome FROM maquinas WHERE tem_horimetro = 1 ORDER BY nome')->fetchAll();

$titulo = 'Dashboard de Horas';
require 'cabecalho.php';
?>
    <div class="container dashboard-container dashboard-compacto">
        <div class="header-painel">
            <h2>Gerenciamento de Horas</h2>
            <a href="painel.php" class="btn-sair">Voltar ao Painel</a>
        </div>

        <form method="get" class="search-form" style="align-items: flex-end;">
            <div class="form-group" style="min-width: 80px;">
                <label for="mes">Mês</label>
                <select id="mes" name="mes" class="input-select" style="margin-bottom: 0;">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $i === $mes ? 'selected' : '' ?>><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group" style="min-width: 90px;">
                <label for="ano">Ano</label>
                <input type="number" id="ano" name="ano" value="<?= $ano ?>" required style="margin-bottom: 0;">
            </div>

            <div class="form-group" style="min-width: 170px;">
                <label for="maquina_id">Máquina / Veículo</label>
                <select id="maquina_id" name="maquina_id" class="input-select" style="margin-bottom: 0;">
                    <option value="">Todas as Máquinas</option>
                    <?php foreach ($maquinas as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= $m['id'] == $maquina_id ? 'selected' : '' ?>><?= e($m['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group flex-expand">
                <label for="busca">Buscar</label>
                <input type="text" id="busca" name="busca" value="<?= e($busca) ?>" placeholder="Cliente ou operador..." style="margin-bottom: 0;">
            </div>

            <button type="submit" class="btn-filtrar" style="height: 47px;">Filtrar</button>
        </form>

        <div class="summary-cards">
            <div class="card card-total card-compacto">
                <h3 style="margin-bottom: 5px;">Total de Horas no Período</h3>
                <div class="value"><?= numero($total_horas) ?>h</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="tabela-horas">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Operador</th>
                        <th>Máquina</th>
                        <th>Serviço / Cliente</th>
                        <th>Total (Horas)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><?= data_br($r['data_hora'], true) ?></td>
                            <td><?= e($r['operador']) ?></td>
                            <td><?= e($r['maquina']) ?></td>
                            <td><?= e($r['servico']) ?></td>
                            <td><strong><?= numero($r['total_horas']) ?>h</strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$registros): ?>
                        <tr><td colspan="5" class="text-center">Nenhum registro encontrado neste período.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
