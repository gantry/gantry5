<?php
// Test-only stand-in for the platform Gantry class (static path helpers used by controllers).
namespace Gantry\Framework;

if (!class_exists('Gantry\Framework\Gantry', false)) {
    class Gantry
    {
        public static function pathinfo($path, $flags = null)
        {
            $path = str_replace(['%2F', '%5C'], ['/', '\\'], rawurlencode($path));
            $info = null === $flags ? pathinfo($path) : pathinfo($path, (int) $flags);

            return is_array($info) ? array_map('rawurldecode', $info) : rawurldecode($info);
        }

        public static function basename($path, $suffix = '')
        {
            return rawurldecode(basename(str_replace(['%2F', '%5C'], '/', rawurlencode($path)), $suffix));
        }
    }
}
