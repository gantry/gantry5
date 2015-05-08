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

namespace Gantry\Component\Twig;

use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Translator\TranslatorInterface;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccess;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class TwigExtension extends \Twig_Extension
{
    use GantryTrait;

    /**
     * Returns extension name.
     *
     * @return string
     */
    public function getName()
    {
        return 'UrlExtension';
    }

    /**
     * Return a list of all filters.
     *
     * @return array
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('fieldName', [$this, 'fieldNameFilter']),
            new \Twig_SimpleFilter('html', [$this, 'htmlFilter']),
            new \Twig_SimpleFilter('url', [$this, 'urlFunc']),
            new \Twig_SimpleFilter('trans', [$this, 'transFilter']),
            new \Twig_SimpleFilter('repeat', [$this, 'repeatFilter']),
            new \Twig_SimpleFilter('base64', 'base64_encode'),
        );
    }

    /**
     * Return a list of all functions.
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('nested', [$this, 'nestedFunc']),
            new \Twig_SimpleFunction('url', [$this, 'urlFunc']),
            new \Twig_SimpleFunction('parse_assets', [$this, 'parseAssetsFunc']),
            new \Twig_SimpleFunction('colorContrast', [$this, 'colorContrastFunc'])
        );
    }

    /**
     * @return array
     */
    public function getTokenParsers()
    {
        return array(new TokenParserTry());
    }

    /**
     * Filters field name by changing dot notation into array notation.
     *
     * @param  string  $str
     * @return string
     */
    public function fieldNameFilter($str)
    {
        $path = explode('.', $str);

        return array_shift($path) . ($path ? '[' . implode('][', $path) . ']' : '');
    }

    /**
     * Parse raw html.
     *
     * @param  string  $str
     * @return string
     */
    public function htmlFilter($str)
    {
        $str = preg_replace_callback('^(\s)(src|href)="(.*?)"^', [$this, 'linkHandler'], $str);
        $str = preg_replace_callback('^(\s)url\((.*?)\)^', [$this, 'urlHandler'], $str);

        return $str;
    }

    public function linkHandler(array $matches)
    {
        $url = $this->urlFunc($matches[3]);
        return "{$matches[1]}{$matches[2]}=\"{$url}\"";
    }

    public function urlHandler(array $matches)
    {
        $url = $this->urlFunc(trim($matches[2]), '"\'');
        return "{$matches[1]}url({$url})";
    }

    /**
     * Translate string.
     *
     * @param  string  $str
     * @return string
     */
    public function transFilter($str)
    {
        /** @var TranslatorInterface $translator */
        static $translator;

        if (!$translator) {
            $translator = self::gantry()['translator'];
        }

        return $translator->translate($str);
    }

    /**
     * Repeat string x times.
     *
     * @param  string  $str
     * @param  int  $count
     * @return string
     */
    public function repeatFilter($str, $count)
    {
        return str_repeat($str, (int) $count);
    }

    /**
     * Get value by using dot notation for nested arrays/objects.
     *
     * @example {{ nested(array, 'this.is.my.nested.variable')|json_encode }}
     *
     * @param array   $items      Array of items.
     * @param string  $name       Dot separated path to the requested value.
     * @param mixed   $default    Default value (or null).
     * @param string  $separator  Separator, defaults to '.'
     * @return mixed  Value.
     */
    public function nestedFunc($items, $name, $default = null, $separator = '.')
    {
        if ($items instanceof NestedArrayAccess) {
            return $items->get($name, $default, $separator);
        }
        $path = explode($separator, $name);
        $current = $items;
        foreach ($path as $field) {
            if (is_object($current) && isset($current->{$field})) {
                $current = $current->{$field};
            } elseif (is_array($current) && isset($current[$field])) {
                $current = $current[$field];
            } else {
                return $default;
            }
        }

        return $current;
    }

    /**
     * Return URL to the resource.
     *
     * @example {{ url('theme://images/logo.png')|default('http://www.placehold.it/150x100/f4f4f4') }}
     *
     * @param  string $input    Resource to be located.
     * @param  bool $domain     True to include domain name.
     * @return string|null      Returns url to the resource or null if resource was not found.
     */
    public function urlFunc($input, $domain = false)
    {
        return Document::url(trim((string) $input), $domain);
    }

    /**
     * Move supported document head elements into platform document object, return all
     * unsupported tags in a string.
     *
     * @param string $input
     * @param string $location
     * @param int $priority
     * @return string
     */
    public function parseAssetsFunc($input, $location = 'head', $priority = 0)
    {
        $doc = new \DOMDocument();
        $doc->loadHTML('<html><head>' . $input . '</head><body></body></html>');
        $raw = [];
        /** @var \DomElement $element */
        foreach ($doc->getElementsByTagName('head')->item(0)->childNodes as $element) {
            $result = ['tag' => $element->tagName, 'content' => $element->textContent];
            foreach ($element->attributes as $attribute) {
                $result[$attribute->name] = $attribute->value;
            }
            $success = Document::addHeaderTag($result, $location, $priority);
            if (!$success) {
                $raw[] = $doc->saveHTML($element);
            }
        }

        return implode("\n", $raw);
    }

    public function colorContrastFunc($value) {
        $value = str_replace(' ', '', $value);
        $rgb = new \stdClass;
        $opacity = 1;

        if (substr($value, 0, 3) != 'rgb') {
            $value = str_replace('#', '', $value);
            if (strlen($value) == 3) {
                $h0 = str_repeat(substr($value, 0, 1), 2);
                $h1 = str_repeat(substr($value, 1, 1), 2);
                $h2 = str_repeat(substr($value, 2, 1), 2);
                $value = $h0 . $h1 . $h2;
            }

            $rgb->r = hexdec(substr($value, 0, 2));
            $rgb->g = hexdec(substr($value, 2, 2));
            $rgb->b = hexdec(substr($value, 4, 2));
        } else {
            preg_match("/(\\d+),\\s*(\\d+),\\s*(\\d+)(?:,\\s*(1\\.|0?\\.?[0-9]?+))?/uim", $value, $matches);
            $rgb->r = $matches[1];
            $rgb->g = $matches[2];
            $rgb->b = $matches[3];
            $opacity = isset($matches[4]) ? $matches[4] : 1;
            $opacity = substr($opacity, 0, 1) == '.' ? '0' . $opacity : $opacity;
        }

        $yiq = ((($rgb->r * 299) + ($rgb->g * 587) + ($rgb->b * 114)) / 1000) >= 128;
        $contrast = $yiq || ($opacity == 0 || (float) $opacity < 0.35);

        return $contrast;
    }
}
