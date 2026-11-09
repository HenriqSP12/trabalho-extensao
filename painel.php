<?php
require 'config.php';
exigir_login();

$id_usuario = $_SESSION['id_usuario'];
$eh_admin   = $_SESSION['tipo_usuario'] === 'admin';

// --------------------------------------------------------------------------
// OPERADOR: salvar apontamento de horas
// --------------------------------------------------------------------------
if (!$eh_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maquina_id = (int) ($_POST['maquina_id'] ?? 0);
    $servico    = trim($_POST['servico'] ?? '');
    $h_inicial  = (float) str_replace(',', '.', $_POST['h_inicial'] ?? '');
    $h_final    = (float) str_replace(',', '.', $_POST['h_final'] ?? '');

    $stmt = $pdo->prepare('SELECT id FROM maquinas WHERE id = ? AND tem_horimetro = 1');
    $stmt->execute([$maquina_id]);

    if (!$stmt->fetch() || $servico === '') {
        definir_mensagem('Preencha todos os campos corretamente.', 'erro');
    } elseif ($h_inicial < 0 || $h_final <= $h_inicial) {
        // Também barra valores negativos e apontamentos de 0 hora
        // (texto inválido vira 0 no (float), então cai aqui também)
        definir_mensagem('ERRO: Horímetro final deve ser maior que o inicial!', 'erro');
    } elseif ($h_final > 99999999) {
        // Limite da coluna DECIMAL(10,2) do banco
        definir_mensagem('ERRO: Valor de horímetro muito alto.', 'erro');
    } else {
        $total = round($h_final - $h_inicial, 2);

        $pdo->prepare('INSERT INTO apontamentos (usuario_id, maquina_id, servico, h_inicial, h_final, total_horas)
                       VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$id_usuario, $maquina_id, $servico, $h_inicial, $h_final, $total]);

        // Atualiza o horímetro da máquina (usado no controle de manutenção)
        $pdo->prepare('UPDATE maquinas SET horimetro_atual = GREATEST(horimetro_atual, ?) WHERE id = ?')
            ->execute([$h_final, $maquina_id]);

        definir_mensagem('Registro salvo! Total trabalhado: ' . numero($total) . ' hora(s).');
    }
    redirecionar('painel.php');
}

// --------------------------------------------------------------------------
// Dados para exibir
// --------------------------------------------------------------------------
if ($eh_admin) {
    // Máquinas que precisam de troca de óleo (vencidas ou perto de vencer)
    $alertas = $pdo->query(
        'SELECT nome, (ultima_troca + intervalo_troca) - horimetro_atual AS restantes
           FROM maquinas
          WHERE tem_horimetro = 1
            AND (ultima_troca + intervalo_troca) - horimetro_atual <= margem_aviso
          ORDER BY restantes'
    )->fetchAll();
} else {
    $maquinas = $pdo->query('SELECT id, nome, horimetro_atual FROM maquinas WHERE tem_horimetro = 1 ORDER BY nome')->fetchAll();

    $stmt = $pdo->prepare(
        'SELECT a.*, m.nome AS maquina
           FROM apontamentos a
           JOIN maquinas m ON m.id = a.maquina_id
          WHERE a.usuario_id = ?
          ORDER BY a.data_hora DESC, a.id DESC
          LIMIT 5'
    );
    $stmt->execute([$id_usuario]);
    $meus_registros = $stmt->fetchAll();
    $ultimo = $meus_registros[0] ?? null;
}

$primeiro_nome = explode(' ', $_SESSION['nome_usuario'])[0];

$titulo = 'Painel';
require 'cabecalho.php';
?>

    <div class="container">

        <div class="header-painel">
            <h3 style="margin: 0; text-align: left; font-size: 1.1rem; color: var(--text-dark);">Olá, <?= e($primeiro_nome) ?>!</h3>
            <a href="logout.php" class="btn-sair">Sair</a>
        </div>

        <?php mostrar_mensagem(); ?>

<?php if ($eh_admin): ?>
        <!-- ================= VISÃO DO ADMINISTRADOR ================= -->
        <h2>Painel Gerencial</h2>

        <div class="card-alertas">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="flex: 1; min-width: 200px;">
                    <h3 style="margin: 0; color: var(--text-dark); text-align: left; font-size: 1.1rem;">Controle de Manutenção</h3>
                    <p style="margin: 5px 0 0 0; font-size: 0.85rem; color: var(--text-main);">Acompanhamento de horímetro e troca de óleo.</p>
                </div>
                <a href="tela_manutencao.php" class="btn-manutencao" style="width: auto;">Acessar Manutenção</a>
            </div>

            <?php if ($alertas): ?>
                <div style="margin-top: 20px; background: var(--primary-light); border-left: 4px solid var(--primary); padding: 15px; border-radius: var(--radius-sm);">
                    <strong style="color: #b45309; font-size: 0.9rem;">Atenção! Máquinas necessitando de revisão:</strong>
                    <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #92400e; font-size: 0.9rem;">
                        <?php foreach ($alertas as $a): ?>
                            <li>
                                <strong><?= e($a['nome']) ?></strong> -
                                <?php if ($a['restantes'] <= 0): ?>
                                    <span style="color:#ef4444;">Vencida há <?= numero(abs($a['restantes'])) ?>h!</span>
                                <?php else: ?>
                                    Restam apenas <?= numero($a['restantes']) ?>h
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div style="margin-top: 20px; background: #f0fdf4; border-left: 4px solid #22c55e; padding: 15px; border-radius: var(--radius-sm); color: #15803d; font-size: 0.9rem;">
                    Todas as máquinas estão com a manutenção em dia.
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-area">
            <a href="dashboard_horas.php" class="btn-excel">Gerenciamento de Horas</a>
            <a href="controle_gastos.php" class="btn-excel">Controle de Gastos</a>
            <a href="relatorios.php" class="btn-excel">Planilha de Relatórios</a>
            <a href="documentos.php" class="btn-excel">Documentos de Veículos</a>

            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 25px 0;">

            <a href="fechamento.php" class="btn-fechamento">Fechamento Mensal</a>
            <a href="cadastro.php" class="btn-login" style="margin-top: 10px;">Cadastrar Novo Usuário</a>
        </div>

<?php else: ?>
        <!-- ================= VISÃO DO OPERADOR ================= -->
        <h2>Apontamento</h2>

        <div id="servico">
            <h3>Último serviço</h3>
            <p>Máquina: <span><?= $ultimo ? e($ultimo['maquina']) : 'Nenhum registro' ?></span></p>
            <p>Serviço: <span><?= $ultimo ? e($ultimo['servico']) : '-' ?></span></p>
            <?php if ($ultimo): ?>
                <p>Data: <span><?= data_br($ultimo['data_hora'], true) ?> — <?= numero($ultimo['total_horas']) ?>h</span></p>
            <?php endif; ?>
        </div>

        <form method="post" action="painel.php" autocomplete="off" id="form-apontamento">
            <?= campo_csrf() ?>
            <div class="form-group">
                <label for="maquina">Máquina:</label>
                <select name="maquina_id" id="maquina" required class="input-select">
                    <option value="" disabled selected>Selecione uma máquina</option>
                    <?php foreach ($maquinas as $m): ?>
                        <option value="<?= $m['id'] ?>" data-horimetro="<?= $m['horimetro_atual'] ?>"><?= e($m['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="servico-input">Serviço / Cliente:</label>
                <input type="text" name="servico" id="servico-input" placeholder="Ex: Limpeza de terreno" maxlength="200" required>
            </div>

            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label for="h_inicial">Horímetro Inicial:</label>
                    <input type="number" step="0.01" min="0" name="h_inicial" id="h_inicial" placeholder="0000.00" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="h_final">Horímetro Final:</label>
                    <input type="number" step="0.01" min="0" name="h_final" id="h_final" placeholder="0000.00" required>
                </div>
            </div>

            <button type="submit" class="btn-salvar">Salvar Registro</button>
        </form>

        <?php if ($meus_registros): ?>
            <h3 style="margin-top: 30px;">Meus últimos registros</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Data</th><th>Máquina</th><th>Serviço</th><th>Horas</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meus_registros as $r): ?>
                            <tr>
                                <td data-label="Data"><?= data_br($r['data_hora'], true) ?></td>
                                <td data-label="Máquina"><?= e($r['maquina']) ?></td>
                                <td data-label="Serviço"><?= e($r['servico']) ?></td>
                                <td data-label="Horas"><strong><?= numero($r['total_horas']) ?>h</strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <script>
            // Ao escolher a máquina, sugere o horímetro inicial (último valor registrado)
            document.getElementById('maquina').addEventListener('change', function () {
                const horimetro = this.selectedOptions[0].dataset.horimetro;
                if (horimetro && parseFloat(horimetro) > 0) {
                    document.getElementById('h_inicial').value = horimetro;
                }
            });

            // Validação rápida antes de enviar
            document.getElementById('form-apontamento').addEventListener('submit', function (e) {
                const ini = parseFloat(document.getElementById('h_inicial').value);
                const fim = parseFloat(document.getElementById('h_final').value);
                if (fim <= ini) {
                    e.preventDefault();
                    alert('O horímetro final deve ser maior que o inicial!');
                }
            });
        </script>
<?php endif; ?>

    </div>

</body>
</html>
