<?php
require 'config.php';
exigir_admin();

// --------------------------------------------------------------------------
// Salvar (novo ou edição) e excluir
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'salvar';
    $id   = (int) ($_POST['id'] ?? 0);

    if ($acao === 'excluir') {
        $pdo->prepare('DELETE FROM gastos WHERE id = ?')->execute([$id]);
        definir_mensagem('Gasto excluído.');
        redirecionar('controle_gastos.php');
    }

    $maquina_id = (int) ($_POST['maquina_id'] ?? 0);
    $valor      = converter_moeda($_POST['gasto'] ?? '');
    $tipo       = ($_POST['tipo_gasto'] ?? '') === 'equipamento' ? 'equipamento' : 'combustivel';
    $data       = $_POST['data'] ?: date('Y-m-d');
    $descricao  = trim($_POST['descricao'] ?? '');

    if (!$maquina_id || $valor <= 0) {
        definir_mensagem('Selecione a máquina e informe um valor válido.', 'erro');
    } elseif ($id) {
        $pdo->prepare('UPDATE gastos SET maquina_id = ?, tipo = ?, valor = ?, data = ?, descricao = ? WHERE id = ?')
            ->execute([$maquina_id, $tipo, $valor, $data, $descricao, $id]);
        definir_mensagem('Gasto atualizado com sucesso!');
    } else {
        $pdo->prepare('INSERT INTO gastos (maquina_id, usuario_id, tipo, valor, data, descricao) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$maquina_id, $_SESSION['id_usuario'], $tipo, $valor, $data, $descricao]);
        definir_mensagem('Gasto registrado com sucesso!');
    }

    // Volta mostrando o mês do gasto salvo
    redirecionar('controle_gastos.php?mes=' . date('n', strtotime($data)) . '&ano=' . date('Y', strtotime($data)));
}

// --------------------------------------------------------------------------
// Edição: carrega o gasto no formulário (?editar=ID)
// --------------------------------------------------------------------------
$editando = null;
if (!empty($_GET['editar'])) {
    $stmt = $pdo->prepare('SELECT * FROM gastos WHERE id = ?');
    $stmt->execute([(int) $_GET['editar']]);
    $editando = $stmt->fetch() ?: null;
}

// --------------------------------------------------------------------------
// Filtros e listagem
// --------------------------------------------------------------------------
$mes        = (int) ($_GET['mes'] ?? date('n'));
$ano        = (int) ($_GET['ano'] ?? date('Y'));
$maquina_id = (int) ($_GET['maquina_filtro'] ?? 0);
$busca      = trim($_GET['busca'] ?? '');

$sql = 'SELECT g.*, m.nome AS maquina
          FROM gastos g
          JOIN maquinas m ON m.id = g.maquina_id
         WHERE MONTH(g.data) = ? AND YEAR(g.data) = ?';
$params = [$mes, $ano];

if ($maquina_id) {
    $sql .= ' AND g.maquina_id = ?';
    $params[] = $maquina_id;
}
if ($busca !== '') {
    $sql .= ' AND (g.descricao LIKE ? OR m.nome LIKE ?)';
    array_push($params, "%$busca%", "%$busca%");
}
$sql .= ' ORDER BY g.data DESC, g.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$gastos = $stmt->fetchAll();

$tot_combustivel = 0;
$tot_equipamento = 0;
foreach ($gastos as $g) {
    if ($g['tipo'] === 'combustivel') $tot_combustivel += $g['valor'];
    else $tot_equipamento += $g['valor'];
}

$maquinas = $pdo->query('SELECT id, nome FROM maquinas ORDER BY nome')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Despesas - HP Terraplenagem</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="media/favicon.ico" type="image/x-icon">
    <meta name="theme-color" content="#f59e0b">
</head>
<body>
    <div class="container dashboard-container">
        <div class="header-painel">
            <h2>Gerenciamento de Despesas</h2>
            <a href="painel.php" class="btn-sair">Voltar ao Painel</a>
        </div>

        <?php mostrar_mensagem(); ?>

        <div class="form-cadastro">
            <h3><?= $editando ? 'Editar Gasto' : 'Lançar Novo Gasto' ?></h3>

            <form method="post" action="controle_gastos.php" autocomplete="off">
                <input type="hidden" name="id" value="<?= $editando['id'] ?? '' ?>">
                <div class="form-grid">
                    <div>
                        <label for="maquina">Máquina / Veículo *</label>
                        <select id="maquina" name="maquina_id" required class="input-select">
                            <option value="" disabled <?= $editando ? '' : 'selected' ?>>Selecione...</option>
                            <?php foreach ($maquinas as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= ($editando['maquina_id'] ?? 0) == $m['id'] ? 'selected' : '' ?>><?= e($m['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="gasto">Valor (Gasto) *</label>
                        <input type="text" id="gasto" name="gasto" placeholder="R$ 0,00" inputmode="numeric" required
                               value="<?= $editando ? 'R$ ' . moeda($editando['valor']) : '' ?>">
                    </div>
                    <div>
                        <label for="tipo_gasto">Tipo de Gasto *</label>
                        <select id="tipo_gasto" name="tipo_gasto" required class="input-select">
                            <option value="combustivel">Combustível</option>
                            <option value="equipamento" <?= ($editando['tipo'] ?? '') === 'equipamento' ? 'selected' : '' ?>>Equipamento/Manutenção</option>
                        </select>
                    </div>
                    <div>
                        <label for="data">Data</label>
                        <input type="date" id="data" name="data" value="<?= e($editando['data'] ?? date('Y-m-d')) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="descricao">Descrição Adicional (Opcional)</label>
                    <input type="text" id="descricao" name="descricao" maxlength="255" placeholder="Detalhes do gasto, fornecedor, etc..."
                           value="<?= e($editando['descricao'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-salvar"><?= $editando ? 'Salvar Alterações' : 'Salvar Lançamento' ?></button>
                <?php if ($editando): ?>
                    <a href="controle_gastos.php" class="btn-sair" style="display: block; text-align: center; margin-top: 10px;">Cancelar Edição</a>
                <?php endif; ?>
            </form>
        </div>

        <hr class="divisor">

        <form method="get" class="search-form">
            <div class="form-group">
                <label for="maquina_filtro">Veículo</label>
                <select id="maquina_filtro" name="maquina_filtro" class="input-select">
                    <option value="">Todas as Máquinas</option>
                    <?php foreach ($maquinas as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= $m['id'] == $maquina_id ? 'selected' : '' ?>><?= e($m['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="mes">Mês</label>
                <select id="mes" name="mes" class="input-select">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $i === $mes ? 'selected' : '' ?>><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="ano">Ano</label>
                <input id="ano" type="number" name="ano" value="<?= $ano ?>" required>
            </div>
            <div class="form-group flex-expand">
                <label for="busca">Buscar</label>
                <input id="busca" type="text" name="busca" value="<?= e($busca) ?>" placeholder="Termo de busca...">
            </div>
            <button type="submit" class="btn-filtrar">Filtrar</button>
        </form>

        <div class="summary-cards">
            <div class="card card-combustivel">
                <h3>Combustível</h3>
                <div class="value">R$ <?= moeda($tot_combustivel) ?></div>
            </div>
            <div class="card card-manutencao">
                <h3>Manutenção / Equipamentos</h3>
                <div class="value">R$ <?= moeda($tot_equipamento) ?></div>
            </div>
            <div class="card card-total">
                <h3>Total Geral</h3>
                <div class="value">R$ <?= moeda($tot_combustivel + $tot_equipamento) ?></div>
            </div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Máquina</th>
                        <th>Tipo</th>
                        <th>Valor (R$)</th>
                        <th>Descrição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gastos as $g): ?>
                        <tr>
                            <td data-label="Data"><?= data_br($g['data']) ?></td>
                            <td data-label="Máquina"><?= e($g['maquina']) ?></td>
                            <td data-label="Tipo"><span class="badge badge-<?= $g['tipo'] ?>"><?= $g['tipo'] === 'combustivel' ? 'Combustível' : 'Equipamento' ?></span></td>
                            <td data-label="Valor"><strong>R$ <?= moeda($g['valor']) ?></strong></td>
                            <td data-label="Descrição"><?= e($g['descricao']) ?></td>
                            <td>
                                <a href="controle_gastos.php?editar=<?= $g['id'] ?>" class="btn-editar">Editar</a>
                                <form method="post" action="controle_gastos.php" style="display: inline;" onsubmit="return confirm('Excluir este gasto?');">
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                    <button type="submit" class="btn-editar" style="width: auto; background: #fee2e2; color: #b91c1c;">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$gastos): ?>
                        <tr><td colspan="6" class="text-center">Nenhuma despesa encontrada neste período.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Máscara de moeda: digita só números e vira "R$ 1.234,56"
        document.getElementById('gasto').addEventListener('input', function (e) {
            let valor = e.target.value.replace(/\D/g, '');
            valor = (valor / 100).toFixed(2).replace('.', ',');
            valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            e.target.value = valor !== '0,00' ? 'R$ ' + valor : '';
        });
    </script>
</body>
</html>
