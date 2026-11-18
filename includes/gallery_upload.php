<?php
// Tikai funkcijas. Oriģinālais augšupielādētais fails netiek saglabāts.
const GALLERY_MAX_UPLOAD = 5 * 1024 * 1024;
const GALLERY_MAX_DIMENSION = 4096;
const GALLERY_MAX_PIXELS = 12500000;
const GALLERY_OUTPUT_EDGE = 1600;
const GALLERY_MAX_OUTPUT = 768 * 1024;
const GALLERY_MAX_IMAGES = 60;

class GalleryValidationException extends RuntimeException {}

function galleryAlt($value): string {
    if (!is_string($value)) {
        throw new GalleryValidationException('Ievadi attēla alternatīvo tekstu.');
    }
    $value = trim($value);
    if ($value === '' || !preg_match('//u', $value) ||
        preg_match('/[\x00-\x1F\x7F]/', $value) ||
        preg_match_all('/./us', $value) > 250) {
        throw new GalleryValidationException('Alternatīvajam tekstam jābūt 1–250 rakstzīmes garam, vienā rindā.');
    }
    return $value;
}

function galleryMemoryLimit(): int {
    $raw = trim((string)ini_get('memory_limit'));
    if ($raw === '-1') return -1;
    if (!preg_match('/^(\d+)\s*([KMG]?)$/i', $raw, $m)) return 0;
    $unit = ['' => 1, 'K' => 1024, 'M' => 1048576, 'G' => 1073741824];
    return (int)$m[1] * $unit[strtoupper($m[2])];
}

function galleryProcessUpload($file): array {
    if (!is_array($file) || !isset($file['error']) || !is_int($file['error'])) {
        throw new GalleryValidationException('Izvēlies attēlu.');
    }
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        throw new GalleryValidationException('Fails pārsniedz augšupielādes izmēra limitu.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK ||
        !is_string($file['tmp_name'] ?? null) || !is_string($file['name'] ?? null) ||
        !is_uploaded_file($file['tmp_name'])) {
        throw new GalleryValidationException('Attēlu neizdevās saņemt.');
    }
    if (!extension_loaded('gd') || !class_exists('finfo') || !function_exists('imagejpeg')) {
        throw new GalleryValidationException('Serverī nepieciešami PHP GD (JPEG) un Fileinfo paplašinājumi.');
    }

    // Neuzticamies pārlūka norādītajam MIME tipam vai faila izmēram.
    $allowed = [
        'jpg' => ['image/jpeg', IMAGETYPE_JPEG],
        'jpeg' => ['image/jpeg', IMAGETYPE_JPEG],
        'png' => ['image/png', IMAGETYPE_PNG],
        'webp' => ['image/webp', IMAGETYPE_WEBP]
    ];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        throw new GalleryValidationException('Atļauti tikai JPG, JPEG, PNG un WebP attēli.');
    }
    $size = filesize($file['tmp_name']);
    if ($size === false || $size < 1 || $size > GALLERY_MAX_UPLOAD) {
        throw new GalleryValidationException('Attēls nedrīkst būt lielāks par 5 MB.');
    }
    $raw = file_get_contents($file['tmp_name'], false, null, 0, GALLERY_MAX_UPLOAD + 1);
    if ($raw === false || strlen($raw) !== $size) {
        throw new GalleryValidationException('Neizdevās nolasīt attēlu.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($raw);
    $info = @getimagesizefromstring($raw);
    if ($mime !== $allowed[$ext][0] || !$info || $info[2] !== $allowed[$ext][1]) {
        throw new GalleryValidationException('Faila saturs neatbilst attēla formātam.');
    }
    $w = (int)$info[0];
    $h = (int)$info[1];
    if ($w < 1 || $h < 1 || $w > GALLERY_MAX_DIMENSION ||
        $h > GALLERY_MAX_DIMENSION || $w * $h > GALLERY_MAX_PIXELS) {
        throw new GalleryValidationException('Attēls ir pārāk liels: līdz 4096 px katrā malā un 12,5 megapikseļiem.');
    }
    // Papildu konservatīva atmiņas pārbaude pirms dekodēšanas.
    $limit = galleryMemoryLimit();
    if ($limit === 0 || ($limit > 0 &&
        memory_get_usage(true) + $w * $h * 8 + 32 * 1024 * 1024 > $limit)) {
        throw new GalleryValidationException('Servera atmiņas limits ir par mazu šim attēlam. Samazini attēlu.');
    }
    $src = @imagecreatefromstring($raw);
    unset($raw);
    if ($src === false || imagesx($src) !== $w || imagesy($src) !== $h) {
        throw new GalleryValidationException('Attēls ir bojāts vai serveris neatbalsta šo formātu.');
    }
    $scale = min(1, GALLERY_OUTPUT_EDGE / max($w, $h));
    $outW = max(1, (int)round($w * $scale));
    $outH = max(1, (int)round($h * $scale));
    $dst = imagecreatetruecolor($outW, $outH);
    if ($dst === false) throw new GalleryValidationException('Neizdevās apstrādāt attēlu.');
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);
    if (!imagecopyresampled($dst, $src, 0, 0, 0, 0, $outW, $outH, $w, $h)) {
        throw new GalleryValidationException('Neizdevās samazināt attēlu.');
    }
    unset($src);

    // Izveidojam pilnīgi jaunu JPEG no pikseļiem: oriģinālais fails,
    // tā nosaukums, pievienotie dati un EXIF metadati netiek glabāti.
    foreach ([85, 75, 65, 55, 45] as $quality) {
        ob_start();
        try {
            $ok = @imagejpeg($dst, null, $quality);
        } finally {
            $bytes = ob_get_clean();
        }
        if ($ok && is_string($bytes) && strlen($bytes) <= GALLERY_MAX_OUTPUT &&
            substr($bytes, 0, 2) === "\xFF\xD8" && substr($bytes, -2) === "\xFF\xD9") {
            return ['bytes' => $bytes, 'width' => $outW, 'height' => $outH];
        }
    }
    throw new GalleryValidationException('Attēlu nevar pietiekami saspiest. Izvēlies mazāku attēlu.');
}
