<?php
/**
 * BoostMe App — shared setup: .env, database, session, helpers, auth.
 * Plain PHP 8.1+, no Composer (cPanel has no SSH/Composer).
 */
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

// ── .env ───────────────────────────────────────────────────────
$GLOBALS['ENV'] = [];
if (is_file(ROOT . '/.env')) {
    foreach (file(ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $GLOBALS['ENV'][trim($k)] = trim(trim($v), "\"'");
    }
}

function env(string $key, string $default = ''): string
{
    return $GLOBALS['ENV'][$key] ?? $default;
}

date_default_timezone_set(env('APP_TZ', 'Africa/Johannesburg'));
error_reporting(E_ALL);
ini_set('display_errors', env('APP_DEBUG') === 'true' ? '1' : '0');

const PLATFORMS = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok'];

// ── Database ───────────────────────────────────────────────────
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . env('DB_HOST', 'localhost') . ';dbname=' . env('DB_NAME') . ';charset=utf8mb4',
            env('DB_USER'),
            env('DB_PASS'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (q('SELECT k, v FROM settings')->fetchAll() as $row) {
                $cache[$row['k']] = $row['v'];
            }
        } catch (Throwable) {
        }
    }
    return $GLOBALS['SETTINGS_OVERRIDE'][$key] ?? $cache[$key] ?? $default;
}

function set_setting(string $key, string $value): void
{
    q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$key, $value]);
    $GLOBALS['SETTINGS_OVERRIDE'][$key] = $value;
}

// ── Web helpers ────────────────────────────────────────────────
function url(string $path = ''): string
{
    return rtrim(env('APP_URL', 'http://localhost'), '/') . '/' . ltrim($path, '/');
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function input(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function redirect(string $to): never
{
    header('Location: ' . (str_starts_with($to, 'http') ? $to : url($to)));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals(csrf_token(), (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(419);
        exit('Your session expired. Go back, refresh the page and try again.');
    }
}

/** Renders views/<name>.php inside the layout (unless $bare). */
function view(string $name, array $vars = [], bool $bare = false): never
{
    extract($vars);
    ob_start();
    require ROOT . '/views/' . $name . '.php';
    $content = ob_get_clean();
    if ($bare) {
        echo $content;
    } else {
        require ROOT . '/views/layout.php';
    }
    exit;
}

function when(?string $datetime): string
{
    return $datetime ? date('D j M, H:i', strtotime($datetime)) : '—';
}

// ── Auth ───────────────────────────────────────────────────────
function user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = empty($_SESSION['uid']) ? null : (q('SELECT * FROM users WHERE id = ?', [$_SESSION['uid']])->fetch() ?: null);
    }
    return $user;
}

function is_admin(): bool
{
    return (user()['role'] ?? '') === 'admin';
}

function require_login(): array
{
    if (!user()) {
        redirect('login');
    }
    return user();
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Admins only.');
    }
}

/** The client a page works on: a client user's own; an admin's from ?client= (0 = all). */
function current_client_id(): int
{
    $u = require_login();
    return is_admin() ? (int) input('client', '0') : (int) $u['client_id'];
}

/** Stops a client user from touching another client's data. */
function can_access_client(int $clientId): bool
{
    return is_admin() || (int) (user()['client_id'] ?? 0) === $clientId;
}

function client(int $id): ?array
{
    return q('SELECT * FROM clients WHERE id = ?', [$id])->fetch() ?: null;
}

function connections(int $clientId): array
{
    $out = [];
    foreach (q('SELECT * FROM connections WHERE client_id = ?', [$clientId])->fetchAll() as $row) {
        $out[$row['platform']] = $row;
    }
    return $out;
}

function media_url(string $path): string
{
    return url('uploads/' . ltrim($path, '/'));
}
