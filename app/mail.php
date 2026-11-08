<?php
/**
 * Minimal SMTP mailer (SSL on 465 or STARTTLS on 587) + the weekly client report.
 * SMTP details live in .env so the password is never in the database.
 */
declare(strict_types=1);

function send_mail(string $to, string $subject, string $html, ?int $clientId = null): bool
{
    $host = env('MAIL_HOST');
    $port = (int) env('MAIL_PORT', '465');
    $user = env('MAIL_USER');
    $from = env('MAIL_FROM', $user);
    $name = env('MAIL_FROM_NAME', 'BoostMe');
    $error = '';
    $boundary = 'b' . bin2hex(random_bytes(8));
    $headers = "From: =?UTF-8?B?" . base64_encode($name) . "?= <$from>\r\nTo: <$to>\r\n"
        . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\nDate: " . date('r') . "\r\n"
        . "Message-ID: <" . bin2hex(random_bytes(8)) . '@' . (explode('@', $from)[1] ?? 'boostme') . ">\r\n"
        . "MIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode(html_entity_decode(strip_tags(str_replace(['<br>', '</p>', '</tr>'], "\n", $html)))))
        . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($html)) . "--$boundary--\r\n";

    if ($host === '') {
        // No SMTP configured: fall back to PHP mail() (works on most cPanel hosts).
        $ok = mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body,
            preg_replace('/^(To|Subject): .*\r\n/m', '', $headers), '-f' . $from);
        $error = $ok ? '' : 'mail() failed';
    } else {
        $ok = smtp_send($host, $port, $user, env('MAIL_PASS'), $from, $to, $headers . "\r\n" . $body, $error);
    }
    q('INSERT INTO email_log (client_id, recipient, subject, ok, error) VALUES (?,?,?,?,?)',
        [$clientId, $to, mb_substr($subject, 0, 200), (int) $ok, mb_substr($error, 0, 300)]);
    return $ok;
}

function smtp_send(string $host, int $port, string $user, string $pass, string $from, string $to, string $data, string &$error): bool
{
    $sock = @stream_socket_client(($port === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port, $no, $str, 20);
    if (!$sock) {
        $error = "Cannot reach $host:$port ($str)";
        return false;
    }
    $read = static function () use ($sock): string {
        $out = '';
        while (($line = fgets($sock, 515)) !== false) {
            $out .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $out;
    };
    $cmd = static function (string $c, string $expect) use ($sock, $read, &$error): bool {
        if ($c !== '') {
            fwrite($sock, $c . "\r\n");
        }
        $reply = $read();
        if (!str_starts_with($reply, $expect)) {
            $error = trim($reply) ?: 'No reply from mail server';
            return false;
        }
        return true;
    };
    $ok = $cmd('', '220') && $cmd('EHLO boostme', '250');
    if ($ok && $port === 587) {
        $ok = $cmd('STARTTLS', '220') && stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) && $cmd('EHLO boostme', '250');
    }
    $ok = $ok && $cmd('AUTH LOGIN', '334') && $cmd(base64_encode($user), '334') && $cmd(base64_encode($pass), '235')
        && $cmd("MAIL FROM:<$from>", '250') && $cmd("RCPT TO:<$to>", '25')
        && $cmd('DATA', '354') && $cmd(preg_replace('/^\./m', '..', $data) . "\r\n.", '250');
    fwrite($sock, "QUIT\r\n");
    fclose($sock);
    return $ok;
}

/** Builds the weekly report for one client. */
function weekly_report_html(array $client): array
{
    $since = date('Y-m-d H:i:s', strtotime('-7 days'));
    $until = date('Y-m-d H:i:s', strtotime('+7 days'));
    $results = q('SELECT r.*, p.caption, p.scheduled_at FROM post_results r JOIN posts p ON p.id = r.post_id
                  WHERE p.client_id = ? AND r.posted_at >= ? ORDER BY r.posted_at DESC', [$client['id'], $since])->fetchAll();
    $upcoming = (int) q("SELECT COUNT(*) FROM posts WHERE client_id = ? AND status = 'scheduled' AND scheduled_at <= ?", [$client['id'], $until])->fetchColumn();

    $byPlatform = array_fill_keys(array_keys(PLATFORMS), 0);
    $rows = '';
    foreach ($results as $r) {
        if ($r['ok']) {
            $byPlatform[$r['platform']] = ($byPlatform[$r['platform']] ?? 0) + 1;
        }
        $link = $r['link'] !== '' ? ' <a href="' . e($r['link']) . '" style="color:#c2185b">view</a>' : '';
        $rows .= '<tr><td style="padding:6px 8px;border-bottom:1px solid #eee">' . e(date('D j M', strtotime($r['posted_at']))) . '</td>'
            . '<td style="padding:6px 8px;border-bottom:1px solid #eee">' . e(PLATFORMS[$r['platform']] ?? $r['platform']) . '</td>'
            . '<td style="padding:6px 8px;border-bottom:1px solid #eee">' . e(mb_strimwidth(strtok($r['caption'], "\n") ?: '', 0, 60, '…')) . '</td>'
            . '<td style="padding:6px 8px;border-bottom:1px solid #eee">' . ($r['ok'] ? '✅' . $link : '⚠️') . '</td></tr>';
    }
    $total = array_sum($byPlatform);
    $tiles = '';
    foreach ($byPlatform as $p => $n) {
        $tiles .= '<td style="background:#fdf2f6;border-radius:8px;padding:12px;text-align:center"><div style="font-size:24px;font-weight:700">' . $n
            . '</div><div style="font-size:12px;color:#666">' . PLATFORMS[$p] . '</div></td><td width="8"></td>';
    }
    $subject = $client['name'] . ' — your social media week (' . $total . ' posts)';
    $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;color:#222">'
        . '<h2 style="color:#c2185b;margin-bottom:4px">Your week on social media</h2>'
        . '<p style="margin-top:0;color:#666">' . e(date('j M', strtotime('-7 days'))) . ' – ' . e(date('j M Y')) . '</p>'
        . '<p>Hi ' . e($client['contact_name'] ?: $client['name']) . ', here is what BoostMe posted for <b>' . e($client['name']) . '</b> this week.</p>'
        . '<table cellspacing="0" cellpadding="0"><tr>' . $tiles . '</tr></table>'
        . '<p><b>' . $upcoming . '</b> more post' . ($upcoming === 1 ? ' is' : 's are') . ' scheduled for the next 7 days.</p>'
        . ($rows !== '' ? '<table width="100%" cellspacing="0" style="font-size:13px"><tr style="background:#fafafa"><th align="left" style="padding:6px 8px">Day</th><th align="left" style="padding:6px 8px">Where</th><th align="left" style="padding:6px 8px">Post</th><th align="left" style="padding:6px 8px">Result</th></tr>' . $rows . '</table>' : '<p>No posts went out this week.</p>')
        . '<p style="margin:24px 0"><a href="' . e(url('dashboard')) . '" style="background:#c2185b;color:#fff;padding:12px 20px;border-radius:6px;text-decoration:none">Open your dashboard</a></p>'
        . '<p style="font-size:12px;color:#999">Sent by BoostMe · ' . e(parse_url(url(), PHP_URL_HOST)) . '</p></div>';
    return [$subject, $html];
}

function send_weekly_report(array $client): bool
{
    if (!filter_var($client['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    [$subject, $html] = weekly_report_html($client);
    $ok = send_mail($client['email'], $subject, $html, (int) $client['id']);
    if ($ok) {
        q('UPDATE clients SET last_report = NOW() WHERE id = ?', [$client['id']]);
    }
    return $ok;
}

/** Called by cron: on the report day (default Monday) from 08:00, once per client per week. */
function run_weekly_reports(): int
{
    if ((int) date('N') !== (int) setting('report_day', '1') || (int) date('G') < 8) {
        return 0;
    }
    $sent = 0;
    $clients = q("SELECT * FROM clients WHERE active = 1 AND weekly_email = 1 AND email <> ''
                  AND (last_report IS NULL OR last_report < ?)", [date('Y-m-d H:i:s', strtotime('-6 days'))])->fetchAll();
    foreach ($clients as $client) {
        $sent += (int) send_weekly_report($client);
    }
    return $sent;
}
