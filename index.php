<?php
require 'config.php';

// Já está logado? Vai direto para o painel
if (!empty($_SESSION['id_usuario'])) {
    redirecionar('painel.php');
}

$erro = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE login = ?');
    $stmt->execute([$login]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        session_regenerate_id(true);
        $_SESSION['id_usuario']   = $usuario['id'];
        $_SESSION['nome_usuario'] = $usuario['nome'];
        $_SESSION['tipo_usuario'] = $usuario['tipo'];
        redirecionar('painel.php');
    }

    $erro = 'Login ou senha incorretos.';
}

$titulo = 'Login';
require 'cabecalho.php';
?>

    <div class="container">
        <img src="media/logo.png" alt="HP Terraplenagem" class="logo-login">
        <h2>Acesso do Operador</h2>

        <?php if ($erro): ?>
            <div class="mensagem erro"><?= e($erro) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php">
            <?= campo_csrf() ?>
            <label for="login">Login:</label>
            <input type="text" id="login" name="login" value="<?= e($login) ?>" placeholder="Digite seu usuário" required autofocus>

            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>

            <button type="submit" class="btn-login">Entrar no Sistema</button>
        </form>

        <p style="text-align: center; margin-top: 15px; font-size: 13px; color: #666;">
            <strong>Usuários de teste:</strong> <em>admin / admin</em> ou <em>operador / operador</em>
        </p>
    </div>

</body>
</html>
