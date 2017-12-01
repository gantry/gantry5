<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Twig;

use Gantry\Component\Content\Document\HtmlDocument;
use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Translator\TranslatorInterface;
use Gantry\Framework\Gantry;
use Gantry\Framework\Request;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccess;

class TwigExtension extends \Twig_Extension
{
    use GantryTrait;

    /**
     * Return a list of all filters.
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('fieldName', [$this, 'fieldNameFilter']),
            new \Twig_SimpleFilter('html', [$this, 'htmlFilter']),
            new \Twig_SimpleFilter('url', [$this, 'urlFunc']),
            new \Twig_SimpleFilter('trans_key', [$this, 'transKeyFilter']),
            new \Twig_SimpleFilter('trans', [$this, 'transFilter']),
            new \Twig_SimpleFilter('repeat', [$this, 'repeatFilter']),
            new \Twig_SimpleFilter('json_decode', [$this, 'jsonDecodeFilter']),
            new \Twig_SimpleFilter('values', [$this, 'valuesFilter']),
            new \Twig_SimpleFilter('base64', 'base64_encode'),
            new \Twig_SimpleFilter('imagesize', [$this, 'imageSize']),
            new \Twig_SimpleFilter('truncate_text', [$this, 'truncateText']),
            new \Twig_SimpleFilter('truncate_html', [$this, 'truncateHtml']),
            new \Twig_SimpleFilter('string', [$this, 'stringFilter']),
            new \Twig_SimpleFilter('int', [$this, 'intFilter']),
            new \Twig_SimpleFilter('float', [$this, 'floatFilter']),
            new \Twig_SimpleFilter('array', [$this, 'arrayFilter']),
            new \Twig_SimpleFilter('attribute_array', [$this, 'attributeArrayFilter'], ['is_safe' => true]),
        ];
    }

    /**
     * Return a list of all functions.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('nested', [$this, 'nestedFunc']),
            new \Twig_SimpleFunction('url', [$this, 'urlFunc']),
            new \Twig_SimpleFunction('parse_assets', [$this, 'parseAssetsFunc']),
            new \Twig_SimpleFunction('colorContrast', [$this, 'colorContrastFunc']),
            new \Twig_SimpleFunction('get_cookie', [$this, 'getCookie']),
            new \Twig_SimpleFunction('preg_match', [$this, 'pregMatch']),
            new \Twig_SimpleFunction('json_decode', [$this, 'jsonDecodeFilter']),
            new \Twig_SimpleFunction('imagesize', [$this, 'imageSize']),
            new \Twig_SimpleFunction('is_selected', [$this, 'is_selectedFunc'])
        ];
    }

    /**
     * @return array
     */
    public function getTokenParsers()
    {
        return [
            new TokenParserPageblock(),
            new TokenParserAssets(),
            new TokenParserScripts(),
            new TokenParserStyles(),
            new TokenParserTry(),
        ];
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
     * Translate by using key, default on original string.
     *
     * @param $str
     * @return string
     */
    public function transKeyFilter($str)
    {
        $params = func_get_args();
        array_shift($params);

        $key = preg_replace('|[^A-Z0-9]+|', '_', strtoupper(implode('_', $params)));

        $translation = $this->transFilter($key);

        return $translation === $key ? $str : $translation;
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

        $params = func_get_args();

        if (!$translator) {
            $translator = self::gantry()['translator'];
        }

        return call_user_func_array([$translator, 'translate'], $params);
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
        return str_repeat($str, max(0, (int) $count));
    }


    /**
     * Decodes string from JSON.
     *
     * @param  string  $str
     * @param  bool  $assoc
     * @param int $depth
     * @param int $options
     * @return array
     */
    public function jsonDecodeFilter($str, $assoc = false, $depth = 512, $options = 0)
    {
        return json_decode(html_entity_decode($str), $assoc, $depth, $options);
    }

    public function imageSize($src, $attrib = true, $remote = false)
    {
        // TODO: need to better handle absolute and relative paths
        //$url = Gantry::instance()['document']->url(trim((string) $src), false, false);
        $width = $height = null;
        $sizes = ['width' => $width, 'height' => $height];
        $attr = '';

        if (@is_file($src) || $remote) {
            try {
                list($width, $height,, $attr) = @getimagesize($src);
            } catch (\Exception $e) {}

            $sizes['width'] = $width;
            $sizes['height'] = $height;
        }

        return $attrib ? $attr : $sizes;
    }

    /**
     * Reindexes values in array.
     *
     * @param array $array
     * @return array
     */
    public function valuesFilter(array $array)
    {
        return array_values($array);
    }

    /**
     * Casts input to string.
     *
     * @param mixed $input
     * @return string
     */
    public function stringFilter($input)
    {
        return (string) $input;
    }


    /**
     * Casts input to int.
     *
     * @param mixed $input
     * @return int
     */
    public function intFilter($input)
    {
        return (int) $input;
    }

    /**
     * Casts input to float.
     *
     * @param mixed $input
     * @return float
     */
    public function floatFilter($input)
    {
        return (float) $input;
    }

    /**
     * Casts input to array.
     *
     * @param mixed $input
     * @return array
     */
    public function arrayFilter($input)
    {
        return (array) $input;
    }

    /**
     * Takes array of attribute keys and values and converts it to properly escaped HTML attributes.
     *
     * @example ['data-id' => 'id', 'data-key' => 'key'] => ' data-id="id" data-key="key"'
     * @example [['data-id' => 'id'], ['data-key' => 'key']] => ' data-id="id" data-key="key"'
     *
     * @param string|string[] $input
     * @return string
     */
    public function attributeArrayFilter($input)
    {
        if (is_string($input)) {
            return $input;
        }

        $array = [];
        foreach ((array) $input as $key => $value) {
            if (is_array($value)) {
                foreach ((array) $value as $key2 => $value2) {
                    $array[] = HtmlDocument::escape($key2) . '="' . HtmlDocument::escape($value2, 'html_attr') . '"';
                }
            } elseif ($key) {
                $array[] = HtmlDocument::escape($key) . '="' . HtmlDocument::escape($value, 'html_attr') . '"';
            }
        }
        return $array ? ' ' . implode(' ', $array) : '';
    }

    public function is_selectedFunc($a, $b)
    {
        $b = (array) $b;
        array_walk(
            $b,
            function (&$item) {
                if (is_bool($item)) {
                    $item = (int) $item;
                }
                $item = (string) $item;
            }
        );

        return in_array((string) $a, $b, true);
    }

    /**
     * Truncate text by number of characters but can cut off words. Removes html tags.
     *
     * @param  string $string
     * @param  int    $limit       Max number of characters.
     *
     * @return string
     */
    public function truncateText($string, $limit = 150)
    {
        $platform = Gantry::instance()['platform'];

        return $platform->truncate($string, (int) $limit, false);
    }

    /**
     * Truncate text by number of characters but can cut off words.
     *
     * @param  string $string
     * @param  int    $limit       Max number of characters.
     *
     * @return string
     */
    public function truncateHtml($string, $limit = 150)
    {
        $platform = Gantry::instance()['platform'];

        return $platform->truncate($string, (int) $limit, true);
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
     * @param  string $input       Resource to be located.
     * @param  bool $domain        True to include domain name.
     * @param  int $timestamp_age  Append timestamp to files that are less than x seconds old. Defaults to a week.
     *                             Use value <= 0 to disable the feature.
     * @return string|null         Returns url to the resource or null if resource was not found.
     */
    public function urlFunc($input, $domain = false, $timestamp_age = null)
    {
        $gantry = Gantry::instance();

        return $gantry['document']->url(trim((string) $input), $domain, $timestamp_age);
    }

    /**
     * Filter stream URLs from HTML input.
     *
     * @param  string $str          HTML input to be filtered.
     * @param  bool $domain         True to include domain name.
     * @param  int $timestamp_age   Append timestamp to files that are less than x seconds old. Defaults to a week.
     *                              Use value <= 0 to disable the feature.
     * @return string               Returns modified HTML.
     */
    public function htmlFilter($str, $domain = false, $timestamp_age = null)
    {
        $gantry = Gantry::instance();

        return $gantry['document']->urlFilter($str, $domain, $timestamp_age);
    }

    /**
     * @param \libXMLError $error
     * @param string $input
     * @throws \RuntimeException
     */
    protected function dealXmlError(\libXMLError $error, $input)
    {
        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $level = 1;
                $message = "DOM Warning {$error->code}: ";
                break;
            case LIBXML_ERR_ERROR:
                $level = 2;
                $message = "DOM Error {$error->code}: ";
                break;
            case LIBXML_ERR_FATAL:
                $level = 3;
                $message = "Fatal DOM Error {$error->code}: ";
                break;
            default:
                $level = 3;
                $message = "Unknown DOM Error {$error->code}: ";
        }
        $message .= "{$error->message} while parsing:\n{$input}\n";

        if ($level <= 2 && !Gantry::instance()->debug()) {
            return;
        }

        throw new \RuntimeException($message, 500);
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
        if ($location == 'head') {
            $scope = 'head';
            $html = "<!doctype html>\n<html><head>{$input}</head><body></body></html>";
        } else {
            $scope = 'body';
            $html = "<!doctype html>\n<html><head></head><body>{$input}</body></html>";
        }

        libxml_clear_errors();

        $internal = libxml_use_internal_errors(true);

        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        foreach (libxml_get_errors() as $error) {
            $this->dealXmlError($error, $html);
        }

        libxml_clear_errors();

        libxml_use_internal_errors($internal);

        $raw = [];
        /** @var \DomElement $element */
        foreach ($doc->getElementsByTagName($scope)->item(0)->childNodes as $element) {
            if (empty($element->tagName)) {
                continue;
            }
            $result = ['tag' => $element->tagName, 'content' => $element->textContent];
            foreach ($element->attributes as $attribute) {
                $result[$attribute->name] = $attribute->value;
            }
            $success = Gantry::instance()['document']->addHeaderTag($result, $location, (int) $priority);
            if (!$success) {
                $raw[] = $doc->saveHTML($element);
            }
        }

        return implode("\n", $raw);
    }

    public function colorContrastFunc($value)
    {
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

    public function getCookie($name)
    {
        $gantry = Gantry::instance();

        /** @var Request $request */
        $request = $gantry['request'];

        return $request->cookie[$name];
    }

    public function pregMatch($pattern, $subject, &$matches = []) {
        preg_match($pattern, $subject, $matches);

        if(isset($matches) && !empty($matches)) {
            return $matches;
        } else {
            return false;
        }
    }
}
