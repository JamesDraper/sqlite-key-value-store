<?php
declare(strict_types=1);

namespace SqliteKeyValueStore;

use function array_reduce;
use function preg_split;
use function array_pop;
use function implode;
use function sprintf;

/**
 * Cleans up a path, standardizing directory separators, removing ".." etc.
 *
 * This function should behave as much like the native PHP function realpath()
 * as possible but without checking that the file actually exists. It has been
 * placed into it's own file so that it can be independently tested.
 *
 * @param string $path the path to format.
 * @return string the formatted path.
 * @throws Exception If the provided path is invalid and cannot be formatted.
 */
function format_path(string $path): string
{
    $segments = preg_split('~[/\\\\]~', $path);

    $segments = array_reduce($segments, function (array $segments, string $segment) use ($path): array {
        switch ($segment) {
            case '.':
            case '':
                break;

            case '..':
                if (empty($segments)) {
                    throw new Exception(sprintf('Invalid file path: %s.', $path));
                }

                array_pop($segments);

                break;

            default:
                $segments[] = $segment;
        }

        return $segments;
    }, []);

    return implode('/', $segments);
}
