<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = $id > 0 ? get_enquiry($id) : null;
$rel = trim((string) ($row['resume'] ?? ''));
if (!$row || !preg_match('#^resumes/[a-f0-9]{32}\.(pdf|docx|doc)$#', $rel)) {
    http_response_code(404);
    echo 'Resume not found.';
    exit;
}

$path = CMS_UPLOADS . '/' . $rel;
if (!is_file($path)) {
    http_response_code(404);
    echo 'Resume not found.';
    exit;
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$name = preg_replace('/[^a-z0-9]+/i', '-', (string) ($row['name'] ?? 'applicant')) ?: 'applicant';
$download = 'resume-' . trim($name, '-') . '.' . $ext;

header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: attachment; filename="' . $download . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
exit;
