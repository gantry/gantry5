<?php
namespace Gantry\Component\Twig;

use Gantry\Framework\Gantry;
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
     * Return a list of all functions.
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('url', array($this, 'urlFunc'))
        );
    }

    /**
     * Return URL to the resource.
     *
     * @param  string $input
     * @param  bool $domain
     * @return string
     */
    public function urlFunc($input, $domain = false)
    {
        $gantry = Gantry::instance();
        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        return $locator->findResource($input, false);
    }
}
