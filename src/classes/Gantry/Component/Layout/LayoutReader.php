<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Layout;

use Gantry\Component\File\CompiledYamlFile;

/**
 * Read layout from yaml file.
 */
class LayoutReader
{
    /**
     * Get layout version.
     *
     * @param array $data
     * @return int
     */
    public static function version(array &$data)
    {
        if (isset($data['version'])) {
            return $data['version'];
        }

        return isset($data['children']) && is_array($data['children']) ? 0 : 1;
    }

    /**
     * Make layout from array data.
     *
     * @param array $data
     * @return array
     */
    public static function data(array $data)
    {
        $version = static::version($data);
        $class = "Gantry\\Component\\Layout\\Version\\Format{$version}";

        if (!class_exists($class)) {
            throw new \RuntimeException('Layout file cound not be read: unsupported version {$version}.');
        }

        $reader = new $class($data);
        $result = $reader->load();

        // Make sure that all preset values are set by defining defaults.
        $result['preset'] += [
            'name' => '',
            'image' => 'gantry-admin://images/layouts/default.png'
        ];

        return $result;
    }

    /**
     * Read layout from yaml file and return parsed version of it.
     *
     * @param string $file
     * @return array
     */
    public static function read($file)
    {
        if (!$file) {
            return [];
        }

        $file = CompiledYamlFile::instance($file);
        $content = (array) $file->content();
        $file->free();

        return static::data($content);
    }
}
