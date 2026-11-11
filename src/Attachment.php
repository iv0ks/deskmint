<?php

declare(strict_types=1);

/**
 * Deskmint attachment resolution — intentionally vulnerable (CWE-22, level 3).
 *
 * The developer "sanitises" the requested filename by stripping "../"
 * sequences before joining it onto the attachments directory. The catch: the
 * replacement is NON-RECURSIVE — it runs a single pass — so a crafted payload
 * like "....//" survives it:
 *
 *     "....//"  --strip "../"-->  "../"
 *
 * The one pass removes the "../" embedded in the middle of "....//" and leaves
 * a working "../" behind. The result still escapes the attachments directory.
 *
 * This is a path traversal behind a naive, single-pass filter and is the whole
 * point of this lab. Do NOT reuse this pattern in real software.
 */

namespace Deskmint;

final class Attachment
{
    /**
     * Apply the (broken) sanitiser and resolve the on-disk path.
     *
     * VULNERABLE BY DESIGN: ``str_replace`` runs a single, non-recursive pass,
     * so overlapping payloads such as ``....//`` reconstitute a ``../`` after
     * the replacement and escape ``$attachmentsDir``.
     */
    public static function resolve(string $attachmentsDir, string $requested): string
    {
        // --- The vulnerable "sanitiser" --------------------------------------
        // Strip "../" once. This FEELS safe but is trivially bypassed because
        // it does not run until no matches remain (no loop / no regex /g over
        // overlaps): "....//" -> "../".
        $safe = str_replace('../', '', $requested);
        // ---------------------------------------------------------------------

        return $attachmentsDir . \DIRECTORY_SEPARATOR . $safe;
    }
}
