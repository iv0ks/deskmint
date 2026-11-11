<?php

declare(strict_types=1);

/**
 * Deskmint demo data.
 *
 * A small, in-memory set of support tickets so the helpdesk behaves like a real
 * product. Attachment filenames map to files under the attachments directory
 * (see Config::attachmentsDir()); they are downloaded through attachment.php.
 */

namespace Deskmint;

final class Tickets
{
    /**
     * @return list<array{
     *   id:int, subject:string, requester:string, status:string,
     *   priority:string, updated:string, body:string, attachments:list<string>
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'id' => 4821,
                'subject' => 'VPN disconnects every few minutes',
                'requester' => 'marta.ruiz@northwind.example',
                'status' => 'open',
                'priority' => 'high',
                'updated' => '2024-07-03 09:12',
                'body' => 'Since the last client update the VPN drops roughly '
                    . 'every 5 minutes. I attached the client log.',
                'attachments' => ['vpn-client.log'],
            ],
            [
                'id' => 4815,
                'subject' => 'Request: additional license for Figma',
                'requester' => 'tom.becker@northwind.example',
                'status' => 'pending',
                'priority' => 'low',
                'updated' => '2024-07-02 16:40',
                'body' => 'We onboarded a new designer and need one more seat. '
                    . 'Approval screenshot attached.',
                'attachments' => ['approval.txt'],
            ],
            [
                'id' => 4790,
                'subject' => 'Printer on 3rd floor returns error E-204',
                'requester' => 'help@northwind.example',
                'status' => 'closed',
                'priority' => 'medium',
                'updated' => '2024-06-28 11:05',
                'body' => 'Resolved by replacing the fuser unit. Service report '
                    . 'attached for the records.',
                'attachments' => ['service-report.txt'],
            ],
        ];
    }

    /**
     * @return array{
     *   id:int, subject:string, requester:string, status:string,
     *   priority:string, updated:string, body:string, attachments:list<string>
     * }|null
     */
    public static function find(int $id): ?array
    {
        foreach (self::all() as $ticket) {
            if ($ticket['id'] === $id) {
                return $ticket;
            }
        }

        return null;
    }
}
