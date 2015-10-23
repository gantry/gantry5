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
        $reader = static::getClass($version, $data);
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

    protected static function object(array $items, $container = true)
    {
        foreach ($items as &$item) {
            $item = (object) $item;

            if (!isset($item->id)) {
                $item->id = static::id();
            }

            // TODO: remove extra fields..
            /*
            if (empty($item->subtype)) {
                unset($item->subtype);
            }

            if (empty($item->title) || $item->title === 'Untitled') {
                unset($item->title);
            }
            */

            if (isset($item->attributes) && (is_array($item->attributes) || is_object($item->attributes))) {
                $item->attributes = (object) $item->attributes;
            } else {
                $item->attributes = (object) [];
            }

            if (!empty($item->children) && is_array($item->children)) {
                $item->children = static::object($item->children, false);
            }
            /*
            else {
                unset($item->children);
            }
            */

            if ($container) {
                static::normalize($item);
            }
        }

        return $items;
    }

    protected static function normalize(&$item)
    {
        if (isset($item->attributes->boxed)) {
            return;
        }

        if (isset($item->children) && count($item->children) === 1) {
            $child = reset($item->children);
            if ($item->type === 'container') {
                // Remove parent container only if the only child is a section.
                if ($child->type === 'section') {
                    $child->attributes->boxed = 1;
                    $item = $child;
                }
            } elseif ($child->type === 'container') {
                // Remove child container.
                $item->attributes->boxed = 0;
                $item->children = $child->children;
            }
        }
        if (!isset($item->attributes->boxed)) {
            $item->attributes->boxed = -1;
        }
    }

    /**
     * Convert layout into file format.
     *
     * @param array $preset
     * @param array $structure
     * @param int $version
     * @return mixed
     */
    public static function store(array $preset, array $structure, $version = 2)
    {
        $reader = static::getClass($version);

        return $reader->store($preset, $structure);
    }

    /**
     * @param int $version
     * @param array $data
     * @return object
     */
    protected static function getClass($version, array $data = [])
    {
        $class = "Gantry\\Component\\Layout\\Version\\Format{$version}";

        if (!class_exists($class)) {
            throw new \RuntimeException('Layout file cound not be read: unsupported version {$version}.');
        }

        return new $class($data);

    }
}
