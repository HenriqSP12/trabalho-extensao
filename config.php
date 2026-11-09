<?php
// ==========================================================================
// Configuração geral: conexão com o banco, sessão e funções auxiliares.
// Todas as páginas incluem este arquivo com: require 'config.php';
// ==========================================================================

// Dados de acesso ao MySQL do XAMPP (padrão: usuário root sem senha)
define('DB_HOST', 'localhost');
define('DB_NOME', 'hp_terraplenagem');
define('DB_USUARIO', 'root');
define('DB_SENHA', '');

date_default_timezone_set('America/Sao_Paulo');

// Cookie de sessão mais seguro: inacessível ao JavaScript e não enviado
// em requisições vindas de outros sites (ajuda contra CSRF)
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

// Qualquer erro não tratado (ex.: falha no banco) mostra uma página amigável
// em vez de uma tela em branco. O detalhe técnico vai só para o log do Apache.
set_exception_handler(function (Throwable $erro) {
    error_log((string) $erro);
    http_response_code(500);
    echo '<h3 style="font-family:sans-serif">Ocorreu um erro inesperado.</h3>'
       . '<p style="font-family:sans-serif"><a href="painel.php">Voltar ao painel</a></p>';
});

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NOME . ';charset=utf8mb4',
        DB_USUARIO,
        DB_SENHA,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('<h3 style="font-family:sans-serif">Erro ao conectar no banco de dados.</h3>'
      . '<p style="font-family:sans-serif">Verifique se o MySQL está ligado no XAMPP e se o arquivo '
      . '<strong>banco.sql</strong> foi importado no phpMyAdmin.</p>');
}

// --------------------------------------------------------------------------
// Controle de acesso
// --------------------------------------------------------------------------

// Manda para o login quem não estiver logado
function exigir_login(): void
{
    if (empty($_SESSION['id_usuario'])) {
        header('Location: index.php');
        exit;
    }
}

// Só deixa passar administradores
function exigir_admin(): void
{
    exigir_login();
    if ($_SESSION['tipo_usuario'] !== 'admin') {
        header('Location: painel.php');
        exit;
    }
}

// --------------------------------------------------------------------------
// Mensagens que aparecem depois de salvar algo (sucesso / erro)
// --------------------------------------------------------------------------
function definir_mensagem(string $texto, string $tipo = 'sucesso'): void
{
    $_SESSION['mensagem'] = ['texto' => $texto, 'tipo' => $tipo];
}

function mostrar_mensagem(): void
{
    if (!empty($_SESSION['mensagem'])) {
        $m = $_SESSION['mensagem'];
        echo '<div class="mensagem ' . e($m['tipo']) . '">' . e($m['texto']) . '</div>';
        unset($_SESSION['mensagem']);
    }
}

// Redireciona de volta (padrão POST -> redirect -> GET, evita reenviar o formulário no F5)
function redirecionar(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// --------------------------------------------------------------------------
// Formatação
// --------------------------------------------------------------------------

// Escapa texto para mostrar no HTML com segurança
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Número no formato brasileiro com 2 casas: 1234.5 -> "1.234,50"
function numero($valor): string
{
    return number_format((float) $valor, 2, ',', '.');
}

// Valor em reais (mesmo formato; o "R$" fica no HTML). Nome separado só para deixar
// claro, em quem lê a página, que ali é dinheiro.
function moeda($valor): string
{
    return numero($valor);
}

// "2026-10-15 08:30:00" -> "15/10/2026" (ou com hora)
function data_br(?string $data, bool $com_hora = false): string
{
    if (!$data) return '-';
    return date($com_hora ? 'd/m/Y H:i' : 'd/m/Y', strtotime($data));
}

// Confere se o texto é uma data real no formato do <input type="date"> ("2026-02-31" -> false)
function data_valida(string $data): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}

// "R$ 1.234,56" -> 1234.56
function converter_moeda(string $texto): float
{
    $texto = str_replace(['R$', ' ', '.'], '', $texto);
    return (float) str_replace(',', '.', $texto);
}

// --------------------------------------------------------------------------
// Proteção CSRF
// Cada sessão recebe um token secreto. Todo formulário POST envia esse token
// (campo_csrf()) e aqui ele é conferido antes de qualquer página processar o
// envio. Assim, outro site não consegue fazer o navegador do usuário logado
// excluir gastos, cadastrar usuários etc. sem ele perceber.
// --------------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Campo oculto para colocar dentro de todo <form method="post">
function campo_csrf(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_enviado = $_POST['csrf_token'] ?? '';
    if (!is_string($token_enviado) || !hash_equals(csrf_token(), $token_enviado)) {
        http_response_code(403);
        die('<h3 style="font-family:sans-serif">Requisição inválida ou expirada.</h3>'
          . '<p style="font-family:sans-serif">Volte, recarregue a página e tente novamente.</p>');
    }
}
