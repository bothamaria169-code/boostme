<?php
/** BoostMe App — front controller. Every page is a case below; templates are in views/. */
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/social.php';
require __DIR__ . '/app/mail.php';
require __DIR__ . '/app/uploads.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => str_starts_with(env('APP_URL'), 'https')]);
session_start();
csrf_check();

$route = trim((string) ($_GET['r'] ?? ''), '/');

// TikTok domain check file, e.g. /tiktokAbC123.txt
if (preg_match('/^tiktok\w+\.txt$/', $route)) {
    if (setting('tiktok_verify_file') !== '' && hash_equals(setting('tiktok_verify_file'), $route)) {
        header('Content-Type: text/plain');
        exit(setting('tiktok_verify_content'));
    }
    http_response_code(404);
    exit('Not found');
}

switch ($route) {

// ── Public ─────────────────────────────────────────────────────
case 'setup':   // creates the first admin; disabled once one exists
    if ((int) q("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0) {
        redirect('login');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (strlen(input('password')) < 8 || !filter_var(input('email'), FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Enter a valid email and a password of at least 8 characters.');
        } else {
            q("INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,'admin')",
                [input('name') ?: 'Admin', strtolower(input('email')), password_hash(input('password'), PASSWORD_DEFAULT)]);
            flash('success', 'Admin created — log in.');
            redirect('login');
        }
    }
    view('login', ['setup' => true], false);

case 'login':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $u = q('SELECT * FROM users WHERE email = ?', [strtolower(input('email'))])->fetch();
        if ($u && password_verify(input('password'), $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = $u['id'];
            q('UPDATE users SET last_login = NOW() WHERE id = ?', [$u['id']]);
            redirect('dashboard');
        }
        flash('error', 'Wrong email or password.');
    }
    view('login', ['setup' => false]);

case 'logout':
    session_destroy();
    redirect('login');

case 'privacy':
case 'terms':
    view('legal', ['page' => $route]);

case 'guide':
    redirect('BoostMe-Guide.pdf');

// ── Dashboard ──────────────────────────────────────────────────
case '':
case 'dashboard':
    require_login();
    $cid = current_client_id();
    $where = $cid ? 'p.client_id = ' . $cid : '1=1';
    $week = date('Y-m-d H:i:s', strtotime('-7 days'));
    $stats = [
        'posted'    => (int) q("SELECT COUNT(*) FROM posts p WHERE $where AND status = 'posted' AND scheduled_at >= ?", [$week])->fetchColumn(),
        'scheduled' => (int) q("SELECT COUNT(*) FROM posts p WHERE $where AND status = 'scheduled'")->fetchColumn(),
        'pending'   => (int) q("SELECT COUNT(*) FROM posts p WHERE $where AND status = 'pending'")->fetchColumn(),
        'failed'    => (int) q("SELECT COUNT(*) FROM posts p WHERE $where AND status IN ('failed','partial')")->fetchColumn(),
    ];
    $upcoming = q("SELECT p.*, c.name client_name FROM posts p JOIN clients c ON c.id = p.client_id
                   WHERE $where AND p.status IN ('scheduled','pending') ORDER BY p.scheduled_at LIMIT 8")->fetchAll();
    $recent = q("SELECT p.*, c.name client_name FROM posts p JOIN clients c ON c.id = p.client_id
                 WHERE $where AND p.status IN ('posted','partial','failed','posting') ORDER BY p.scheduled_at DESC LIMIT 8")->fetchAll();
    $clients = is_admin() ? q('SELECT c.*, (SELECT COUNT(*) FROM connections x WHERE x.client_id = c.id) conns,
        (SELECT COUNT(*) FROM posts x WHERE x.client_id = c.id AND x.status = \'scheduled\') queued FROM clients c WHERE active = 1 ORDER BY name')->fetchAll() : [];
    view('dashboard', compact('cid', 'stats', 'upcoming', 'recent', 'clients') + ['client' => $cid ? client($cid) : null, 'conns' => $cid ? connections($cid) : []]);

// ── Posts ──────────────────────────────────────────────────────
case 'posts':
    require_login();
    $cid = current_client_id();
    $status = input('status');
    $sql = 'SELECT p.*, c.name client_name FROM posts p JOIN clients c ON c.id = p.client_id WHERE 1=1';
    $args = [];
    if ($cid) { $sql .= ' AND p.client_id = ?'; $args[] = $cid; }
    if ($status !== '') { $sql .= ' AND p.status = ?'; $args[] = $status; }
    $posts = q($sql . ' ORDER BY p.scheduled_at DESC LIMIT 200', $args)->fetchAll();
    $results = [];
    if ($posts) {
        $ids = implode(',', array_map('intval', array_column($posts, 'id')));
        foreach (q("SELECT * FROM post_results WHERE post_id IN ($ids)")->fetchAll() as $r) {
            $results[$r['post_id']][$r['platform']] = $r;
        }
    }
    view('posts', compact('posts', 'results', 'cid', 'status'));

case 'posts/edit':
    $u = require_login();
    $id = (int) input('id');
    $post = $id ? q('SELECT * FROM posts WHERE id = ?', [$id])->fetch() : null;
    $cid = $post ? (int) $post['client_id'] : (is_admin() ? (int) input('client_id', input('client')) : (int) $u['client_id']);
    if (($post && !can_access_client($cid)) || ($post && !in_array($post['status'], ['pending', 'scheduled'], true))) {
        flash('error', 'That post can no longer be edited.');
        redirect('posts');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$cid || !can_access_client($cid)) {
            flash('error', 'Choose a client.');
        } else {
            [$paths, $type, $err] = save_uploads($cid);
            $media = $paths ?: json_decode($post['media'] ?? '[]', true);
            $type = $paths ? $type : ($post['media_type'] ?? '');
            $platforms = array_values(array_intersect(array_keys(PLATFORMS), (array) ($_POST['platforms'] ?? [])));
            $when = strtotime(input('scheduled_at')) ?: time();
            if ($err !== '') {
                flash('error', $err);
            } elseif (!$media) {
                flash('error', 'Add at least one photo or a video.');
            } elseif (!$platforms) {
                flash('error', 'Tick at least one platform.');
            } else {
                // Client posts wait for the admin's approval; admin posts go straight to the queue.
                $status = is_admin() ? 'scheduled' : 'pending';
                $row = [$cid, input('caption'), json_encode($media), $type, implode(',', $platforms), date('Y-m-d H:i:s', $when), $status];
                if ($post) {
                    q('UPDATE posts SET client_id=?, caption=?, media=?, media_type=?, platforms=?, scheduled_at=?, status=? WHERE id=?', [...$row, $id]);
                } else {
                    q('INSERT INTO posts (client_id, caption, media, media_type, platforms, scheduled_at, status, created_by) VALUES (?,?,?,?,?,?,?,?)', [...$row, $u['id']]);
                }
                flash('success', $status === 'pending' ? 'Saved — your post is waiting for approval.' : 'Scheduled for ' . when(date('Y-m-d H:i:s', $when)) . '.');
                redirect('posts' . (is_admin() ? '?client=' . $cid : ''));
            }
        }
    }
    $clients = is_admin() ? q('SELECT id, name FROM clients WHERE active = 1 ORDER BY name')->fetchAll() : [];
    view('post_edit', compact('post', 'cid', 'clients') + ['conns' => $cid ? connections($cid) : []]);

case 'posts/action':
    require_login();
    $post = q('SELECT * FROM posts WHERE id = ?', [(int) input('id')])->fetch();
    if (!$post || !can_access_client((int) $post['client_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('posts');
    }
    $do = input('do');
    if ($do === 'delete' && (is_admin() || $post['status'] === 'pending')) {
        q('DELETE FROM posts WHERE id = ?', [$post['id']]);
        flash('success', 'Post deleted.');
    } elseif ($do === 'approve' && is_admin() && $post['status'] === 'pending') {
        q("UPDATE posts SET status = 'scheduled' WHERE id = ?", [$post['id']]);
        flash('success', 'Approved and scheduled.');
    } elseif (in_array($do, ['now', 'retry'], true) && is_admin()) {
        if ($post['status'] === 'pending') {
            q("UPDATE posts SET status = 'scheduled' WHERE id = ?", [$post['id']]);
        }
        @set_time_limit(600);
        $status = run_post((int) $post['id']);
        $errors = q('SELECT platform, error FROM post_results WHERE post_id = ? AND ok = 0', [$post['id']])->fetchAll();
        flash($status === 'posted' ? 'success' : 'error', 'Result: ' . $status . '. '
            . implode(' ', array_map(fn($r) => PLATFORMS[$r['platform']] . ': ' . $r['error'], $errors)));
    }
    redirect('posts' . (is_admin() ? '?client=' . $post['client_id'] : ''));

// ── Clients (admin) ────────────────────────────────────────────
case 'clients':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && input('name') !== '') {
        q('INSERT INTO clients (name, industry) VALUES (?, ?)', [input('name'), input('industry')]);
        redirect('clients/edit?id=' . db()->lastInsertId());
    }
    view('clients', ['clients' => q('SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.client_id = c.id) posts FROM clients c ORDER BY active DESC, name')->fetchAll()]);

case 'clients/edit':
    require_admin();
    $id = (int) input('id');
    $client = client($id) ?? redirect('clients');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $do = input('do');
        if ($do === 'save') {
            q('UPDATE clients SET name=?, industry=?, contact_name=?, email=?, phone=?, hashtags=?, signature=?, tiktok_mode=?, weekly_email=?, active=? WHERE id=?', [
                input('name'), input('industry'), input('contact_name'), input('email'), input('phone'),
                input('hashtags'), input('signature'), input('tiktok_mode') === 'direct' ? 'direct' : 'inbox',
                (int) isset($_POST['weekly_email']), (int) isset($_POST['active']), $id]);
            flash('success', 'Saved.');
        } elseif ($do === 'user') {
            $pass = input('password') ?: substr(bin2hex(random_bytes(6)), 0, 10);
            try {
                q("INSERT INTO users (name, email, password_hash, role, client_id) VALUES (?,?,?,'client',?)",
                    [input('user_name') ?: $client['name'], strtolower(input('user_email')), password_hash($pass, PASSWORD_DEFAULT), $id]);
                flash('success', 'Login created: ' . strtolower(input('user_email')) . ' / ' . $pass . ' — share it with the client (shown once).');
            } catch (PDOException) {
                flash('error', 'That email already has a login.');
            }
        } elseif ($do === 'reset') {
            $pass = substr(bin2hex(random_bytes(6)), 0, 10);
            q("UPDATE users SET password_hash = ? WHERE id = ? AND client_id = ?", [password_hash($pass, PASSWORD_DEFAULT), (int) input('user_id'), $id]);
            flash('success', 'New password: ' . $pass . ' (shown once).');
        } elseif ($do === 'remove_user') {
            q("DELETE FROM users WHERE id = ? AND client_id = ?", [(int) input('user_id'), $id]);
        } elseif ($do === 'disconnect') {
            q('DELETE FROM connections WHERE client_id = ? AND platform = ?', [$id, input('platform')]);
            flash('success', 'Disconnected.');
        } elseif ($do === 'report') {
            flash(send_weekly_report($client) ? 'success' : 'error', send_weekly_report_status($client));
        }
        redirect('clients/edit?id=' . $id);
    }
    $users = q('SELECT * FROM users WHERE client_id = ?', [$id])->fetchAll();
    view('client_edit', ['client' => $client, 'users' => $users, 'conns' => connections($id)]);

case 'report/preview':
    require_admin();
    $client = client((int) input('id')) ?? redirect('clients');
    echo weekly_report_html($client)[1];
    exit;

// ── Connecting accounts (admin or the client themself) ─────────
case 'connect/meta':
    require_admin();   // you connect accounts for your clients
    $meta = meta_app();
    $back = static fn(int $c) => is_admin() ? 'clients/edit?id=' . $c : 'dashboard';
    if ($meta['app_id'] === '' || $meta['app_secret'] === '') {
        flash('error', 'The Meta app is not set up yet (Settings).');
        redirect('dashboard');
    }
    $redirectUri = url('connect/meta');
    if (input('page') !== '') {   // chose one of several Pages
        $cid = (int) ($_SESSION['meta_client'] ?? 0);
        $page = $_SESSION['meta_pages'][input('page')] ?? null;
        if ($page && can_access_client($cid)) {
            save_meta_page($cid, $page);
        }
        unset($_SESSION['meta_pages']);
        redirect($back($cid));
    }
    if (input('code') === '' && input('error') === '') {
        $cid = is_admin() ? (int) input('client') : (int) user()['client_id'];
        $_SESSION['meta_client'] = $cid;
        $_SESSION['meta_state'] = $state = bin2hex(random_bytes(16));
        redirect('https://www.facebook.com/' . GRAPH_VERSION . '/dialog/oauth?' . http_build_query([
            'client_id' => $meta['app_id'], 'redirect_uri' => $redirectUri, 'state' => $state, 'response_type' => 'code',
        ] + ($meta['config_id'] !== '' ? ['config_id' => $meta['config_id']] : ['scope' => implode(',', META_SCOPES)])));
    }
    $cid = (int) ($_SESSION['meta_client'] ?? 0);
    if (!hash_equals((string) ($_SESSION['meta_state'] ?? 'x'), input('state')) || !can_access_client($cid)) {
        flash('error', 'That Facebook connection expired — try again.');
        redirect($back($cid));
    }
    if (input('error') !== '') {
        flash('error', 'Facebook connection cancelled.');
        redirect($back($cid));
    }
    $short = graph_get('oauth/access_token', ['client_id' => $meta['app_id'], 'client_secret' => $meta['app_secret'], 'redirect_uri' => $redirectUri, 'code' => input('code')]);
    $long = empty($short['access_token']) ? $short : graph_get('oauth/access_token', [
        'grant_type' => 'fb_exchange_token', 'client_id' => $meta['app_id'], 'client_secret' => $meta['app_secret'], 'fb_exchange_token' => $short['access_token']]);
    $accounts = empty($long['access_token']) ? $long : graph_get('me/accounts', [
        'fields' => 'id,name,access_token,instagram_business_account{id,username}', 'limit' => 50, 'access_token' => $long['access_token']]);
    $pages = [];
    foreach ($accounts['data'] ?? [] as $row) {
        $pages[(string) $row['id']] = ['id' => (string) $row['id'], 'name' => (string) $row['name'], 'token' => (string) $row['access_token'],
            'ig' => (string) ($row['instagram_business_account']['id'] ?? ''), 'ig_name' => (string) ($row['instagram_business_account']['username'] ?? '')];
    }
    if (!$pages) {
        flash('error', 'No Facebook Page came back' . (isset($accounts['error']) ? ': ' . ($accounts['error']['message'] ?? '') : '. When Facebook asks, tick the business Page and its Instagram account.'));
        redirect($back($cid));
    }
    if (count($pages) === 1) {
        save_meta_page($cid, reset($pages));
        redirect($back($cid));
    }
    $_SESSION['meta_pages'] = $pages;
    view('choose_page', ['pages' => $pages]);

case 'connect/tiktok':
    require_admin();
    $app = tiktok_app();
    $back = static fn(int $c) => is_admin() ? 'clients/edit?id=' . $c : 'dashboard';
    if ($app['client_key'] === '') {
        flash('error', 'The TikTok app is not set up yet (Settings).');
        redirect('dashboard');
    }
    if (input('code') === '' && input('error') === '') {
        $_SESSION['tt_client'] = is_admin() ? (int) input('client') : (int) user()['client_id'];
        $_SESSION['tt_state'] = $state = bin2hex(random_bytes(16));
        redirect('https://www.tiktok.com/v2/auth/authorize/?' . http_build_query([
            'client_key' => $app['client_key'], 'scope' => implode(',', TIKTOK_SCOPES), 'response_type' => 'code',
            'redirect_uri' => url('connect/tiktok'), 'state' => $state]));
    }
    $cid = (int) ($_SESSION['tt_client'] ?? 0);
    if (!hash_equals((string) ($_SESSION['tt_state'] ?? 'x'), input('state')) || !can_access_client($cid) || input('error') !== '') {
        flash('error', 'TikTok connection cancelled or expired — try again.');
        redirect($back($cid));
    }
    $reply = tiktok_request('oauth/token/', null, '', ['client_key' => $app['client_key'], 'client_secret' => $app['client_secret'],
        'code' => input('code'), 'grant_type' => 'authorization_code', 'redirect_uri' => url('connect/tiktok')]);
    if (empty($reply['access_token'])) {
        flash('error', 'TikTok did not give access: ' . tiktok_error($reply));
        redirect($back($cid));
    }
    $info = tiktok_request('user/info/?fields=open_id,display_name', null, (string) $reply['access_token']);
    $name = (string) ($info['data']['user']['display_name'] ?? 'TikTok account');
    q('REPLACE INTO connections (client_id, platform, account_id, account_name, token, refresh_token, expires_at) VALUES (?,?,?,?,?,?,?)',
        [$cid, 'tiktok', (string) ($reply['open_id'] ?? ''), $name, $reply['access_token'], $reply['refresh_token'] ?? '', time() + (int) ($reply['expires_in'] ?? 86400)]);
    $missing = array_diff(['video.publish', 'video.upload'], explode(',', (string) ($reply['scope'] ?? '')));
    flash($missing ? 'error' : 'success', 'TikTok connected as ' . $name . '.' . ($missing ? ' Missing permission: ' . implode(', ', $missing) : ''));
    redirect($back($cid));

// ── Settings (admin) & account ─────────────────────────────────
case 'settings':
    require_admin();
    $keys = ['meta_app_id', 'meta_app_secret', 'meta_config_id', 'tiktok_client_key', 'tiktok_client_secret',
        'tiktok_verify_file', 'tiktok_verify_content', 'report_day'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($keys as $k) {
            if (isset($_POST[$k]) && !(str_ends_with($k, 'secret') && $_POST[$k] === '')) {   // blank secret = keep
                set_setting($k, trim((string) $_POST[$k]));
            }
        }
        if (input('test_email') !== '') {
            $ok = send_mail(input('test_email'), 'BoostMe test email', '<p>Email from BoostMe works.</p>');
            flash($ok ? 'success' : 'error', $ok ? 'Test email sent.' : 'Test email failed: ' . q('SELECT error FROM email_log ORDER BY id DESC LIMIT 1')->fetchColumn());
        }
        flash('success', 'Settings saved.');
        redirect('settings');
    }
    view('settings', ['emails' => q('SELECT * FROM email_log ORDER BY id DESC LIMIT 10')->fetchAll()]);

case 'account':
    $u = require_login();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!password_verify(input('current'), $u['password_hash'])) {
            flash('error', 'Current password is wrong.');
        } elseif (strlen(input('new')) < 8) {
            flash('error', 'New password needs 8+ characters.');
        } else {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash(input('new'), PASSWORD_DEFAULT), $u['id']]);
            flash('success', 'Password changed.');
        }
        redirect('account');
    }
    view('account');

default:
    http_response_code(404);
    view('legal', ['page' => '404']);
}

// ── Helpers used above ─────────────────────────────────────────
function save_meta_page(int $cid, array $page): void
{
    q('REPLACE INTO connections (client_id, platform, account_id, account_name, token) VALUES (?,?,?,?,?)', [$cid, 'facebook', $page['id'], $page['name'], $page['token']]);
    if ($page['ig'] !== '') {
        q('REPLACE INTO connections (client_id, platform, account_id, account_name, token) VALUES (?,?,?,?,?)', [$cid, 'instagram', $page['ig'], '@' . $page['ig_name'], $page['token']]);
    }
    flash('success', 'Connected Facebook Page "' . $page['name'] . '"' . ($page['ig'] !== ''
        ? ' and Instagram @' . $page['ig_name'] . '.'
        : '. No Instagram Business account is linked to this Page — link it in Meta Business Suite, then connect again.'));
}

function send_weekly_report_status(array $client): string
{
    if (!filter_var($client['email'], FILTER_VALIDATE_EMAIL)) {
        return 'Add the client\'s email address first.';
    }
    $last = q('SELECT ok, error FROM email_log WHERE client_id = ? ORDER BY id DESC LIMIT 1', [$client['id']])->fetch();
    return $last && $last['ok'] ? 'Weekly report sent to ' . $client['email'] . '.' : 'Report failed: ' . ($last['error'] ?? 'unknown');
}
