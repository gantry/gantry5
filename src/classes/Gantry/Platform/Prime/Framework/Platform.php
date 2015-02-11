<?php
namespace Gantry\Framework;

use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Platform as BasePlatform;
use RocketTheme\Toolbox\DI\Container;

/**
 * The Platform Configuration class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */

class Platform extends BasePlatform
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        Folder::create(GANTRY5_ROOT . '/custom');

        // Initialize custom streams for Prime.
        $this->items['streams'] += [
            'gantry-prime' => [
                'type' => 'ReadOnlyStream',
                'prefixes' => [
                    '' => ['']
                ]
            ],
            'gantry-custom' => [
                'type' => 'ReadOnlyStream',
                'prefixes' => [
                    '' => []
                ]
            ],
            'gantry-pages' => [
                'type' => 'ReadOnlyStream',
                'prefixes' => [
                    '' => ['pages']
                ]
            ],
            'gantry-positions' => [
                'type' => 'ReadOnlyStream',
                'prefixes' => [
                    '' => ['positions']
                ]
            ]
        ];

        $this->items['streams']['gantry-layouts']['prefixes'][''][] = 'gantry-prime://layouts';
        $this->items['streams']['gantry-config']['prefixes'][''][] = 'gantry-prime://config';
    }

    public function getCachePath()
    {
        return 'cache';
    }

    public function getThemesPaths()
    {
        return  ['' => ['themes']];
    }

    public function getEnginesPaths()
    {
        return ['' => ['engines']];
    }

    public function getEnginePaths()
    {
        return ['' => ['gantry-theme://engine', 'gantry-engines://nucleus']];
    }

    public function getAssetsPaths()
    {
        return ['' => ['gantry-theme://', 'assets']];
    }

    public function getMediaPaths()
    {
        return ['' => ['gantry-theme://images', 'media']];
    }

    public function getModules($position)
    {
        $path = PRIME_ROOT . '/positions/' . $position;

        if (!is_dir($path)) {
            return [];
        }

        $params = [
            'levels' => 0,
            'pattern' => '|\.html\.twig|',
            'filters' => ['value' => '|\.html\.twig|']
        ];

        return Folder::all($path, $params);
    }
}
