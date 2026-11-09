<?php
require 'config.php';
exigir_admin();

// --------------------------------------------------------------------------
// Consolidar um mês (soma as horas e os gastos reais do banco) ou excluir
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['acao'] ?? '') === 'excluir') {
        $pdo->prepare('DELETE FROM fechamentos WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        definir_mensagem('Fechamento excluído. O período pode ser consolidado novamente.');
        redirecionar('fechamento.php');
    }

    $mes = (int) ($_POST['mes'] ?? 0);
    $ano = (int) ($_POST['ano'] ?? 0);
    $periodo = str_pad($mes, 2, '0', STR_PAD_LEFT) . '/' . $ano;

    $stmt = $pdo->prepare('SELECT id FROM fechamentos WHERE mes = ? AND ano = ?');
    $stmt->execute([$mes, $ano]);

    if ($mes < 1 || $mes > 12 || $ano < 2000) {
        definir_mensagem('Período inválido.', 'erro');
    } elseif ($stmt->fetch()) {
        definir_mensagem("Erro: O período de $periodo já foi fechado!", 'erro');
    } else {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(total_horas), 0) FROM apontamentos WHERE MONTH(data_hora) = ? AND YEAR(data_hora) = ?');
        $stmt->execute([$mes, $ano]);
        $total_horas = $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(valor), 0) FROM gastos WHERE MONTH(data) = ? AND YEAR(data) = ?');
        $stmt->execute([$mes, $ano]);
        $total_gastos = $stmt->fetchColumn();

        $pdo->prepare('INSERT INTO fechamentos (mes, ano, total_horas, total_gastos, usuario_id) VALUES (?, ?, ?, ?, ?)')
            ->execute([$mes, $ano, $total_horas, $total_gastos, $_SESSION['id_usuario']]);

        definir_mensagem("Fechamento de $periodo consolidado! Total: " . numero($total_horas) . 'h | Gastos: R$ ' . moeda($total_gastos));
    }
    redirecionar('fechamento.php');
}

$historico = $pdo->query(
    'SELECT f.*, u.nome AS responsavel
       FROM fechamentos f
       JOIN usuarios u ON u.id = f.usuario_id
      ORDER BY f.ano DESC, f.mes DESC'
)->fetchAll();

$mes_atual = (int) date('n');

$titulo = 'Fechamento Mensal';
require 'cabecalho.php';
?>
    <div class="container dashboard-container">
        <div class="header-painel">
            <h2>Fechamento Mensal da Frota</h2>
            <a href="painel.php" class="btn-sair">Voltar ao Painel</a>
        </div>

        <?php mostrar_mensagem(); ?>

        <div class="form-cadastro">
            <h3>Consolidar Período</h3>
            <p style="color: var(--text-main); font-size: 0.9rem; margin-bottom: 15px;">
                Soma todas as horas apontadas e todos os gastos lançados no mês escolhido e salva no histórico.
            </p>
            <form method="post" action="fechamento.php">
                <?= campo_csrf() ?>
                <div class="form-grid">
                    <div>
                        <label for="mes">Mês de Referência:</label>
                        <select id="mes" name="mes" required class="input-select">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $i === $mes_atual ? 'selected' : '' ?>><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label for="ano">Ano de Referência:</label>
                        <input id="ano" type="number" name="ano" value="<?= date('Y') ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn-salvar btn-fechamento">Consolidar Dados</button>
            </form>
        </div>

        <hr class="divisor">

        <h3>Histórico de Fechamentos</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Total de Horas</th>
                        <th>Total de Gastos</th>
                        <th>Responsável</th>
                        <th>Fechado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $f): ?>
                        <tr>
                            <td data-label="Período"><strong><?= str_pad($f['mes'], 2, '0', STR_PAD_LEFT) ?>/<?= $f['ano'] ?></strong></td>
                            <td data-label="Horas"><?= numero($f['total_horas']) ?>h</td>
                            <td data-label="Gastos">R$ <?= moeda($f['total_gastos']) ?></td>
                            <td data-label="Responsável"><?= e($f['responsavel']) ?></td>
                            <td data-label="Fechado em"><?= data_br($f['criado_em'], true) ?></td>
                            <td>
                                <form method="post" action="fechamento.php" onsubmit="return confirm('Excluir este fechamento?');">
                                    <?= campo_csrf() ?>
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                    <button type="submit" class="btn-editar" style="width: auto; background: #fee2e2; color: #b91c1c;">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$historico): ?>
                        <tr><td colspan="6" class="text-center" style="color: #64748b;">Nenhum fechamento realizado até o momento.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
