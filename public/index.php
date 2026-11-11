<?php

declare(strict_types=1);

/**
 * Deskmint — ticket list (home).
 */

require __DIR__ . '/../src/View.php';
require __DIR__ . '/../src/Tickets.php';

use Deskmint\Tickets;
use Deskmint\View;

$tickets = Tickets::all();

$rows = '';
foreach ($tickets as $t) {
    $rows .= sprintf(
        '<a class="ticket-row" href="/ticket.php?id=%d">'
            . '<span class="ticket-id">#%d</span>'
            . '<span class="ticket-subject">%s<small>%s</small></span>'
            . '<span class="ticket-badges">%s %s</span>'
            . '<span class="ticket-updated">%s</span>'
            . '<span class="ticket-attach">%s</span>'
            . '</a>',
        $t['id'],
        $t['id'],
        View::esc($t['subject']),
        View::esc($t['requester']),
        View::statusBadge($t['status']),
        View::priorityBadge($t['priority']),
        View::esc($t['updated']),
        count($t['attachments']) > 0 ? '📎 ' . count($t['attachments']) : ''
    );
}

$open = 0;
$pending = 0;
foreach ($tickets as $t) {
    if ($t['status'] === 'open') {
        ++$open;
    }
    if ($t['status'] === 'pending') {
        ++$pending;
    }
}

$body = <<<HTML
<div class="page-head">
    <div>
        <h1>Tickets</h1>
        <p class="sub">Inbox · all open and recent requests</p>
    </div>
    <div class="stats">
        <span class="stat"><strong>{$open}</strong> open</span>
        <span class="stat"><strong>{$pending}</strong> pending</span>
    </div>
</div>
<div class="ticket-list">
    <div class="ticket-row head">
        <span>ID</span><span>Subject</span><span>Status · Priority</span>
        <span>Updated</span><span></span>
    </div>
    {$rows}
</div>
HTML;

echo View::layout('Tickets', 'tickets', $body);
