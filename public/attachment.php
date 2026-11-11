<?php

declare(strict_types=1);

/**
 * Deskmint — attachment download endpoint (intentionally vulnerable).
 *
 *     GET /attachment.php?file=vpn-client.log   -> serves attachments/vpn-client.log
 *     GET /attachment.php?file=....//flag.txt   -> escapes via the broken filter
 *
 * See src/Attachment.php for the vulnerability writeup (CWE-22, level 3).
 */

require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/Attachment.php';

use Deskmint\Attachment;
use Deskmint\Config;

$file = $_GET['file'] ?? '';

if (!is_string($file) || $file === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Missing 'file' query parameter.";

    return;
}

$path = Attachment::resolve(Config::attachmentsDir(), $file);

if (!is_file($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Attachment not found: ' . $file;

    return;
}

// Serve the file inline. Content type is best-effort; most lab files are text.
header('Content-Type: application/octet-stream');
header('Content-Disposition: inline; filename="' . basename($path) . '"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
