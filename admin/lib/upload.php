<?php
declare(strict_types=1);

function ensure_uploads_dir(): void
{
    if (!is_dir(CMS_UPLOADS)) {
        mkdir(CMS_UPLOADS, 0755, true);
    }
}

function uploaded_files(string $field): array
{
    if (empty($_FILES[$field]) || !isset($_FILES[$field]['name'])) {
        return [];
    }
    $raw = $_FILES[$field];
    if (!is_array($raw['name'])) {
        return [$raw];
    }
    $out = [];
    foreach ($raw['name'] as $i => $name) {
        $out[] = [
            'name' => $name,
            'type' => $raw['type'][$i] ?? '',
            'tmp_name' => $raw['tmp_name'][$i] ?? '',
            'error' => $raw['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $raw['size'][$i] ?? 0,
        ];
    }
    return $out;
}

function store_uploaded_image(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('No image selected.');
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed. Try a smaller JPG, PNG, or WebP.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Image must be 5 MB or smaller.');
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    $info = @getimagesize($tmp);
    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF => 'gif',
    ];
    if (!is_array($info) || !isset($allowed[$info[2]])) {
        throw new RuntimeException('Choose a JPG, PNG, WebP, or GIF image.');
    }

    ensure_uploads_dir();
    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$info[2]];
    $dest = CMS_UPLOADS . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }
    return CMS_UPLOADS_URL . '/' . $name;
}

function save_uploaded_image(string $field, ?string $keep = null): ?string
{
    $files = uploaded_files($field);
    if (!$files) {
        return $keep;
    }
    $file = $files[0];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $keep;
    }
    return store_uploaded_image($file);
}

function save_uploaded_images(string $field): array
{
    $paths = [];
    foreach (uploaded_files($field) as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $paths[] = store_uploaded_image($file);
    }
    return $paths;
}
