<?php
// Run every 5 minutes. cPanel → Cron Jobs (see DEPLOY.md for the exact line):
//   */5 * * * * /usr/local/bin/php /home/swiftfusionco/boostme.swiftfusion.co.za/cron.php >/dev/null 2>&1
// No CLI cron? Call https://boostme.swiftfusion.co.za/cron.php?key=CRON_KEY instead.
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/social.php';
require __DIR__ . '/app/mail.php';

if (PHP_SAPI !== 'cli' && (env('CRON_KEY') === '' || !hash_equals(env('CRON_KEY'), (string) ($_GET['key'] ?? '')))) {
    http_response_code(403);
    exit('Forbidden');
}
@set_time_limit(0);

// One run at a time.
$lock = fopen(sys_get_temp_dir() . '/boostme-cron.lock', 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    exit("Already running\n");
}

$posted = run_due_posts();
$reports = run_weekly_reports();
set_setting('cron_last_run', date('Y-m-d H:i:s'));
echo date('c') . ' posts: ' . json_encode($posted) . " reports: $reports\n";
