<?php
namespace Gantry\Framework;

use Gantry\Component\Config\Config;
use Gantry\Component\File\CompiledYamlFile;

class Gantry extends Base\Gantry
{
    /**
     * @throws \LogicException
     */
    protected static function load()
    {
        $container = parent::load();

        $container['global'] = function ($c) {
            $file = CompiledYamlFile::instance(PRIME_ROOT . '/config/global.yaml');
            $data = (array) $file->content() + [
                    'debug' => true,
                    'production' => false,
                    'asset_timestamps' => true,
                    'asset_timestamps_period' => 7
                ];
            $file->free();

            return new Config($data);
        };

        return $container;
    }
}
