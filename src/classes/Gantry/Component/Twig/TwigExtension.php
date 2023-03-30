<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
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
use Gantry\Component\Twig\TokenParser\TokenParserPageblock;
use Gantry\Component\Twig\TokenParser\TokenParserAssets;
use Gantry\Component\Twig\TokenParser\TokenParserScripts;
use Gantry\Component\Twig\TokenParser\TokenParserStyles;
use Gantry\Component\Twig\TokenParser\TokenParserTryCatch;
use Gantry\Component\Twig\TokenParser\TokenParserMarkdown;
use Gantry\Component\Twig\TokenParser\TokenParserSwitch;
use Gantry\Component\Twig\TokenParser\TokenParserThrow;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use Gantry\Framework\Markdown\Parsedown;
use Gantry\Framework\Markdown\ParsedownExtra;
use Gantry\Framework\Platform;
use Gantry\Framework\Request;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccess;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class TwigExtension
 * @package Gantry\Component\Twig
 */
class TwigExtension extends AbstractExtension implements GlobalsInterface
{
    use GantryTrait;

    /**
     * Register some standard globals
     *
     * @return array
     */
    public function getGlobals()
    {
        return [
            'gantry' => static::gantry(),
        ];
    }

    /**
     * Return a list of all filters.
     *
     * @return array
     */
    public function getFilters()
    {
        $filters = [
            new TwigFilter('html', [$this, 'htmlFilter']),
            new TwigFilter('url', [$this, 'urlFunc']),
            new TwigFilter('trans_key', [$this, 'transKeyFilter']),
            new TwigFilter('substr', 'substr'),
            new TwigFilter('trans', [$this, 'transFilter']),
            new TwigFilter('repeat', [$this, 'repeatFilter']),
            new TwigFilter('values', [$this, 'valuesFilter']),
            new TwigFilter('base64', 'base64_encode'),
            new TwigFilter('imagesize', [$this, 'imageSize'], ['is_safe' => ['html']]),
            new TwigFilter('truncate_text', [$this, 'truncateText']),
            new TwigFilter('attribute_array', [$this, 'attributeArrayFilter'], ['is_safe' => ['html']]),
        ];

        //if (1 || GANTRY5_PLATFORM !== 'grav') {
        $filters = array_merge($filters, [
            new TwigFilter('fieldName', [$this, 'fieldNameFilter']),
            new TwigFilter('json_decode', [$this, 'jsonDecodeFilter']),
            new TwigFilter('truncate_html', [$this, 'truncateHtml']),
            new TwigFilter('markdown', [$this, 'markdownFunction'], ['is_safe' => ['html']]),
            new TwigFilter('nicetime', [$this, 'nicetimeFilter']),

            // Casting values
            new TwigFilter('string', [$this, 'stringFilter']),
            new TwigFilter('int', [$this, 'intFilter'], ['is_safe' => ['all']]),
            new TwigFilter('bool', [$this, 'boolFilter']),
            new TwigFilter('float', [$this, 'floatFilter'], ['is_safe' => ['all']]),
            new TwigFilter('array', [$this, 'arrayFilter']),
        ]);
        //}

        return $filters;
    }

    /**
     * Return a list of all functions.
     *
     * @return array
     */
    public function getFunctions()
    {
        $functions = [
            new TwigFunction('nested', [$this, 'nestedFunc']),
            new TwigFunction('parse_assets', [$this, 'parseAssetsFunc']),
            new TwigFunction('colorContrast', [$this, 'colorContrastFunc']),
            new TwigFunction('get_cookie', [$this, 'getCookie']),
            new TwigFunction('preg_match', [$this, 'pregMatch']),
            new TwigFunction('imagesize', [$this, 'imageSize'], ['is_safe' => ['html']]),
            new TwigFunction('is_selected', [$this, 'is_selectedFunc']),
            new TwigFunction('url', [$this, 'urlFunc']),
        ];

        if (GANTRY5_PLATFORM === 'grav') {
            $functions[] = new TwigFunction('taxonomy_categories', [$this, 'taxonomyCategories']);
        }

//        if (1 || GANTRY5_PLATFORM !== 'grav') {
        $functions = array_merge($functions, [
            new TwigFunction('array', [$this, 'arrayFilter']),
            new TwigFunction('json_decode', [$this, 'jsonDecodeFilter']),
        ]);
//        }

        return $functions;
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
            new TokenParserThrow(),
            new TokenParserTryCatch(),
            new TokenParserMarkdown(),
            new TokenParserSwitch()
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
     * @param string $str
     * @return string
     */
    public function transKeyFilter($str)
    {
        $params = \func_get_args();
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
        /** @var TranslatorInterface|null $translator */
        static $translator;

        $params = \func_get_args();

        if (!$translator) {
            $translator = self::gantry()['translator'];
        }

        return $translator->translate(...$params);
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
        return json_decode(html_entity_decode($str ?? ''), $assoc, $depth, $options);
    }

    /**
     * @param mixed $src
     * @param bool $attrib
     * @param bool $remote
     * @return array|string
     */
    public function imageSize($src, $attrib = true, $remote = false)
    {
        // TODO: need to better handle absolute and relative paths
        //$url = Gantry::instance()['document']->url(trim((string) $src), false, false);
        $width = $height = null;
        $sizes = ['width' => $width, 'height' => $height];
        $attr = '';

        if ($remote || @is_file($src)) {
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
     * Casts input to bool.
     *
     * @param mixed $input
     * @return bool
     */
    public function boolFilter($input)
    {
        return (bool) $input;
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
     * @param string|array $input
     * @return string
     */
    public function attributeArrayFilter($input)
    {
        if (\is_string($input)) {
            return $input;
        }

        $array = [];
        /**
         * @var string $key
         * @var string|string[] $value
         */
        foreach ((array) $input as $key => $value) {
            if (\is_array($value)) {
                /**
                 * @var string $key2
                 * @var string $value2
                 */
                foreach ($value as $key2 => $value2) {
                    $array[] = HtmlDocument::escape($key2) . '="' . HtmlDocument::escape($value2, 'html_attr') . '"';
                }
            } elseif ($key) {
                $array[] = HtmlDocument::escape($key) . '="' . HtmlDocument::escape($value, 'html_attr') . '"';
            }
        }
        return $array ? ' ' . implode(' ', $array) : '';
    }

    /**
     * @param string $a
     * @param string|array $b
     * @return bool
     */
    public function is_selectedFunc($a, $b)
    {
        $b = (array) $b;
        array_walk(
            $b,
            static function (&$item) {
                if (\is_bool($item)) {
                    $item = (int) $item;
                }
                $item = (string) $item;
            }
        );

        return \in_array((string) $a, $b, true);
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
        /** @var Platform $platform */
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
        /** @var Platform $platform */
        $platform = Gantry::instance()['platform'];

        return $platform->truncate($string, (int) $limit, true);
    }

    /**
     * @param string $string
     * @param bool $block  Block or Line processing
     * @param array $settings
     * @return mixed|string
     */
    public function markdownFunction($string, $block = true, array $settings = null)
    {
        // Initialize the preferred variant of Parsedown
        if (!empty($settings['extra'])) {
            $parsedown = new ParsedownExtra($settings);
        } else {
            $parsedown = new Parsedown($settings);
        }

        if ($block) {
            $string = $parsedown->text($string);
        } else {
            $string = $parsedown->line($string);
        }

        return $string;
    }

    /**
     * Get value by using dot notation for nested arrays/objects.
     *
     * @example {{ nested(array, 'this.is.my.nested.variable')|json_encode }}
     *
     * @param array|object $items Array of items.
     * @param string  $name       Dot separated path to the requested value.
     * @param mixed   $default    Default value (or null).
     * @param string  $separator  Separator, defaults to '.'
     * @return mixed  Value.
     */
    public function nestedFunc($items, $name, $default = null, $separator = '.')
    {
        if (is_callable([$items, 'getNestedProperty'])) {
            return $items->getNestedProperty($name, $default, $separator);
        }
        $path = explode($separator, $name) ?: [];
        $current = $items;
        foreach ($path as $field) {
            if (\is_object($current) && isset($current->{$field})) {
                $current = $current->{$field};
            } elseif (\is_array($current) && isset($current[$field])) {
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
     * @param  bool|null $domain   True to include domain name.
     * @param  int $timestamp_age  Append timestamp to files that are less than x seconds old. Defaults to a week.
     *                             Use value <= 0 to disable the feature.
     * @return string|null         Returns url to the resource or null if resource was not found.
     */
    public function urlFunc($input, $domain = null, $timestamp_age = null)
    {
        $gantry = Gantry::instance();

        /** @var Document $document */
        $document = $gantry['document'];

        return $document::url(trim((string) $input), $domain, $timestamp_age);
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

        /** @var Document $document */
        $document = $gantry['document'];

        return $document::urlFilter($str, $domain, $timestamp_age);
    }

    /**
     * @param \LibXMLError $error
     * @param string $input
     * @throws \RuntimeException
     */
    protected function dealXmlError(\LibXMLError $error, $input)
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
        if ($location === 'head') {
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

                /** @var Document $document */
            $document = Gantry::instance()['document'];
            $success = $document::addHeaderTag($result, $location, (int) $priority);
            if (!$success) {
                $raw[] = $doc->saveHTML($element);
            }
        }

        return implode("\n", $raw);
    }

    /**
     * @param string $value
     * @return bool
     */
    public function colorContrastFunc($value)
    {
        $value = str_replace(' ', '', $value);
        $rgb = new \stdClass;
        $opacity = 1;

        if (0 !== strpos($value, 'rgb')) {
            $value = str_replace('#', '', $value);
            if (\strlen($value) === 3) {
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
            $opacity = substr($opacity, 0, 1) === '.' ? '0' . $opacity : $opacity;
        }

        $yiq = ((($rgb->r * 299) + ($rgb->g * 587) + ($rgb->b * 114)) / 1000) >= 128;

        return $yiq || (!$opacity || (float) $opacity < 0.35);
    }

    /**
     * Displays a facebook style 'time ago' formatted date/time.
     *
     * @param string|int $date
     * @param bool $long_strings
     *
     * @return string
     */
    public function nicetimeFilter($date, $long_strings = true)
    {
        static $lengths = [60, 60, 24, 7, 4.35, 12, 10];
        static $periods_long = [
            'GANTRY5_ENGINE_NICETIME_SECOND',
            'GANTRY5_ENGINE_NICETIME_MINUTE',
            'GANTRY5_ENGINE_NICETIME_HOUR',
            'GANTRY5_ENGINE_NICETIME_DAY',
            'GANTRY5_ENGINE_NICETIME_WEEK',
            'GANTRY5_ENGINE_NICETIME_MONTH',
            'GANTRY5_ENGINE_NICETIME_YEAR',
            'GANTRY5_ENGINE_NICETIME_DECADE'
        ];
        static $periods_short = [
            'GANTRY5_ENGINE_NICETIME_SEC',
            'GANTRY5_ENGINE_NICETIME_MIN',
            'GANTRY5_ENGINE_NICETIME_HR',
            'GANTRY5_ENGINE_NICETIME_DAY',
            'GANTRY5_ENGINE_NICETIME_WK',
            'GANTRY5_ENGINE_NICETIME_MO',
            'GANTRY5_ENGINE_NICETIME_YR',
            'GANTRY5_ENGINE_NICETIME_DEC'
        ];

        if (empty($date)) {
            return $this->transFilter('GANTRY5_ENGINE_NICETIME_NO_DATE_PROVIDED');
        }

        $periods = $long_strings ? $periods_long : $periods_short;

        $now = time();

        // check if unix timestamp
        if (is_int($date) || (string)(int)$date === (string)$date) {
            $unix_date = (int)$date;
        } else {
            $unix_date = strtotime($date);
        }

        // check validity of date
        if (!$unix_date) {
            return $this->transFilter('GANTRY5_ENGINE_NICETIME_BAD_DATE');
        }

        // is it future date or past date
        if ($now > $unix_date) {
            $difference = $now - $unix_date;
            $tense      = $this->transFilter('GANTRY5_ENGINE_NICETIME_AGO');

        } else if ($now === $unix_date) {
            $difference = $now - $unix_date;
            $tense      = $this->transFilter('GANTRY5_ENGINE_NICETIME_JUST_NOW');

        } else {
            $difference = $unix_date - $now;
            $tense      = $this->transFilter('GANTRY5_ENGINE_NICETIME_FROM_NOW');
        }


        for ($j = 0; $difference >= $lengths[$j] && $j < \count($lengths) - 1; $j++) {
            $difference /= $lengths[$j];
        }
        $period = $periods[$j];

        $difference = round($difference);

        if ($difference !== 1) {
            $period .= '_PLURAL';
        }

        $period = $this->transFilter($period);

        if ($now === $unix_date) {
            return $tense;
        }

        return "{$difference} {$period} {$tense}";
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getCookie($name)
    {
        $gantry = Gantry::instance();

        /** @var Request $request */
        $request = $gantry['request'];

        return $request->cookie[$name];
    }

    /**
     * @param string $pattern
     * @param string $subject
     * @param array $matches
     * @return array|bool
     */
    public function pregMatch($pattern, $subject, &$matches = [])
    {
        preg_match($pattern, $subject, $matches);

        return $matches ?: false;
    }

    /**
     * @param string|array $str
     * @return array
     */
    public function taxonomyCategories($str)
    {
        if (!is_array($str)) {
            $taxonomies = explode(' ', $str);
        } else {
            $taxonomies = $str;
        }

        $list = [];
        foreach ($taxonomies as $taxonomy) {
            $list[] = ['@taxonomy.category' => $taxonomy];
        }

        return $list;
    }
}
