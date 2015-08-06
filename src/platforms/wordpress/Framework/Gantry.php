<?php
namespace Gantry\Framework;

use Gantry\Component\Config\Config;

class Gantry extends Base\Gantry
{
    /**
     * @return boolean
     */
    public function debug()
    {
        $option = (array) get_option('gantry5_plugin');

        return !empty($option['debug']);
    }

    /**
     * @return boolean
     */
    public function admin()
    {
        return \is_admin();
    }

    /**
     * @param string $location
     * @param bool   $force
     * @return array
     */
    public function styles($location = 'head', $force = false)
    {
        // Do not display head, WordPress will take care of it (most of the time).
        return (!$force && in_array($location, ['head'])) ? Document::$wp_styles : parent::styles($location);
    }

    /**
     * @param string $location
     * @param bool $force
     * @return array
     */
    public function scripts($location = 'head', $force = false)
    {
        // Do not display head and footer, WordPress will take care of it (most of the time).
        return (!$force && in_array($location, ['head', 'footer'])) ? Document::$wp_scripts[$location] : parent::scripts($location);
    }

    /**
     * @throws \LogicException
     */
    protected static function load()
    {

        // Make sure Timber plugin has been loaded.
        if ( !class_exists( 'Timber' ) ) {
            $action = 'install-plugin';
            $slug = 'timber-library';
            throw new \LogicException( '<strong>Timber not activated</strong>. Click <a href="' . wp_nonce_url( add_query_arg( [ 'action' => $action, 'plugin' => $slug ], admin_url( 'update.php' ) ), $action.'_'.$slug ) . '"><strong>here</strong></a> to install it or go to the <a href=" ' . admin_url( 'plugins.php#timber' ) . '"><strong>Installed Plugins</strong></a> page to activate it, if already installed.' );
        }

        $container = parent::load();

        $container['site'] = function ( $c ) {
            return new Site;
        };

        $container['page'] = function ( $c ) {
            return new Page( $c );
        };

        $container['menu'] = function ($c) {
            return new Menu;
        };

        $container['global'] = function ($c) {
            return new Config((array) \get_option('gantry5_plugin'));
        };

        return $container;
    }
}
