<?php
require 'config.php';
exigir_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome'] ?? '');
    $login = strtolower(trim($_POST['login'] ?? ''));
    $senha = $_POST['senha'] ?? '';
    $tipo  = ($_POST['tipo'] ?? '') === 'admin' ? 'admin' : 'operador';

    if ($nome === '' || $login === '' || $senha === '') {
        definir_mensagem('Preencha todos os campos.', 'erro');
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

$usuarios = $pdo->query('SELECT nome, login, tipo, criado_em FROM usuarios ORDER BY nome')->fetchAll();

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
                <input type="text" id="login" name="login" placeholder="Ex: joao.silva" maxlength="50" required>
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
                    <tr><th>Nome</th><th>Login</th><th>Nível</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td data-label="Nome"><?= e($u['nome']) ?></td>
                            <td data-label="Login"><?= e($u['login']) ?></td>
                            <td data-label="Nível"><?= $u['tipo'] === 'admin' ? 'Administrador' : 'Operador' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
