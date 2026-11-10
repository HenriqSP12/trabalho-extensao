<?php
require 'config.php';
exigir_admin();

// --------------------------------------------------------------------------
// Desativar / reativar usuário
// Não apaga do banco: o histórico (apontamentos, gastos...) continua com o nome dele
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['acao'] ?? '', ['desativar', 'reativar'])) {
    $id    = (int) ($_POST['id'] ?? 0);
    $ativo = $_POST['acao'] === 'reativar' ? 1 : 0;

    if ($id === ID_DONO) {
        definir_mensagem('A conta principal do dono não pode ser desativada.', 'erro');
    } elseif ($id === (int) $_SESSION['id_usuario']) {
        definir_mensagem('Você não pode desativar a sua própria conta.', 'erro');
    } else {
        $stmt = $pdo->prepare('UPDATE usuarios SET ativo = ? WHERE id = ?');
        $stmt->execute([$ativo, $id]);
        if ($stmt->rowCount()) {
            definir_mensagem($ativo ? 'Usuário reativado.' : 'Usuário desativado. Ele não consegue mais entrar no sistema.');
        }
    }
    redirecionar('cadastro.php');
}

// --------------------------------------------------------------------------
// Cadastrar novo usuário
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome'] ?? '');
    $login = strtolower(trim($_POST['login'] ?? ''));
    $senha = $_POST['senha'] ?? '';
    $tipo  = ($_POST['tipo'] ?? '') === 'admin' ? 'admin' : 'operador';

    if ($nome === '' || $login === '' || $senha === '') {
        definir_mensagem('Preencha todos os campos.', 'erro');
    } elseif (!preg_match('/^[a-z0-9._-]{3,50}$/', $login)) {
        // Login simples evita erro de digitação na hora de entrar (espaços, acentos)
        definir_mensagem('Login inválido: use de 3 a 50 caracteres, só letras sem acento, números, ponto, hífen ou underline. Ex.: fulano.tal', 'erro');
    } else {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE login = ?');
        $stmt->execute([$login]);

        if ($stmt->fetch()) {
            definir_mensagem("Erro: O login '$login' já existe no sistema!", 'erro');
        } else {
            $pdo->prepare('INSERT INTO usuarios (nome, login, senha, tipo) VALUES (?, ?, ?, ?)')
                ->execute([$nome, $login, password_hash($senha, PASSWORD_DEFAULT), $tipo]);
            definir_mensagem("Usuário $nome cadastrado com sucesso!");
        }
    }
    redirecionar('cadastro.php');
}

// Ativos primeiro, depois os desativados
$usuarios = $pdo->query('SELECT id, nome, login, tipo, ativo FROM usuarios ORDER BY ativo DESC, nome')->fetchAll();

$titulo = 'Cadastrar Usuário';
require 'cabecalho.php';
?>
    <div class="container form-card">
        <a href="painel.php" class="link-voltar">&larr; Voltar ao Painel</a>

        <h2>Novo Usuário</h2>

        <?php mostrar_mensagem(); ?>

        <form method="post" action="cadastro.php" autocomplete="off">
            <?= campo_csrf() ?>
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" placeholder="Ex: João da Silva" maxlength="100" required>
            </div>

            <div class="form-group">
                <label for="login">Login de Acesso:</label>
                <input type="text" id="login" name="login" placeholder="Ex: joao.silva" maxlength="50" required
                       pattern="[A-Za-z0-9._\-]{3,50}" autocapitalize="none"
                       title="Sem espaços nem acentos: letras, números, ponto, hífen ou underline. Ex.: joao.silva">
                <small style="color: var(--text-main); font-size: 0.8rem;">Sem espaços nem acentos. Ex.: joao.silva</small>
            </div>

            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" placeholder="Crie uma senha de acesso" required>
            </div>

            <div class="form-group">
                <label for="tipo">Nível de Acesso:</label>
                <select id="tipo" name="tipo" class="input-select">
                    <option value="operador">Operador</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>

            <button type="submit" class="btn-salvar">Cadastrar Usuário</button>
        </form>

        <h3 style="margin-top: 30px;">Usuários cadastrados</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Nome</th><th>Login</th><th>Nível</th><th>Situação</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr<?= $u['ativo'] ? '' : ' style="opacity: 0.6;"' ?>>
                            <td data-label="Nome"><?= e($u['nome']) ?></td>
                            <td data-label="Login"><?= e($u['login']) ?></td>
                            <td data-label="Nível"><?= $u['tipo'] === 'admin' ? 'Administrador' : 'Operador' ?></td>
                            <td data-label="Situação">
                                <span class="badge <?= $u['ativo'] ? 'badge-ok' : 'badge-critico' ?>"><?= $u['ativo'] ? 'Ativo' : 'Desativado' ?></span>
                            </td>
                            <td>
                                <?php if ($u['id'] == ID_DONO): ?>
                                    <span style="color: #64748b; font-size: 0.8rem;">Conta principal</span>
                                <?php elseif ($u['id'] == $_SESSION['id_usuario']): ?>
                                    <span style="color: #64748b; font-size: 0.8rem;">Você</span>
                                <?php elseif ($u['ativo']): ?>
                                    <form method="post" action="cadastro.php" style="display: inline;"
                                          onsubmit="return confirm('Desativar este usuário? Ele não vai conseguir mais entrar no sistema.');">
                                        <?= campo_csrf() ?>
                                        <input type="hidden" name="acao" value="desativar">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn-editar" style="width: auto; background: #fee2e2; color: #b91c1c;">Desativar</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="cadastro.php" style="display: inline;">
                                        <?= campo_csrf() ?>
                                        <input type="hidden" name="acao" value="reativar">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn-editar" style="width: auto;">Reativar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
