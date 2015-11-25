<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Config\Config;

class Gantry extends Base\Gantry
{
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
     * @return Gantry
     * @throws \LogicException
     */
    protected static function load()
    {

        // Make sure Timber plugin has been loaded.
        if (!class_exists('Timber')) {
            $action = 'install-plugin';
            $slug = 'timber-library';
            throw new \LogicException('<strong>Timber not activated</strong>. Click <a href="' . wp_nonce_url( add_query_arg( [ 'action' => $action, 'plugin' => $slug ], admin_url( 'update.php' ) ), $action.'_'.$slug ) . '"><strong>here</strong></a> to install it or go to the <a href=" ' . admin_url('plugins.php#timber') . '"><strong>Installed Plugins</strong></a> page to activate it, if already installed.');
        }

        return parent::load();
    }

    /**
     * @return array
     */
    protected static function loadGlobal()
    {
        return (array) \get_option('gantry5_plugin');
    }
}
