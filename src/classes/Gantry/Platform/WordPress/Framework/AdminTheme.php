<?php
namespace Gantry\Framework;

use RocketTheme\Toolbox\ResourceLocator\ResourceLocatorInterface;
use RocketTheme\Toolbox\StreamWrapper\ReadOnlyStream;
use RocketTheme\Toolbox\StreamWrapper\Stream;

class AdminTheme extends Base\Theme
{
    public $path;

    public function __construct( $path, $name = '' )
    {
        parent::__construct($path, $name);
        $this->boot();
    }

    protected function boot()
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var ResourceLocatorInterface $locator */
        $locator = $gantry['locator'];
        $schemes = $gantry['admin.config']->get('streams.schemes');

        if (!$schemes) {
            return;
        }

        // Set locator to both streams.
        Stream::setLocator($locator);
        ReadOnlyStream::setLocator($locator);

        $registered = stream_get_wrappers();

        foreach ($schemes as $scheme => $config) {
            if (isset($config['paths'])) {
                $locator->addPath($scheme, '', $config['paths']);
            }
            if (isset($config['prefixes'])) {
                foreach ($config['prefixes'] as $prefix => $paths) {
                    $locator->addPath($scheme, $prefix, $paths);
                }
            }

            if (in_array($scheme, $registered)) {
                stream_wrapper_unregister($scheme);
            }
            $type = !empty($config['type']) ? $config['type'] : 'ReadOnlyStream';
            if ($type[0] != '\\') {
                $type = '\\Rockettheme\\Toolbox\\StreamWrapper\\' . $type;
            }

            if (!stream_wrapper_register($scheme, $type)) {
                throw new \InvalidArgumentException("Stream '{$type}' could not be initialized.");
            }
        }
    }

    public function render($file, array $context = array())
    {
        $loader = new \Twig_Loader_Filesystem(
            [$this->path . '/templates', $this->path . '/common/templates']
        );

        $params = array(
            'cache' => GANTRYADMIN_PATH . '/cache',
            'debug' => true,
            'auto_reload' => true,
            'autoescape' => false
        );

        $twig = new \Twig_Environment($loader, $params);

        $this->add_to_twig($twig);

        // Include Gantry specific things to the context.
        $context = $this->add_to_context($context);

        return $twig->render($file, $context);
    }

    public function add_to_context( array $context )
    {
        $context = parent::add_to_context( $context );

        $this->url = $context['site']->theme->link;

        return $context;
    }
}
