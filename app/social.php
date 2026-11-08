<?php
/**
 * Posting to Facebook, Instagram (Graph API) and TikTok (Content Posting API).
 * Ported from BidCo's includes/social.php + social-tiktok.php, made per-client.
 *
 * Every poster returns ['ok' => bool, 'id' => string, 'link' => string, 'error' => string].
 * Lesson kept from BoostMe v1: a Meta write can succeed while the reply errors, so never
 * retry a Meta write blind — failed posts are retried only when someone presses Retry.
 */
declare(strict_types=1);

const GRAPH_VERSION = 'v21.0';
const GRAPH = 'https://graph.facebook.com/' . GRAPH_VERSION . '/';
const META_SCOPES = ['pages_show_list', 'pages_manage_posts', 'pages_read_engagement',
    'instagram_basic', 'instagram_content_publish', 'business_management'];
const TIKTOK_API = 'https://open.tiktokapis.com/v2/';
const TIKTOK_SCOPES = ['user.info.basic', 'video.publish', 'video.upload'];

function result(bool $ok, string $id = '', string $error = '', string $link = ''): array
{
    return ['ok' => $ok, 'id' => $id, 'error' => $error, 'link' => $link];
}

function http_call(string $method, string $url, ?array $form = null, ?array $json = null, array $headers = []): array
{
    $h = curl_init($url);
    $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120, CURLOPT_CONNECTTIMEOUT => 15];
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        if ($json !== null) {
            $headers[] = 'Content-Type: application/json; charset=UTF-8';
            $opts[CURLOPT_POSTFIELDS] = json_encode($json ?: new stdClass(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $opts[CURLOPT_POSTFIELDS] = http_build_query($form ?? []);
        }
    }
    $opts[CURLOPT_HTTPHEADER] = $headers;
    curl_setopt_array($h, $opts);
    $body = (string) curl_exec($h);
    $err = curl_error($h);
    curl_close($h);
    if ($err !== '') {
        return ['error' => ['message' => $err, 'code' => 'network']];
    }
    return json_decode($body, true) ?: ['error' => ['message' => 'Unexpected reply: ' . mb_substr($body, 0, 150), 'code' => 'bad_reply']];
}

// ── Meta (Facebook + Instagram) ────────────────────────────────
function meta_app(): array
{
    return [
        'app_id'     => preg_replace('/\D/', '', setting('meta_app_id')) ?? '',
        'app_secret' => trim(setting('meta_app_secret')),
        'config_id'  => preg_replace('/\D/', '', setting('meta_config_id')) ?? '',
    ];
}

function graph_get(string $path, array $query): array
{
    return http_call('GET', GRAPH . $path . '?' . http_build_query($query));
}

function graph_post(string $path, array $fields): array
{
    $reply = http_call('POST', GRAPH . $path, $fields);
    if (isset($reply['error'])) {
        return result(false, '', (string) ($reply['error']['message'] ?? 'Facebook said no'));
    }
    $id = (string) ($reply['post_id'] ?? $reply['id'] ?? '');
    return result($id !== '', $id, $id === '' ? 'No id came back' : '');
}

function post_facebook(array $conn, array $media, string $type, string $caption): array
{
    $page = $conn['account_id'];
    $token = $conn['token'];
    if ($type === 'video') {
        $r = graph_post($page . '/videos', ['file_url' => $media[0], 'description' => $caption, 'access_token' => $token]);
        $r['link'] = $r['ok'] ? 'https://www.facebook.com/' . $r['id'] : '';
        return $r;
    }
    if (count($media) === 1) {
        $r = graph_post($page . '/photos', ['url' => $media[0], 'caption' => $caption, 'access_token' => $token]);
    } else {
        $attached = [];
        foreach ($media as $photo) {
            $up = graph_post($page . '/photos', ['url' => $photo, 'published' => 'false', 'access_token' => $token]);
            if ($up['ok']) {
                $attached[] = json_encode(['media_fbid' => $up['id']]);
            }
        }
        if (!$attached) {
            return result(false, '', 'No photo could be uploaded to Facebook');
        }
        $r = graph_post($page . '/feed', ['message' => $caption, 'attached_media' => $attached, 'access_token' => $token]);
    }
    $r['link'] = $r['ok'] ? 'https://www.facebook.com/' . $r['id'] : '';
    return $r;
}

function post_instagram(array $conn, array $media, string $type, string $caption): array
{
    $acct = $conn['account_id'];
    $token = $conn['token'];
    $caption = mb_substr($caption, 0, 2100);

    if ($type === 'video') {
        $container = graph_post($acct . '/media', ['media_type' => 'REELS', 'video_url' => $media[0], 'caption' => $caption, 'access_token' => $token]);
    } elseif (count($media) > 1) {
        $children = [];
        foreach (array_slice($media, 0, 10) as $photo) {
            $item = graph_post($acct . '/media', ['image_url' => $photo, 'is_carousel_item' => 'true', 'access_token' => $token]);
            if ($item['ok']) {
                $children[] = $item['id'];
            }
        }
        $container = graph_post($acct . '/media', ['media_type' => 'CAROUSEL', 'children' => implode(',', $children), 'caption' => $caption, 'access_token' => $token]);
    } else {
        $container = graph_post($acct . '/media', ['image_url' => $media[0], 'caption' => $caption, 'access_token' => $token]);
    }
    if (!$container['ok']) {
        return $container;
    }
    // Instagram fetches the media in the background; videos can take a few minutes.
    $tries = $type === 'video' ? 60 : 15;
    for ($i = 0; $i < $tries; $i++) {
        sleep($type === 'video' ? 5 : 2);
        $status = (string) (graph_get($container['id'], ['fields' => 'status_code', 'access_token' => $token])['status_code'] ?? '');
        if ($status === 'FINISHED') {
            break;
        }
        if ($status === 'ERROR' || $status === 'EXPIRED') {
            return result(false, '', 'Instagram could not process the media (' . $status . '). Photos must be JPG; videos MP4 (H.264), 3–90 s.');
        }
    }
    $r = graph_post($acct . '/media_publish', ['creation_id' => $container['id'], 'access_token' => $token]);
    if ($r['ok']) {
        $r['link'] = (string) (graph_get($r['id'], ['fields' => 'permalink', 'access_token' => $token])['permalink'] ?? '');
    }
    return $r;
}

// ── TikTok ─────────────────────────────────────────────────────
function tiktok_app(): array
{
    return ['client_key' => trim(setting('tiktok_client_key')), 'client_secret' => trim(setting('tiktok_client_secret'))];
}

function tiktok_error(array $reply): string
{
    if (isset($reply['error']) && is_string($reply['error']) && $reply['error'] !== '') {
        return $reply['error'] . ': ' . ($reply['error_description'] ?? '');
    }
    $code = (string) ($reply['error']['code'] ?? 'ok');
    return $code === 'ok' ? '' : $code . ': ' . ($reply['error']['message'] ?? '');
}

function tiktok_request(string $path, ?array $json = null, string $token = '', ?array $form = null): array
{
    $headers = $token !== '' ? ['Authorization: Bearer ' . $token] : [];
    if ($form !== null) {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        return http_call('POST', TIKTOK_API . $path, $form, null, $headers);
    }
    return http_call($json === null ? 'GET' : 'POST', TIKTOK_API . $path, null, $json, $headers);
}

/** A working access token for this connection, refreshed (and saved) when near expiry. */
function tiktok_token(array $conn, string &$error): string
{
    if ($conn['expires_at'] > time() + 600) {
        return $conn['token'];
    }
    $app = tiktok_app();
    $reply = tiktok_request('oauth/token/', null, '', [
        'client_key' => $app['client_key'], 'client_secret' => $app['client_secret'],
        'grant_type' => 'refresh_token', 'refresh_token' => (string) $conn['refresh_token'],
    ]);
    if (empty($reply['access_token'])) {
        $error = 'TikTok login expired (' . (tiktok_error($reply) ?: 'no token') . '). Press Connect TikTok again.';
        return '';
    }
    q('UPDATE connections SET token = ?, refresh_token = ?, expires_at = ? WHERE id = ?', [
        $reply['access_token'], $reply['refresh_token'] ?? $conn['refresh_token'],
        time() + (int) ($reply['expires_in'] ?? 86400), $conn['id'],
    ]);
    return (string) $reply['access_token'];
}

/** TikTok counts UTF-16 units; trims without splitting emoji. */
function tiktok_text(string $text, int $max): string
{
    $text = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', mb_convert_encoding($text, 'UTF-8', 'UTF-8'));
    $out = '';
    $units = 0;
    foreach (mb_str_split($text) as $ch) {
        $size = strlen(mb_convert_encoding($ch, 'UTF-16LE', 'UTF-8')) / 2;
        if ($units + $size > $max) {
            break;
        }
        $out .= $ch;
        $units += $size;
    }
    return trim($out);
}

/**
 * inbox  — lands in the TikTok app's inbox; the client taps it, adds a sound and posts. Works from day one.
 * direct — posts straight to the profile; until TikTok audits the app this is private (SELF_ONLY).
 */
function post_tiktok(array $conn, array $media, string $type, string $caption, string $mode): array
{
    $error = '';
    $token = tiktok_token($conn, $error);
    if ($token === '') {
        return result(false, '', $error);
    }
    $direct = $mode === 'direct';
    $info = [];
    if ($direct) {
        $creator = tiktok_request('post/publish/creator_info/query/', [], $token);
        if (($msg = tiktok_error($creator)) !== '') {
            return result(false, '', $msg);
        }
        $levels = (array) ($creator['data']['privacy_level_options'] ?? []);
        $info = [
            'privacy_level'   => in_array('PUBLIC_TO_EVERYONE', $levels, true) ? 'PUBLIC_TO_EVERYONE' : (string) ($levels[0] ?? 'SELF_ONLY'),
            'disable_comment' => false,
        ];
    }

    if ($type === 'video') {
        $body = ['source_info' => ['source' => 'PULL_FROM_URL', 'video_url' => $media[0]]];
        if ($direct) {
            $body['post_info'] = $info + ['title' => tiktok_text($caption, 2200)];
        }
        $reply = tiktok_request($direct ? 'post/publish/video/init/' : 'post/publish/inbox/video/init/', $body, $token);
    } else {
        $title = tiktok_text(trim(strtok($caption, "\n") ?: 'New post'), 90);
        $reply = tiktok_request('post/publish/content/init/', [
            'media_type'  => 'PHOTO',
            'post_mode'   => $direct ? 'DIRECT_POST' : 'MEDIA_UPLOAD',
            'post_info'   => $info + ['title' => $title ?: 'New post', 'description' => tiktok_text($caption, 4000), 'auto_add_music' => true],
            'source_info' => ['source' => 'PULL_FROM_URL', 'photo_images' => array_slice($media, 0, 35), 'photo_cover_index' => 0],
        ], $token);
    }
    if (($msg = tiktok_error($reply)) !== '') {
        if (str_contains($msg, 'url_ownership')) {
            $msg .= ' — verify ' . parse_url(url(), PHP_URL_HOST) . ' in the TikTok developer portal (Settings → TikTok domain check).';
        }
        return result(false, '', $msg);
    }
    $id = (string) ($reply['data']['publish_id'] ?? '');
    return result($id !== '', $id, $id === '' ? 'TikTok gave no publish id' : '');
}

// ── Running posts ──────────────────────────────────────────────
function full_caption(array $post, array $client, string $platform): string
{
    $caption = trim($post['caption']);
    if ($client['signature'] !== '' && !str_contains($caption, $client['signature'])) {
        $caption .= "\n\n" . $client['signature'];
    }
    $tags = trim($client['hashtags']);
    if ($platform === 'tiktok') {   // TikTok: fewer tags read better
        $tags = implode(' ', array_slice(preg_split('/\s+/', $tags) ?: [], 0, 5));
    }
    return $tags !== '' && !str_contains($caption, '#') ? $caption . "\n\n" . $tags : $caption;
}

/** Posts one post to every platform that has not succeeded yet. Returns the new status. */
function run_post(int $postId): string
{
    // Claim it atomically so two cron runs never post the same thing twice.
    $claimed = q("UPDATE posts SET status = 'posting' WHERE id = ? AND status IN ('scheduled','failed','partial')", [$postId])->rowCount();
    if (!$claimed) {
        return 'skipped';
    }
    $post = q('SELECT * FROM posts WHERE id = ?', [$postId])->fetch();
    $client = client((int) $post['client_id']);
    $conns = connections((int) $post['client_id']);
    $media = array_map('media_url', json_decode($post['media'], true) ?: []);
    $done = q('SELECT platform FROM post_results WHERE post_id = ? AND ok = 1', [$postId])->fetchAll(PDO::FETCH_COLUMN);

    $platforms = array_filter(explode(',', $post['platforms']));
    $okCount = count(array_intersect($platforms, $done));
    foreach (array_diff($platforms, $done) as $platform) {
        if (empty($conns[$platform])) {
            $r = result(false, '', PLATFORMS[$platform] . ' is not connected for this client.');
        } elseif (!$media) {
            $r = result(false, '', 'The post has no photo or video.');
        } else {
            $caption = full_caption($post, $client, $platform);
            try {
                $r = match ($platform) {
                    'facebook'  => post_facebook($conns[$platform], $media, $post['media_type'], $caption),
                    'instagram' => post_instagram($conns[$platform], $media, $post['media_type'], $caption),
                    'tiktok'    => post_tiktok($conns[$platform], $media, $post['media_type'], $caption, $client['tiktok_mode']),
                };
            } catch (Throwable $ex) {
                $r = result(false, '', $ex->getMessage());
            }
        }
        q('INSERT INTO post_results (post_id, platform, ok, remote_id, link, error, posted_at) VALUES (?,?,?,?,?,?,NOW())
           ON DUPLICATE KEY UPDATE ok = VALUES(ok), remote_id = VALUES(remote_id), link = VALUES(link), error = VALUES(error), posted_at = NOW()',
            [$postId, $platform, (int) $r['ok'], $r['id'], mb_substr($r['link'], 0, 300), mb_substr($r['error'], 0, 500)]);
        $okCount += (int) $r['ok'];
    }
    $status = $okCount === count($platforms) ? 'posted' : ($okCount > 0 ? 'partial' : 'failed');
    q('UPDATE posts SET status = ? WHERE id = ?', [$status, $postId]);
    return $status;
}

/** Every scheduled post whose time has come. Failed ones wait for a manual Retry. */
function run_due_posts(): array
{
    $ids = q("SELECT id FROM posts WHERE status = 'scheduled' AND scheduled_at <= ? ORDER BY scheduled_at LIMIT 20", [date('Y-m-d H:i:s')])->fetchAll(PDO::FETCH_COLUMN);
    $out = [];
    foreach ($ids as $id) {
        $out[$id] = run_post((int) $id);
    }
    return $out;
}
