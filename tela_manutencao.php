<?php
require 'config.php';
exigir_admin();

// --------------------------------------------------------------------------
// Registrar troca de óleo (baixa de manutenção)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maquina_id = (int) ($_POST['maquina_id'] ?? 0);
    $horimetro  = (float) str_replace(',', '.', $_POST['horimetro'] ?? '');
    $observacao = trim($_POST['observacao'] ?? '');

    if (!$maquina_id || $horimetro <= 0) {
        definir_mensagem('Informe o horímetro da troca.', 'erro');
    } else {
        $pdo->prepare('INSERT INTO manutencoes (maquina_id, usuario_id, horimetro, observacao) VALUES (?, ?, ?, ?)')
            ->execute([$maquina_id, $_SESSION['id_usuario'], $horimetro, $observacao]);

        // A próxima troca passa a contar a partir deste horímetro
        $pdo->prepare('UPDATE maquinas SET ultima_troca = ?, horimetro_atual = GREATEST(horimetro_atual, ?) WHERE id = ?')
            ->execute([$horimetro, $horimetro, $maquina_id]);

        definir_mensagem('Baixa de manutenção registrada com sucesso!');
    }
    redirecionar('tela_manutencao.php');
}

// --------------------------------------------------------------------------
// Situação de cada máquina (críticas e em atenção aparecem primeiro)
// --------------------------------------------------------------------------
$frota = $pdo->query(
    'SELECT *,
            ultima_troca + intervalo_troca                    AS proxima_troca,
            (ultima_troca + intervalo_troca) - horimetro_atual AS restantes
       FROM maquinas
      WHERE tem_horimetro = 1
      ORDER BY restantes'
)->fetchAll();

$historico = $pdo->query(
    'SELECT mt.*, m.nome AS maquina, u.nome AS responsavel
       FROM manutencoes mt
       JOIN maquinas m ON m.id = mt.maquina_id
       JOIN usuarios u ON u.id = mt.usuario_id
      ORDER BY mt.data_hora DESC
      LIMIT 10'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manutenção de Frota - HP Terraplenagem</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="media/favicon.ico" type="image/x-icon">
    <meta name="theme-color" content="#f59e0b">
</head>
<body>
    <div class="container dashboard-container">
        <div class="header-painel">
            <h2>Manutenção</h2>
            <a href="painel.php" class="btn-sair">Voltar ao Painel</a>
        </div>

        <?php mostrar_mensagem(); ?>

        <div class="table-responsive" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <table>
                <thead>
                    <tr>
                        <th>Máquina / Veículo</th>
                        <th>Horímetro Atual</th>
                        <th>Próxima Troca</th>
                        <th>Horas Restantes</th>
                        <th>Status</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($frota as $m):
                        if ($m['restantes'] <= 0) {
                            $status = 'CRÍTICO'; $badge = 'badge-critico';
                        } elseif ($m['restantes'] <= $m['margem_aviso']) {
                            $status = 'ATENÇÃO'; $badge = 'badge-atencao';
                        } else {
                            $status = 'OK'; $badge = 'badge-ok';
                        }
                    ?>
                        <tr>
                            <td data-label="Máquina"><strong><?= e($m['nome']) ?></strong></td>
                            <td data-label="Horímetro"><?= numero($m['horimetro_atual']) ?> h</td>
                            <td data-label="Próxima troca"><?= numero($m['proxima_troca']) ?> h</td>
                            <td data-label="Restantes">
                                <?php if ($m['restantes'] <= 0): ?>
                                    <span style="color:#ef4444; font-weight:bold;">Vencido há <?= numero(abs($m['restantes'])) ?> h</span>
                                <?php else: ?>
                                    <?= numero($m['restantes']) ?> h
                                <?php endif; ?>
                            </td>
                            <td data-label="Status"><span class="badge <?= $badge ?>"><?= $status ?></span></td>
                            <td>
                                <button type="button" class="btn-editar btn-baixa"
                                        data-id="<?= $m['id'] ?>"
                                        data-nome="<?= e($m['nome']) ?>"
                                        data-horimetro="<?= $m['horimetro_atual'] ?>">Dar Baixa</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 style="margin-top: 30px;">Últimas trocas de óleo</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Data</th><th>Máquina</th><th>Horímetro</th><th>Observação</th><th>Responsável</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $h): ?>
                        <tr>
                            <td data-label="Data"><?= data_br($h['data_hora'], true) ?></td>
                            <td data-label="Máquina"><?= e($h['maquina']) ?></td>
                            <td data-label="Horímetro"><?= numero($h['horimetro']) ?> h</td>
                            <td data-label="Observação"><?= e($h['observacao']) ?: '-' ?></td>
                            <td data-label="Responsável"><?= e($h['responsavel']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$historico): ?>
                        <tr><td colspan="5" class="text-center">Nenhuma troca registrada ainda.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para Baixa de Manutenção -->
    <div id="modalBaixa" class="modal-baixa">
        <div class="modal-content">
            <h3>Registrar Troca de Óleo</h3>
            <form method="post" action="tela_manutencao.php">
                <input type="hidden" name="maquina_id" id="modal_maquina_id">

                <div class="form-group">
                    <label>Máquina:</label>
                    <input type="text" id="modal_maquina_nome" readonly style="background-color: #f1f5f9;">
                </div>

                <div class="form-group">
                    <label for="modal_horimetro">Horímetro no Momento da Troca *</label>
                    <input type="number" step="0.01" min="0" name="horimetro" id="modal_horimetro" required>
                </div>

                <div class="form-group">
                    <label for="modal_observacao">Observação (Opcional)</label>
                    <input type="text" name="observacao" id="modal_observacao" maxlength="255" placeholder="Ex: Trocado filtro e óleo">
                </div>

                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn-salvar">Confirmar Baixa</button>
                    <button type="button" id="btn-cancelar" class="btn-sair" style="background:#64748b;">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modalBaixa');

        document.querySelectorAll('.btn-baixa').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('modal_maquina_id').value   = btn.dataset.id;
                document.getElementById('modal_maquina_nome').value = btn.dataset.nome;
                document.getElementById('modal_horimetro').value    = btn.dataset.horimetro;
                document.getElementById('modal_observacao').value   = '';
                modal.style.display = 'flex';
            });
        });

        document.getElementById('btn-cancelar').addEventListener('click', function () {
            modal.style.display = 'none';
        });
    </script>
</body>
</html>
