<?php

declare(strict_types=1);

/**
 * Deskmint configuration.
 *
 * Runtime paths depend on the DATA_ROOT environment variable (the Docker image
 * sets DATA_ROOT=/app). Nothing here has side effects on include; callers ask
 * for a value through the helper functions below.
 *
 * Runtime layout:
 *   <data_root>/               <- DATA_ROOT        (holds flag.txt, the target)
 *   <data_root>/attachments/   <- ATTACHMENTS dir  (the served ticket files)
 */

namespace Deskmint;

final class Config
{
    /** Sub-directory (under DATA_ROOT) that attachments are served from. */
    public const ATTACHMENTS_DIRNAME = 'attachments';

    /** Name of the challenge flag file (the path-traversal target). */
    public const FLAG_FILENAME = 'flag.txt';

    /** Default flag value; overridable via the FLAG env var / build arg. */
    public const DEFAULT_FLAG = 'FLAG{DESKMINT_ROOT_TICKET}';

    /** Root that holds both the attachments dir and the flag. */
    public static function dataRoot(): string
    {
        $root = getenv('DATA_ROOT');
        if ($root === false || $root === '') {
            // Fall back to the repo root (one level above this src/ file).
            $root = \dirname(__DIR__);
        }

        return $root;
    }

    /** Absolute path of the directory attachments are served from. */
    public static function attachmentsDir(): string
    {
        return self::dataRoot() . \DIRECTORY_SEPARATOR . self::ATTACHMENTS_DIRNAME;
    }
}
