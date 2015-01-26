<?php
namespace Gantry\Component\Twig;

use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccess;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class TwigExtension extends \Twig_Extension
{
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
            new \Twig_SimpleFilter('base64', 'base64_encode')
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
            new \Twig_SimpleFunction('parseHtmlHeader', [$this, 'parseHtmlHeaderFunc'])
        );
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
        $resource = trim((string) $input);
        if (!$resource) {
            return null;
        }

        if ($resource[0] == '/') {
            // Absolute path in our server, nothing to do.
            // TODO: add support to include domain..
            return $resource;

        } elseif (strpos($resource, '://') !== false) {
            // Resolve stream to a relative path.
            $gantry = Gantry::instance();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            try {
                // Attempt to find our resource.
                $resource = $locator->findResource($resource, false);
            } catch (\Exception $e) {
                // Scheme did not exist; assume that we had valid scheme (like http) so no modification is needed.
                return $resource;
            }
        }

        // TODO: add support to include domain..
        return $resource ? rtrim(Document::rootUri(), '/') .'/'. $resource : null;
    }

    /**
     * Move supported document head elements into platform document object, return all
     * unsupported tags in a string.
     *
     * @param $input
     * @return string
     */
    public function parseHtmlHeaderFunc($input, $in_footer = false)
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
            $success = Document::addHeaderTag($result, $in_footer);
            if (!$success) {
                $raw[] = $doc->saveHTML($element);
            }
        }

        return implode("\n", $raw);
    }
}
