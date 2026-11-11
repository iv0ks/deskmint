<?php

declare(strict_types=1);

/**
 * Tiny view helpers for Deskmint.
 *
 * Plain PHP rendering (no template engine) — enough for a small helpdesk UI.
 * The layout gives every page the same chrome: a top bar, a left sidebar and a
 * footer, so the app reads like a real product.
 */

namespace Deskmint;

final class View
{
    public static function esc(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES, 'UTF-8');
    }

    /** Render the shared chrome around a page body. */
    public static function layout(string $title, string $active, string $body): string
    {
        $t = self::esc($title);
        $nav = self::sidebar($active);

        return <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>{$t} · Deskmint</title>
            <link rel="icon" type="image/svg+xml" href="/favicon.svg">
            <link rel="stylesheet" href="/style.css">
        </head>
        <body>
            <header class="topbar">
                <div class="brand">
                    <span class="brand-mark" aria-hidden="true">◈</span>
                    <span class="brand-name">Deskmint</span>
                    <span class="brand-sub">Support</span>
                </div>
                <div class="topbar-right">
                    <input class="search" type="search" placeholder="Search tickets…" disabled>
                    <span class="avatar" title="Agent">AO</span>
                </div>
            </header>
            <div class="shell">
                <aside class="sidebar">{$nav}</aside>
                <main class="content">{$body}</main>
            </div>
            <footer class="footer">
                <span>Deskmint Support Desk</span>
                <span class="muted">Attachments are served via
                    <code>/attachment.php?file=&lt;name&gt;</code></span>
            </footer>
        </body>
        </html>
        HTML;
    }

    private static function sidebar(string $active): string
    {
        $items = [
            'tickets' => ['🎫', 'Tickets'],
            'kb' => ['📚', 'Knowledge Base'],
            'reports' => ['📊', 'Reports'],
            'settings' => ['⚙️', 'Settings'],
        ];
        $html = '<nav class="nav">';
        foreach ($items as $key => [$icon, $label]) {
            $cls = $key === $active ? 'nav-item active' : 'nav-item';
            $href = $key === 'tickets' ? '/index.php' : '#';
            $dis = $key === 'tickets' ? '' : ' aria-disabled="true"';
            $html .= sprintf(
                '<a class="%s" href="%s"%s><span class="nav-ico" aria-hidden="true">%s</span>%s</a>',
                $cls,
                $href,
                $dis,
                $icon,
                self::esc($label)
            );
        }

        return $html . '</nav>';
    }

    /** Coloured badge for a ticket status. */
    public static function statusBadge(string $status): string
    {
        $s = self::esc($status);

        return '<span class="badge status-' . $s . '">' . ucfirst($s) . '</span>';
    }

    /** Coloured badge for a ticket priority. */
    public static function priorityBadge(string $priority): string
    {
        $p = self::esc($priority);

        return '<span class="badge prio-' . $p . '">' . ucfirst($p) . '</span>';
    }
}
