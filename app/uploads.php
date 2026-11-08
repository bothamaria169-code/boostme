<?php
/** Saves uploaded photos/videos under uploads/c<client>/<yyyymm>/. Photos become JPG (Instagram needs JPG). */
declare(strict_types=1);

const PHOTO_EXT = ['jpg', 'jpeg', 'png', 'webp'];
const VIDEO_EXT = ['mp4', 'mov'];

/** @return array{0: list<string>, 1: string, 2: string} [paths, media_type, error] */
function save_uploads(int $clientId, string $field = 'media'): array
{
    $files = $_FILES[$field] ?? null;
    if (!$files || !is_array($files['name'])) {
        return [[], '', ''];
    }
    $dir = 'c' . $clientId . '/' . date('Ym');
    @mkdir(ROOT . '/uploads/' . $dir, 0755, true);
    $paths = [];
    $type = '';
    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            return [$paths, $type, $name . ': upload failed (too big? the server limit is ' . ini_get('upload_max_filesize') . ').'];
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $kind = in_array($ext, PHOTO_EXT, true) ? 'photo' : (in_array($ext, VIDEO_EXT, true) ? 'video' : '');
        if ($kind === '') {
            return [$paths, $type, $name . ': only JPG, PNG, WEBP photos or MP4/MOV videos.'];
        }
        if ($type !== '' && ($type !== $kind || $kind === 'video')) {
            return [$paths, $type, 'A post is either photos (up to 10) or one video — not both.'];
        }
        $type = $kind;
        $base = $dir . '/' . bin2hex(random_bytes(8));
        if ($kind === 'photo') {
            $img = @imagecreatefromstring((string) file_get_contents($files['tmp_name'][$i]));
            if (!$img) {
                return [$paths, $type, $name . ': not a readable image.'];
            }
            // Keep within 1440px wide (Instagram's maximum) and save as JPG.
            if (imagesx($img) > 1440) {
                $img = imagescale($img, 1440);
            }
            $bg = imagecreatetruecolor(imagesx($img), imagesy($img));
            imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
            imagecopy($bg, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
            imagejpeg($bg, ROOT . '/uploads/' . $base . '.jpg', 88);
            $paths[] = $base . '.jpg';
        } else {
            move_uploaded_file($files['tmp_name'][$i], ROOT . '/uploads/' . $base . '.' . $ext);
            $paths[] = $base . '.' . $ext;
        }
        if (count($paths) >= 10) {
            break;
        }
    }
    return [$paths, $type, ''];
}
