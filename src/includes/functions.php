<?php
namespace Gantry;

use \Gantry\Component\Stream\StreamHelper;

/**
 * Checks if a path is absolute or relative.
 *
 * @param  string  $path  Path to check
 *
 * @return boolean
 */
function is_path_absolute($path)
{
    return (isset($path[0]) &&
        ($path[0] == '/'                                    // Unix root path
        || $path[0] == '\\'                                 // Windows single \ or Windows UNC root \\
        || preg_match('#^[a-z]:[/\\]#i', $path)             // Windows drive letter root c:\ or c:/
        || preg_match('#^\w[\w\d\-\.]+://(?!/)#', $path)    // PHP streams
        )
    );
}

/**
 * Resolve path
 *
 * @param $path
 * @return string|bool
 */
function realpath($path)
{
    // For streams we just resolve absolute path.
    if (is_stream($path)) {
        return StreamHelper::resolveLocalStreamPath($path);
    }

    // Convert relative paths to absolute.
    if (!is_path_absolute($path)) {
        $path = GANTRY5_ROOT . '/' . $path;
    }

    if (strpos($path, '\\\\') === 0) {
        // Windows UNC: remove the UNC hostname and share.
        $offset = strpos($path, '\\', strpos($path, '\\', 2) + 1) + 1;
        $path_prefix = substr($path, 0, $offset);
        $path        = substr($path, $offset);

    } elseif (preg_match('#^[a-z]:#i', $path)) {
        // Windows drive: remove the drive letter and colon.
        $path_prefix = $path[0] . ':';
        $path        = substr($path, 2);

    } else {
        $path_prefix = '';
    }

    // Switch to use UNIX slashes.
    $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

    // Break the string into bits.
    $bits = array();
    foreach (explode('/', $path) as $i => $folder) {
        if ($folder == '' || $folder == '.') {
            continue;
        } elseif ($folder == '..' && $i > 0 && end($bits) != '..') {
            array_pop($bits);
        } else {
            $bits[] = $folder;
        }
    }

    if (in_array('..', $bits)) {
        // We couldn't fully resolve path: failing.
        return false;
    }

    // Prepend the path prefix.
    array_unshift($bits, $path_prefix);

    $resolved = implode('/', $bits);

    // @todo If the file exists fine and open_basedir only has one path we should be able to prepend it
    // because we must be inside that basedir, the question is where...
    // @internal The slash in is_dir() gets around an open_basedir restriction
    if (!@file_exists($resolved) || (!@is_dir($resolved . '/') && !is_file($resolved))) {
        return false;
    }

    // Put the slashes back to the native operating systems slashes
    $resolved = str_replace('/', DIRECTORY_SEPARATOR, $resolved);

    // Check for DIRECTORY_SEPARATOR at the end (and remove it!)
    if (substr($resolved, -1) == DIRECTORY_SEPARATOR) {
        return substr($resolved, 0, -1);
    }

    return $resolved; // We got here, in the end!
}

function is_stream($path)
{
    return (strpos($path, '://') !== false);
}


