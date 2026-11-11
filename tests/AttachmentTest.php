<?php

declare(strict_types=1);

/**
 * Tests for Deskmint's attachment resolution.
 *
 * These document the behaviour executably:
 *   - a listed attachment resolves inside the attachments directory,
 *   - the naive "../" filter blocks a plain "../" payload, yet
 *   - the non-recursive filter is bypassed by "....//", escaping the dir.
 *
 * The exploit test deliberately asserts that the vulnerability is present — if
 * someone "fixes" the single-pass filter, these turn red and flag that the lab
 * no longer teaches what it claims to (CWE-22, level 3).
 */

namespace Deskmint\Tests;

use Deskmint\Attachment;
use PHPUnit\Framework\TestCase;

final class AttachmentTest extends TestCase
{
    private string $dir = '/app/attachments';

    public function testLegitimateFileStaysInsideTheDirectory(): void
    {
        $path = Attachment::resolve($this->dir, 'vpn-client.log');
        self::assertSame('/app/attachments/vpn-client.log', $path);
    }

    public function testPlainDotDotSlashIsNeutralisedBySingleFilter(): void
    {
        // The naive filter strips "../", so a plain payload no longer escapes:
        // it collapses to a filename *inside* the attachments directory.
        $path = Attachment::resolve($this->dir, '../flag.txt');
        self::assertSame('/app/attachments/flag.txt', $path);
    }

    public function testDoubledPayloadBypassesTheNonRecursiveFilter(): void
    {
        // "....//" survives the single pass -> "../", so the path escapes.
        $path = Attachment::resolve($this->dir, '....//flag.txt');
        self::assertSame('/app/attachments/../flag.txt', $path);
        // And that resolved path really points outside the attachments dir.
        self::assertStringContainsString('/app/attachments/../', $path);
    }
}
