<?php
require 'config.php';
exigir_admin();

$pasta_uploads = __DIR__ . '/uploads/';

// --------------------------------------------------------------------------
// Salvar placa, RENAVAM e arquivo do CRLV
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = (int) ($_POST['id'] ?? 0);
    $placa   = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['placa'] ?? ''));
    $renavam = preg_replace('/\D/', '', $_POST['renavam'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM maquinas WHERE id = ?');
    $stmt->execute([$id]);
    $maquina = $stmt->fetch();

    if (!$maquina) {
        definir_mensagem('Veículo não encontrado.', 'erro');
        redirecionar('documentos.php');
    }

    $arquivo = $maquina['crlv_arquivo'];

    // Foi enviado um arquivo novo?
    if (!empty($_FILES['arquivo']['name'])) {
        $extensao = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));

        if ($_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            definir_mensagem('Erro ao enviar o arquivo (talvez seja grande demais).', 'erro');
            redirecionar('documentos.php');
        }
        if (!in_array($extensao, ['pdf', 'jpg', 'jpeg', 'png'])) {
            definir_mensagem('Formato inválido. Envie PDF, JPG ou PNG.', 'erro');
            redirecionar('documentos.php');
        }
        if ($_FILES['arquivo']['size'] > 5 * 1024 * 1024) {
            definir_mensagem('Arquivo muito grande (máximo 5 MB).', 'erro');
            redirecionar('documentos.php');
        }

        if (!is_dir($pasta_uploads)) {
            mkdir($pasta_uploads, 0777, true);
        }

        $novo_nome = 'crlv_' . $id . '_' . time() . '.' . $extensao;
        if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $pasta_uploads . $novo_nome)) {
            definir_mensagem('Não foi possível salvar o arquivo. Verifique a permissão da pasta uploads/.', 'erro');
            redirecionar('documentos.php');
        }

        // Apaga o arquivo antigo, se existir
        if ($arquivo && is_file($pasta_uploads . $arquivo)) {
            unlink($pasta_uploads . $arquivo);
        }
        $arquivo = $novo_nome;
    }

    $pdo->prepare('UPDATE maquinas SET placa = ?, renavam = ?, crlv_arquivo = ? WHERE id = ?')
        ->execute([$placa ?: null, $renavam ?: null, $arquivo, $id]);

    definir_mensagem('Dados de ' . $maquina['nome'] . ' atualizados com sucesso!');
    redirecionar('documentos.php');
}

$frota = $pdo->query('SELECT * FROM maquinas ORDER BY nome')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos - HP Terraplenagem</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="media/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="container dashboard-container max-w-800">
        <div class="header-painel">
            <h2>Documentos</h2>
            <a href="painel.php" class="btn-sair">Voltar ao Painel</a>
        </div>

        <?php mostrar_mensagem(); ?>

        <div class="table-responsive" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <table>
                <thead>
                    <tr>
                        <th>Máquina / Veículo</th>
                        <th>Placa</th>
                        <th>RENAVAM</th>
                        <th>Documento (CRLV)</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($frota as $v): ?>
                        <tr>
                            <td data-label="Veículo"><strong><?= e($v['nome']) ?></strong></td>
                            <td data-label="Placa"><?= $v['placa'] ? e($v['placa']) : '<span style="color:#94a3b8;">Não informado</span>' ?></td>
                            <td data-label="RENAVAM"><?= $v['renavam'] ? e($v['renavam']) : '<span style="color:#94a3b8;">Não informado</span>' ?></td>
                            <td data-label="CRLV">
                                <?php if ($v['crlv_arquivo']): ?>
                                    <a href="uploads/<?= e($v['crlv_arquivo']) ?>" target="_blank" class="btn-editar" style="background-color: #10b981; border-color: #10b981; color: #fff;">Abrir Arquivo</a>
                                <?php else: ?>
                                    <span style="color:#ef4444; font-size: 12px; font-weight: bold;">Pendente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn-editar btn-abrir"
                                        data-id="<?= $v['id'] ?>"
                                        data-nome="<?= e($v['nome']) ?>"
                                        data-placa="<?= e($v['placa']) ?>"
                                        data-renavam="<?= e($v['renavam']) ?>">Editar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para Editar Documentos -->
    <div id="modalDoc" class="modal-baixa">
        <div class="modal-content">
            <h3>Atualizar Documentação</h3>
            <form method="post" action="documentos.php" enctype="multipart/form-data">
                <input type="hidden" name="id" id="modal_doc_id">

                <div class="form-group">
                    <label>Veículo:</label>
                    <input type="text" id="modal_doc_nome" readonly style="background-color: #f1f5f9;">
                </div>

                <div class="form-group">
                    <label for="modal_doc_placa">Placa:</label>
                    <input type="text" name="placa" id="modal_doc_placa" maxlength="8" placeholder="Ex: ABC1D23">
                </div>

                <div class="form-group">
                    <label for="modal_doc_renavam">RENAVAM:</label>
                    <input type="text" name="renavam" id="modal_doc_renavam" inputmode="numeric" maxlength="11" placeholder="Apenas números">
                </div>

                <div class="form-group">
                    <label for="arquivo_doc">Anexar CRLV (PDF ou Imagem, até 5 MB):</label>
                    <input type="file" name="arquivo" id="arquivo_doc" accept=".pdf,.jpg,.jpeg,.png" style="width: 100%; padding: 10px; border: 1.5px dashed #cbd5e1; border-radius: 6px; background: #f8fafc;">
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn-salvar">Salvar Dados</button>
                    <button type="button" id="btn-cancelar" class="btn-sair" style="background:#64748b;">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modalDoc');

        document.querySelectorAll('.btn-abrir').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('modal_doc_id').value      = btn.dataset.id;
                document.getElementById('modal_doc_nome').value    = btn.dataset.nome;
                document.getElementById('modal_doc_placa').value   = btn.dataset.placa;
                document.getElementById('modal_doc_renavam').value = btn.dataset.renavam;
                document.getElementById('arquivo_doc').value = '';
                modal.style.display = 'flex';
            });
        });

        document.getElementById('btn-cancelar').addEventListener('click', function () {
            modal.style.display = 'none';
        });
    </script>
</body>
</html>
