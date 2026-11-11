<?php

declare(strict_types=1);

/**
 * Deskmint — single ticket view (with downloadable attachments).
 */

require __DIR__ . '/../src/View.php';
require __DIR__ . '/../src/Tickets.php';

use Deskmint\Tickets;
use Deskmint\View;

$id = (int) ($_GET['id'] ?? 0);
$ticket = Tickets::find($id);

if ($ticket === null) {
    http_response_code(404);
    echo View::layout('Not found', 'tickets', '<div class="page-head"><h1>404</h1>'
        . '<p class="sub">No ticket with that id.</p></div>'
        . '<p><a class="back" href="/index.php">← Back to tickets</a></p>');

    return;
}

$attachments = '';
foreach ($ticket['attachments'] as $name) {
    $enc = rawurlencode($name);
    $attachments .= sprintf(
        '<li class="attach"><span class="attach-ico" aria-hidden="true">📎</span>'
            . '<span class="attach-name">%s</span>'
            . '<a class="btn" href="/attachment.php?file=%s">Download</a></li>',
        View::esc($name),
        $enc
    );
}
if ($attachments === '') {
    $attachments = '<li class="attach muted">No attachments.</li>';
}

$subject = View::esc($ticket['subject']);
$requester = View::esc($ticket['requester']);
$updated = View::esc($ticket['updated']);
$bodyText = View::esc($ticket['body']);
$status = View::statusBadge($ticket['status']);
$priority = View::priorityBadge($ticket['priority']);
$tid = $ticket['id'];

$body = <<<HTML
<p><a class="back" href="/index.php">← Back to tickets</a></p>
<div class="ticket-head">
    <h1>#{$tid} · {$subject}</h1>
    <div class="ticket-meta">{$status} {$priority}
        <span class="muted">· opened by {$requester} · updated {$updated}</span>
    </div>
</div>
<div class="ticket-body">
    <p>{$bodyText}</p>
</div>
<section class="attachments">
    <h2>Attachments</h2>
    <ul class="attach-list">{$attachments}</ul>
</section>
HTML;

echo View::layout('#' . $tid . ' ' . $ticket['subject'], 'tickets', $body);
